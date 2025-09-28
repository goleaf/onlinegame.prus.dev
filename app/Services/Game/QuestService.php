<?php

namespace App\Services\Game;

use App\Models\Game\Player;
use App\Models\Game\PlayerQuest;
use App\Models\Game\Quest;
use Illuminate\Support\Facades\DB;
use SmartCache\Facades\SmartCache;

class QuestService
{
    public function __construct(
        private ResourceService $resourceService
    ) {
    }

    /**
     * Start a quest for a player
     */
    public function startQuest(Player $player, Quest $quest): array
    {
        // Validate quest start
        $validation = $this->validateQuestStart($player, $quest);
        if (! $validation['valid']) {
            return $validation;
        }

        // Create player quest record
        $playerQuest = PlayerQuest::create([
            'player_id' => $player->id,
            'quest_id' => $quest->id,
            'status' => 'active',
            'progress' => 0,
            'progress_data' => [],
            'started_at' => now(),
            'expires_at' => $quest->is_repeatable ? now()->addDays(7) : null,
        ]);

        // Clear cache
        $this->clearQuestCache($player);

        return [
            'success' => true,
            'message' => 'Quest started successfully',
            'player_quest' => $playerQuest,
        ];
    }

    /**
     * Complete a quest for a player
     */
    public function completeQuest(Player $player, Quest $quest): array
    {
        $playerQuest = PlayerQuest::where('player_id', $player->id)
            ->where('quest_id', $quest->id)
            ->where('status', 'active')
            ->first();

        if (! $playerQuest) {
            return [
                'success' => false,
                'message' => 'Quest not found or not active',
            ];
        }

        // Validate quest completion
        $validation = $this->validateQuestCompletion($player, $quest, $playerQuest);
        if (! $validation['valid']) {
            return $validation;
        }

        DB::transaction(function () use ($player, $quest, $playerQuest): void {
            // Update quest status
            $playerQuest->update([
                'status' => 'completed',
                'progress' => 100,
                'completed_at' => now(),
            ]);

            // Give rewards
            $this->giveQuestRewards($player, $quest);
        });

        // Clear cache
        $this->clearQuestCache($player);

        return [
            'success' => true,
            'message' => 'Quest completed successfully',
            'rewards' => $this->getQuestRewards($quest),
        ];
    }

    /**
     * Update quest progress
     */
    public function updateQuestProgress(Player $player, Quest $quest, array $progressData): array
    {
        $playerQuest = PlayerQuest::where('player_id', $player->id)
            ->where('quest_id', $quest->id)
            ->where('status', 'active')
            ->first();

        if (! $playerQuest) {
            return [
                'success' => false,
                'message' => 'Quest not found or not active',
            ];
        }

        // Calculate new progress
        $newProgress = $this->calculateQuestProgress($quest, $progressData);

        // Update progress
        $playerQuest->update([
            'progress' => $newProgress,
            'progress_data' => $progressData,
        ]);

        // Check if quest is completed
        if ($newProgress >= 100) {
            return $this->completeQuest($player, $quest);
        }

        return [
            'success' => true,
            'message' => 'Quest progress updated',
            'progress' => $newProgress,
        ];
    }

    /**
     * Get available quests for a player
     */
    public function getAvailableQuests(Player $player): array
    {
        $cacheKey = "available_quests:{$player->id}";

        return SmartCache::remember($cacheKey, 300, function () use ($player) {
            $completedQuestIds = PlayerQuest::where('player_id', $player->id)
                ->where('status', 'completed')
                ->pluck('quest_id')
                ->toArray();

            $activeQuestIds = PlayerQuest::where('player_id', $player->id)
                ->where('status', 'active')
                ->pluck('quest_id')
                ->toArray();

            $quests = Quest::active()
                ->whereNotIn('id', $completedQuestIds)
                ->whereNotIn('id', $activeQuestIds)
                ->get();

            return $quests->map(function ($quest) {
                return [
                    'id' => $quest->id,
                    'name' => $quest->name,
                    'description' => $quest->description,
                    'instructions' => $quest->instructions,
                    'category' => $quest->category,
                    'difficulty' => $quest->difficulty,
                    'requirements' => $quest->requirements,
                    'rewards' => $this->getQuestRewards($quest),
                    'is_repeatable' => $quest->is_repeatable,
                ];
            })->toArray();
        });
    }

    /**
     * Get active quests for a player
     */
    public function getActiveQuests(Player $player): array
    {
        $cacheKey = "active_quests:{$player->id}";

        return SmartCache::remember($cacheKey, 300, function () use ($player) {
            return PlayerQuest::where('player_id', $player->id)
                ->where('status', 'active')
                ->with('quest')
                ->get()
                ->map(function ($playerQuest) {
                    return [
                        'id' => $playerQuest->id,
                        'quest' => [
                            'id' => $playerQuest->quest->id,
                            'name' => $playerQuest->quest->name,
                            'description' => $playerQuest->quest->description,
                            'instructions' => $playerQuest->quest->instructions,
                            'category' => $playerQuest->quest->category,
                            'difficulty' => $playerQuest->quest->difficulty,
                        ],
                        'progress' => $playerQuest->progress,
                        'progress_data' => $playerQuest->progress_data,
                        'started_at' => $playerQuest->started_at,
                        'expires_at' => $playerQuest->expires_at,
                        'time_remaining' => $playerQuest->expires_at ? $playerQuest->expires_at->diffInSeconds(now()) : null,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get completed quests for a player
     */
    public function getCompletedQuests(Player $player): array
    {
        $cacheKey = "completed_quests:{$player->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($player) {
            return PlayerQuest::where('player_id', $player->id)
                ->where('status', 'completed')
                ->with('quest')
                ->orderBy('completed_at', 'desc')
                ->get()
                ->map(function ($playerQuest) {
                    return [
                        'id' => $playerQuest->id,
                        'quest' => [
                            'id' => $playerQuest->quest->id,
                            'name' => $playerQuest->quest->name,
                            'description' => $playerQuest->quest->description,
                            'category' => $playerQuest->quest->category,
                            'difficulty' => $playerQuest->quest->difficulty,
                        ],
                        'completed_at' => $playerQuest->completed_at,
                        'rewards' => $this->getQuestRewards($playerQuest->quest),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Generate daily quests for a player
     */
    public function generateDailyQuests(Player $player): array
    {
        $today = now()->startOfDay();

        // Check if daily quests already generated today
        $existingDailyQuests = PlayerQuest::where('player_id', $player->id)
            ->where('quest_id', 'like', 'DailyQuest%')
            ->where('started_at', '>=', $today)
            ->exists();

        if ($existingDailyQuests) {
            return [
                'success' => false,
                'message' => 'Daily quests already generated for today',
            ];
        }

        $dailyQuests = Quest::where('category', 'daily')
            ->active()
            ->inRandomOrder()
            ->limit(3)
            ->get();

        $generatedQuests = [];

        foreach ($dailyQuests as $quest) {
            $playerQuest = PlayerQuest::create([
                'player_id' => $player->id,
                'quest_id' => $quest->id,
                'status' => 'active',
                'progress' => 0,
                'progress_data' => [],
                'started_at' => now(),
                'expires_at' => now()->endOfDay(),
            ]);

            $generatedQuests[] = $playerQuest;
        }

        // Clear cache
        $this->clearQuestCache($player);

        return [
            'success' => true,
            'message' => 'Daily quests generated successfully',
            'quests' => $generatedQuests,
        ];
    }

    /**
     * Get quest statistics for a player
     */
    public function getQuestStatistics(Player $player): array
    {
        $cacheKey = "quest_stats:{$player->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($player) {
            $totalQuests = PlayerQuest::where('player_id', $player->id)->count();
            $completedQuests = PlayerQuest::where('player_id', $player->id)->where('status', 'completed')->count();
            $activeQuests = PlayerQuest::where('player_id', $player->id)->where('status', 'active')->count();

            $categoryStats = PlayerQuest::where('player_id', $player->id)
                ->where('status', 'completed')
                ->with('quest')
                ->get()
                ->groupBy('quest.category')
                ->map(function ($quests, $category) {
                    return [
                        'category' => $category,
                        'completed' => $quests->count(),
                    ];
                })
                ->values()
                ->toArray();

            return [
                'total_quests' => $totalQuests,
                'completed_quests' => $completedQuests,
                'active_quests' => $activeQuests,
                'completion_rate' => $totalQuests > 0 ? ($completedQuests / $totalQuests) * 100 : 0,
                'category_stats' => $categoryStats,
            ];
        });
    }

    /**
     * Validate quest start
     */
    private function validateQuestStart(Player $player, Quest $quest): array
    {
        if (! $quest->is_active) {
            return [
                'valid' => false,
                'message' => 'Quest is not active',
            ];
        }

        // Check if player already has this quest
        $existingQuest = PlayerQuest::where('player_id', $player->id)
            ->where('quest_id', $quest->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if ($existingQuest && ! $quest->is_repeatable) {
            return [
                'valid' => false,
                'message' => 'Quest already completed or in progress',
            ];
        }

        // Check requirements
        if (! $this->meetsQuestRequirements($player, $quest)) {
            return [
                'valid' => false,
                'message' => 'Quest requirements not met',
                'requirements' => $quest->requirements,
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate quest completion
     */
    private function validateQuestCompletion(Player $player, Quest $quest, PlayerQuest $playerQuest): array
    {
        if ($playerQuest->progress < 100) {
            return [
                'valid' => false,
                'message' => 'Quest progress not complete',
                'progress' => $playerQuest->progress,
            ];
        }

        if ($playerQuest->expires_at && $playerQuest->expires_at->isPast()) {
            return [
                'valid' => false,
                'message' => 'Quest has expired',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check if player meets quest requirements
     */
    private function meetsQuestRequirements(Player $player, Quest $quest): bool
    {
        if (! $quest->requirements) {
            return true;
        }

        foreach ($quest->requirements as $requirement => $value) {
            switch ($requirement) {
                case 'level':
                    if ($player->level < $value) {
                        return false;
                    }

                    break;
                case 'village_count':
                    if ($player->villages()->count() < $value) {
                        return false;
                    }

                    break;
                case 'building_level':
                    // Check if player has required building level
                    break;
                case 'resource_amount':
                    // Check if player has required resources
                    break;
            }
        }

        return true;
    }

    /**
     * Calculate quest progress
     */
    private function calculateQuestProgress(Quest $quest, array $progressData): int
    {
        // This would be implemented based on specific quest requirements
        // For now, return a simple progress calculation
        return min(100, count($progressData) * 10);
    }

    /**
     * Give quest rewards
     */
    private function giveQuestRewards(Player $player, Quest $quest): void
    {
        // Give experience
        if ($quest->experience_reward) {
            $player->increment('experience', $quest->experience_reward);
        }

        // Give gold
        if ($quest->gold_reward) {
            $player->increment('gold', $quest->gold_reward);
        }

        // Give resources
        if ($quest->resource_rewards) {
            $village = $player->villages()->first();
            if ($village) {
                $this->resourceService->addResources($village, $quest->resource_rewards);
            }
        }
    }

    /**
     * Get quest rewards
     */
    private function getQuestRewards(Quest $quest): array
    {
        return [
            'experience' => $quest->experience_reward ?? 0,
            'gold' => $quest->gold_reward ?? 0,
            'resources' => $quest->resource_rewards ?? [],
        ];
    }

    /**
     * Clear quest cache
     */
    private function clearQuestCache(Player $player): void
    {
        SmartCache::forget("available_quests:{$player->id}");
        SmartCache::forget("active_quests:{$player->id}");
        SmartCache::forget("completed_quests:{$player->id}");
        SmartCache::forget("quest_stats:{$player->id}");
    }
}
