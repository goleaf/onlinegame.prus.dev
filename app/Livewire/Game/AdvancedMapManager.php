<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\GeographicService;
use App\Services\LarautilxIntegrationService;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\FilteringUtil;
use Livewire\Component;

class AdvancedMapManager extends Component
{
    use ApiResponseTrait;

    public $worlds = [];
    public $selectedWorld = null;
    public $centerX = 500;
    public $centerY = 500;
    public $radius = 20;
    public $filter = '';
    public $realWorldMode = false;
    public $villages = [];
    public $mapData = [];
    public $statistics = [];
    public $selectedVillage = null;
    public $isLoading = false;

    protected $listeners = [
        'mapUpdated' => 'handleMapUpdate',
        'villageSelected' => 'handleVillageSelection',
        'worldChanged' => 'handleWorldChange',
    ];

    public function mount()
    {
        $this->loadWorlds();
        $this->loadInitialData();
    }

    public function loadWorlds()
    {
        $this->worlds = World::active()->get();
        if ($this->worlds->isNotEmpty()) {
            $this->selectedWorld = $this->worlds->first()->id;
        }
    }

    public function loadInitialData()
    {
        if (!$this->selectedWorld) {
            return;
        }

        $this->isLoading = true;
        
        try {
            // Use LarautilxIntegrationService for caching
            $integrationService = app(LarautilxIntegrationService::class);
            
            $this->villages = $integrationService->cacheWorldData(
                $this->selectedWorld,
                'villages_map_data',
                function() {
                    return Village::with(['player', 'world'])
                        ->byWorld($this->selectedWorld)
                        ->withStats()
                        ->get()
                        ->map(function ($village) {
                            return [
                                'id' => $village->id,
                                'name' => $village->name,
                                'x_coordinate' => $village->x_coordinate,
                                'y_coordinate' => $village->y_coordinate,
                                'player_id' => $village->player_id,
                                'player_name' => $village->player->name ?? 'Unknown',
                                'alliance_id' => $village->player->alliance_id ?? null,
                                'population' => $village->population ?? 0,
                                'is_capital' => $village->is_capital ?? false,
                                'building_count' => $village->building_count ?? 0,
                                'troop_count' => $village->troop_count ?? 0,
                                'total_resources' => $village->total_resources ?? 0,
                            ];
                        });
                },
                300 // 5 minutes cache
            );

            $this->calculateStatistics();
            $this->updateMapData();
            
        } catch (\Exception $e) {
            $this->addNotification('Error loading map data: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function updatedSelectedWorld()
    {
        $this->loadInitialData();
        $this->dispatch('worldChanged', ['worldId' => $this->selectedWorld]);
    }

    public function updatedCenterX()
    {
        $this->updateMapData();
    }

    public function updatedCenterY()
    {
        $this->updateMapData();
    }

    public function updatedRadius()
    {
        $this->updateMapData();
    }

    public function updatedFilter()
    {
        $this->updateMapData();
    }

    public function updatedRealWorldMode()
    {
        $this->updateMapData();
        $this->dispatch('mapModeChanged', ['mode' => $this->realWorldMode ? 'real_world' : 'game']);
    }

    public function updateMapData()
    {
        if (empty($this->villages)) {
            return;
        }

        $filteredVillages = collect($this->villages);

        // Apply radius filter
        $filteredVillages = $filteredVillages->filter(function ($village) {
            $distance = sqrt(
                pow($village['x_coordinate'] - $this->centerX, 2) + 
                pow($village['y_coordinate'] - $this->centerY, 2)
            );
            return $distance <= $this->radius;
        });

        // Apply additional filters using FilteringUtil
        if (!empty($this->filter)) {
            switch ($this->filter) {
                case 'player':
                    $currentPlayer = Auth::user()?->player;
                    if ($currentPlayer) {
                        $filteredVillages = FilteringUtil::filter(
                            $filteredVillages,
                            'player_id',
                            'equals',
                            $currentPlayer->id
                        );
                    }
                    break;
                case 'alliance':
                    $currentPlayer = Auth::user()?->player;
                    if ($currentPlayer && $currentPlayer->alliance_id) {
                        $filteredVillages = FilteringUtil::filter(
                            $filteredVillages,
                            'alliance_id',
                            'equals',
                            $currentPlayer->alliance_id
                        );
                    }
                    break;
                case 'enemy':
                    $currentPlayer = Auth::user()?->player;
                    if ($currentPlayer) {
                        $filteredVillages = FilteringUtil::filter(
                            $filteredVillages,
                            'player_id',
                            'not_equals',
                            $currentPlayer->id
                        );
                    }
                    break;
                case 'abandoned':
                    $filteredVillages = FilteringUtil::filter(
                        $filteredVillages,
                        'population',
                        'equals',
                        0
                    );
                    break;
            }
        }

        $this->mapData = $filteredVillages->values()->toArray();
        $this->calculateStatistics();
        
        $this->dispatch('mapUpdated', [
            'villages' => $this->mapData,
            'center' => ['x' => $this->centerX, 'y' => $this->centerY],
            'radius' => $this->radius,
            'realWorldMode' => $this->realWorldMode
        ]);
    }

    public function calculateStatistics()
    {
        $currentPlayer = Auth::user()?->player;
        
        $this->statistics = [
            'total_villages' => count($this->mapData),
            'my_villages' => 0,
            'alliance_villages' => 0,
            'enemy_villages' => 0,
            'abandoned_villages' => 0,
        ];

        foreach ($this->mapData as $village) {
            if ($village['population'] == 0) {
                $this->statistics['abandoned_villages']++;
            } elseif ($currentPlayer && $village['player_id'] == $currentPlayer->id) {
                $this->statistics['my_villages']++;
            } elseif ($currentPlayer && $village['alliance_id'] == $currentPlayer->alliance_id) {
                $this->statistics['alliance_villages']++;
            } else {
                $this->statistics['enemy_villages']++;
            }
        }
    }

    public function selectVillage($villageId)
    {
        $village = collect($this->villages)->firstWhere('id', $villageId);
        if ($village) {
            $this->selectedVillage = $village;
            $this->dispatch('villageSelected', ['village' => $village]);
        }
    }

    public function centerOnVillage($villageId)
    {
        $village = collect($this->villages)->firstWhere('id', $villageId);
        if ($village) {
            $this->centerX = $village['x_coordinate'];
            $this->centerY = $village['y_coordinate'];
            $this->updateMapData();
        }
    }

    public function refreshMap()
    {
        // Clear cache and reload data
        $integrationService = app(LarautilxIntegrationService::class);
        $integrationService->clearWorldCache($this->selectedWorld);
        
        $this->loadInitialData();
        $this->addNotification('Map refreshed successfully', 'success');
    }

    public function toggleRealWorldView()
    {
        $this->realWorldMode = !$this->realWorldMode;
        $this->updateMapData();
    }

    public function getVillageDetails($villageId)
    {
        try {
            $village = Village::with([
                'player',
                'world',
                'buildings.buildingType',
                'troops.unitType',
                'resources'
            ])->findOrFail($villageId);

            $details = [
                'village' => $village,
                'coordinates' => [
                    'game' => ['x' => $village->x_coordinate, 'y' => $village->y_coordinate],
                    'real_world' => $village->getRealWorldCoordinates(),
                ],
                'distance' => $this->calculateDistanceToVillage($village),
                'statistics' => [
                    'buildings' => $village->buildings->count(),
                    'troops' => $village->troops->sum('quantity'),
                    'resources' => $village->resources->sum(function ($resource) {
                        return $resource->wood + $resource->clay + $resource->iron + $resource->crop;
                    }),
                ],
            ];

            return $this->successResponse($details, 'Village details retrieved successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve village details: ' . $e->getMessage());
        }
    }

    public function findNearbyVillages($villageId, $radius = 10)
    {
        try {
            $village = Village::findOrFail($villageId);
            
            $nearbyVillages = Village::with(['player'])
                ->where('world_id', $village->world_id)
                ->where('id', '!=', $villageId)
                ->whereRaw('
                    SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2)) <= ?
                ', [$village->x_coordinate, $village->y_coordinate, $radius])
                ->orderByRaw('
                    SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2))
                ', [$village->x_coordinate, $village->y_coordinate])
                ->limit(20)
                ->get();

            return $this->successResponse($nearbyVillages, 'Nearby villages retrieved successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to find nearby villages: ' . $e->getMessage());
        }
    }

    private function calculateDistanceToVillage($village)
    {
        $currentPlayer = Auth::user()?->player;
        if (!$currentPlayer) {
            return null;
        }

        $playerVillage = $currentPlayer->villages->first();
        if (!$playerVillage) {
            return null;
        }

        $geoService = app(GeographicService::class);
        return [
            'game_distance' => $geoService->calculateGameDistance(
                $playerVillage->x_coordinate,
                $playerVillage->y_coordinate,
                $village->x_coordinate,
                $village->y_coordinate
            ),
            'real_world_distance' => $playerVillage->realWorldDistanceTo($village),
        ];
    }

    public function handleMapUpdate($data)
    {
        // Handle map update from JavaScript
        $this->mapData = $data['villages'] ?? [];
        $this->calculateStatistics();
    }

    public function handleVillageSelection($data)
    {
        $this->selectedVillage = $data['village'] ?? null;
    }

    public function handleWorldChange($data)
    {
        $this->selectedWorld = $data['worldId'] ?? null;
        $this->loadInitialData();
    }

    public function addNotification($message, $type = 'info')
    {
        $this->dispatch('notification', [
            'message' => $message,
            'type' => $type
        ]);
    }

    public function render()
    {
        return view('livewire.game.advanced-map-manager');
    }
}
