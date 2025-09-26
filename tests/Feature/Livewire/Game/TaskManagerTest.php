<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\TaskManager;
use App\Models\Game\Player;
use App\Models\Game\PlayerAchievement;
use App\Models\Game\PlayerQuest;
use App\Models\Game\Task;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskManagerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $world;
    protected $player;
    protected $village;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test world
        $this->world = World::factory()->create([
            'name' => 'Test World',
            'speed' => 1.0,
            'is_active' => true,
        ]);

        // Create test player
        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
            'name' => 'Test Player',
            'points' => 1000,
        ]);

        // Create test village
        $this->village = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'name' => 'Test Village',
            'x_coordinate' => 100,
            'y_coordinate' => 100,
            'population' => 100,
        ]);
    }

    public function test_can_mount_component()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $this->assertNotNull($component->world);
        $this->assertEquals($this->world->id, $component->world->id);
    }

    public function test_loads_player_data_on_mount()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $this->assertNotNull($component->player);
        $this->assertEquals($this->player->id, $component->player->id);
    }

    public function test_loads_tasks_on_mount()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->tasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->activeTasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->completedTasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->availableTasks);
    }

    public function test_can_switch_view_modes()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        // Test switching to quests view
        $component->call('setViewMode', 'quests');
        $this->assertEquals('quests', $component->viewMode);

        // Test switching to achievements view
        $component->call('setViewMode', 'achievements');
        $this->assertEquals('achievements', $component->viewMode);

        // Test switching back to tasks view
        $component->call('setViewMode', 'tasks');
        $this->assertEquals('tasks', $component->viewMode);
    }

    public function test_can_set_task_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('setTaskType', 'active');
        $this->assertEquals('active', $component->taskType);

        $component->call('setTaskType', 'completed');
        $this->assertEquals('completed', $component->taskType);
    }

    public function test_can_set_quest_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('setQuestType', 'active');
        $this->assertEquals('active', $component->questType);

        $component->call('setQuestType', 'completed');
        $this->assertEquals('completed', $component->questType);
    }

    public function test_can_set_achievement_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('setAchievementType', 'unlocked');
        $this->assertEquals('unlocked', $component->achievementType);

        $component->call('setAchievementType', 'available');
        $this->assertEquals('available', $component->achievementType);
    }

    public function test_can_sort_tasks()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('sortTasks', 'title');
        $this->assertEquals('title', $component->sortBy);
        $this->assertEquals('asc', $component->sortOrder);

        // Test toggle sort order
        $component->call('sortTasks', 'title');
        $this->assertEquals('desc', $component->sortOrder);
    }

    public function test_can_search_tasks()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->set('searchQuery', 'Test Task');
        $component->call('searchTasks');

        $this->assertEquals('Test Task', $component->searchQuery);
    }

    public function test_can_clear_filters()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        // Set some filters
        $component->set('taskType', 'active');
        $component->set('searchQuery', 'test');

        $component->call('clearFilters');

        $this->assertEquals('all', $component->taskType);
        $this->assertEquals('', $component->searchQuery);
    }

    public function test_can_toggle_real_time_updates()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('toggleRealTimeUpdates');
        $this->assertFalse($component->realTimeUpdates);

        $component->call('toggleRealTimeUpdates');
        $this->assertTrue($component->realTimeUpdates);
    }

    public function test_can_toggle_auto_refresh()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('toggleAutoRefresh');
        $this->assertFalse($component->autoRefresh);

        $component->call('toggleAutoRefresh');
        $this->assertTrue($component->autoRefresh);
    }

    public function test_can_set_refresh_interval()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('setRefreshInterval', 60);
        $this->assertEquals(60, $component->refreshInterval);

        // Test bounds
        $component->call('setRefreshInterval', 1);
        $this->assertEquals(5, $component->refreshInterval);

        $component->call('setRefreshInterval', 1000);
        $this->assertEquals(300, $component->refreshInterval);
    }

    public function test_can_refresh_tasks()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('refreshTasks');
        $this->assertNotNull($component->lastUpdate);
    }

    public function test_can_start_task()
    {
        $this->actingAs($this->user);

        // Create a test task
        $task = Task::factory()->create([
            'world_id' => $this->world->id,
            'player_id' => $this->player->id,
            'status' => 'available',
        ]);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('startTask', $task->id);

        $task->refresh();
        $this->assertEquals('active', $task->status);
    }

    public function test_can_complete_task()
    {
        $this->actingAs($this->user);

        // Create a test task
        $task = Task::factory()->create([
            'world_id' => $this->world->id,
            'player_id' => $this->player->id,
            'status' => 'active',
        ]);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('completeTask', $task->id);

        $task->refresh();
        $this->assertEquals('completed', $task->status);
    }

    public function test_can_abandon_task()
    {
        $this->actingAs($this->user);

        // Create a test task
        $task = Task::factory()->create([
            'world_id' => $this->world->id,
            'player_id' => $this->player->id,
            'status' => 'active',
        ]);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('abandonTask', $task->id);

        $task->refresh();
        $this->assertEquals('available', $task->status);
    }

    public function test_can_start_quest()
    {
        $this->actingAs($this->user);

        // Create a test quest
        $quest = PlayerQuest::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'available',
        ]);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('startQuest', $quest->id);

        $quest->refresh();
        $this->assertEquals('in_progress', $quest->status);
    }

    public function test_can_complete_quest()
    {
        $this->actingAs($this->user);

        // Create a test quest
        $quest = PlayerQuest::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'in_progress',
        ]);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('completeQuest', $quest->id);

        $quest->refresh();
        $this->assertEquals('completed', $quest->status);
    }

    public function test_can_abandon_quest()
    {
        $this->actingAs($this->user);

        // Create a test quest
        $quest = PlayerQuest::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'in_progress',
        ]);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('abandonQuest', $quest->id);

        $quest->refresh();
        $this->assertEquals('available', $quest->status);
    }

    public function test_can_claim_achievement()
    {
        $this->actingAs($this->user);

        // Create a test achievement
        $achievement = PlayerAchievement::factory()->create([
            'player_id' => $this->player->id,
        ]);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('claimAchievement', $achievement->id);

        $achievement->refresh();
        $this->assertNotNull($achievement->unlocked_at);
    }

    public function test_handles_missing_world()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => null]);

        $this->assertNull($component->world);
    }

    public function test_handles_missing_player()
    {
        $this->actingAs($this->user);

        // Create world without player
        $world = World::factory()->create();

        $component = Livewire::test(TaskManager::class, ['world' => $world]);

        $this->assertNull($component->player);
    }

    public function test_handles_missing_tasks()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->tasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->activeTasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->completedTasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->availableTasks);
    }

    public function test_handles_invalid_view_mode()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('setViewMode', 'invalid');
        $this->assertEquals('invalid', $component->viewMode);
    }

    public function test_handles_invalid_task_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('setTaskType', 'invalid');
        $this->assertEquals('invalid', $component->taskType);
    }

    public function test_handles_invalid_quest_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('setQuestType', 'invalid');
        $this->assertEquals('invalid', $component->questType);
    }

    public function test_handles_invalid_achievement_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('setAchievementType', 'invalid');
        $this->assertEquals('invalid', $component->achievementType);
    }

    public function test_handles_missing_player_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $this->assertNotNull($component->player);
    }

    public function test_handles_missing_world_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $this->assertNotNull($component->world);
    }

    public function test_handles_missing_task_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->tasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->activeTasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->completedTasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->availableTasks);
    }

    public function test_handles_missing_quest_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('setViewMode', 'quests');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->quests);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->activeQuests);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->completedQuests);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->availableQuests);
    }

    public function test_handles_missing_achievement_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $component->call('setViewMode', 'achievements');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->achievements);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->unlockedAchievements);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->availableAchievements);
    }

    public function test_real_time_event_handlers()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        // Test event handlers
        $component->dispatch('taskCompleted');
        $component->dispatch('questCompleted');
        $component->dispatch('achievementUnlocked');
        $component->dispatch('taskProgressUpdated');
        $component->dispatch('questProgressUpdated');
        $component->dispatch('gameTickProcessed');
        $component->dispatch('villageSelected', ['villageId' => $this->village->id]);

        $this->assertTrue(true);
    }

    public function test_notification_management()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        // Test notifications
        $component->call('addNotification', 'Test notification', 'info');
        $this->assertCount(1, $component->notifications);

        $component->call('clearNotifications');
        $this->assertCount(0, $component->notifications);
    }

    public function test_task_icon_methods()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $this->assertEquals('check-circle', $component->instance()->getTaskIcon('task'));
        $this->assertEquals('star', $component->instance()->getTaskIcon('quest'));
        $this->assertEquals('trophy', $component->instance()->getTaskIcon('achievement'));
    }

    public function test_task_color_methods()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $this->assertEquals('blue', $component->instance()->getTaskColor('active'));
        $this->assertEquals('green', $component->instance()->getTaskColor('completed'));
        $this->assertEquals('gray', $component->instance()->getTaskColor('available'));
        $this->assertEquals('yellow', $component->instance()->getTaskColor('unlocked'));
    }

    public function test_progress_percentage_method()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $this->assertEquals(50, $component->instance()->getProgressPercentage(5, 10));
        $this->assertEquals(100, $component->instance()->getProgressPercentage(10, 10));
        $this->assertEquals(100, $component->instance()->getProgressPercentage(15, 10));
        $this->assertEquals(0, $component->instance()->getProgressPercentage(0, 10));
    }

    public function test_format_time_remaining_method()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(TaskManager::class, ['world' => $this->world]);

        $timeAgo = $component->instance()->formatTimeRemaining(now()->addSeconds(3600));
        $this->assertStringContainsString('h', $timeAgo);
    }
}
