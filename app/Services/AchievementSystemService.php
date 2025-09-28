<?php

namespace App\Services;

use App\Models\Game\Achievement;
use App\Models\Game\Player;
use App\Models\Game\PlayerAchievement;
use App\Models\Game\Village;
use App\Utilities\LoggingUtil;
use Illuminate\Support\Facades\DB;
use LaraUtilX\Utilities\CachingUtil;

class AchievementSystemService
{
    protected CachingUtil $cachingUtil;

    protected LoggingUtil $loggingUtil;

    public function __construct()
    {
        $this->cachingUtil = new CachingUtil(3600, ['achievement_system']);
        $this->loggingUtil = new LoggingUtil();
    }

    /**
     * Check and award achievements for a player
     */
    public function checkAndAwardAchievements(Player $player, array $triggerData = []): array
    {
        $awardedAchievements = [];

        // Get available achievements for the player
        $availableAchievements = $this->getAvailableAchievements($player);

        foreach ($availableAchievements as $achievement) {
            if ($this->checkAchievementRequirements($player, $achievement, $triggerData)) {
                try {
                    $playerAchievement = $this->awardAchievement($player, $achievement);
                    $awardedAchievements[] = $playerAchievement;

                    $this->loggingUtil->info('Achievement awarded', [
                        'player_id' => $player->id,
                        'achievement_id' => $achievement->id,
                        'achievement_name' => $achievement->name,
                    ]);
                } catch (\Exception $e) {
                    $this->loggingUtil->error('Failed to award achievement', [
                        'player_id' => $player->id,
                        'achievement_id' => $achievement->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (! empty($awardedAchievements)) {
            $this->clearPlayerAchievementCache($player);
        }

        return $awardedAchievements;
    }

    /**
     * Award a specific achievement to a player
     */
    public function awardAchievement(Player $player, Achievement $achievement): PlayerAchievement
    {
        // Check if player already has this achievement
        $existingAchievement = PlayerAchievement::where('player_id', $player->id)
            ->where('achievement_id', $achievement->id)
            ->first();

        if ($existingAchievement) {
            throw new \Exception('Player already has this achievement');
        }

        DB::beginTransaction();

        try {
            $playerAchievement = PlayerAchievement::create([
                'player_id' => $player->id,
                'achievement_id' => $achievement->id,
                'earned_at' => now(),
                'progress' => 100,
            ]);

            // Award achievement rewards
            $this->awardAchievementRewards($player, $achievement);

            DB::commit();

            return $playerAchievement;

        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Get available achievements for a player
     */
    public function getAvailableAchievements(Player $player): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "available_achievements_{$player->id}";

        return $this->cachingUtil->remember($cacheKey, 600, function () use ($player) {
            // Get achievements the player doesn't have yet
            $earnedAchievementIds = PlayerAchievement::where('player_id', $player->id)
                ->pluck('achievement_id')
                ->toArray();

            return Achievement::where('is_active', true)
                ->whereNotIn('id', $earnedAchievementIds)
                ->where(function ($query) use ($player): void {
                    $query->whereNull('required_level')
                        ->orWhere('required_level', '<=', $player->level);
                })
                ->orderBy('category')
                ->orderBy('required_level')
                ->get();
        });
    }

    /**
     * Get player's achievements
     */
    public function getPlayerAchievements(Player $player): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "player_achievements_{$player->id}";

        return $this->cachingUtil->remember($cacheKey, 300, function () use ($player) {
            return PlayerAchievement::where('player_id', $player->id)
                ->with(['achievement'])
                ->orderBy('earned_at', 'desc')
                ->get();
        });
    }

    /**
     * Get player's achievement progress
     */
    public function getPlayerAchievementProgress(Player $player): array
    {
        $cacheKey = "achievement_progress_{$player->id}";

        return $this->cachingUtil->remember($cacheKey, 600, function () use ($player) {
            $availableAchievements = $this->getAvailableAchievements($player);
            $progressData = [];

            foreach ($availableAchievements as $achievement) {
                $progress = $this->calculateAchievementProgress($player, $achievement);
                $progressData[] = [
                    'achievement' => $achievement,
                    'progress' => $progress,
                    'is_complete' => $progress >= 100,
                ];
            }

            return $progressData;
        });
    }

    /**
     * Get achievement statistics
     */
    public function getAchievementStatistics(): array
    {
        $cacheKey = 'achievement_statistics';

        return $this->cachingUtil->remember($cacheKey, 1800, function () {
            $stats = PlayerAchievement::selectRaw('
                COUNT(*) as total_player_achievements,
                COUNT(DISTINCT player_id) as players_with_achievements,
                COUNT(DISTINCT achievement_id) as unique_achievements_earned,
                AVG(DATEDIFF(earned_at, created_at)) as avg_time_to_earn
            ')->first();

            $achievementStats = Achievement::selectRaw('
                COUNT(*) as total_achievements,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_achievements,
                COUNT(DISTINCT category) as unique_categories,
                AVG(experience_reward) as avg_experience_reward,
                AVG(resource_reward_wood) as avg_wood_reward,
                AVG(resource_reward_clay) as avg_clay_reward,
                AVG(resource_reward_iron) as avg_iron_reward,
                AVG(resource_reward_crop) as avg_crop_reward
            ')->first();

            $categoryStats = Achievement::selectRaw('
                category,
                COUNT(*) as count,
                AVG(experience_reward) as avg_experience_reward
            ')
                ->where('is_active', true)
                ->groupBy('category')
                ->get();

            return [
                'player_achievements' => [
                    'total' => $stats->total_player_achievements ?? 0,
                    'players_with_achievements' => $stats->players_with_achievements ?? 0,
                    'unique_achievements_earned' => $stats->unique_achievements_earned ?? 0,
                    'avg_time_to_earn' => round($stats->avg_time_to_earn ?? 0, 2),
                ],
                'achievements' => [
                    'total' => $achievementStats->total_achievements ?? 0,
                    'active' => $achievementStats->active_achievements ?? 0,
                    'unique_categories' => $achievementStats->unique_categories ?? 0,
                    'avg_experience_reward' => round($achievementStats->avg_experience_reward ?? 0, 2),
                    'avg_wood_reward' => round($achievementStats->avg_wood_reward ?? 0, 2),
                    'avg_clay_reward' => round($achievementStats->avg_clay_reward ?? 0, 2),
                    'avg_iron_reward' => round($achievementStats->avg_iron_reward ?? 0, 2),
                    'avg_crop_reward' => round($achievementStats->avg_crop_reward ?? 0, 2),
                ],
                'categories' => $categoryStats->toArray(),
            ];
        });
    }

    /**
     * Get top achievers
     */
    public function getTopAchievers(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "top_achievers_{$limit}";

        return $this->cachingUtil->remember($cacheKey, 900, function () use ($limit) {
            return Player::selectRaw('
                players.*,
                COUNT(player_achievements.id) as achievement_count,
                SUM(achievements.experience_reward) as total_experience_earned
            ')
                ->join('player_achievements', 'players.id', '=', 'player_achievements.player_id')
                ->join('achievements', 'player_achievements.achievement_id', '=', 'achievements.id')
                ->groupBy('players.id')
                ->orderBy('achievement_count', 'desc')
                ->orderBy('total_experience_earned', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get rare achievements (least earned)
     */
    public function getRareAchievements(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "rare_achievements_{$limit}";

        return $this->cachingUtil->remember($cacheKey, 1800, function () use ($limit) {
            return Achievement::selectRaw('
                achievements.*,
                COUNT(player_achievements.id) as earned_count
            ')
                ->leftJoin('player_achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
                ->where('achievements.is_active', true)
                ->groupBy('achievements.id')
                ->orderBy('earned_count', 'asc')
                ->orderBy('achievements.required_level', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Check achievement requirements
     */
    protected function checkAchievementRequirements(Player $player, Achievement $achievement, array $triggerData = []): bool
    {
        $requirements = $achievement->requirements ?? [];

        foreach ($requirements as $requirement) {
            if (! $this->checkRequirement($player, $requirement, $triggerData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check individual requirement
     */
    protected function checkRequirement(Player $player, array $requirement, array $triggerData = []): bool
    {
        $type = $requirement['type'] ?? '';
        $value = $requirement['value'] ?? 0;

        switch ($type) {
            case 'level':
                return $player->level >= $value;

            case 'village_count':
                return $player->villages()->count() >= $value;

            case 'total_resources':
                $totalResources = $player->villages()->with('resources')->get()
                    ->sum(function ($village) {
                        return $village->resources->sum('amount');
                    });

                return $totalResources >= $value;

            case 'buildings_built':
                $buildingCount = $player->villages()->with('buildings')->get()
                    ->sum(function ($village) {
                        return $village->buildings->sum('level');
                    });

                return $buildingCount >= $value;

            case 'troops_trained':
                $troopCount = $player->villages()->with('troops')->get()
                    ->sum(function ($village) {
                        return $village->troops->sum('amount');
                    });

                return $troopCount >= $value;

            case 'attacks_launched':
                return ($triggerData['attacks_launched'] ?? 0) >= $value;

            case 'battles_won':
                return ($triggerData['battles_won'] ?? 0) >= $value;

            case 'alliance_joined':
                return $player->alliance_id !== null;

            case 'wonder_contributed':
                return ($triggerData['wonder_contributions'] ?? 0) >= $value;

            case 'quests_completed':
                return $player->playerQuests()->where('status', 'completed')->count() >= $value;

            case 'market_trades':
                return ($triggerData['market_trades'] ?? 0) >= $value;

            default:
                return false;
        }
    }

    /**
     * Calculate achievement progress
     */
    protected function calculateAchievementProgress(Player $player, Achievement $achievement): float
    {
        $requirements = $achievement->requirements ?? [];
        $totalProgress = 0;

        foreach ($requirements as $requirement) {
            $progress = $this->getRequirementProgress($player, $requirement);
            $totalProgress += $progress;
        }

        return count($requirements) > 0 ? ($totalProgress / count($requirements)) : 0;
    }

    /**
     * Get requirement progress percentage
     */
    protected function getRequirementProgress(Player $player, array $requirement): float
    {
        $type = $requirement['type'] ?? '';
        $value = $requirement['value'] ?? 0;

        if ($value <= 0) {
            return 100;
        }

        $currentValue = match ($type) {
            'level' => $player->level,
            'village_count' => $player->villages()->count(),
            'total_resources' => $player->villages()->with('resources')->get()
                ->sum(function ($village) {
                    return $village->resources->sum('amount');
                }),
            'buildings_built' => $player->villages()->with('buildings')->get()
                ->sum(function ($village) {
                    return $village->buildings->sum('level');
                }),
            'troops_trained' => $player->villages()->with('troops')->get()
                ->sum(function ($village) {
                    return $village->troops->sum('amount');
                }),
            'quests_completed' => $player->playerQuests()->where('status', 'completed')->count(),
            default => 0
        };

        return min(100, ($currentValue / $value) * 100);
    }

    /**
     * Award achievement rewards
     */
    protected function awardAchievementRewards(Player $player, Achievement $achievement): void
    {
        // Award experience
        if ($achievement->experience_reward > 0) {
            $player->increment('experience', $achievement->experience_reward);
        }

        // Award resources to player's main village
        $mainVillage = $player->villages()->orderBy('created_at')->first();
        if ($mainVillage) {
            if ($achievement->resource_reward_wood > 0) {
                $this->addResourceToVillage($mainVillage, 'wood', $achievement->resource_reward_wood);
            }
            if ($achievement->resource_reward_clay > 0) {
                $this->addResourceToVillage($mainVillage, 'clay', $achievement->resource_reward_clay);
            }
            if ($achievement->resource_reward_iron > 0) {
                $this->addResourceToVillage($mainVillage, 'iron', $achievement->resource_reward_iron);
            }
            if ($achievement->resource_reward_crop > 0) {
                $this->addResourceToVillage($mainVillage, 'crop', $achievement->resource_reward_crop);
            }
        }

        $this->loggingUtil->info('Achievement rewards awarded', [
            'player_id' => $player->id,
            'achievement_id' => $achievement->id,
            'experience' => $achievement->experience_reward,
            'resources' => [
                'wood' => $achievement->resource_reward_wood,
                'clay' => $achievement->resource_reward_clay,
                'iron' => $achievement->resource_reward_iron,
                'crop' => $achievement->resource_reward_crop,
            ],
        ]);
    }

    /**
     * Add resource to village
     */
    protected function addResourceToVillage(Village $village, string $resourceType, int $amount): void
    {
        $resource = $village->resources()->where('type', $resourceType)->first();
        if ($resource) {
            $resource->increment('amount', $amount);
        }
    }

    /**
     * Clear player achievement cache
     */
    protected function clearPlayerAchievementCache(Player $player): void
    {
        $patterns = [
            "available_achievements_{$player->id}",
            "player_achievements_{$player->id}",
            "achievement_progress_{$player->id}",
        ];

        foreach ($patterns as $pattern) {
            $this->cachingUtil->forgetPattern($pattern);
        }
    }
}
