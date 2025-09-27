<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\QueryEnrichService;
use App\Services\QueryOptimizationService;
use Livewire\Component;
use Livewire\WithPagination;
use sbamtr\LaravelQueryEnrich\QE;
use function sbamtr\LaravelQueryEnrich\c;

class QueryEnrichDemo extends Component
{
    use WithPagination;

    public $worldId = null;
    public $playerId = null;
    public $days = 7;
    public $hours = 24;
    public $limit = 20;
    
    // Results
    public $activePlayers = [];
    public $playerStats = [];
    public $villageStats = [];
    public $battleStats = [];
    public $upcomingCompletions = [];
    public $resourcesReachingCapacity = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->loadActivePlayers();
        $this->loadPlayerStats();
        $this->loadVillageStats();
        $this->loadBattleStats();
        $this->loadUpcomingCompletions();
        $this->loadResourcesReachingCapacity();
    }

    public function loadActivePlayers()
    {
        // Using Query Enrich to get players active in the last N days
        $this->activePlayers = QueryEnrichService::getActivePlayersQuery($this->days, $this->worldId)
            ->orderByDesc('last_activity')
            ->limit($this->limit)
            ->get()
            ->toArray();
    }

    public function loadPlayerStats()
    {
        // Using Query Enrich for enhanced player statistics
        $this->playerStats = QueryEnrichService::getPlayerStatsQuery($this->worldId)
            ->orderByDesc('total_population')
            ->limit($this->limit)
            ->get()
            ->toArray();
    }

    public function loadVillageStats()
    {
        // Using Query Enrich for village statistics
        $this->villageStats = QueryEnrichService::getVillageStatsQuery($this->playerId)
            ->orderByDesc('total_population')
            ->limit($this->limit)
            ->get()
            ->toArray();
    }

    public function loadBattleStats()
    {
        // Using Query Enrich for battle statistics
        $this->battleStats = QueryEnrichService::getBattleStatsQuery($this->playerId)
            ->first();
    }

    public function loadUpcomingCompletions()
    {
        // Using Query Enrich to find buildings completing soon
        $this->upcomingCompletions = QueryEnrichService::getUpcomingCompletionsQuery($this->hours, $this->playerId)
            ->with(['village:id,name', 'buildingType:id,name'])
            ->orderBy('construction_completed_at')
            ->limit($this->limit)
            ->get()
            ->toArray();
    }

    public function loadResourcesReachingCapacity()
    {
        // Using Query Enrich to find resources reaching capacity
        $this->resourcesReachingCapacity = QueryEnrichService::getResourcesReachingCapacityQuery($this->hours, $this->playerId)
            ->with(['village:id,name'])
            ->limit($this->limit)
            ->get()
            ->toArray();
    }

    public function refreshData()
    {
        $this->loadData();
        $this->dispatch('data-refreshed');
    }

    public function updatedDays()
    {
        $this->loadActivePlayers();
        $this->loadBattleStats();
    }

    public function updatedHours()
    {
        $this->loadUpcomingCompletions();
        $this->loadResourcesReachingCapacity();
    }

    public function updatedWorldId()
    {
        $this->loadActivePlayers();
        $this->loadPlayerStats();
    }

    public function updatedPlayerId()
    {
        $this->loadVillageStats();
        $this->loadBattleStats();
        $this->loadUpcomingCompletions();
        $this->loadResourcesReachingCapacity();
    }

    public function render()
    {
        return view('livewire.game.query-enrich-demo', [
            'players' => Player::when($this->worldId, function($q) {
                return $q->where('world_id', $this->worldId);
            })->paginate(10),
            'villages' => Village::when($this->playerId, function($q) {
                return $q->where('player_id', $this->playerId);
            })->paginate(10)
        ]);
    }
}

