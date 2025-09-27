<?php

namespace App\Services\Game;

use App\Models\Game\Tournament;
use App\Models\Game\TournamentParticipant;
use App\Models\Game\Player;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TournamentService
{
    protected $integrationService;
    protected $notificationService;

    public function __construct(
        GameIntegrationService $integrationService,
        GameNotificationService $notificationService
    ) {
        $this->integrationService = $integrationService;
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new tournament
     */
    public function createTournament(array $data): Tournament
    {
        return DB::transaction(function () use ($data) {
            $tournament = Tournament::create($data);

            // Send system notification
            $this->notificationService->sendSystemNotification(
                'New Tournament Created',
                "A new tournament '{$tournament->name}' has been created. Registration is now open!",
                'info'
            );

            Log::info('Tournament created', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name,
                'type' => $tournament->type,
            ]);

            return $tournament;
        });
    }

    /**
     * Register a player for a tournament
     */
    public function registerPlayer(Tournament $tournament, Player $player): bool
    {
        if (!$tournament->canRegister($player)) {
            return false;
        }

        return DB::transaction(function () use ($tournament, $player) {
            // Create participant record
            $participant = TournamentParticipant::create([
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'status' => 'registered',
                'registered_at' => now(),
            ]);

            // Send notification to player
            $this->notificationService->sendUserNotification(
                $player->user_id,
                'Tournament Registration',
                "You have successfully registered for the tournament '{$tournament->name}'.",
                'success'
            );

            // Send system notification
            $this->notificationService->sendSystemNotification(
                'Tournament Registration',
                "Player {$player->name} has registered for tournament '{$tournament->name}'.",
                'info'
            );

            Log::info('Player registered for tournament', [
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'participant_id' => $participant->id,
            ]);

            return true;
        });
    }

    /**
     * Start a tournament
     */
    public function startTournament(Tournament $tournament): bool
    {
        if (!$tournament->startTournament()) {
            return false;
        }

        // Send notifications to all participants
        $participants = $tournament->participants()->with('player')->get();
        
        foreach ($participants as $participant) {
            $this->notificationService->sendUserNotification(
                $participant->player->user_id,
                'Tournament Started',
                "The tournament '{$tournament->name}' has started! Good luck!",
                'success'
            );
        }

        // Send system notification
        $this->notificationService->sendSystemNotification(
            'Tournament Started',
            "Tournament '{$tournament->name}' has started with {$participants->count()} participants.",
            'info'
        );

        Log::info('Tournament started', [
            'tournament_id' => $tournament->id,
            'participants_count' => $participants->count(),
        ]);

        return true;
    }

    /**
     * End a tournament
     */
    public function endTournament(Tournament $tournament): bool
    {
        if (!$tournament->endTournament()) {
            return false;
        }

        // Send notifications to all participants
        $participants = $tournament->participants()->with('player')->get();
        
        foreach ($participants as $participant) {
            $message = "The tournament '{$tournament->name}' has ended.";
            if ($participant->final_rank) {
                $message .= " You finished in position {$participant->final_rank}.";
            }

            $this->notificationService->sendUserNotification(
                $participant->player->user_id,
                'Tournament Ended',
                $message,
                'info'
            );
        }

        // Send system notification
        $this->notificationService->sendSystemNotification(
            'Tournament Ended',
            "Tournament '{$tournament->name}' has ended. Check the results!",
            'info'
        );

        Log::info('Tournament ended', [
            'tournament_id' => $tournament->id,
            'participants_count' => $participants->count(),
        ]);

        return true;
    }

    /**
     * Get tournament statistics
     */
    public function getTournamentStats(): array
    {
        $totalTournaments = Tournament::count();
        $activeTournaments = Tournament::where('status', 'active')->count();
        $upcomingTournaments = Tournament::where('status', 'upcoming')->count();
        $completedTournaments = Tournament::where('status', 'completed')->count();

        $totalParticipants = TournamentParticipant::count();
        $activeParticipants = TournamentParticipant::whereHas('tournament', function ($query) {
            $query->where('status', 'active');
        })->count();

        return [
            'total_tournaments' => $totalTournaments,
            'active_tournaments' => $activeTournaments,
            'upcoming_tournaments' => $upcomingTournaments,
            'completed_tournaments' => $completedTournaments,
            'total_participants' => $totalParticipants,
            'active_participants' => $activeParticipants,
        ];
    }

    /**
     * Get upcoming tournaments
     */
    public function getUpcomingTournaments(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Tournament::where('status', 'upcoming')
            ->where('registration_start', '<=', now())
            ->where('registration_end', '>=', now())
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }

    /**
     * Get active tournaments
     */
    public function getActiveTournaments(): \Illuminate\Database\Eloquent\Collection
    {
        return Tournament::where('status', 'active')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get completed tournaments
     */
    public function getCompletedTournaments(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Tournament::where('status', 'completed')
            ->orderBy('end_time', 'desc')
            ->limit($limit)
            ->get();
    }
}
