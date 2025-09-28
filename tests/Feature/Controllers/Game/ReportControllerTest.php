<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_reports()
    {
        $user = User::factory()->create();
        Report::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/reports');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'content',
                        'status',
                        'priority',
                        'is_important',
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
    public function it_can_get_specific_report()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/reports/{$report->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'type',
                'title',
                'content',
                'data',
                'status',
                'priority',
                'is_important',
                'player',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_report()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $reportData = [
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Battle Report',
            'content' => 'Detailed battle information',
            'data' => ['attacker' => 'Player1', 'defender' => 'Player2'],
            'status' => 'unread',
            'priority' => 'high',
            'is_important' => true,
        ];

        $response = $this->actingAs($user)->post('/api/game/reports', $reportData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'report' => [
                    'id',
                    'type',
                    'title',
                    'content',
                    'status',
                    'priority',
                    'is_important',
                    'player_id',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('reports', [
            'title' => 'Battle Report',
            'type' => 'battle',
            'player_id' => $player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_report()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();

        $updateData = [
            'title' => 'Updated Battle Report',
            'content' => 'Updated battle information',
            'status' => 'read',
            'priority' => 'medium',
        ];

        $response = $this->actingAs($user)->put("/api/game/reports/{$report->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'report' => [
                    'id',
                    'title',
                    'content',
                    'status',
                    'priority',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'title' => 'Updated Battle Report',
            'status' => 'read',
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_report()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();

        $response = $this->actingAs($user)->delete("/api/game/reports/{$report->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    /**
     * @test
     */
    public function it_can_get_battle_reports()
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->create(['type' => 'battle']);
        Report::factory()->count(1)->create(['type' => 'resource']);

        $response = $this->actingAs($user)->get('/api/game/reports/battle');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_resource_reports()
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->create(['type' => 'resource']);
        Report::factory()->count(1)->create(['type' => 'battle']);

        $response = $this->actingAs($user)->get('/api/game/reports/resource');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_construction_reports()
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->create(['type' => 'construction']);
        Report::factory()->count(1)->create(['type' => 'battle']);

        $response = $this->actingAs($user)->get('/api/game/reports/construction');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_attack_reports()
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->create(['type' => 'attack']);
        Report::factory()->count(1)->create(['type' => 'defense']);

        $response = $this->actingAs($user)->get('/api/game/reports/attack');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_defense_reports()
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->create(['type' => 'defense']);
        Report::factory()->count(1)->create(['type' => 'attack']);

        $response = $this->actingAs($user)->get('/api/game/reports/defense');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_system_reports()
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->create(['type' => 'system']);
        Report::factory()->count(1)->create(['type' => 'battle']);

        $response = $this->actingAs($user)->get('/api/game/reports/system');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_unread_reports()
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->create(['status' => 'unread']);
        Report::factory()->count(1)->create(['status' => 'read']);

        $response = $this->actingAs($user)->get('/api/game/reports/unread');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_important_reports()
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->create(['is_important' => true]);
        Report::factory()->count(1)->create(['is_important' => false]);

        $response = $this->actingAs($user)->get('/api/game/reports/important');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_mark_report_as_read()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create(['status' => 'unread']);

        $response = $this->actingAs($user)->post("/api/game/reports/{$report->id}/read");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'report' => [
                    'id',
                    'status',
                    'read_at',
                ],
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'read',
        ]);
    }

    /**
     * @test
     */
    public function it_can_archive_report()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create(['status' => 'read']);

        $response = $this->actingAs($user)->post("/api/game/reports/{$report->id}/archive");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'report' => [
                    'id',
                    'status',
                    'archived_at',
                ],
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'archived',
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_reports_by_priority()
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->create(['priority' => 'high']);
        Report::factory()->count(1)->create(['priority' => 'low']);

        $response = $this->actingAs($user)->get('/api/game/reports?priority=high');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_search_reports()
    {
        $user = User::factory()->create();
        Report::factory()->create(['title' => 'Important Battle Report']);
        Report::factory()->create(['title' => 'Regular Report']);

        $response = $this->actingAs($user)->get('/api/game/reports?search=Important');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/reports');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_report_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/reports', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['player_id', 'type', 'title', 'content', 'status', 'priority']);
    }

    /**
     * @test
     */
    public function it_validates_report_type_enum()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $reportData = [
            'player_id' => $player->id,
            'type' => 'invalid_type',
            'title' => 'Test Report',
            'content' => 'Test content',
            'status' => 'unread',
            'priority' => 'high',
        ];

        $response = $this->actingAs($user)->post('/api/game/reports', $reportData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * @test
     */
    public function it_validates_priority_enum()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $reportData = [
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Test Report',
            'content' => 'Test content',
            'status' => 'unread',
            'priority' => 'invalid_priority',
        ];

        $response = $this->actingAs($user)->post('/api/game/reports', $reportData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    /**
     * @test
     */
    public function it_validates_status_enum()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $reportData = [
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Test Report',
            'content' => 'Test content',
            'status' => 'invalid_status',
            'priority' => 'high',
        ];

        $response = $this->actingAs($user)->post('/api/game/reports', $reportData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_report()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/reports/999');

        $response->assertStatus(404);
    }
}
