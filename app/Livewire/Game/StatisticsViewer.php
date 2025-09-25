<?php

namespace App\Livewire\Game;

use App\Models\Game\Building;
use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\Game\Troop;
use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class StatisticsViewer extends Component
{
    use WithPagination;

    public $world;
    public $player;
    public $isLoading = false;
    public $notifications = [];
    // Statistics data
    public $playerStats = [];
    public $rankingStats = [];
    public $battleStats = [];
    public $resourceStats = [];
    public $buildingStats = [];
    public $troopStats = [];
    public $achievementStats = [];
    public $recentActivity = [];
    public $performanceMetrics = [];
    // View modes and filters
    public $viewMode = 'overview';  // overview, rankings, battles, resources, buildings, troops, achievements
    public $timeRange = 'all';  // today, week, month, all
    public $statType = 'all';  // all, personal, alliance, world
    public $sortBy = 'rank';
    public $sortOrder = 'asc';
    public $searchQuery = '';
    // Real-time features
    public $realTimeUpdates = true;
    public $autoRefresh = true;
    public $refreshInterval = 30;  // seconds
    public $lastUpdate = null;
    // Pagination
    public $perPage = 20;
    public $currentPage = 1;

    // Statistics categories
    public $statCategories = [
        'overview' => 'Overview',
        'rankings' => 'Rankings',
        'battles' => 'Battles',
        'resources' => 'Resources',
        'buildings' => 'Buildings',
        'troops' => 'Troops',
        'achievements' => 'Achievements'
    ];

    public $timeRanges = [
        'today' => 'Today',
        'week' => 'This Week',
        'month' => 'This Month',
        'all' => 'All Time'
    ];

    public $statTypes = [
        'all' => 'All Statistics',
        'personal' => 'Personal',
        'alliance' => 'Alliance',
        'world' => 'World'
    ];

    protected $listeners = [
        'refreshStatistics',
        'statisticsUpdated',
        'playerRankingChanged',
        'battleCompleted',
        'achievementUnlocked',
        'gameTickProcessed',
        'villageSelected'
    ];

    public function mount($worldId = null, $world = null)
    {
        if ($world) {
            $this->world = $world;
        } elseif ($worldId) {
            $this->world = World::findOrFail($worldId);
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->world = $player?->village?->world;
        }

        if ($this->world) {
            $this->loadPlayerData();
            $this->loadStatistics();
            $this->initializeStatisticsFeatures();
        }
    }

    public function loadPlayerData()
    {
        try {
            $this->player = Player::where('user_id', Auth::id())
                ->where('world_id', $this->world->id)
                ->with(['villages', 'alliance'])
                ->first();

            if (!$this->player) {
                $this->addNotification('Player not found in this world', 'error');
                return;
            }
        } catch (\Exception $e) {
            $this->addNotification('Error loading player data: ' . $e->getMessage(), 'error');
        }
    }

    public function loadStatistics()
    {
        $this->isLoading = true;

        try {
            switch ($this->viewMode) {
                case 'overview':
                    $this->loadOverviewStats();
                    break;
                case 'rankings':
                    $this->loadRankingStats();
                    break;
                case 'battles':
                    $this->loadBattleStats();
                    break;
                case 'resources':
                    $this->loadResourceStats();
                    break;
                case 'buildings':
                    $this->loadBuildingStats();
                    break;
                case 'troops':
                    $this->loadTroopStats();
                    break;
                case 'achievements':
                    $this->loadAchievementStats();
                    break;
            }

            $this->lastUpdate = now();
        } catch (\Exception $e) {
            $this->addNotification('Error loading statistics: ' . $e->getMessage(), 'error');
        }

        $this->isLoading = false;
    }

    private function loadOverviewStats()
    {
        $this->playerStats = [
            'rank' => $this->getPlayerRank(),
            'points' => $this->player->points ?? 0,
            'villages' => $this->player->villages->count(),
            'population' => $this->player->villages->sum('population'),
            'alliance' => $this->player->alliance?->name ?? 'No Alliance',
            'join_date' => $this->player->created_at,
            'last_active' => $this->player->updated_at,
        ];

        $this->battleStats = [
            'attacks_won' => $this->getAttackWins(),
            'attacks_lost' => $this->getAttackLosses(),
            'defenses_won' => $this->getDefenseWins(),
            'defenses_lost' => $this->getDefenseLosses(),
            'total_battles' => $this->getTotalBattles(),
            'win_rate' => $this->getWinRate(),
        ];

        $this->resourceStats = [
            'total_wood' => $this->getTotalResource('wood'),
            'total_clay' => $this->getTotalResource('clay'),
            'total_iron' => $this->getTotalResource('iron'),
            'total_crop' => $this->getTotalResource('crop'),
            'production_rate' => $this->getProductionRate(),
        ];

        $this->buildingStats = [
            'total_buildings' => $this->getTotalBuildings(),
            'building_levels' => $this->getBuildingLevels(),
            'upgrade_progress' => $this->getUpgradeProgress(),
        ];

        $this->troopStats = [
            'total_troops' => $this->getTotalTroops(),
            'troop_types' => $this->getTroopTypes(),
            'army_strength' => $this->getArmyStrength(),
        ];
    }

    private function loadRankingStats()
    {
        $query = Player::where('world_id', $this->world->id)
            ->with(['villages', 'alliance'])
            ->orderBy('points', 'desc');

        if ($this->searchQuery) {
            $query->where('name', 'like', '%' . $this->searchQuery . '%');
        }

        $this->rankingStats = $query->get();
    }

    private function loadBattleStats()
    {
        $this->battleStats = [
            'recent_battles' => $this->getRecentBattles(),
            'battle_history' => $this->getBattleHistory(),
            'attack_stats' => $this->getAttackStats(),
            'defense_stats' => $this->getDefenseStats(),
            'casualties' => $this->getCasualties(),
            'loot_gained' => $this->getLootGained(),
            'loot_lost' => $this->getLootLost(),
        ];
    }

    private function loadResourceStats()
    {
        $this->resourceStats = [
            'current_resources' => $this->getCurrentResources(),
            'production_rates' => $this->getProductionRates(),
            'storage_capacity' => $this->getStorageCapacity(),
            'resource_history' => $this->getResourceHistory(),
            'trade_stats' => $this->getTradeStats(),
        ];
    }

    private function loadBuildingStats()
    {
        $this->buildingStats = [
            'building_counts' => $this->getBuildingCounts(),
            'building_levels' => $this->getBuildingLevels(),
            'upgrade_queue' => $this->getUpgradeQueue(),
            'construction_time' => $this->getConstructionTime(),
            'building_efficiency' => $this->getBuildingEfficiency(),
        ];
    }

    private function loadTroopStats()
    {
        $this->troopStats = [
            'troop_counts' => $this->getTroopCounts(),
            'troop_production' => $this->getTroopProduction(),
            'army_composition' => $this->getArmyComposition(),
            'training_time' => $this->getTrainingTime(),
            'troop_efficiency' => $this->getTroopEfficiency(),
        ];
    }

    private function loadAchievementStats()
    {
        $this->achievementStats = [
            'unlocked_achievements' => $this->getUnlockedAchievements(),
            'available_achievements' => $this->getAvailableAchievements(),
            'achievement_progress' => $this->getAchievementProgress(),
            'achievement_points' => $this->getAchievementPoints(),
        ];
    }

    // Statistics calculation methods
    private function getPlayerRank()
    {
        return Player::where('world_id', $this->world->id)
            ->where('points', '>', $this->player->points ?? 0)
            ->count() + 1;
    }

    private function getAttackWins()
    {
        return Report::where('world_id', $this->world->id)
            ->where('attacker_id', $this->player->id)
            ->where('status', 'victory')
            ->count();
    }

    private function getAttackLosses()
    {
        return Report::where('world_id', $this->world->id)
            ->where('attacker_id', $this->player->id)
            ->where('status', 'defeat')
            ->count();
    }

    private function getDefenseWins()
    {
        return Report::where('world_id', $this->world->id)
            ->where('defender_id', $this->player->id)
            ->where('status', 'victory')
            ->count();
    }

    private function getDefenseLosses()
    {
        return Report::where('world_id', $this->world->id)
            ->where('defender_id', $this->player->id)
            ->where('status', 'defeat')
            ->count();
    }

    private function getTotalBattles()
    {
        return $this->getAttackWins() + $this->getAttackLosses()
            + $this->getDefenseWins() + $this->getDefenseLosses();
    }

    private function getWinRate()
    {
        $totalBattles = $this->getTotalBattles();
        if ($totalBattles === 0)
            return 0;

        $wins = $this->getAttackWins() + $this->getDefenseWins();
        return round(($wins / $totalBattles) * 100, 2);
    }

    private function getTotalResource($resource)
    {
        return $this->player->villages->sum($resource);
    }

    private function getProductionRate()
    {
        return [
            'wood' => $this->player->villages->sum('wood_production'),
            'clay' => $this->player->villages->sum('clay_production'),
            'iron' => $this->player->villages->sum('iron_production'),
            'crop' => $this->player->villages->sum('crop_production'),
        ];
    }

    private function getTotalBuildings()
    {
        return Building::whereIn('village_id', $this->player->villages->pluck('id'))
            ->count();
    }

    private function getBuildingLevels()
    {
        return Building::whereIn('village_id', $this->player->villages->pluck('id'))
            ->groupBy('building_type_id')
            ->selectRaw('building_type_id, AVG(level) as avg_level, MAX(level) as max_level')
            ->get();
    }

    private function getUpgradeProgress()
    {
        return Building::whereIn('village_id', $this->player->villages->pluck('id'))
            ->where('upgrade_finishes_at', '>', now())
            ->count();
    }

    private function getTotalTroops()
    {
        return Troop::whereIn('village_id', $this->player->villages->pluck('id'))
            ->sum('count');
    }

    private function getTroopTypes()
    {
        return Troop::whereIn('village_id', $this->player->villages->pluck('id'))
            ->with('unitType')
            ->get()
            ->groupBy('unit_type_id')
            ->map(function ($troops) {
                return [
                    'type' => $troops->first()->unitType->name ?? 'Unknown',
                    'count' => $troops->sum('count')
                ];
            });
    }

    private function getArmyStrength()
    {
        return Troop::whereIn('village_id', $this->player->villages->pluck('id'))
            ->with('unitType')
            ->get()
            ->sum(function ($troop) {
                return $troop->count * ($troop->unitType->attack ?? 0);
            });
    }

    // View mode methods
    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->loadStatistics();
        $this->addNotification('Switched to ' . ($this->statCategories[$mode] ?? $mode) . ' view', 'info');
    }

    public function setTimeRange($range)
    {
        $this->timeRange = $range;
        $this->loadStatistics();
        $this->addNotification('Time range set to ' . ($this->timeRanges[$range] ?? $range), 'info');
    }

    public function setStatType($type)
    {
        $this->statType = $type;
        $this->loadStatistics();
        $this->addNotification('Statistics type set to ' . ($this->statTypes[$type] ?? $type), 'info');
    }

    public function sortStatistics($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortOrder = 'asc';
        }

        $this->loadStatistics();
        $this->addNotification("Sorted by {$sortBy} ({$this->sortOrder})", 'info');
    }

    public function searchStatistics()
    {
        if (empty($this->searchQuery)) {
            $this->addNotification('Search cleared', 'info');
            return;
        }

        $this->loadStatistics();
        $this->addNotification("Searching for: {$this->searchQuery}", 'info');
    }

    public function clearFilters()
    {
        $this->timeRange = 'all';
        $this->statType = 'all';
        $this->searchQuery = '';
        $this->sortBy = 'rank';
        $this->sortOrder = 'asc';

        $this->loadStatistics();
        $this->addNotification('All filters cleared', 'info');
    }

    // Real-time features
    public function toggleRealTimeUpdates()
    {
        $this->realTimeUpdates = !$this->realTimeUpdates;
        $this->addNotification(
            $this->realTimeUpdates ? 'Real-time updates enabled' : 'Real-time updates disabled',
            'info'
        );
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
        $this->refreshInterval = max(5, min(300, $interval));
        $this->addNotification("Refresh interval set to {$this->refreshInterval} seconds", 'info');
    }

    public function refreshStatistics()
    {
        $this->loadStatistics();
        $this->addNotification('Statistics refreshed', 'success');
    }

    // Event handlers
    #[On('statisticsUpdated')]
    public function handleStatisticsUpdated($data)
    {
        $this->loadStatistics();
        $this->addNotification('Statistics updated', 'info');
    }

    #[On('playerRankingChanged')]
    public function handlePlayerRankingChanged($data)
    {
        $this->loadStatistics();
        $this->addNotification('Player ranking updated', 'info');
    }

    #[On('battleCompleted')]
    public function handleBattleCompleted($data)
    {
        $this->loadStatistics();
        $this->addNotification('Battle completed - statistics updated', 'success');
    }

    #[On('achievementUnlocked')]
    public function handleAchievementUnlocked($data)
    {
        $this->loadStatistics();
        $this->addNotification('Achievement unlocked!', 'success');
    }

    #[On('gameTickProcessed')]
    public function handleGameTickProcessed()
    {
        if ($this->realTimeUpdates) {
            $this->loadStatistics();
        }
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $this->loadStatistics();
        $this->addNotification('Village selected - statistics updated', 'info');
    }

    // Utility methods
    public function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now()
        ];
    }

    public function clearNotifications()
    {
        $this->notifications = [];
    }

    public function getStatIcon($category)
    {
        $icons = [
            'overview' => 'chart-bar',
            'rankings' => 'trophy',
            'battles' => 'sword',
            'resources' => 'coins',
            'buildings' => 'home',
            'troops' => 'users',
            'achievements' => 'star'
        ];

        return $icons[$category] ?? 'chart-bar';
    }

    public function getStatColor($category)
    {
        $colors = [
            'overview' => 'blue',
            'rankings' => 'yellow',
            'battles' => 'red',
            'resources' => 'green',
            'buildings' => 'purple',
            'troops' => 'orange',
            'achievements' => 'pink'
        ];

        return $colors[$category] ?? 'blue';
    }

    public function formatNumber($number)
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }

    public function getTimeAgo($date)
    {
        return $date->diffForHumans();
    }

    private function initializeStatisticsFeatures()
    {
        // Initialize any additional features
        $this->lastUpdate = now();
    }

    public function render()
    {
        return view('livewire.game.statistics-viewer', [
            'statCategories' => $this->statCategories,
            'timeRanges' => $this->timeRanges,
            'statTypes' => $this->statTypes,
        ]);
    }
}
