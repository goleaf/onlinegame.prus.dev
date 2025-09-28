<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Player;
use App\Models\Game\Task;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_tasks()
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/tasks');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'type',
                        'status',
                        'progress',
                        'target',
                        'rewards',
                        'deadline',
                        'world_id',
                        'player_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_specific_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/tasks/{$task->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'type',
                'status',
                'progress',
                'target',
                'rewards',
                'deadline',
                'world',
                'player',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_task()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $world = World::factory()->create();

        $taskData = [
            'title' => 'Build a Barracks',
            'description' => 'Construct a barracks building in your village',
            'type' => 'building',
            'status' => 'available',
            'progress' => 0,
            'target' => 1,
            'rewards' => ['wood' => 100, 'clay' => 50],
            'deadline' => now()->addDays(7)->toISOString(),
            'world_id' => $world->id,
            'player_id' => $player->id,
        ];

        $response = $this->actingAs($user)->post('/api/game/tasks', $taskData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'task' => [
                    'id',
                    'title',
                    'description',
                    'type',
                    'status',
                    'progress',
                    'target',
                    'rewards',
                    'deadline',
                    'world_id',
                    'player_id',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Build a Barracks',
            'type' => 'building',
            'player_id' => $player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $updateData = [
            'title' => 'Updated Task Title',
            'description' => 'Updated task description',
            'progress' => 50,
            'status' => 'active',
        ];

        $response = $this->actingAs($user)->put("/api/game/tasks/{$task->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'task' => [
                    'id',
                    'title',
                    'description',
                    'progress',
                    'status',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'progress' => 50,
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $response = $this->actingAs($user)->delete("/api/game/tasks/{$task->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /**
     * @test
     */
    public function it_can_get_tasks_with_statistics()
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/tasks/with-stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'type',
                        'status',
                        'progress',
                        'target',
                        'player',
                        'world',
                        'created_at',
                    ],
                ],
                'statistics' => [
                    'total_tasks',
                    'by_status',
                    'by_type',
                    'completion_rate',
                    'average_progress',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_tasks_by_type()
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['type' => 'building']);
        Task::factory()->count(1)->create(['type' => 'combat']);

        $response = $this->actingAs($user)->get('/api/game/tasks?type=building');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_tasks_by_status()
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['status' => 'active']);
        Task::factory()->count(1)->create(['status' => 'completed']);

        $response = $this->actingAs($user)->get('/api/game/tasks?status=active');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_available_tasks()
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['status' => 'available']);
        Task::factory()->count(1)->create(['status' => 'active']);

        $response = $this->actingAs($user)->get('/api/game/tasks/available');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_active_tasks()
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['status' => 'active']);
        Task::factory()->count(1)->create(['status' => 'completed']);

        $response = $this->actingAs($user)->get('/api/game/tasks/active');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_completed_tasks()
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['status' => 'completed']);
        Task::factory()->count(1)->create(['status' => 'active']);

        $response = $this->actingAs($user)->get('/api/game/tasks/completed');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_expired_tasks()
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['status' => 'expired']);
        Task::factory()->count(1)->create(['status' => 'active']);

        $response = $this->actingAs($user)->get('/api/game/tasks/expired');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_start_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['status' => 'available']);

        $response = $this->actingAs($user)->post("/api/game/tasks/{$task->id}/start");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'task' => [
                    'id',
                    'status',
                    'started_at',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'active',
        ]);
    }

    /**
     * @test
     */
    public function it_can_complete_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['status' => 'active', 'progress' => 100]);

        $response = $this->actingAs($user)->post("/api/game/tasks/{$task->id}/complete");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'task' => [
                    'id',
                    'status',
                    'completed_at',
                ],
                'rewards' => [
                    'resources',
                    'experience',
                    'achievements',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_task_progress()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['status' => 'active', 'progress' => 50]);

        $response = $this->actingAs($user)->post("/api/game/tasks/{$task->id}/progress", [
            'progress' => 75,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'task' => [
                    'id',
                    'progress',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'progress' => 75,
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_task_rewards()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['rewards' => ['wood' => 100, 'clay' => 50]]);

        $response = $this->actingAs($user)->get("/api/game/tasks/{$task->id}/rewards");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'task',
                'rewards' => [
                    'resources',
                    'experience',
                    'achievements',
                    'special_items',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_search_tasks()
    {
        $user = User::factory()->create();
        Task::factory()->create(['title' => 'Build Barracks']);
        Task::factory()->create(['title' => 'Train Troops']);

        $response = $this->actingAs($user)->get('/api/game/tasks?search=Barracks');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/tasks');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_task_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/tasks', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'type', 'status', 'target', 'world_id', 'player_id']);
    }

    /**
     * @test
     */
    public function it_validates_task_type_enum()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();
        $world = World::factory()->create();

        $taskData = [
            'title' => 'Test Task',
            'type' => 'invalid_type',
            'status' => 'available',
            'target' => 1,
            'world_id' => $world->id,
            'player_id' => $player->id,
        ];

        $response = $this->actingAs($user)->post('/api/game/tasks', $taskData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * @test
     */
    public function it_validates_task_status_enum()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();
        $world = World::factory()->create();

        $taskData = [
            'title' => 'Test Task',
            'type' => 'building',
            'status' => 'invalid_status',
            'target' => 1,
            'world_id' => $world->id,
            'player_id' => $player->id,
        ];

        $response = $this->actingAs($user)->post('/api/game/tasks', $taskData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * @test
     */
    public function it_validates_progress_range()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $response = $this->actingAs($user)->post("/api/game/tasks/{$task->id}/progress", [
            'progress' => 150,  // Invalid: exceeds max 100
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['progress']);
    }

    /**
     * @test
     */
    public function it_validates_target_is_positive()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();
        $world = World::factory()->create();

        $taskData = [
            'title' => 'Test Task',
            'type' => 'building',
            'status' => 'available',
            'target' => -1,  // Invalid: negative target
            'world_id' => $world->id,
            'player_id' => $player->id,
        ];

        $response = $this->actingAs($user)->post('/api/game/tasks', $taskData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['target']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_task()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/tasks/999');

        $response->assertStatus(404);
    }
}
