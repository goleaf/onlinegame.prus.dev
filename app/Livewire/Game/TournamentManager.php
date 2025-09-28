<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Tournament;
use App\Services\Game\TournamentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Tournament Manager')]
#[Layout('layouts.game')]
class TournamentManager extends Component
{
    use WithPagination;

    public $player;

    public $activeTab = 'upcoming';

    public $search = '';

    public $filterType = '';

    public $filterFormat = '';

    public $selectedTournament = null;

    public $showRegistrationModal = false;

    public $notifications = [];

    protected $tournamentService;

    protected $listeners = [
        'tournamentRegistered' => 'refreshTournaments',
        'tournamentStarted' => 'refreshTournaments',
        'tournamentEnded' => 'refreshTournaments',
    ];

    public function boot()
    {
        $this->tournamentService = new TournamentService(
            app(\App\Services\GameIntegrationService::class),
            app(\App\Services\GameNotificationService::class)
        );
    }

    public function mount()
    {
        $this->player = Player::where('user_id', Auth::id())->first();

        if (! $this->player) {
            abort(404, 'Player not found');
        }
    }

    public function render()
    {
        $tournaments = $this->getTournaments();
        $tournamentStats = $this->tournamentService->getTournamentStats();

        return view('livewire.game.tournament-manager', [
            'tournaments' => $tournaments,
            'tournamentStats' => $tournamentStats,
        ]);
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function showTournamentDetails($tournamentId)
    {
        $this->selectedTournament = Tournament::with(['participants.player'])->find($tournamentId);
    }

    public function registerForTournament($tournamentId)
    {
        $tournament = Tournament::find($tournamentId);

        if (! $tournament) {
            $this->addNotification('Tournament not found', 'error');

            return;
        }

        if ($this->tournamentService->registerPlayer($tournament, $this->player)) {
            $this->addNotification(
                "Successfully registered for tournament '{$tournament->name}'",
                'success'
            );

            $this->dispatch('tournamentRegistered', [
                'tournament_id' => $tournament->id,
                'player_id' => $this->player->id,
            ]);
        } else {
            $this->addNotification('Failed to register for tournament', 'error');
        }
    }

    public function refreshTournaments()
    {
        $this->resetPage();
    }

    private function getTournaments()
    {
        $query = Tournament::query();

        // Apply filters
        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterFormat) {
            $query->where('format', $this->filterFormat);
        }

        // Apply tab filter
        switch ($this->activeTab) {
            case 'upcoming':
                $query->where('status', 'upcoming')
                    ->where('registration_start', '<=', now())
                    ->where('registration_end', '>=', now());

                break;
            case 'active':
                $query->where('status', 'active');

                break;
            case 'completed':
                $query->where('status', 'completed');

                break;
            case 'my_tournaments':
                $query->whereHas('participants', function ($q): void {
                    $q->where('player_id', $this->player->id);
                });

                break;
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    private function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
        ];
    }

    public function removeNotification($index)
    {
        unset($this->notifications[$index]);
        $this->notifications = array_values($this->notifications);
    }
}
