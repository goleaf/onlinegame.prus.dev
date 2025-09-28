<?php

namespace App\Services;

use App\Models\Game\Player;
use App\Models\Game\PlayerQuest;
use App\Models\Game\Quest;
use App\Models\Game\Village;
use App\Utilities\LoggingUtil;
use Illuminate\Support\Facades\DB;
use LaraUtilX\Utilities\CachingUtil;

class QuestSystemService
{
    protected CachingUtil $cachingUtil;

    protected LoggingUtil $loggingUtil;

    public function __construct()
    {
        $this->cachingUtil = new CachingUtil(2400, ['quest_system']);
        $this->loggingUtil = new LoggingUtil();
    }

    /**
     * Get available quests for a player
     */
    public function getAvailableQuests(Player $player): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "available_quests_{$player->id}";

        return $this->cachingUtil->remember($cacheKey, 600, function () use ($player) {
            // Get quests that the player hasn't completed yet
            $completedQuestIds = PlayerQuest::where('player_id', $player->id)
                ->where('status', 'completed')
                ->pluck('quest_id')
                ->toArray();

            // Get quests that meet the requirements
            $quests = Quest::where('is_active', true)
                ->whereNotIn('id', $completedQuestIds)
                ->where(function ($query) use ($player): void {
                    $query->whereNull('required_level')
                        ->orWhere('required_level', '<=', $player->level);
                })
                ->where(function ($query) use ($player): void {
                    $query->whereNull('required_villages')
                        ->orWhere('required_villages', '<=', $player->villages()->count());
                })
                ->orderBy('priority', 'desc')
                ->orderBy('required_level', 'asc')
                ->get();

            $this->loggingUtil->debug('Retrieved available quests', [
                'player_id' => $player->id,
                'quest_count' => $quests->count(),
            ]);

            return $quests;
        });
    }

    /**
     * Start a quest for a player
     */
    public function startQuest(Player $player, Quest $quest): PlayerQuest
    {
        // Check if player can start this quest
        if (! $this->canPlayerStartQuest($player, $quest)) {
            throw new \Exception('Player cannot start this quest');
        }

        // Check if player already has this quest active
        $existingQuest = PlayerQuest::where('player_id', $player->id)
            ->where('quest_id', $quest->id)
            ->where('status', 'active')
            ->first();

        if ($existingQuest) {
            throw new \Exception('Player already has this quest active');
        }

        DB::beginTransaction();

        try {
            $playerQuest = PlayerQuest::create([
                'player_id' => $player->id,
                'quest_id' => $quest->id,
                'status' => 'active',
                'progress' => 0,
                'started_at' => now(),
                'completed_at' => null,
            ]);

            // Initialize quest progress
            $this->initializeQuestProgress($playerQuest);

            DB::commit();

            $this->loggingUtil->info('Quest started', [
                'player_id' => $player->id,
                'quest_id' => $quest->id,
                'player_quest_id' => $playerQuest->id,
            ]);

            // Clear cache
            $this->clearPlayerQuestCache($player);

            return $playerQuest;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->loggingUtil->error('Failed to start quest', [
                'player_id' => $player->id,
                'quest_id' => $quest->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update quest progress
     */
    public function updateQuestProgress(PlayerQuest $playerQuest, array $progressData): void
    {
        $quest = $playerQuest->quest;
        $requirements = $quest->requirements ?? [];

        DB::beginTransaction();

        try {
            $totalProgress = 0;
            $completedRequirements = 0;

            foreach ($requirements as $requirement) {
                $requirementProgress = $this->calculateRequirementProgress($playerQuest, $requirement, $progressData);
                $totalProgress += $requirementProgress;

                if ($requirementProgress >= 100) {
                    $completedRequirements++;
                }
            }

            $overallProgress = count($requirements) > 0 ? ($totalProgress / count($requirements)) : 100;
            $isCompleted = $completedRequirements === count($requirements);

            $playerQuest->update([
                'progress' => min(100, $overallProgress),
                'completed_at' => $isCompleted ? now() : null,
                'status' => $isCompleted ? 'completed' : 'active',
            ]);

            // Award rewards if completed
            if ($isCompleted) {
                $this->awardQuestRewards($playerQuest);
            }

            DB::commit();

            $this->loggingUtil->info('Quest progress updated', [
                'player_quest_id' => $playerQuest->id,
                'progress' => $overallProgress,
                'completed' => $isCompleted,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->loggingUtil->error('Failed to update quest progress', [
                'player_quest_id' => $playerQuest->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Complete a quest
     */
    public function completeQuest(PlayerQuest $playerQuest): void
    {
        if ($playerQuest->status === 'completed') {
            throw new \Exception('Quest is already completed');
        }

        DB::beginTransaction();

        try {
            $playerQuest->update([
                'status' => 'completed',
                'completed_at' => now(),
                'progress' => 100,
            ]);

            // Award rewards
            $this->awardQuestRewards($playerQuest);

            DB::commit();

            $this->loggingUtil->info('Quest completed', [
                'player_quest_id' => $playerQuest->id,
                'player_id' => $playerQuest->player_id,
                'quest_id' => $playerQuest->quest_id,
            ]);

            // Clear cache
            $this->clearPlayerQuestCache($playerQuest->player);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->loggingUtil->error('Failed to complete quest', [
                'player_quest_id' => $playerQuest->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get player's active quests
     */
    public function getPlayerActiveQuests(Player $player): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "active_quests_{$player->id}";

        return $this->cachingUtil->remember($cacheKey, 300, function () use ($player) {
            return PlayerQuest::where('player_id', $player->id)
                ->where('status', 'active')
                ->with(['quest'])
                ->orderBy('started_at', 'asc')
                ->get();
        });
    }

    /**
     * Get player's completed quests
     */
    public function getPlayerCompletedQuests(Player $player, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "completed_quests_{$player->id}_{$limit}";

        return $this->cachingUtil->remember($cacheKey, 600, function () use ($player, $limit) {
            return PlayerQuest::where('player_id', $player->id)
                ->where('status', 'completed')
                ->with(['quest'])
                ->orderBy('completed_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get quest statistics
     */
    public function getQuestStatistics(): array
    {
        $cacheKey = 'quest_statistics';

        return $this->cachingUtil->remember($cacheKey, 900, function () {
            $stats = PlayerQuest::selectRaw('
                COUNT(*) as total_player_quests,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_quests,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_quests,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_quests,
                AVG(progress) as avg_progress,
                COUNT(DISTINCT player_id) as players_with_quests,
                COUNT(DISTINCT quest_id) as unique_quests_completed
            ')->first();

            $questStats = Quest::selectRaw('
                COUNT(*) as total_quests,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_quests,
                AVG(experience_reward) as avg_experience_reward,
                AVG(resource_reward_wood) as avg_wood_reward,
                AVG(resource_reward_clay) as avg_clay_reward,
                AVG(resource_reward_iron) as avg_iron_reward,
                AVG(resource_reward_crop) as avg_crop_reward
            ')->first();

            return [
                'player_quests' => [
                    'total' => $stats->total_player_quests ?? 0,
                    'active' => $stats->active_quests ?? 0,
                    'completed' => $stats->completed_quests ?? 0,
                    'failed' => $stats->failed_quests ?? 0,
                    'avg_progress' => round($stats->avg_progress ?? 0, 2),
                    'players_with_quests' => $stats->players_with_quests ?? 0,
                    'unique_quests_completed' => $stats->unique_quests_completed ?? 0,
                ],
                'quests' => [
                    'total' => $questStats->total_quests ?? 0,
                    'active' => $questStats->active_quests ?? 0,
                    'avg_experience_reward' => round($questStats->avg_experience_reward ?? 0, 2),
                    'avg_wood_reward' => round($questStats->avg_wood_reward ?? 0, 2),
                    'avg_clay_reward' => round($questStats->avg_clay_reward ?? 0, 2),
                    'avg_iron_reward' => round($questStats->avg_iron_reward ?? 0, 2),
                    'avg_crop_reward' => round($questStats->avg_crop_reward ?? 0, 2),
                ],
            ];
        });
    }

    /**
     * Generate daily quests for players
     */
    public function generateDailyQuests(): int
    {
        $players = Player::where('is_active', true)->get();
        $dailyQuests = Quest::where('type', 'daily')
            ->where('is_active', true)
            ->get();

        $generated = 0;

        foreach ($players as $player) {
            foreach ($dailyQuests as $quest) {
                // Check if player already has this daily quest
                $existingQuest = PlayerQuest::where('player_id', $player->id)
                    ->where('quest_id', $quest->id)
                    ->where('created_at', '>=', now()->startOfDay())
                    ->first();

                if (! $existingQuest && $this->canPlayerStartQuest($player, $quest)) {
                    try {
                        $this->startQuest($player, $quest);
                        $generated++;
                    } catch (\Exception $e) {
                        $this->loggingUtil->error('Failed to generate daily quest', [
                            'player_id' => $player->id,
                            'quest_id' => $quest->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        $this->loggingUtil->info('Daily quests generated', [
            'generated_count' => $generated,
            'players_processed' => $players->count(),
        ]);

        return $generated;
    }

    /**
     * Check if player can start a quest
     */
    protected function canPlayerStartQuest(Player $player, Quest $quest): bool
    {
        // Check level requirement
        if ($quest->required_level && $player->level < $quest->required_level) {
            return false;
        }

        // Check village requirement
        if ($quest->required_villages && $player->villages()->count() < $quest->required_villages) {
            return false;
        }

        // Check if quest is active
        if (! $quest->is_active) {
            return false;
        }

        return true;
    }

    /**
     * Initialize quest progress
     */
    protected function initializeQuestProgress(PlayerQuest $playerQuest): void
    {
        // Initialize any quest-specific progress tracking
        // This could include setting up progress counters, timers, etc.
    }

    /**
     * Calculate requirement progress
     */
    protected function calculateRequirementProgress(PlayerQuest $playerQuest, array $requirement, array $progressData): float
    {
        $requirementType = $requirement['type'] ?? '';
        $requirementValue = $requirement['value'] ?? 0;
        $currentValue = $progressData[$requirementType] ?? 0;

        if ($requirementValue <= 0) {
            return 100;
        }

        return min(100, ($currentValue / $requirementValue) * 100);
    }

    /**
     * Award quest rewards
     */
    protected function awardQuestRewards(PlayerQuest $playerQuest): void
    {
        $quest = $playerQuest->quest;
        $player = $playerQuest->player;

        // Award experience
        if ($quest->experience_reward > 0) {
            $player->increment('experience', $quest->experience_reward);
        }

        // Award resources to player's main village
        $mainVillage = $player->villages()->orderBy('created_at')->first();
        if ($mainVillage) {
            if ($quest->resource_reward_wood > 0) {
                $this->addResourceToVillage($mainVillage, 'wood', $quest->resource_reward_wood);
            }
            if ($quest->resource_reward_clay > 0) {
                $this->addResourceToVillage($mainVillage, 'clay', $quest->resource_reward_clay);
            }
            if ($quest->resource_reward_iron > 0) {
                $this->addResourceToVillage($mainVillage, 'iron', $quest->resource_reward_iron);
            }
            if ($quest->resource_reward_crop > 0) {
                $this->addResourceToVillage($mainVillage, 'crop', $quest->resource_reward_crop);
            }
        }

        $this->loggingUtil->info('Quest rewards awarded', [
            'player_quest_id' => $playerQuest->id,
            'experience' => $quest->experience_reward,
            'resources' => [
                'wood' => $quest->resource_reward_wood,
                'clay' => $quest->resource_reward_clay,
                'iron' => $quest->resource_reward_iron,
                'crop' => $quest->resource_reward_crop,
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
     * Clear player quest cache
     */
    protected function clearPlayerQuestCache(Player $player): void
    {
        $patterns = [
            "available_quests_{$player->id}",
            "active_quests_{$player->id}",
            "completed_quests_{$player->id}_*",
        ];

        foreach ($patterns as $pattern) {
            $this->cachingUtil->forgetPattern($pattern);
        }
    }
}
