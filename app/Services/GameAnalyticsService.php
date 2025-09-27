<?php

namespace App\Services;

use App\Models\Game\Battle;
use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\Alliance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;

class GameAnalyticsService
{
    protected CachingUtil $cachingUtil;
    protected LoggingUtil $loggingUtil;

    public function __construct()
    {
        $this->cachingUtil = new CachingUtil();
        $this->loggingUtil = new LoggingUtil();
    }

    /**
     * Get comprehensive game analytics
     */
    public function getGameAnalytics(array $filters = []): array
    {
        $cacheKey = 'game_analytics_' . md5(serialize($filters));
        
        return $this->cachingUtil->remember($cacheKey, 300, function () use ($filters) {
            return [
                'player_statistics' => $this->getPlayerStatistics($filters),
                'battle_analytics' => $this->getBattleAnalytics($filters),
                'alliance_analytics' => $this->getAllianceAnalytics($filters),
                'resource_analytics' => $this->getResourceAnalytics($filters),
                'movement_analytics' => $this->getMovementAnalytics($filters),
                'village_analytics' => $this->getVillageAnalytics($filters),
                'performance_metrics' => $this->getPerformanceMetrics(),
                'generated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get player statistics
     */
    protected function getPlayerStatistics(array $filters = []): array
    {
        $query = Player::query();

        if (isset($filters['alliance_id'])) {
            $query->where('alliance_id', $filters['alliance_id']);
        }

        if (isset($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        return [
            'total_players' => $query->count(),
            'active_players' => $query->where('last_activity', '>=', now()->subDays(7))->count(),
            'alliance_members' => $query->whereNotNull('alliance_id')->count(),
            'independent_players' => $query->whereNull('alliance_id')->count(),
            'average_villages_per_player' => $query->withCount('villages')->get()->avg('villages_count'),
            'top_players' => $this->getTopPlayers($filters),
        ];
    }

    /**
     * Get battle analytics
     */
    protected function getBattleAnalytics(array $filters = []): array
    {
        $query = Battle::query();

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $battles = $query->get();

        return [
            'total_battles' => $battles->count(),
            'attacker_victories' => $battles->where('result', 'attacker_victory')->count(),
            'defender_victories' => $battles->where('result', 'defender_victory')->count(),
            'draws' => $battles->where('result', 'draw')->count(),
            'average_battle_duration' => $battles->avg('duration_seconds'),
            'total_casualties' => $battles->sum('total_casualties'),
            'total_loot' => $battles->sum('total_loot'),
            'battles_by_type' => $battles->groupBy('battle_type')->map->count(),
        ];
    }

    /**
     * Get alliance analytics
     */
    protected function getAllianceAnalytics(array $filters = []): array
    {
        $query = Alliance::query();

        if (isset($filters['min_members'])) {
            $query->having('member_count', '>=', $filters['min_members']);
        }

        $alliances = $query->withCount('members')->get();

        return [
            'total_alliances' => $alliances->count(),
            'active_alliances' => $alliances->where('member_count', '>', 0)->count(),
            'average_members_per_alliance' => $alliances->avg('member_count'),
            'largest_alliance' => $alliances->max('member_count'),
            'top_alliances' => $this->getTopAlliances($filters),
            'alliance_distribution' => $alliances->groupBy(function ($alliance) {
                return match (true) {
                    $alliance->member_count >= 50 => '50+',
                    $alliance->member_count >= 25 => '25-49',
                    $alliance->member_count >= 10 => '10-24',
                    default => '1-9'
                };
            })->map->count(),
        ];
    }

    /**
     * Get resource analytics
     */
    protected function getResourceAnalytics(array $filters = []): array
    {
        $villages = Village::query();

        if (isset($filters['alliance_id'])) {
            $villages->whereHas('player', function ($query) use ($filters) {
                $query->where('alliance_id', $filters['alliance_id']);
            });
        }

        $villages = $villages->with('resources')->get();

        $totalResources = $villages->flatMap->resources->groupBy('type')->map->sum('amount');

        return [
            'total_wood' => $totalResources->get('wood', 0),
            'total_clay' => $totalResources->get('clay', 0),
            'total_iron' => $totalResources->get('iron', 0),
            'total_crop' => $totalResources->get('crop', 0),
            'average_resources_per_village' => $villages->map(function ($village) {
                return $village->resources->sum('amount');
            })->avg(),
            'resource_distribution' => $totalResources,
        ];
    }

    /**
     * Get movement analytics
     */
    protected function getMovementAnalytics(array $filters = []): array
    {
        $query = Movement::query();

        if (isset($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        $movements = $query->get();

        return [
            'total_movements' => $movements->count(),
            'active_movements' => $movements->where('arrives_at', '>', now())->count(),
            'completed_movements' => $movements->where('arrives_at', '<=', now())->count(),
            'movements_by_type' => $movements->groupBy('movement_type')->map->count(),
            'average_movement_duration' => $movements->avg('duration_seconds'),
            'movements_by_distance' => $movements->groupBy(function ($movement) {
                $distance = $movement->distance ?? 0;
                return match (true) {
                    $distance >= 100 => '100+',
                    $distance >= 50 => '50-99',
                    $distance >= 20 => '20-49',
                    default => '0-19'
                };
            })->map->count(),
        ];
    }

    /**
     * Get village analytics
     */
    protected function getVillageAnalytics(array $filters = []): array
    {
        $query = Village::query();

        if (isset($filters['alliance_id'])) {
            $query->whereHas('player', function ($query) use ($filters) {
                $query->where('alliance_id', $filters['alliance_id']);
            });
        }

        $villages = $query->get();

        return [
            'total_villages' => $villages->count(),
            'villages_by_population' => $villages->groupBy(function ($village) {
                $population = $village->population ?? 0;
                return match (true) {
                    $population >= 1000 => '1000+',
                    $population >= 500 => '500-999',
                    $population >= 100 => '100-499',
                    default => '0-99'
                };
            })->map->count(),
            'average_population' => $villages->avg('population'),
            'villages_with_defense' => $villages->where('defense_strength', '>', 0)->count(),
            'villages_by_continent' => $villages->groupBy('continent')->map->count(),
        ];
    }

    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics(): array
    {
        return [
            'database_queries' => [
                'total_queries' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0,
                'slow_queries' => 0, // Would need query logging enabled
            ],
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
            ],
            'cache_performance' => [
                'hits' => Cache::getStore()->getStats()['hits'] ?? 0,
                'misses' => Cache::getStore()->getStats()['misses'] ?? 0,
            ],
            'response_time' => microtime(true) - LARAVEL_START,
        ];
    }

    /**
     * Get top players
     */
    protected function getTopPlayers(array $filters = [], int $limit = 10): array
    {
        $query = Player::query();

        if (isset($filters['alliance_id'])) {
            $query->where('alliance_id', $filters['alliance_id']);
        }

        return $query->withCount('villages')
            ->orderBy('villages_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->name,
                    'villages_count' => $player->villages_count,
                    'alliance' => $player->alliance?->name,
                ];
            })
            ->toArray();
    }

    /**
     * Get top alliances
     */
    protected function getTopAlliances(array $filters = [], int $limit = 10): array
    {
        return Alliance::withCount('members')
            ->orderBy('members_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($alliance) {
                return [
                    'id' => $alliance->id,
                    'name' => $alliance->name,
                    'member_count' => $alliance->members_count,
                    'tag' => $alliance->tag,
                ];
            })
            ->toArray();
    }

    /**
     * Generate analytics report
     */
    public function generateReport(array $filters = []): string
    {
        $analytics = $this->getGameAnalytics($filters);
        
        $report = "# Game Analytics Report\n\n";
        $report .= "Generated: {$analytics['generated_at']}\n\n";
        
        $report .= "## Player Statistics\n";
        $report .= "- Total Players: {$analytics['player_statistics']['total_players']}\n";
        $report .= "- Active Players: {$analytics['player_statistics']['active_players']}\n";
        $report .= "- Alliance Members: {$analytics['player_statistics']['alliance_members']}\n\n";
        
        $report .= "## Battle Analytics\n";
        $report .= "- Total Battles: {$analytics['battle_analytics']['total_battles']}\n";
        $report .= "- Attacker Victories: {$analytics['battle_analytics']['attacker_victories']}\n";
        $report .= "- Defender Victories: {$analytics['battle_analytics']['defender_victories']}\n\n";
        
        $report .= "## Alliance Analytics\n";
        $report .= "- Total Alliances: {$analytics['alliance_analytics']['total_alliances']}\n";
        $report .= "- Average Members: " . number_format($analytics['alliance_analytics']['average_members_per_alliance'], 2) . "\n\n";
        
        $this->loggingUtil->info('Generated analytics report', [
            'filters' => $filters,
            'report_size' => strlen($report)
        ]);
        
        return $report;
    }
}
