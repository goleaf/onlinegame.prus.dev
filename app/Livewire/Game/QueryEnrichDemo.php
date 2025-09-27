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
        // Using Query Enrich with when() for conditional filtering and selectRaw for optimization
        $baseQuery = Player::query();

        $filters = [
            $this->worldId => function ($q) {
                return $q->where('world_id', $this->worldId);
            },
            $this->days > 0 => function ($q) {
                return $q->where('last_activity', '>=', now()->subDays($this->days));
            }
        ];

        $this->activePlayers = QueryOptimizationService::applyConditionalFilters($baseQuery, $filters)
            ->selectRaw('
                players.*,
                (SELECT COUNT(*) FROM villages WHERE player_id = players.id) as village_count,
                (SELECT SUM(population) FROM villages WHERE player_id = players.id) as total_population,
                (SELECT COUNT(*) FROM battles WHERE attacker_id = players.id OR defender_id = players.id) as total_battles
            ')
            ->orderByDesc('last_activity')
            ->limit($this->limit)
            ->get()
            ->toArray();
    }

    public function loadPlayerStats()
    {
        // Using Query Enrich with when() for conditional filtering and selectRaw for optimization
        $baseQuery = Player::query();

        $filters = [
            $this->worldId => function ($q) {
                return $q->where('world_id', $this->worldId);
            }
        ];

        $this->playerStats = QueryOptimizationService::applyConditionalFilters($baseQuery, $filters)
            ->selectRaw('
                players.*,
                (SELECT COUNT(*) FROM villages WHERE player_id = players.id) as village_count,
                (SELECT SUM(population) FROM villages WHERE player_id = players.id) as total_population,
                (SELECT SUM(wood + clay + iron + crop) FROM resources r 
                 JOIN villages v ON r.village_id = v.id WHERE v.player_id = players.id) as total_resources,
                (SELECT COUNT(*) FROM battles WHERE attacker_id = players.id OR defender_id = players.id) as total_battles,
                (SELECT SUM(CASE WHEN attacker_id = players.id AND result = "victory" THEN 1 ELSE 0 END) +
                        SUM(CASE WHEN defender_id = players.id AND result = "victory" THEN 1 ELSE 0 END)
                 FROM battles) as total_victories
            ')
            ->orderByDesc('total_population')
            ->limit($this->limit)
            ->get()
            ->toArray();
    }

    public function loadVillageStats()
    {
        // Using Query Enrich with when() for conditional filtering and selectRaw for optimization
        $baseQuery = Village::query();

        $filters = [
            $this->playerId => function ($q) {
                return $q->where('player_id', $this->playerId);
            }
        ];

        $this->villageStats = QueryOptimizationService::applyConditionalFilters($baseQuery, $filters)
            ->selectRaw('
                villages.*,
                (SELECT COUNT(*) FROM buildings WHERE village_id = villages.id) as building_count,
                (SELECT COUNT(*) FROM troops WHERE village_id = villages.id AND quantity > 0) as troop_count,
                (SELECT SUM(wood + clay + iron + crop) FROM resources WHERE village_id = villages.id) as total_resources,
                (SELECT SUM(wood_production + clay_production + iron_production + crop_production) FROM resources WHERE village_id = villages.id) as total_production,
                (SELECT COUNT(*) FROM movements WHERE from_village_id = villages.id OR to_village_id = villages.id) as total_movements,
                (SELECT COUNT(*) FROM battles WHERE village_id = villages.id) as total_battles
            ')
            ->orderByDesc('population')
            ->limit($this->limit)
            ->get()
            ->toArray();
    }

    public function loadBattleStats()
    {
        // Using Query Enrich with when() for conditional filtering and selectRaw for optimization
        $baseQuery = \App\Models\Game\Battle::query();

        $filters = [
            $this->playerId => function ($q) {
                return $q->where(function ($subQ) {
                    $subQ
                        ->where('attacker_id', $this->playerId)
                        ->orWhere('defender_id', $this->playerId);
                });
            }
        ];

        $this->battleStats = QueryOptimizationService::applyConditionalFilters($baseQuery, $filters)
            ->selectRaw('
                COUNT(*) as total_battles,
                SUM(CASE WHEN attacker_id = ? AND result = "victory" THEN 1 ELSE 0 END) as attack_victories,
                SUM(CASE WHEN defender_id = ? AND result = "victory" THEN 1 ELSE 0 END) as defense_victories,
                SUM(CASE WHEN attacker_id = ? AND result = "defeat" THEN 1 ELSE 0 END) as attack_defeats,
                SUM(CASE WHEN defender_id = ? AND result = "defeat" THEN 1 ELSE 0 END) as defense_defeats,
                AVG(EXTRACT(EPOCH FROM (occurred_at - created_at))/3600) as avg_battle_duration_hours,
                SUM(attacker_casualties) as total_attacker_casualties,
                SUM(defender_casualties) as total_defender_casualties
            ', [$this->playerId, $this->playerId, $this->playerId, $this->playerId])
            ->first();
    }

    public function loadUpcomingCompletions()
    {
        // Using Query Enrich with when() for conditional filtering and selectRaw for optimization
        $baseQuery = \App\Models\Game\BuildingQueue::query();

        $filters = [
            $this->playerId => function ($q) {
                return $q->whereHas('village', function ($villageQ) {
                    $villageQ->where('player_id', $this->playerId);
                });
            },
            $this->hours > 0 => function ($q) {
                return $q
                    ->where('construction_completed_at', '<=', now()->addHours($this->hours))
                    ->where('construction_completed_at', '>', now());
            }
        ];

        $this->upcomingCompletions = QueryOptimizationService::applyConditionalFilters($baseQuery, $filters)
            ->selectRaw('
                building_queues.*,
                (SELECT name FROM villages WHERE id = building_queues.village_id) as village_name,
                (SELECT name FROM building_types WHERE id = building_queues.building_type_id) as building_type_name,
                EXTRACT(EPOCH FROM (construction_completed_at - NOW()))/3600 as hours_remaining
            ')
            ->with(['village:id,name', 'buildingType:id,name'])
            ->orderBy('construction_completed_at')
            ->limit($this->limit)
            ->get()
            ->toArray();
    }

    public function loadResourcesReachingCapacity()
    {
        // Using Query Enrich with when() for conditional filtering and selectRaw for optimization
        $baseQuery = \App\Models\Game\Resource::query();

        $filters = [
            $this->playerId => function ($q) {
                return $q->whereHas('village', function ($villageQ) {
                    $villageQ->where('player_id', $this->playerId);
                });
            },
            $this->hours > 0 => function ($q) {
                return $q->whereRaw('(storage_capacity - amount) / GREATEST(production_rate, 1) <= ?', [$this->hours]);
            }
        ];

        $this->resourcesReachingCapacity = QueryOptimizationService::applyConditionalFilters($baseQuery, $filters)
            ->selectRaw('
                resources.*,
                (SELECT name FROM villages WHERE id = resources.village_id) as village_name,
                (storage_capacity - amount) as remaining_capacity,
                (storage_capacity - amount) / GREATEST(production_rate, 1) as hours_to_capacity,
                (amount / storage_capacity * 100) as capacity_percentage
            ')
            ->with(['village:id,name'])
            ->orderBy('hours_to_capacity')
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
        // Use QueryOptimizationService for optimized pagination with when() and selectRaw
        $playersQuery = Player::query();
        $villagesQuery = Village::query();

        $playerFilters = [
            $this->worldId => function ($q) {
                return $q->where('world_id', $this->worldId);
            }
        ];

        $villageFilters = [
            $this->playerId => function ($q) {
                return $q->where('player_id', $this->playerId);
            }
        ];

        return view('livewire.game.query-enrich-demo', [
            'players' => QueryOptimizationService::applyConditionalFilters($playersQuery, $playerFilters)
                ->selectRaw('
                    players.*,
                    (SELECT COUNT(*) FROM villages WHERE player_id = players.id) as village_count,
                    (SELECT SUM(population) FROM villages WHERE player_id = players.id) as total_population
                ')
                ->paginate(10),
            'villages' => QueryOptimizationService::applyConditionalFilters($villagesQuery, $villageFilters)
                ->selectRaw('
                    villages.*,
                    (SELECT COUNT(*) FROM buildings WHERE village_id = villages.id) as building_count,
                    (SELECT COUNT(*) FROM troops WHERE village_id = villages.id AND quantity > 0) as troop_count,
                    (SELECT SUM(wood + clay + iron + crop) FROM resources WHERE village_id = villages.id) as total_resources
                ')
                ->paginate(10)
        ]);
    }
}
