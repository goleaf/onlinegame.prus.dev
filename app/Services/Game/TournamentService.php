<?php

namespace App\Services\Game;

use App\Models\Game\Tournament;
use App\Models\Game\Player;
use App\Models\Game\TournamentParticipant;
use App\Models\Game\TournamentMatch;
use App\Models\Game\TournamentBracket;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class TournamentService
{
    public function __construct(
        private GameIntegrationService $gameIntegrationService,
        private GameNotificationService $gameNotificationService
    ) {}

    /**
     * Create a new tournament
     */
    public function createTournament(array $data): Tournament
    {
        return DB::transaction(function () use ($data) {
            $tournament = Tournament::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => $data['type'],
                'format' => $data['format'],
                'max_participants' => $data['max_participants'],
                'entry_fee' => $data['entry_fee'] ?? 0,
                'prize_pool' => $data['prize_pool'] ?? 0,
                'registration_start' => $data['registration_start'],
                'registration_end' => $data['registration_end'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'upcoming',
                'settings' => $data['settings'] ?? [],
            ]);

            // Create tournament bracket
            $this->createTournamentBracket($tournament);

            // Log tournament creation
            Log::info('Tournament created', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name,
                'type' => $tournament->type,
                'format' => $tournament->format,
            ]);

            return $tournament;
        });
    }

    /**
     * Register a player for a tournament
     */
    public function registerPlayer(Tournament $tournament, Player $player): bool
    {
        // Check if registration is open
        if (!$this->isRegistrationOpen($tournament)) {
            return false;
        }

        // Check if tournament is full
        if ($this->isTournamentFull($tournament)) {
            return false;
        }

        // Check if player is already registered
        if ($this->isPlayerRegistered($tournament, $player)) {
            return false;
        }

        return DB::transaction(function () use ($tournament, $player) {
            // Create participant record
            TournamentParticipant::create([
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'registered_at' => now(),
                'status' => 'registered',
            ]);

            // Deduct entry fee if applicable
            if ($tournament->entry_fee > 0) {
                $this->deductEntryFee($player, $tournament->entry_fee);
            }

            // Send notification
            $this->gameNotificationService->sendTournamentNotification(
                $player,
                'tournament_registered',
                "You have been registered for tournament '{$tournament->name}'"
            );

            // Log registration
            Log::info('Player registered for tournament', [
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'entry_fee' => $tournament->entry_fee,
            ]);

            return true;
        });
    }

    /**
     * Start a tournament
     */
    public function startTournament(Tournament $tournament): bool
    {
        if ($tournament->status !== 'upcoming') {
            return false;
        }

        return DB::transaction(function () use ($tournament) {
            // Update tournament status
            $tournament->update(['status' => 'active']);

            // Generate bracket
            $this->generateTournamentBracket($tournament);

            // Create first round matches
            $this->createFirstRoundMatches($tournament);

            // Notify all participants
            $this->notifyTournamentStart($tournament);

            // Log tournament start
            Log::info('Tournament started', [
                'tournament_id' => $tournament->id,
                'participants' => $tournament->participants()->count(),
            ]);

            return true;
        });
    }

    /**
     * End a tournament
     */
    public function endTournament(Tournament $tournament): bool
    {
        if ($tournament->status !== 'active') {
            return false;
        }

        return DB::transaction(function () use ($tournament) {
            // Update tournament status
            $tournament->update(['status' => 'completed']);

            // Determine winner
            $winner = $this->determineTournamentWinner($tournament);

            // Distribute prizes
            $this->distributePrizes($tournament, $winner);

            // Notify all participants
            $this->notifyTournamentEnd($tournament, $winner);

            // Log tournament end
            Log::info('Tournament ended', [
                'tournament_id' => $tournament->id,
                'winner_id' => $winner?->id,
            ]);

            return true;
        });
    }

    /**
     * Get tournament statistics
     */
    public function getTournamentStats(): array
    {
        return [
            'total_tournaments' => Tournament::count(),
            'active_tournaments' => Tournament::where('status', 'active')->count(),
            'upcoming_tournaments' => Tournament::where('status', 'upcoming')->count(),
            'completed_tournaments' => Tournament::where('status', 'completed')->count(),
            'total_participants' => TournamentParticipant::count(),
            'total_prizes_distributed' => Tournament::where('status', 'completed')->sum('prize_pool'),
        ];
    }

    /**
     * Get player tournament history
     */
    public function getPlayerTournamentHistory(Player $player): Collection
    {
        return TournamentParticipant::where('player_id', $player->id)
            ->with(['tournament'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get tournament leaderboard
     */
    public function getTournamentLeaderboard(Tournament $tournament): Collection
    {
        return TournamentParticipant::where('tournament_id', $tournament->id)
            ->with(['player'])
            ->orderBy('final_rank', 'asc')
            ->get();
    }

    /**
     * Check if registration is open
     */
    private function isRegistrationOpen(Tournament $tournament): bool
    {
        $now = now();
        return $now->between($tournament->registration_start, $tournament->registration_end);
    }

    /**
     * Check if tournament is full
     */
    private function isTournamentFull(Tournament $tournament): bool
    {
        $currentParticipants = $tournament->participants()->count();
        return $currentParticipants >= $tournament->max_participants;
    }

    /**
     * Check if player is already registered
     */
    private function isPlayerRegistered(Tournament $tournament, Player $player): bool
    {
        return TournamentParticipant::where('tournament_id', $tournament->id)
            ->where('player_id', $player->id)
            ->exists();
    }

    /**
     * Deduct entry fee from player
     */
    private function deductEntryFee(Player $player, int $entryFee): void
    {
        // This would integrate with the resource system
        // For now, we'll just log the deduction
        Log::info('Entry fee deducted', [
            'player_id' => $player->id,
            'entry_fee' => $entryFee,
        ]);
    }

    /**
     * Create tournament bracket
     */
    private function createTournamentBracket(Tournament $tournament): void
    {
        TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type' => $tournament->format,
            'status' => 'pending',
        ]);
    }

    /**
     * Generate tournament bracket
     */
    private function generateTournamentBracket(Tournament $tournament): void
    {
        $participants = $tournament->participants()->get();
        $bracket = $tournament->bracket;

        // Generate bracket based on tournament format
        switch ($tournament->format) {
            case 'single_elimination':
                $this->generateSingleEliminationBracket($bracket, $participants);
                break;
            case 'double_elimination':
                $this->generateDoubleEliminationBracket($bracket, $participants);
                break;
            case 'round_robin':
                $this->generateRoundRobinBracket($bracket, $participants);
                break;
        }
    }

    /**
     * Generate single elimination bracket
     */
    private function generateSingleEliminationBracket(TournamentBracket $bracket, Collection $participants): void
    {
        // Implementation for single elimination bracket
        // This would create the bracket structure and initial matches
    }

    /**
     * Generate double elimination bracket
     */
    private function generateDoubleEliminationBracket(TournamentBracket $bracket, Collection $participants): void
    {
        // Implementation for double elimination bracket
    }

    /**
     * Generate round robin bracket
     */
    private function generateRoundRobinBracket(TournamentBracket $bracket, Collection $participants): void
    {
        // Implementation for round robin bracket
    }

    /**
     * Create first round matches
     */
    private function createFirstRoundMatches(Tournament $tournament): void
    {
        $bracket = $tournament->bracket;
        $participants = $tournament->participants()->get();

        // Create matches based on bracket structure
        // This would create the initial matches for the tournament
    }

    /**
     * Determine tournament winner
     */
    private function determineTournamentWinner(Tournament $tournament): ?Player
    {
        $winner = TournamentParticipant::where('tournament_id', $tournament->id)
            ->where('final_rank', 1)
            ->with('player')
            ->first();

        return $winner?->player;
    }

    /**
     * Distribute prizes
     */
    private function distributePrizes(Tournament $tournament, ?Player $winner): void
    {
        if (!$winner || $tournament->prize_pool <= 0) {
            return;
        }

        // Distribute prizes to winners
        // This would integrate with the resource system
        Log::info('Prizes distributed', [
            'tournament_id' => $tournament->id,
            'winner_id' => $winner->id,
            'prize_amount' => $tournament->prize_pool,
        ]);
    }

    /**
     * Notify tournament start
     */
    private function notifyTournamentStart(Tournament $tournament): void
    {
        $participants = $tournament->participants()->with('player')->get();

        foreach ($participants as $participant) {
            $this->gameNotificationService->sendTournamentNotification(
                $participant->player,
                'tournament_started',
                "Tournament '{$tournament->name}' has started!"
            );
        }
    }

    /**
     * Notify tournament end
     */
    private function notifyTournamentEnd(Tournament $tournament, ?Player $winner): void
    {
        $participants = $tournament->participants()->with('player')->get();

        foreach ($participants as $participant) {
            $message = "Tournament '{$tournament->name}' has ended.";
            if ($winner && $participant->player->id === $winner->id) {
                $message .= " Congratulations! You won!";
            }

            $this->gameNotificationService->sendTournamentNotification(
                $participant->player,
                'tournament_ended',
                $message
            );
        }
    }
}