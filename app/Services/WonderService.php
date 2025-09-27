<?php

namespace App\Services;

use App\Models\Game\Wonder;
use App\Models\Game\WonderConstruction;
use App\Models\Game\Alliance;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\PerformanceMonitoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;
use SmartCache\Facades\SmartCache;

class WonderService
{
    protected CachingUtil $cachingUtil;
    protected LoggingUtil $loggingUtil;

    public function __construct()
    {
        $this->cachingUtil = new CachingUtil(1800, ['wonder_operations']);
        $this->loggingUtil = new LoggingUtil();
    }

    /**
     * Start wonder construction
     */
    public function startConstruction(
        Wonder $wonder,
        Alliance $alliance,
        int $targetLevel,
        array $resources
    ): WonderConstruction {
        if (!$wonder->canUpgrade()) {
            throw new \Exception('Wonder cannot be upgraded at this time');
        }

        if ($targetLevel <= $wonder->current_level) {
            throw new \Exception('Target level must be higher than current level');
        }

        $constructionCost = $wonder->getConstructionCost($targetLevel);
        $constructionTime = $wonder->getConstructionTime($targetLevel);

        DB::beginTransaction();

        try {
            // Create construction record
            $construction = WonderConstruction::create([
                'wonder_id' => $wonder->id,
                'alliance_id' => $alliance->id,
                'level' => $targetLevel,
                'construction_started_at' => now(),
                'construction_completed_at' => null,
                'resources_contributed' => $resources,
                'construction_time' => $constructionTime,
            ]);

            // Update wonder current level
            $wonder->update(['current_level' => $targetLevel]);

            DB::commit();

            $this->loggingUtil->info('Wonder construction started', [
                'wonder_id' => $wonder->id,
                'alliance_id' => $alliance->id,
                'target_level' => $targetLevel,
                'construction_time' => $constructionTime,
            ]);

            // Clear cache
            $this->clearWonderCache($wonder);

            return $construction;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->loggingUtil->error('Failed to start wonder construction', [
                'wonder_id' => $wonder->id,
                'alliance_id' => $alliance->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Complete wonder construction
     */
    public function completeConstruction(WonderConstruction $construction): Wonder
    {
        DB::beginTransaction();

        try {
            // Update construction record
            $construction->update([
                'construction_completed_at' => now(),
            ]);

            $wonder = $construction->wonder;

            // Apply wonder bonuses
            $this->applyWonderBonuses($wonder, $construction);

            DB::commit();

            $this->loggingUtil->info('Wonder construction completed', [
                'wonder_id' => $wonder->id,
                'alliance_id' => $construction->alliance_id,
                'level' => $construction->level,
            ]);

            // Clear cache
            $this->clearWonderCache($wonder);

            return $wonder;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->loggingUtil->error('Failed to complete wonder construction', [
                'construction_id' => $construction->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get wonder statistics
     */
    public function getWonderStats(Wonder $wonder): array
    {
        $cacheKey = "wonder_stats_{$wonder->id}";

        return $this->cachingUtil->remember($cacheKey, 300, function () use ($wonder) {
            $constructions = $wonder->constructions()->with('alliance')->get();

            return [
                'wonder' => [
                    'id' => $wonder->id,
                    'name' => $wonder->name,
                    'location' => $wonder->getLocationString(),
                    'current_level' => $wonder->current_level,
                    'max_level' => $wonder->max_level,
                    'status' => $wonder->status,
                    'progress' => $wonder->getConstructionProgress(),
                ],
                'constructions' => [
                    'total' => $constructions->count(),
                    'completed' => $constructions->where('construction_completed_at', '!=', null)->count(),
                    'in_progress' => $constructions->where('construction_completed_at', null)->count(),
                    'by_alliance' => $constructions->groupBy('alliance_id')->map->count(),
                ],
                'resources' => [
                    'total_contributed' => $wonder->getTotalResourcesContributed(),
                    'next_level_cost' => $wonder->canUpgrade() 
                        ? $wonder->getConstructionCost($wonder->current_level + 1)
                        : null,
                ],
                'bonuses' => $wonder->getBonusEffects($wonder->current_level),
            ];
        });
    }

    /**
     * Get all wonders with statistics
     */
    public function getAllWondersStats(): array
    {
        $cacheKey = 'all_wonders_stats';

        return $this->cachingUtil->remember($cacheKey, 600, function () {
            $wonders = Wonder::active()->with(['constructions', 'constructions.alliance'])->get();

            return $wonders->map(function ($wonder) {
                return $this->getWonderStats($wonder);
            })->toArray();
        });
    }

    /**
     * Get wonder rankings by level
     */
    public function getWonderRankings(): array
    {
        $cacheKey = 'wonder_rankings';

        return $this->cachingUtil->remember($cacheKey, 900, function () {
            return Wonder::active()
                ->with(['constructions.alliance'])
                ->orderBy('current_level', 'desc')
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($wonder, $index) {
                    $topContributor = $wonder->constructions()
                        ->whereNotNull('construction_completed_at')
                        ->with('alliance')
                        ->get()
                        ->groupBy('alliance_id')
                        ->map(function ($constructions) {
                            return [
                                'alliance' => $constructions->first()->alliance,
                                'contributions' => $constructions->count(),
                            ];
                        })
                        ->sortByDesc('contributions')
                        ->first();

                    return [
                        'rank' => $index + 1,
                        'wonder' => $wonder->name,
                        'location' => $wonder->getLocationString(),
                        'level' => $wonder->current_level,
                        'status' => $wonder->status,
                        'top_contributor' => $topContributor,
                        'total_contributions' => $wonder->constructions()->count(),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Process wonder constructions (called by game tick)
     */
    public function processConstructions(): array
    {
        $completed = [];
        $constructions = WonderConstruction::inProgress()
            ->where('construction_started_at', '<=', now()->subSeconds(1))
            ->get();

        foreach ($constructions as $construction) {
            if ($construction->getRemainingTime() <= 0) {
                $wonder = $this->completeConstruction($construction);
                $completed[] = [
                    'wonder' => $wonder,
                    'construction' => $construction,
                ];
            }
        }

        if (!empty($completed)) {
            $this->loggingUtil->info('Processed wonder constructions', [
                'completed_count' => count($completed)
            ]);
        }

        return $completed;
    }

    /**
     * Get alliance contribution to wonder
     */
    public function getAllianceContribution(Alliance $alliance, Wonder $wonder): array
    {
        $cacheKey = "alliance_wonder_contribution_{$alliance->id}_{$wonder->id}";

        return $this->cachingUtil->remember($cacheKey, 300, function () use ($alliance, $wonder) {
            $constructions = $wonder->constructions()
                ->where('alliance_id', $alliance->id)
                ->get();

            $totalResources = ['wood' => 0, 'clay' => 0, 'iron' => 0, 'crop' => 0];

            foreach ($constructions as $construction) {
                $resources = $construction->resources_contributed ?? [];
                foreach ($totalResources as $resource => $amount) {
                    $totalResources[$resource] += $resources[$resource] ?? 0;
                }
            }

            return [
                'alliance' => [
                    'id' => $alliance->id,
                    'name' => $alliance->name,
                    'tag' => $alliance->tag,
                ],
                'wonder' => [
                    'id' => $wonder->id,
                    'name' => $wonder->name,
                    'level' => $wonder->current_level,
                ],
                'contributions' => [
                    'total_constructions' => $constructions->count(),
                    'completed_constructions' => $constructions->where('construction_completed_at', '!=', null)->count(),
                    'total_resources' => $totalResources,
                ],
            ];
        });
    }

    /**
     * Apply wonder bonuses
     */
    protected function applyWonderBonuses(Wonder $wonder, WonderConstruction $construction): void
    {
        $bonuses = $wonder->getBonusEffects($construction->level);

        // Apply bonuses to alliance members
        $alliance = $construction->alliance;
        $members = $alliance->members;

        foreach ($members as $member) {
            $this->applyBonusesToPlayer($member, $bonuses);
        }

        $this->loggingUtil->info('Applied wonder bonuses', [
            'wonder_id' => $wonder->id,
            'alliance_id' => $alliance->id,
            'level' => $construction->level,
            'bonuses' => $bonuses,
            'members_affected' => $members->count(),
        ]);
    }

    /**
     * Apply bonuses to player
     */
    protected function applyBonusesToPlayer(Player $player, array $bonuses): void
    {
        // This would typically update player bonuses in the database
        // For now, we'll just log the application
        
        $this->loggingUtil->debug('Applied bonuses to player', [
            'player_id' => $player->id,
            'bonuses' => $bonuses,
        ]);
    }

    /**
     * Clear wonder cache
     */
    protected function clearWonderCache(Wonder $wonder): void
    {
        $patterns = [
            "wonder_stats_{$wonder->id}",
            'all_wonders_stats',
            'wonder_rankings',
            "alliance_wonder_contribution_*_{$wonder->id}",
        ];

        foreach ($patterns as $pattern) {
            $this->cachingUtil->forgetPattern($pattern);
        }
    }

    /**
     * Get wonder construction requirements
     */
    public function getConstructionRequirements(Wonder $wonder, int $targetLevel): array
    {
        if ($targetLevel <= $wonder->current_level) {
            throw new \Exception('Target level must be higher than current level');
        }

        $cacheKey = "wonder_requirements_{$wonder->id}_{$targetLevel}";
        
        return SmartCache::remember($cacheKey, now()->addMinutes(20), function () use ($wonder, $targetLevel) {
            return [
                'wonder' => [
                    'id' => $wonder->id,
                    'name' => $wonder->name,
                    'current_level' => $wonder->current_level,
                    'target_level' => $targetLevel,
                ],
                'cost' => $wonder->getConstructionCost($targetLevel),
                'time' => $wonder->getConstructionTime($targetLevel),
                'bonuses' => $wonder->getBonusEffects($targetLevel),
                'can_construct' => $wonder->canUpgrade(),
            ];
        });
    }
}
