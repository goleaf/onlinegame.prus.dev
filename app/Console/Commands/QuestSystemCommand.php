<?php

namespace App\Console\Commands;

use App\Models\Game\Player;
use App\Models\Game\PlayerQuest;
use App\Models\Game\Quest;
use App\Models\Game\World;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QuestSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quest:manage 
                            {action : Action to perform (assign|complete|reset|daily)}
                            {--player-id= : Specific player ID}
                            {--world-id= : Specific world ID}
                            {--quest-type= : Quest type filter (tutorial|daily|special)}
                            {--force : Force the operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage quest system - assign, complete, reset, and generate daily quests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        $this->info('🎯 Quest System Management');
        $this->info('==========================');

        switch ($action) {
            case 'assign':
                $this->assignQuests();
                break;
            case 'complete':
                $this->completeQuests();
                break;
            case 'reset':
                $this->resetQuests();
                break;
            case 'daily':
                $this->generateDailyQuests();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    /**
     * Assign quests to players based on their progress.
     */
    protected function assignQuests(): void
    {
        $this->info('📋 Assigning quests to players...');

        $worldId = $this->option('world-id');
        $playerId = $this->option('player-id');
        $questType = $this->option('quest-type');

        $query = Player::with(['villages', 'quests']);
        
        if ($worldId) {
            $query->where('world_id', $worldId);
        }
        
        if ($playerId) {
            $query->where('id', $playerId);
        }

        $players = $query->get();

        $assignedCount = 0;

        foreach ($players as $player) {
            $assigned = $this->assignQuestsToPlayer($player, $questType);
            $assignedCount += $assigned;
        }

        $this->info("✅ Assigned {$assignedCount} quests to players");
    }

    /**
     * Assign quests to a specific player.
     */
    protected function assignQuestsToPlayer(Player $player, ?string $questType = null): int
    {
        $assignedCount = 0;

        // Get available quests for the player
        $questQuery = Quest::where('is_active', true)
            ->where('world_id', $player->world_id);

        if ($questType) {
            $questQuery->where('category', $questType);
        }

        $availableQuests = $questQuery->get();

        foreach ($availableQuests as $quest) {
            // Check if player already has this quest
            $existingQuest = $player->quests()
                ->where('quest_id', $quest->id)
                ->first();

            if ($existingQuest) {
                continue;
            }

            // Check if player meets requirements
            if ($this->playerMeetsQuestRequirements($player, $quest)) {
                PlayerQuest::create([
                    'player_id' => $player->id,
                    'quest_id' => $quest->id,
                    'status' => 'available',
                    'progress' => 0,
                    'progress_data' => [],
                ]);

                $assignedCount++;
                $this->line("  → Assigned '{$quest->name}' to {$player->name}");
            }
        }

        return $assignedCount;
    }

    /**
     * Check if player meets quest requirements.
     */
    protected function playerMeetsQuestRequirements(Player $player, Quest $quest): bool
    {
        $requirements = $quest->requirements ?? [];

        foreach ($requirements as $requirement => $value) {
            switch ($requirement) {
                case 'level':
                    if ($player->level < $value) {
                        return false;
                    }
                    break;
                case 'village_count':
                    if ($player->villages_count < $value) {
                        return false;
                    }
                    break;
                case 'building_level':
                    // Check if player has required building level
                    $buildingKey = $requirements['building_key'] ?? null;
                    if ($buildingKey) {
                        $hasBuilding = $player->villages()
                            ->whereHas('buildings', function ($query) use ($buildingKey, $value) {
                                $query->whereHas('buildingType', function ($q) use ($buildingKey) {
                                    $q->where('key', $buildingKey);
                                })->where('level', '>=', $value);
                            })
                            ->exists();
                        
                        if (!$hasBuilding) {
                            return false;
                        }
                    }
                    break;
                case 'troop_count':
                    $troopKey = $requirements['troop_key'] ?? null;
                    if ($troopKey) {
                        $hasTroops = $player->villages()
                            ->whereHas('troops', function ($query) use ($troopKey, $value) {
                                $query->whereHas('unitType', function ($q) use ($troopKey) {
                                    $q->where('key', $troopKey);
                                })->where('count', '>=', $value);
                            })
                            ->exists();
                        
                        if (!$hasTroops) {
                            return false;
                        }
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Complete quests that have been finished.
     */
    protected function completeQuests(): void
    {
        $this->info('✅ Checking for completed quests...');

        $playerQuests = PlayerQuest::where('status', 'in_progress')
            ->with(['player', 'quest'])
            ->get();

        $completedCount = 0;

        foreach ($playerQuests as $playerQuest) {
            if ($this->isQuestCompleted($playerQuest)) {
                $this->completePlayerQuest($playerQuest);
                $completedCount++;
            }
        }

        $this->info("✅ Completed {$completedCount} quests");
    }

    /**
     * Check if a quest is completed.
     */
    protected function isQuestCompleted(PlayerQuest $playerQuest): bool
    {
        $quest = $playerQuest->quest;
        $player = $playerQuest->player;
        $requirements = $quest->requirements ?? [];

        foreach ($requirements as $requirement => $value) {
            switch ($requirement) {
                case 'build_building':
                    $buildingKey = $requirements['building_key'] ?? null;
                    if ($buildingKey) {
                        $hasBuilding = $player->villages()
                            ->whereHas('buildings', function ($query) use ($buildingKey, $value) {
                                $query->whereHas('buildingType', function ($q) use ($buildingKey) {
                                    $q->where('key', $buildingKey);
                                })->where('level', '>=', $value);
                            })
                            ->exists();
                        
                        if (!$hasBuilding) {
                            return false;
                        }
                    }
                    break;
                case 'train_troops':
                    $troopKey = $requirements['troop_key'] ?? null;
                    if ($troopKey) {
                        $hasTroops = $player->villages()
                            ->whereHas('troops', function ($query) use ($troopKey, $value) {
                                $query->whereHas('unitType', function ($q) use ($troopKey) {
                                    $q->where('key', $troopKey);
                                })->where('count', '>=', $value);
                            })
                            ->exists();
                        
                        if (!$hasTroops) {
                            return false;
                        }
                    }
                    break;
                case 'attack_village':
                    // Check if player has launched an attack
                    $hasAttack = $player->villages()
                        ->whereHas('movements', function ($query) {
                            $query->where('type', 'attack');
                        })
                        ->exists();
                    
                    if (!$hasAttack) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Complete a player quest and give rewards.
     */
    protected function completePlayerQuest(PlayerQuest $playerQuest): void
    {
        $quest = $playerQuest->quest;
        $player = $playerQuest->player;

        // Update quest status
        $playerQuest->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress' => 100,
        ]);

        // Give rewards
        $this->giveQuestRewards($player, $quest);

        $this->line("  → Completed '{$quest->name}' for {$player->name}");
    }

    /**
     * Give quest rewards to player.
     */
    protected function giveQuestRewards(Player $player, Quest $quest): void
    {
        $rewards = $quest->rewards ?? [];

        foreach ($rewards as $rewardType => $amount) {
            switch ($rewardType) {
                case 'resources':
                    $this->giveResourceRewards($player, $amount);
                    break;
                case 'experience':
                    $player->increment('experience', $amount);
                    break;
                case 'gold':
                    $player->increment('gold', $amount);
                    break;
            }
        }
    }

    /**
     * Give resource rewards to player.
     */
    protected function giveResourceRewards(Player $player, array $resources): void
    {
        foreach ($player->villages as $village) {
            foreach ($resources as $resourceType => $amount) {
                $resource = $village->resources()
                    ->where('type', $resourceType)
                    ->first();
                
                if ($resource) {
                    $resource->increment('amount', $amount);
                }
            }
        }
    }

    /**
     * Reset quest system.
     */
    protected function resetQuests(): void
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to reset all quests?')) {
                $this->info('Quest reset cancelled.');
                return;
            }
        }

        $this->info('🔄 Resetting quest system...');

        DB::transaction(function () {
            // Reset all player quests
            PlayerQuest::truncate();
            
            $this->info('✅ All player quests reset');
        });
    }

    /**
     * Generate daily quests.
     */
    protected function generateDailyQuests(): void
    {
        $this->info('📅 Generating daily quests...');

        $worldId = $this->option('world-id');
        
        $query = Player::with(['villages', 'quests']);
        
        if ($worldId) {
            $query->where('world_id', $worldId);
        }

        $players = $query->get();

        $generatedCount = 0;

        foreach ($players as $player) {
            // Remove old daily quests
            $player->quests()
                ->whereHas('quest', function ($query) {
                    $query->where('category', 'daily');
                })
                ->delete();

            // Generate new daily quests
            $dailyQuests = Quest::where('category', 'daily')
                ->where('is_active', true)
                ->where('world_id', $player->world_id)
                ->get();

            foreach ($dailyQuests as $quest) {
                PlayerQuest::create([
                    'player_id' => $player->id,
                    'quest_id' => $quest->id,
                    'status' => 'available',
                    'progress' => 0,
                    'progress_data' => [],
                ]);

                $generatedCount++;
            }
        }

        $this->info("✅ Generated {$generatedCount} daily quests");
    }
}
