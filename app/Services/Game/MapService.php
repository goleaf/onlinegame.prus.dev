<?php

namespace App\Services\Game;

use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\Game\Player;
use App\Services\GeographicService;
use Illuminate\Support\Facades\DB;
use SmartCache\Facades\SmartCache;

class MapService
{
    public function __construct(
        private GeographicService $geographicService
    ) {}

    /**
     * Get map data for a world
     */
    public function getMapData(World $world, int $centerX = 0, int $centerY = 0, int $radius = 10): array
    {
        $cacheKey = "map_data:{$world->id}:{$centerX}:{$centerY}:{$radius}";

        return SmartCache::remember($cacheKey, 300, function () use ($world, $centerX, $centerY, $radius) {
            $villages = Village::where('world_id', $world->id)
                ->whereBetween('x_coordinate', [$centerX - $radius, $centerX + $radius])
                ->whereBetween('y_coordinate', [$centerY - $radius, $centerY + $radius])
                ->with(['player', 'player.alliance'])
                ->get();

            $mapData = [];
            $oasisData = [];

            foreach ($villages as $village) {
                $mapData[] = [
                    'id' => $village->id,
                    'name' => $village->name,
                    'x' => $village->x_coordinate,
                    'y' => $village->y_coordinate,
                    'player_name' => $village->player->name,
                    'alliance_tag' => $village->player->alliance?->tag,
                    'population' => $village->population,
                    'is_capital' => $village->is_capital,
                    'distance' => $this->calculateDistance($centerX, $centerY, $village->x_coordinate, $village->y_coordinate),
                ];
            }

            // Add oasis data
            $oasisData = $this->getOasisData($world, $centerX, $centerY, $radius);

            return [
                'villages' => $mapData,
                'oases' => $oasisData,
                'center' => ['x' => $centerX, 'y' => $centerY],
                'radius' => $radius,
                'total_villages' => count($mapData),
                'total_oases' => count($oasisData),
            ];
        });
    }

    /**
     * Get village details for map
     */
    public function getVillageDetails(Village $village): array
    {
        $cacheKey = "village_details:{$village->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($village) {
            $village->load(['player', 'player.alliance', 'buildings', 'troops.unitType']);

            return [
                'id' => $village->id,
                'name' => $village->name,
                'coordinates' => [
                    'x' => $village->x_coordinate,
                    'y' => $village->y_coordinate,
                ],
                'player' => [
                    'id' => $village->player->id,
                    'name' => $village->player->name,
                    'tribe' => $village->player->tribe,
                    'points' => $village->player->points,
                ],
                'alliance' => $village->player->alliance ? [
                    'id' => $village->player->alliance->id,
                    'name' => $village->player->alliance->name,
                    'tag' => $village->player->alliance->tag,
                ] : null,
                'population' => $village->population,
                'is_capital' => $village->is_capital,
                'buildings' => $village->buildings->map(function ($building) {
                    return [
                        'type' => $building->building_type,
                        'level' => $building->level,
                    ];
                }),
                'troops' => $village->troops->map(function ($troop) {
                    return [
                        'unit_type' => $troop->unitType->name,
                        'quantity' => $troop->quantity,
                    ];
                }),
                'resources' => $this->getVillageResources($village),
            ];
        });
    }

    /**
     * Search villages by name or player
     */
    public function searchVillages(World $world, string $searchTerm, int $limit = 50): array
    {
        $cacheKey = "village_search:{$world->id}:" . md5($searchTerm);

        return SmartCache::remember($cacheKey, 300, function () use ($world, $searchTerm, $limit) {
            $villages = Village::where('world_id', $world->id)
                ->where(function ($query) use ($searchTerm) {
                    $query->where('name', 'like', "%{$searchTerm}%")
                        ->orWhereHas('player', function ($q) use ($searchTerm) {
                            $q->where('name', 'like', "%{$searchTerm}%");
                        });
                })
                ->with(['player', 'player.alliance'])
                ->limit($limit)
                ->get();

            return $villages->map(function ($village) {
                return [
                    'id' => $village->id,
                    'name' => $village->name,
                    'coordinates' => [
                        'x' => $village->x_coordinate,
                        'y' => $village->y_coordinate,
                    ],
                    'player_name' => $village->player->name,
                    'alliance_tag' => $village->player->alliance?->tag,
                    'population' => $village->population,
                ];
            })->toArray();
        });
    }

    /**
     * Get nearby villages
     */
    public function getNearbyVillages(Village $village, int $radius = 5): array
    {
        $cacheKey = "nearby_villages:{$village->id}:{$radius}";

        return SmartCache::remember($cacheKey, 300, function () use ($village, $radius) {
            $nearbyVillages = Village::where('world_id', $village->world_id)
                ->where('id', '!=', $village->id)
                ->whereBetween('x_coordinate', [$village->x_coordinate - $radius, $village->x_coordinate + $radius])
                ->whereBetween('y_coordinate', [$village->y_coordinate - $radius, $village->y_coordinate + $radius])
                ->with(['player', 'player.alliance'])
                ->get();

            return $nearbyVillages->map(function ($nearbyVillage) use ($village) {
                $distance = $this->calculateDistance(
                    $village->x_coordinate,
                    $village->y_coordinate,
                    $nearbyVillage->x_coordinate,
                    $nearbyVillage->y_coordinate
                );

                return [
                    'id' => $nearbyVillage->id,
                    'name' => $nearbyVillage->name,
                    'coordinates' => [
                        'x' => $nearbyVillage->x_coordinate,
                        'y' => $nearbyVillage->y_coordinate,
                    ],
                    'player_name' => $nearbyVillage->player->name,
                    'alliance_tag' => $nearbyVillage->player->alliance?->tag,
                    'population' => $nearbyVillage->population,
                    'distance' => $distance,
                    'travel_time' => $this->calculateTravelTime($distance),
                ];
            })->sortBy('distance')->values()->toArray();
        });
    }

    /**
     * Get world statistics
     */
    public function getWorldStatistics(World $world): array
    {
        $cacheKey = "world_stats:{$world->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($world) {
            $totalVillages = Village::where('world_id', $world->id)->count();
            $totalPlayers = Player::where('world_id', $world->id)->count();
            $totalAlliances = $world->alliances()->count();

            $villageDistribution = Village::where('world_id', $world->id)
                ->selectRaw('
                    CASE 
                        WHEN x_coordinate BETWEEN -100 AND 100 AND y_coordinate BETWEEN -100 AND 100 THEN "center"
                        WHEN x_coordinate BETWEEN -200 AND 200 AND y_coordinate BETWEEN -200 AND 200 THEN "inner"
                        WHEN x_coordinate BETWEEN -400 AND 400 AND y_coordinate BETWEEN -400 AND 400 THEN "outer"
                        ELSE "edge"
                    END as region,
                    COUNT(*) as count
                ')
                ->groupBy('region')
                ->pluck('count', 'region')
                ->toArray();

            return [
                'total_villages' => $totalVillages,
                'total_players' => $totalPlayers,
                'total_alliances' => $totalAlliances,
                'village_distribution' => $villageDistribution,
                'world_size' => $this->getWorldSize($world),
                'active_players' => Player::where('world_id', $world->id)
                    ->where('last_active_at', '>=', now()->subDays(7))
                    ->count(),
            ];
        });
    }

    /**
     * Calculate distance between two coordinates
     */
    public function calculateDistance(int $x1, int $y1, int $x2, int $y2): float
    {
        return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
    }

    /**
     * Calculate travel time between two points
     */
    public function calculateTravelTime(float $distance, float $speed = 1.0): int
    {
        // Base travel time calculation
        $baseTime = $distance * 60; // 1 minute per unit distance
        return (int) ($baseTime / $speed);
    }

    /**
     * Get oasis data for map
     */
    private function getOasisData(World $world, int $centerX, int $centerY, int $radius): array
    {
        // Generate random oasis data for demonstration
        $oases = [];
        $oasisCount = rand(5, 15);

        for ($i = 0; $i < $oasisCount; $i++) {
            $x = $centerX + rand(-$radius, $radius);
            $y = $centerY + rand(-$radius, $radius);

            // Check if position is not occupied by a village
            $existingVillage = Village::where('world_id', $world->id)
                ->where('x_coordinate', $x)
                ->where('y_coordinate', $y)
                ->exists();

            if (!$existingVillage) {
                $oases[] = [
                    'x' => $x,
                    'y' => $y,
                    'type' => $this->getRandomOasisType(),
                    'resources' => $this->getRandomOasisResources(),
                ];
            }
        }

        return $oases;
    }

    /**
     * Get random oasis type
     */
    private function getRandomOasisType(): string
    {
        $types = ['wood', 'clay', 'iron', 'crop', 'mixed'];
        return $types[array_rand($types)];
    }

    /**
     * Get random oasis resources
     */
    private function getRandomOasisResources(): array
    {
        return [
            'wood' => rand(100, 500),
            'clay' => rand(100, 500),
            'iron' => rand(100, 500),
            'crop' => rand(100, 500),
        ];
    }

    /**
     * Get village resources
     */
    private function getVillageResources(Village $village): array
    {
        // This would typically come from a ResourceService
        return [
            'wood' => rand(1000, 10000),
            'clay' => rand(1000, 10000),
            'iron' => rand(1000, 10000),
            'crop' => rand(1000, 10000),
        ];
    }

    /**
     * Get world size
     */
    private function getWorldSize(World $world): array
    {
        $bounds = Village::where('world_id', $world->id)
            ->selectRaw('
                MIN(x_coordinate) as min_x,
                MAX(x_coordinate) as max_x,
                MIN(y_coordinate) as min_y,
                MAX(y_coordinate) as max_y
            ')
            ->first();

        return [
            'min_x' => $bounds->min_x ?? 0,
            'max_x' => $bounds->max_x ?? 0,
            'min_y' => $bounds->min_y ?? 0,
            'max_y' => $bounds->max_y ?? 0,
            'width' => ($bounds->max_x ?? 0) - ($bounds->min_x ?? 0),
            'height' => ($bounds->max_y ?? 0) - ($bounds->min_y ?? 0),
        ];
    }

    /**
     * Clear map cache
     */
    public function clearMapCache(World $world): void
    {
        SmartCache::forget("world_stats:{$world->id}");
        // Clear other map-related caches as needed
    }
}
