<?php

namespace Tests\Feature\Game;

use App\Models\Game\Player;
use App\Models\Game\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Player $player;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_can_get_tasks()
    {
        Task::factory()->count(3)->create(['player_id' => $this->player->id]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/tasks');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_can_filter_tasks_by_status()
    {
        Task::factory()->create(['player_id' => $this->player->id, 'status' => 'pending']);
        Task::factory()->create(['player_id' => $this->player->id, 'status' => 'completed']);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/tasks?status=pending');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('pending', $data[0]['status']);
    }

    public function test_can_filter_tasks_by_type()
    {
        Task::factory()->create(['player_id' => $this->player->id, 'type' => 'building']);
        Task::factory()->create(['player_id' => $this->player->id, 'type' => 'research']);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/tasks?type=building');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('building', $data[0]['type']);
    }

    public function test_can_filter_tasks_by_priority()
    {
        Task::factory()->create(['player_id' => $this->player->id, 'priority' => 'high']);
        Task::factory()->create(['player_id' => $this->player->id, 'priority' => 'low']);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/tasks?priority=high');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('high', $data[0]['priority']);
    }

    public function test_can_search_tasks()
    {
        Task::factory()->create(['player_id' => $this->player->id, 'title' => 'Build Barracks']);
        Task::factory()->create(['player_id' => $this->player->id, 'title' => 'Research Technology']);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/tasks?search=Barracks');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Barracks', $data[0]['title']);
    }

    public function test_can_get_task_details()
    {
        $task = Task::factory()->create(['player_id' => $this->player->id]);

        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/tasks/{$task->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'type',
                    'status',
                    'priority',
                    'progress',
                ],
            ]);
    }

    public function test_can_update_task_progress()
    {
        $task = Task::factory()->create(['player_id' => $this->player->id]);

        $response = $this
            ->actingAs($this->user)
            ->putJson("/game/tasks/{$task->id}/progress", [
                'progress' => 75,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task progress updated successfully.',
            ]);

        $this->assertEquals(75, $task->fresh()->progress);
    }

    public function test_validation_errors_for_update_progress()
    {
        $task = Task::factory()->create(['player_id' => $this->player->id]);

        $response = $this
            ->actingAs($this->user)
            ->putJson("/game/tasks/{$task->id}/progress", [
                'progress' => 150,  // Invalid: over 100
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_can_filter_due_soon_tasks()
    {
        Task::factory()->create([
            'player_id' => $this->player->id,
            'due_date' => now()->addHours(2),
        ]);
        Task::factory()->create([
            'player_id' => $this->player->id,
            'due_date' => now()->addDays(2),
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/tasks?due_soon=24');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson('/game/tasks');
        $response->assertStatus(401);

        $task = Task::factory()->create(['player_id' => $this->player->id]);
        $response = $this->getJson("/game/tasks/{$task->id}");
        $response->assertStatus(401);
    }

    public function test_cannot_access_other_player_tasks()
    {
        $otherPlayer = Player::factory()->create();
        $otherTask = Task::factory()->create(['player_id' => $otherPlayer->id]);

        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/tasks/{$otherTask->id}");

        $response->assertStatus(404);
    }
}
