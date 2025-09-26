<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\QuestManager;
use App\Models\Game\Achievement;
use App\Models\Game\Player;
use App\Models\Game\Quest;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $this->actingAs($user);
    }

    public function test_can_render_quest_manager()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertStatus(200)
            ->assertSee('Quest Manager');
    }

    public function test_loads_quest_data_on_mount()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('world', $world)
            ->assertSet('availableQuests', [])
            ->assertSet('activeQuests', [])
            ->assertSet('completedQuests', [])
            ->assertSet('achievements', [])
            ->assertSet('playerAchievements', []);
    }

    public function test_can_toggle_real_time_updates()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('realTimeUpdates', true)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', false)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_can_toggle_auto_refresh()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('autoRefresh', true)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', false)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', true);
    }

    public function test_can_set_refresh_interval()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('refreshInterval', 15)
            ->call('setRefreshInterval', 20)
            ->assertSet('refreshInterval', 20)
            ->call('setRefreshInterval', 0)
            ->assertSet('refreshInterval', 5)
            ->call('setRefreshInterval', 100)
            ->assertSet('refreshInterval', 60);
    }

    public function test_can_set_game_speed()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('gameSpeed', 1)
            ->call('setGameSpeed', 2)
            ->assertSet('gameSpeed', 2)
            ->call('setGameSpeed', 0.1)
            ->assertSet('gameSpeed', 0.5)
            ->call('setGameSpeed', 5)
            ->assertSet('gameSpeed', 3);
    }

    public function test_can_select_quest()
    {
        $world = World::first();
        $quest = Quest::factory()->create(['world_id' => $world->id]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('selectedQuest', null)
            ->assertSet('showDetails', false)
            ->call('selectQuest', $quest->id)
            ->assertSet('selectedQuest.id', $quest->id)
            ->assertSet('showDetails', true);
    }

    public function test_can_select_achievement()
    {
        $world = World::first();
        $achievement = Achievement::factory()->create(['world_id' => $world->id]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('selectedAchievement', null)
            ->assertSet('showDetails', false)
            ->call('selectAchievement', $achievement->id)
            ->assertSet('selectedAchievement.id', $achievement->id)
            ->assertSet('showDetails', true);
    }

    public function test_can_toggle_details()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('showDetails', false)
            ->call('toggleDetails')
            ->assertSet('showDetails', true)
            ->call('toggleDetails')
            ->assertSet('showDetails', false);
    }

    public function test_can_accept_quest()
    {
        $world = World::first();
        $quest = Quest::factory()->create([
            'world_id' => $world->id,
            'min_level' => 1,
        ]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('acceptQuest', $quest->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Quest \"{$quest->title}\" accepted")
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_accept_quest_with_insufficient_level()
    {
        $world = World::first();
        $quest = Quest::factory()->create([
            'world_id' => $world->id,
            'min_level' => 10,
        ]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('acceptQuest', $quest->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Quest requires higher level')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_cannot_accept_nonexistent_quest()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('acceptQuest', 999)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Quest not found')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_complete_quest()
    {
        $world = World::first();
        $player = Player::first();
        $quest = Quest::factory()->create([
            'world_id' => $world->id,
            'target_value' => 100,
        ]);

        // Create quest progress
        $player->quests()->create([
            'quest_id' => $quest->id,
            'status' => 'active',
            'progress' => 100,
            'started_at' => now(),
        ]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('completeQuest', $quest->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Quest \"{$quest->title}\" completed!")
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_complete_quest_with_insufficient_progress()
    {
        $world = World::first();
        $player = Player::first();
        $quest = Quest::factory()->create([
            'world_id' => $world->id,
            'target_value' => 100,
        ]);

        // Create quest progress with insufficient progress
        $player->quests()->create([
            'quest_id' => $quest->id,
            'status' => 'active',
            'progress' => 50,
            'started_at' => now(),
        ]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('completeQuest', $quest->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Quest not completed yet')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_abandon_quest()
    {
        $world = World::first();
        $player = Player::first();
        $quest = Quest::factory()->create(['world_id' => $world->id]);

        // Create quest progress
        $player->quests()->create([
            'quest_id' => $quest->id,
            'status' => 'active',
            'progress' => 50,
            'started_at' => now(),
        ]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('abandonQuest', $quest->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Quest \"{$quest->title}\" abandoned")
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_can_claim_achievement()
    {
        $world = World::first();
        $achievement = Achievement::factory()->create([
            'world_id' => $world->id,
            'requirements' => json_encode([['type' => 'level', 'value' => 1]]),
        ]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('claimAchievement', $achievement->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Achievement \"{$achievement->title}\" unlocked!")
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_claim_achievement_with_insufficient_requirements()
    {
        $world = World::first();
        $achievement = Achievement::factory()->create([
            'world_id' => $world->id,
            'requirements' => json_encode([['type' => 'level', 'value' => 10]]),
        ]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('claimAchievement', $achievement->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Achievement requirements not met')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_cannot_claim_nonexistent_achievement()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('claimAchievement', 999)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Achievement not found')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_claim_reward()
    {
        $world = World::first();
        $player = Player::first();
        $reward = $player->rewards()->create([
            'type' => 'experience',
            'value' => 100,
            'claimed_at' => null,
        ]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('claimReward', $reward->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Reward claimed successfully')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_claim_already_claimed_reward()
    {
        $world = World::first();
        $player = Player::first();
        $reward = $player->rewards()->create([
            'type' => 'experience',
            'value' => 100,
            'claimed_at' => now(),
        ]);

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('claimReward', $reward->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Reward already claimed')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_filter_by_type()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('filterByType', null)
            ->call('filterByType', 'building')
            ->assertSet('filterByType', 'building');
    }

    public function test_can_filter_by_difficulty()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('filterByDifficulty', null)
            ->call('filterByDifficulty', 'easy')
            ->assertSet('filterByDifficulty', 'easy');
    }

    public function test_can_filter_by_status()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('filterByStatus', null)
            ->call('filterByStatus', 'active')
            ->assertSet('filterByStatus', 'active');
    }

    public function test_can_clear_filters()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->set('filterByType', 'building')
            ->set('filterByDifficulty', 'easy')
            ->set('filterByStatus', 'active')
            ->set('searchQuery', 'test')
            ->set('showOnlyAvailable', true)
            ->set('showOnlyActive', true)
            ->set('showOnlyCompleted', true)
            ->set('showOnlyAchievements', true)
            ->call('clearFilters')
            ->assertSet('filterByType', null)
            ->assertSet('filterByDifficulty', null)
            ->assertSet('filterByStatus', null)
            ->assertSet('searchQuery', '')
            ->assertSet('showOnlyAvailable', false)
            ->assertSet('showOnlyActive', false)
            ->assertSet('showOnlyCompleted', false)
            ->assertSet('showOnlyAchievements', false);
    }

    public function test_can_sort_quests()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('sortBy', 'created_at')
            ->assertSet('sortOrder', 'desc')
            ->call('sortQuests', 'title')
            ->assertSet('sortBy', 'title')
            ->assertSet('sortOrder', 'desc')
            ->call('sortQuests', 'title')
            ->assertSet('sortBy', 'title')
            ->assertSet('sortOrder', 'asc');
    }

    public function test_can_search_quests()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->set('searchQuery', 'building quest')
            ->call('searchQuests')
            ->assertSet('searchQuery', 'building quest');
    }

    public function test_can_toggle_available_filter()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('showOnlyAvailable', false)
            ->call('toggleAvailableFilter')
            ->assertSet('showOnlyAvailable', true)
            ->call('toggleAvailableFilter')
            ->assertSet('showOnlyAvailable', false);
    }

    public function test_can_toggle_active_filter()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('showOnlyActive', false)
            ->call('toggleActiveFilter')
            ->assertSet('showOnlyActive', true)
            ->call('toggleActiveFilter')
            ->assertSet('showOnlyActive', false);
    }

    public function test_can_toggle_completed_filter()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('showOnlyCompleted', false)
            ->call('toggleCompletedFilter')
            ->assertSet('showOnlyCompleted', true)
            ->call('toggleCompletedFilter')
            ->assertSet('showOnlyCompleted', false);
    }

    public function test_can_toggle_achievements_filter()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('showOnlyAchievements', false)
            ->call('toggleAchievementsFilter')
            ->assertSet('showOnlyAchievements', true)
            ->call('toggleAchievementsFilter')
            ->assertSet('showOnlyAchievements', false);
    }

    public function test_calculates_quest_stats()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('questStats', [])
            ->call('calculateQuestStats')
            ->assertSet('questStats', []);
    }

    public function test_calculates_achievement_stats()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('achievementStats', [])
            ->call('calculateAchievementStats')
            ->assertSet('achievementStats', []);
    }

    public function test_calculates_player_progress()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('playerProgress', [])
            ->call('calculatePlayerProgress')
            ->assertSet('playerProgress', []);
    }

    public function test_calculates_quest_history()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('questHistory', [])
            ->call('calculateQuestHistory')
            ->assertSet('questHistory', []);
    }

    public function test_calculates_achievement_history()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('achievementHistory', [])
            ->call('calculateAchievementHistory')
            ->assertSet('achievementHistory', []);
    }

    public function test_calculates_rewards()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('rewards', [])
            ->call('calculateRewards')
            ->assertSet('rewards', []);
    }

    public function test_get_quest_icon()
    {
        $world = World::first();

        $component = Livewire::test(QuestManager::class, ['world' => $world]);

        $this->assertEquals('📚', $component->instance()->getQuestIcon(['category' => 'tutorial']));
        $this->assertEquals('🏗️', $component->instance()->getQuestIcon(['category' => 'building']));
        $this->assertEquals('💰', $component->instance()->getQuestIcon(['category' => 'resource']));
        $this->assertEquals('⚔️', $component->instance()->getQuestIcon(['category' => 'combat']));
        $this->assertEquals('🤝', $component->instance()->getQuestIcon(['category' => 'alliance']));
        $this->assertEquals('⭐', $component->instance()->getQuestIcon(['category' => 'special']));
        $this->assertEquals('📋', $component->instance()->getQuestIcon(['category' => 'unknown']));
    }

    public function test_get_quest_color()
    {
        $world = World::first();

        $component = Livewire::test(QuestManager::class, ['world' => $world]);

        $this->assertEquals('green', $component->instance()->getQuestColor(['difficulty' => 'easy']));
        $this->assertEquals('yellow', $component->instance()->getQuestColor(['difficulty' => 'medium']));
        $this->assertEquals('red', $component->instance()->getQuestColor(['difficulty' => 'hard']));
        $this->assertEquals('purple', $component->instance()->getQuestColor(['difficulty' => 'expert']));
        $this->assertEquals('blue', $component->instance()->getQuestColor(['difficulty' => 'unknown']));
    }

    public function test_get_quest_status()
    {
        $world = World::first();

        $component = Livewire::test(QuestManager::class, ['world' => $world]);

        $quest = ['status' => 'active'];
        $this->assertEquals('Active', $component->instance()->getQuestStatus($quest));

        $quest = ['status' => 'completed'];
        $this->assertEquals('Completed', $component->instance()->getQuestStatus($quest));

        $quest = ['status' => 'abandoned'];
        $this->assertEquals('Abandoned', $component->instance()->getQuestStatus($quest));

        $quest = ['status' => 'available'];
        $this->assertEquals('Available', $component->instance()->getQuestStatus($quest));
    }

    public function test_get_achievement_icon()
    {
        $world = World::first();

        $component = Livewire::test(QuestManager::class, ['world' => $world]);

        $this->assertEquals('🏗️', $component->instance()->getAchievementIcon(['category' => 'building']));
        $this->assertEquals('⚔️', $component->instance()->getAchievementIcon(['category' => 'combat']));
        $this->assertEquals('🤝', $component->instance()->getAchievementIcon(['category' => 'alliance']));
        $this->assertEquals('💰', $component->instance()->getAchievementIcon(['category' => 'resource']));
        $this->assertEquals('⭐', $component->instance()->getAchievementIcon(['category' => 'special']));
        $this->assertEquals('🏆', $component->instance()->getAchievementIcon(['category' => 'milestone']));
        $this->assertEquals('🏅', $component->instance()->getAchievementIcon(['category' => 'unknown']));
    }

    public function test_get_achievement_color()
    {
        $world = World::first();

        $component = Livewire::test(QuestManager::class, ['world' => $world]);

        $this->assertEquals('brown', $component->instance()->getAchievementColor(['rarity' => 'bronze']));
        $this->assertEquals('gray', $component->instance()->getAchievementColor(['rarity' => 'silver']));
        $this->assertEquals('yellow', $component->instance()->getAchievementColor(['rarity' => 'gold']));
        $this->assertEquals('purple', $component->instance()->getAchievementColor(['rarity' => 'platinum']));
        $this->assertEquals('blue', $component->instance()->getAchievementColor(['rarity' => 'unknown']));
    }

    public function test_get_progress_percentage()
    {
        $world = World::first();

        $component = Livewire::test(QuestManager::class, ['world' => $world]);

        $quest = ['progress' => 50, 'target_value' => 100];
        $this->assertEquals(50, $component->instance()->getProgressPercentage($quest));

        $quest = ['progress' => 100, 'target_value' => 100];
        $this->assertEquals(100, $component->instance()->getProgressPercentage($quest));

        $quest = ['progress' => 150, 'target_value' => 100];
        $this->assertEquals(100, $component->instance()->getProgressPercentage($quest));

        $quest = ['progress' => 50];
        $this->assertEquals(0, $component->instance()->getProgressPercentage($quest));
    }

    public function test_get_time_remaining()
    {
        $world = World::first();

        $component = Livewire::test(QuestManager::class, ['world' => $world]);

        $quest = ['expires_at' => now()->addHours(2)];
        $this->assertNotEquals('N/A', $component->instance()->getTimeRemaining($quest));

        $quest = ['expires_at' => now()->subHours(1)];
        $this->assertEquals('Expired', $component->instance()->getTimeRemaining($quest));

        $quest = [];
        $this->assertEquals('No time limit', $component->instance()->getTimeRemaining($quest));
    }

    public function test_notification_system()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('notifications', [])
            ->call('addNotification', 'Test notification', 'info')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Test notification')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_can_remove_notification()
    {
        $world = World::first();

        $component = Livewire::test(QuestManager::class, ['world' => $world]);

        $component->call('addNotification', 'Test notification', 'info');
        $notificationId = $component->get('notifications.0.id');

        $component
            ->call('removeNotification', $notificationId)
            ->assertCount('notifications', 0);
    }

    public function test_can_clear_notifications()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->call('addNotification', 'Test notification 1', 'info')
            ->call('addNotification', 'Test notification 2', 'success')
            ->assertCount('notifications', 2)
            ->call('clearNotifications')
            ->assertCount('notifications', 0);
    }

    public function test_handles_game_tick_processed()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSet('realTimeUpdates', true)
            ->dispatch('gameTickProcessed')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_handles_quest_accepted()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->dispatch('questAccepted', ['quest_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Quest accepted')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_quest_completed()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->dispatch('questCompleted', ['quest_id' => 1, 'player_id' => 1, 'rewards' => []])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Quest completed')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_quest_abandoned()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->dispatch('questAbandoned', ['quest_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Quest abandoned')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_achievement_unlocked()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->dispatch('achievementUnlocked', ['achievement_id' => 1, 'player_id' => 1, 'rewards' => []])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Achievement unlocked')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_reward_claimed()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->dispatch('rewardClaimed', ['reward_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Reward claimed')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_village_selected()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->dispatch('villageSelected', 1)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Village selected - quest data updated')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_component_renders_with_all_data()
    {
        $world = World::first();

        Livewire::test(QuestManager::class, ['world' => $world])
            ->assertSee('Quest Manager')
            ->assertSee('Quests')
            ->assertSee('Achievements');
    }

    public function test_handles_missing_world()
    {
        Livewire::test(QuestManager::class, ['world' => null])
            ->assertSet('world', null);
    }
}
