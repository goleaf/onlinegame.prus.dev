<?php

namespace App\Livewire\Game;

use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\GameTickService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithPagination;

class GameDashboard extends Component
{
    use WithPagination;

    public $player;

    public $currentVillage;

    public $villages = [];

    public $recentEvents = [];

    public $gameStats = [];

    public $autoRefresh = true;

    public $refreshInterval = 5;  // seconds

    public $notifications = [];

    public $isLoading = false;

    protected $listeners = [
        'refreshGameData',
        'gameTickProcessed',
        'gameTickError',
        'buildingCompleted',
        'resourceUpdated',
        'villageUpdated'
    ];

    public function mount()
    {
        if (!Auth::check()) {
            return redirect('/login');
        }
        $this->loadGameData();

        // Start real-time polling for game updates
        $this->startPolling();
    }

    public function loadGameData()
    {
        $this->isLoading = true;

        try {
            $user = Auth::user();
            if (!$user) {
                $this->loadGameStats();  // Load default stats even when no user
                $this->isLoading = false;
                return;
            }
            $this->player = Player::where('user_id', $user->id)->first();

            if ($this->player) {
                $this->villages = $this
                    ->player
                    ->villages()
                    ->with(['resources', 'buildings', 'buildingQueues'])
                    ->get();
                $this->currentVillage = $this->villages->first();
                $this->loadRecentEvents();
                $this->loadGameStats();
            } else {
                $this->loadGameStats();  // Load default stats when no player
            }
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadRecentEvents()
    {
        if ($this->player) {
            $this->recentEvents = GameEvent::where('player_id', $this->player->id)
                ->orderBy('occurred_at', 'desc')
                ->limit(10)
                ->get();
        }
    }

    public function loadGameStats()
    {
        if ($this->player) {
            $this->gameStats = [
                'total_villages' => $this->player->villages()->count(),
                'total_points' => $this->player->points ?? 0,
                'alliance_name' => $this->player->alliance?->name ?? 'No Alliance',
                'online_status' => $this->player->is_online ? 'Online' : 'Offline',
                'last_active' => $this->player->last_active_at ? \Carbon\Carbon::parse($this->player->last_active_at)->diffForHumans() : 'Never',
                'total_population' => $this->player->villages()->sum('population'),
                'total_attack_points' => $this->player->total_attack_points ?? 0,
                'total_defense_points' => $this->player->total_defense_points ?? 0,
            ];
        } else {
            $this->gameStats = [
                'total_villages' => 0,
                'total_points' => 0,
                'alliance_name' => 'No Alliance',
                'online_status' => 'Offline',
                'last_active' => 'Never',
                'total_population' => 0,
                'total_attack_points' => 0,
                'total_defense_points' => 0,
            ];
        }
    }

    #[On('tick')]
    public function processGameTick()
    {
        if (!$this->autoRefresh) {
            return;
        }

        try {
            $gameTickService = app(\App\Services\GameTickService::class);
            $gameTickService->processGameTick();

            $this->loadGameData();
            $this->dispatch('gameTickProcessed');

            // Add notification
            $this->addNotification('Game tick processed successfully', 'success');
        } catch (\Exception $e) {
            $this->dispatch('gameTickError', ['message' => $e->getMessage()]);
            $this->addNotification('Game tick error: ' . $e->getMessage(), 'error');
        }
    }

    public function refreshGameData()
    {
        $this->loadGameData();
        $this->addNotification('Game data refreshed', 'info');
        $this->dispatch('gameTickProcessed');
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        $this->addNotification(
            $this->autoRefresh ? 'Auto-refresh enabled' : 'Auto-refresh disabled',
            'info'
        );
    }

    public function setRefreshInterval($interval)
    {
        $this->refreshInterval = max(1, min(60, $interval));
        $this->addNotification("Refresh interval set to {$this->refreshInterval} seconds", 'info');
    }

    public function selectVillage($villageId)
    {
        $this->currentVillage = $this->villages->find($villageId);
        $this->dispatch('villageSelected', ['villageId' => $villageId]);
    }

    public function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now()
        ];

        // Keep only last 10 notifications
        $this->notifications = array_slice($this->notifications, -10);
    }

    public function removeNotification($notificationId)
    {
        $this->notifications = array_filter($this->notifications, function ($notification) use ($notificationId) {
            return $notification['id'] !== $notificationId;
        });
    }

    public function clearNotifications()
    {
        $this->notifications = [];
    }

    public function startPolling()
    {
        // Start polling for real-time updates every 30 seconds
        $this->dispatch('start-polling', ['interval' => 30000]);
    }

    public function stopPolling()
    {
        $this->dispatch('stop-polling');
    }

    public function handleRealTimeUpdate($data)
    {
        // Handle real-time updates from WebSocket or polling
        if (isset($data['resources'])) {
            $this->loadGameData();
            $this->addNotification('Resources updated in real-time', 'success');
        }

        if (isset($data['buildings'])) {
            $this->loadGameData();
            $this->addNotification('Building progress updated', 'info');
        }
    }

    #[On('buildingCompleted')]
    public function handleBuildingCompleted($data)
    {
        $this->addNotification("Building completed: {$data['building_name']}", 'success');
        $this->loadGameData();
    }

    #[On('resourceUpdated')]
    public function handleResourceUpdated($data)
    {
        $this->loadGameData();
    }

    #[On('villageUpdated')]
    public function handleVillageUpdated($data)
    {
        $this->loadGameData();
    }

    public function render()
    {
        return view('livewire.game.game-dashboard', [
            'player' => $this->player,
            'currentVillage' => $this->currentVillage,
            'villages' => $this->villages,
            'recentEvents' => $this->recentEvents,
            'gameStats' => $this->gameStats,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading
        ]);
    }
}
