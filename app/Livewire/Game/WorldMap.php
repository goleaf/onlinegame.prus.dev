<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class WorldMap extends Component
{
    #[Reactive]
    public $world;

    public $mapData = [];
    public $selectedVillage = null;
    public $mapSize = 400;  // 400x400 grid
    public $viewCenter = ['x' => 200, 'y' => 200];
    public $zoomLevel = 1;
    public $showCoordinates = true;
    public $showVillageNames = true;
    public $showAlliances = true;
    public $filterAlliance = null;
    public $filterTribe = null;
    public $isLoading = false;
    public $notifications = [];
    public $autoRefresh = true;
    public $refreshInterval = 10;
    public $realTimeUpdates = true;
    public $showPlayerVillages = true;
    public $showEnemyVillages = true;
    public $showNeutralVillages = true;
    public $searchQuery = '';
    public $selectedPlayer = null;
    public $mapMode = 'normal';  // normal, alliance, tribe, player
    public $highlightedVillages = [];
    public $mapStats = [];
    // Enhanced map features
    public $showGrid = true;
    public $showDistance = false;
    public $showMovementPaths = false;
    public $showResourceFields = false;
    public $showOasis = false;
    public $showBarbarianVillages = true;
    public $showNatarianVillages = true;
    public $coordinateSystem = 'game';  // game, decimal, dms
    public $mapTheme = 'classic';  // classic, modern, dark
    public $showPlayerStats = false;
    public $showAllianceStats = false;
    public $showBattleHistory = false;
    public $showTradeRoutes = false;
    public $selectedCoordinates = null;
    public $mapBounds = ['min_x' => 0, 'max_x' => 400, 'min_y' => 0, 'max_y' => 400];
    public $visibleVillages = [];
    public $mapLayers = ['villages' => true, 'alliances' => true, 'resources' => false, 'movements' => false];

    protected $listeners = [
        'refreshMap',
        'villageSelected',
        'mapUpdated',
        'centerOnVillage',
        'gameTickProcessed',
        'playerSelected',
        'allianceSelected',
        'coordinatesSelected',
        'mapLayerToggled',
        'mapThemeChanged',
        'movementPathSelected',
        'tradeRouteSelected',
    ];

    public function mount($worldId = null)
    {
        if ($worldId) {
            $this->world = World::selectRaw('
                worlds.*,
                (SELECT COUNT(*) FROM players p WHERE p.world_id = worlds.id) as total_players,
                (SELECT COUNT(*) FROM villages v WHERE v.world_id = worlds.id) as total_villages,
                (SELECT COUNT(*) FROM alliances a WHERE a.world_id = worlds.id) as total_alliances
            ')->findOrFail($worldId);
        } else {
            $player = Player::where('user_id', Auth::id())
                ->with(['world:id,name'])
                ->selectRaw('
                    players.*,
                    (SELECT COUNT(*) FROM villages WHERE player_id = players.id) as village_count
                ')
                ->first();
            $this->world = $player?->world;
        }

        if ($this->world) {
            $this->loadMapData();
            $this->initializeMapFeatures();
            $this->calculateMapBounds();
            $this->loadVisibleVillages();
        }
    }

    public function initializeMapFeatures()
    {
        $this->calculateMapStats();
        $this->dispatch('initializeMapRealTime', [
            'interval' => $this->refreshInterval * 1000,
            'autoRefresh' => $this->autoRefresh,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadMapData()
    {
        $this->isLoading = true;

        try {
            $villages = Village::where('world_id', $this->world->id)
                ->with(['player:id,name,tribe,alliance_id', 'player.alliance:id,name,tag'])
                ->selectRaw('
                    villages.*,
                    (SELECT COUNT(*) FROM buildings WHERE village_id = villages.id) as building_count,
                    (SELECT COUNT(*) FROM troops WHERE village_id = villages.id AND quantity > 0) as troop_count,
                    (SELECT SUM(wood + clay + iron + crop) FROM resources WHERE village_id = villages.id) as total_resources
                ')
                ->get();

            $this->mapData = $villages->map(function ($village) {
                return [
                    'id' => $village->id,
                    'name' => $village->name,
                    'x' => $village->x_coordinate,
                    'y' => $village->y_coordinate,
                    'population' => $village->population,
                    'player_name' => $village->player->name,
                    'player_id' => $village->player_id,
                    'alliance_name' => $village->player->alliance?->name,
                    'alliance_tag' => $village->player->alliance?->tag,
                    'tribe' => $village->player->tribe,
                    'is_capital' => $village->is_capital,
                    'is_active' => $village->is_active,
                    'building_count' => $village->building_count ?? 0,
                    'troop_count' => $village->troop_count ?? 0,
                    'total_resources' => $village->total_resources ?? 0,
                ];
            })->toArray();

            $this->addNotification('Map data loaded successfully', 'success');
        } catch (\Exception $e) {
            $this->addNotification('Failed to load map data: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectVillage($villageId)
    {
        $village = Village::with(['player:id,name,tribe,alliance_id', 'player.alliance:id,name,tag'])
            ->selectRaw('
                villages.*,
                (SELECT COUNT(*) FROM buildings WHERE village_id = villages.id) as building_count,
                (SELECT COUNT(*) FROM troops WHERE village_id = villages.id AND quantity > 0) as troop_count,
                (SELECT SUM(wood + clay + iron + crop) FROM resources WHERE village_id = villages.id) as total_resources
            ')
            ->find($villageId);

        if ($village) {
            $this->selectedVillage = [
                'id' => $village->id,
                'name' => $village->name,
                'x' => $village->x_coordinate,
                'y' => $village->y_coordinate,
                'population' => $village->population,
                'player_name' => $village->player->name,
                'alliance_name' => $village->player->alliance?->name,
                'tribe' => $village->player->tribe,
                'is_capital' => $village->is_capital,
                'building_count' => $village->building_count ?? 0,
                'troop_count' => $village->troop_count ?? 0,
                'total_resources' => $village->total_resources ?? 0,
            ];

            $this->dispatch('villageSelected', ['villageId' => $villageId]);
            $this->addNotification("Selected village: {$village->name}", 'info');
        }
    }

    public function centerOnVillage($villageId)
    {
        $village = Village::find($villageId);

        if ($village) {
            $this->viewCenter = [
                'x' => $village->x_coordinate,
                'y' => $village->y_coordinate,
            ];

            $this->addNotification("Centered on village: {$village->name}", 'info');
        }
    }

    public function moveMap($direction)
    {
        $moveDistance = 50 / $this->zoomLevel;

        switch ($direction) {
            case 'north':
                $this->viewCenter['y'] = max(0, $this->viewCenter['y'] - $moveDistance);

                break;
            case 'south':
                $this->viewCenter['y'] = min($this->mapSize, $this->viewCenter['y'] + $moveDistance);

                break;
            case 'east':
                $this->viewCenter['x'] = min($this->mapSize, $this->viewCenter['x'] + $moveDistance);

                break;
            case 'west':
                $this->viewCenter['x'] = max(0, $this->viewCenter['x'] - $moveDistance);

                break;
        }
    }

    public function toggleCoordinates()
    {
        $this->showCoordinates = !$this->showCoordinates;
    }

    public function toggleVillageNames()
    {
        $this->showVillageNames = !$this->showVillageNames;
    }

    public function toggleAlliances()
    {
        $this->showAlliances = !$this->showAlliances;
    }

    public function setFilterAlliance($allianceId)
    {
        $this->filterAlliance = $allianceId;
        $this->addNotification('Filtered by alliance', 'info');
    }

    public function setFilterTribe($tribe)
    {
        $this->filterTribe = $tribe;
        $this->addNotification("Filtered by tribe: {$tribe}", 'info');
    }

    public function getFilteredMapData()
    {
        $data = $this->mapData;

        if ($this->filterAlliance) {
            $data = array_filter($data, function ($village) {
                return $village['alliance_name'] === $this->filterAlliance;
            });
        }

        if ($this->filterTribe) {
            $data = array_filter($data, function ($village) {
                return $village['tribe'] === $this->filterTribe;
            });
        }

        return $data;
    }

    public function getVisibleVillages()
    {
        $data = $this->getFilteredMapData();
        $viewSize = 100 / $this->zoomLevel;

        return array_filter($data, function ($village) use ($viewSize) {
            return abs($village['x'] - $this->viewCenter['x']) <= $viewSize &&
                abs($village['y'] - $this->viewCenter['y']) <= $viewSize;
        });
    }

    #[On('refreshMap')]
    public function refreshMap()
    {
        $this->loadMapData();
    }

    #[On('mapUpdated')]
    public function handleMapUpdated()
    {
        $this->loadMapData();
    }

    public function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
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

    public function calculateMapStats()
    {
        if (!$this->world) {
            return;
        }

        // Use optimized query to get all stats in one go
        $stats = Village::where('world_id', $this->world->id)
            ->selectRaw('
                COUNT(*) as total_villages,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_villages,
                SUM(CASE WHEN is_capital = 1 THEN 1 ELSE 0 END) as capital_villages,
                AVG(population) as average_population,
                MAX(population) as largest_village,
                MIN(population) as smallest_village,
                SUM(population) as total_population
            ')
            ->first();

        $playerStats = Player::where('world_id', $this->world->id)
            ->selectRaw('
                COUNT(*) as total_players,
                COUNT(DISTINCT tribe) as unique_tribes,
                GROUP_CONCAT(DISTINCT tribe) as tribes
            ')
            ->first();

        $allianceStats = \DB::table('alliances')
            ->where('world_id', $this->world->id)
            ->selectRaw('COUNT(*) as total_alliances')
            ->first();

        $this->mapStats = [
            'total_villages' => $stats->total_villages ?? 0,
            'total_players' => $playerStats->total_players ?? 0,
            'total_alliances' => $allianceStats->total_alliances ?? 0,
            'active_villages' => $stats->active_villages ?? 0,
            'capital_villages' => $stats->capital_villages ?? 0,
            'average_population' => round($stats->average_population ?? 0, 2),
            'largest_village' => $stats->largest_village ?? 0,
            'smallest_village' => $stats->smallest_village ?? 0,
            'total_population' => $stats->total_population ?? 0,
            'unique_tribes' => $playerStats->unique_tribes ?? 0,
            'tribes' => $playerStats->tribes ? explode(',', $playerStats->tribes) : [],
        ];
    }

    public function filterByAlliance($allianceId)
    {
        $this->filterAlliance = $allianceId;
        $this->mapMode = 'alliance';
        $this->addNotification('Filtering by alliance', 'info');
    }

    public function filterByTribe($tribe)
    {
        $this->filterTribe = $tribe;
        $this->mapMode = 'tribe';
        $this->addNotification('Filtering by tribe: ' . $tribe, 'info');
    }

    public function filterByPlayer($playerId)
    {
        $this->selectedPlayer = $playerId;
        $this->mapMode = 'player';
        $this->addNotification('Filtering by player', 'info');
    }

    public function clearFilters()
    {
        $this->filterAlliance = null;
        $this->filterTribe = null;
        $this->selectedPlayer = null;
        $this->mapMode = 'normal';
        $this->highlightedVillages = [];
        $this->searchQuery = '';
        $this->addNotification('All filters cleared', 'info');
    }

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
        $this->refreshInterval = max(5, min(60, $interval));
        $this->addNotification("Refresh interval set to {$this->refreshInterval} seconds", 'info');
    }

    public function zoomIn()
    {
        $this->zoomLevel = min(3, $this->zoomLevel + 0.5);
        $this->addNotification('Zoomed in', 'info');
    }

    public function zoomOut()
    {
        $this->zoomLevel = max(0.5, $this->zoomLevel - 0.5);
        $this->addNotification('Zoomed out', 'info');
    }

    public function resetZoom()
    {
        $this->zoomLevel = 1;
        $this->addNotification('Zoom reset', 'info');
    }

    public function centerOnCoordinates($x, $y)
    {
        $this->viewCenter = ['x' => $x, 'y' => $y];
        $this->addNotification("Centered on coordinates ({$x}|{$y})", 'info');
    }

    public function getVillageColor($village)
    {
        if (in_array($village['id'], $this->highlightedVillages)) {
            return 'highlight';
        }

        if ($village['is_capital']) {
            return 'capital';
        }

        if ($village['alliance_name']) {
            return 'alliance';
        }

        return 'normal';
    }

    public function getTribeIcon($tribe)
    {
        $icons = [
            'roman' => '🏛️',
            'teuton' => '⚔️',
            'gaul' => '🌿',
        ];

        return $icons[$tribe] ?? '🏘️';
    }

    #[On('gameTickProcessed')]
    public function handleGameTickProcessed()
    {
        if ($this->realTimeUpdates) {
            $this->loadMapData();
            $this->calculateMapStats();
            $this->loadVisibleVillages();
        }
    }

    public function calculateMapBounds()
    {
        $villages = Village::where('world_id', $this->world->id)
            ->selectRaw('MIN(x_coordinate) as min_x, MAX(x_coordinate) as max_x, MIN(y_coordinate) as min_y, MAX(y_coordinate) as max_y')
            ->first();

        if ($villages) {
            $this->mapBounds = [
                'min_x' => max(0, $villages->min_x - 10),
                'max_x' => min(400, $villages->max_x + 10),
                'min_y' => max(0, $villages->min_y - 10),
                'max_y' => min(400, $villages->max_y + 10),
            ];
        }
    }

    public function loadVisibleVillages()
    {
        $viewRadius = 50 / $this->zoomLevel;  // Adjust based on zoom level

        $this->visibleVillages = collect($this->mapData)->filter(function ($village) use ($viewRadius) {
            $distance = sqrt(
                pow($village['x'] - $this->viewCenter['x'], 2)
                + pow($village['y'] - $this->viewCenter['y'], 2)
            );
            return $distance <= $viewRadius;
        })->values()->toArray();
    }

    public function selectCoordinates($x, $y)
    {
        $this->selectedCoordinates = ['x' => $x, 'y' => $y];
        $this->dispatch('coordinatesSelected', ['x' => $x, 'y' => $y]);
        $this->addNotification("Selected coordinates: ({$x}, {$y})", 'info');
    }

    public function toggleMapLayer($layer)
    {
        if (isset($this->mapLayers[$layer])) {
            $this->mapLayers[$layer] = !$this->mapLayers[$layer];
            $this->dispatch('mapLayerToggled', ['layer' => $layer, 'enabled' => $this->mapLayers[$layer]]);
        }
    }

    public function changeMapTheme($theme)
    {
        $this->mapTheme = $theme;
        $this->dispatch('mapThemeChanged', ['theme' => $theme]);
        $this->addNotification("Map theme changed to: {$theme}", 'info');
    }

    public function calculateDistance($x1, $y1, $x2, $y2)
    {
        return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
    }

    public function getVillageDistance($villageId)
    {
        if (!$this->selectedVillage) {
            return null;
        }

        $village = collect($this->mapData)->firstWhere('id', $villageId);
        if (!$village) {
            return null;
        }

        return $this->calculateDistance(
            $this->selectedVillage['x'],
            $this->selectedVillage['y'],
            $village['x'],
            $village['y']
        );
    }

    public function searchVillages()
    {
        if (empty($this->searchQuery)) {
            $this->loadVisibleVillages();
            return;
        }

        $this->visibleVillages = collect($this->mapData)->filter(function ($village) {
            return stripos($village['name'], $this->searchQuery) !== false ||
                stripos($village['player_name'], $this->searchQuery) !== false ||
                stripos($village['alliance_name'] ?? '', $this->searchQuery) !== false;
        })->values()->toArray();
    }

    public function filterVillages()
    {
        $this->visibleVillages = collect($this->mapData)->filter(function ($village) {
            // Filter by tribe
            if ($this->filterTribe && $village['tribe'] !== $this->filterTribe) {
                return false;
            }

            // Filter by alliance
            if ($this->filterAlliance && $village['alliance_name'] !== $this->filterAlliance) {
                return false;
            }

            // Filter by village type
            if (!$this->showPlayerVillages && $village['player_name'] !== 'Barbarian') {
                return false;
            }

            if (!$this->showBarbarianVillages && $village['player_name'] === 'Barbarian') {
                return false;
            }

            if (!$this->showNatarianVillages && $village['player_name'] === 'Natarian') {
                return false;
            }

            return true;
        })->values()->toArray();
    }

    public function getMapThemeClass()
    {
        return match ($this->mapTheme) {
            'modern' => 'map-theme-modern',
            'dark' => 'map-theme-dark',
            default => 'map-theme-classic',
        };
    }

    public function getCoordinateDisplay($x, $y)
    {
        return match ($this->coordinateSystem) {
            'decimal' => "({$x}.0, {$y}.0)",
            'dms' => "({$x}°0'0\", {$y}°0'0\")",
            default => "({$x}|{$y})",
        };
    }

    public function render()
    {
        return view('livewire.game.world-map', [
            'world' => $this->world,
            'mapData' => $this->mapData,
            'selectedVillage' => $this->selectedVillage,
            'visibleVillages' => $this->visibleVillages,
            'viewCenter' => $this->viewCenter,
            'zoomLevel' => $this->zoomLevel,
            'showCoordinates' => $this->showCoordinates,
            'showVillageNames' => $this->showVillageNames,
            'showAlliances' => $this->showAlliances,
            'filterAlliance' => $this->filterAlliance,
            'filterTribe' => $this->filterTribe,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
            'realTimeUpdates' => $this->realTimeUpdates,
            'showPlayerVillages' => $this->showPlayerVillages,
            'showEnemyVillages' => $this->showEnemyVillages,
            'showNeutralVillages' => $this->showNeutralVillages,
            'searchQuery' => $this->searchQuery,
            'selectedPlayer' => $this->selectedPlayer,
            'mapMode' => $this->mapMode,
            'highlightedVillages' => $this->highlightedVillages,
            'mapStats' => $this->mapStats,
            'showGrid' => $this->showGrid,
            'showDistance' => $this->showDistance,
            'showMovementPaths' => $this->showMovementPaths,
            'showResourceFields' => $this->showResourceFields,
            'showOasis' => $this->showOasis,
            'showBarbarianVillages' => $this->showBarbarianVillages,
            'showNatarianVillages' => $this->showNatarianVillages,
            'coordinateSystem' => $this->coordinateSystem,
            'mapTheme' => $this->mapTheme,
            'showPlayerStats' => $this->showPlayerStats,
            'showAllianceStats' => $this->showAllianceStats,
            'showBattleHistory' => $this->showBattleHistory,
            'showTradeRoutes' => $this->showTradeRoutes,
            'selectedCoordinates' => $this->selectedCoordinates,
            'mapBounds' => $this->mapBounds,
            'mapLayers' => $this->mapLayers,
        ]);
    }
}
