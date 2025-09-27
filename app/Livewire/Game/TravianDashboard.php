<?php

namespace App\Livewire\Game;

use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Services\GameTickService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TravianDashboard extends Component
{
    use WithPagination;

    public $player;
    public $currentVillage;
    public $villages = [];
    public $recentEvents = [];
    public $gameStats = [];
    public $autoRefresh = true;
    public $refreshInterval = 5;  // seconds
    public $lastUpdate;

    protected $listeners = ['refreshGameData', 'gameTickProcessed', 'gameTickError'];

    public function mount()
    {
        $this->loadGameData();
        $this->lastUpdate = now();
    }

    public function loadGameData()
    {
        $user = Auth::user();
        $this->player = Player::where('user_id', $user->id)
            ->with(['villages' => function ($query) {
                $query->with(['resources', 'buildings.buildingType:id,name,description']);
            }, 'alliance:id,name'])
            ->first();

        if ($this->player) {
            $this->villages = $this->player->villages;
            $this->currentVillage = $this->villages->first();
            $this->loadRecentEvents();
            $this->loadGameStats();
            $this->lastUpdate = now();
        }
    }

    public function loadRecentEvents()
    {
        if ($this->player) {
            $this->recentEvents = GameEvent::byPlayer($this->player->id)
                ->withStats()
                ->withPlayerInfo()
                ->recent(7)
                ->orderBy('occurred_at', 'desc')
                ->limit(10)
                ->get();
        }
    }

    public function loadGameStats()
    {
        if ($this->player) {
            // Use optimized query with selectRaw for village stats
            $villageStats = $this
                ->player
                ->villages()
                ->selectRaw('
                    COUNT(*) as total_villages,
                    SUM(population) as total_population,
                    AVG(population) as avg_population,
                    MAX(population) as max_population
                ')
                ->first();

            $this->gameStats = [
                'total_villages' => $villageStats->total_villages ?? 0,
                'total_population' => $villageStats->total_population ?? 0,
                'avg_population' => round($villageStats->avg_population ?? 0, 2),
                'max_population' => $villageStats->max_population ?? 0,
                'total_points' => $this->player->points ?? 0,
                'alliance_name' => $this->player->alliance?->name ?? 'No Alliance',
                'online_status' => $this->player->is_online ? 'Online' : 'Offline',
                'last_active' => $this->player->last_active_at?->diffForHumans() ?? 'Never',
            ];
        }
    }

    public function processGameTick()
    {
        try {
            $gameTickService = new GameTickService();
            $gameTickService->processGameTick();

            $this->loadGameData();
            $this->dispatch('gameTickProcessed');
        } catch (\Exception $e) {
            $this->dispatch('gameTickError', ['message' => $e->getMessage()]);
        }
    }

    public function refreshGameData()
    {
        $this->loadGameData();
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    public function setRefreshInterval($interval)
    {
        $this->refreshInterval = $interval;
    }

    public function selectVillage($villageId)
    {
        $this->currentVillage = $this->villages->find($villageId);
    }

    public function upgradeBuilding($buildingId)
    {
        // Implement building upgrade logic
        $this->dispatch('buildingUpgradeStarted', ['buildingId' => $buildingId]);
    }

    public function trainTroops($unitTypeId, $count)
    {
        // Implement troop training logic
        $this->dispatch('troopTrainingStarted', ['unitTypeId' => $unitTypeId, 'count' => $count]);
    }

    public function sendAttack($targetVillageId, $troops)
    {
        // Implement attack logic
        $this->dispatch('attackSent', ['targetVillageId' => $targetVillageId, 'troops' => $troops]);
    }

    public function render()
    {
        return view('livewire.game.travian-dashboard', [
            'player' => $this->player,
            'currentVillage' => $this->currentVillage,
            'villages' => $this->villages,
            'recentEvents' => $this->recentEvents,
            'gameStats' => $this->gameStats,
        ]);
    }
}
