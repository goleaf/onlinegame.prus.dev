<?php

namespace Tests\Feature\Game;

use App\Models\Game\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $player;

    protected $report;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->player = $this->user->player()->create([
            'name' => 'TestPlayer',
            'world_id' => 1,
        ]);

        $this->report = Report::factory()->create([
            'player_id' => $this->player->id,
            'type' => 'battle',
            'title' => 'Test Battle Report',
            'content' => 'You won a battle',
            'status' => 'unread',
        ]);
    }

    public function test_can_get_reports()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game/reports');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'player_id',
                            'type',
                            'title',
                            'content',
                            'status',
                            'created_at',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                ],
                'message',
            ]);
    }

    public function test_can_filter_reports_by_type()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game/reports?type=battle');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);
    }

    public function test_can_filter_reports_by_status()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game/reports?status=unread');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);
    }

    public function test_can_get_report_details()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/api/game/reports/{$this->report->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'player_id',
                    'type',
                    'title',
                    'content',
                    'status',
                ],
                'message',
            ]);

        // Should mark as read when viewed
        $this->assertDatabaseHas('reports', [
            'id' => $this->report->id,
            'status' => 'read',
        ]);
    }

    public function test_can_mark_report_as_read()
    {
        $response = $this
            ->actingAs($this->user)
            ->putJson("/api/game/reports/{$this->report->id}/mark-read");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $this->report->id,
            'status' => 'read',
        ]);
    }

    public function test_can_mark_all_reports_as_read()
    {
        Report::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'unread',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->putJson('/api/game/reports/mark-all-read');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'updated_count',
                ],
                'message',
            ]);
    }

    public function test_can_delete_report()
    {
        $response = $this
            ->actingAs($this->user)
            ->deleteJson("/api/game/reports/{$this->report->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('reports', [
            'id' => $this->report->id,
        ]);
    }

    public function test_can_get_report_statistics()
    {
        Report::factory()->create([
            'player_id' => $this->player->id,
            'type' => 'system',
            'status' => 'read',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game/reports/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_reports',
                    'unread_reports',
                    'read_reports',
                    'by_type',
                    'recent_reports',
                ],
                'message',
            ]);
    }

    public function test_can_get_unread_count()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game/reports/unread-count');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'unread_count',
                ],
                'message',
            ]);
    }

    public function test_cannot_access_other_player_reports()
    {
        $otherUser = User::factory()->create();
        $otherPlayer = $otherUser->player()->create([
            'name' => 'OtherPlayer',
            'world_id' => 1,
        ]);
        $otherReport = Report::factory()->create([
            'player_id' => $otherPlayer->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson("/api/game/reports/{$otherReport->id}");

        $response->assertStatus(404);
    }

    public function test_cannot_delete_other_player_reports()
    {
        $otherUser = User::factory()->create();
        $otherPlayer = $otherUser->player()->create([
            'name' => 'OtherPlayer',
            'world_id' => 1,
        ]);
        $otherReport = Report::factory()->create([
            'player_id' => $otherPlayer->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->deleteJson("/api/game/reports/{$otherReport->id}");

        $response->assertStatus(404);
    }

    public function test_can_create_report()
    {
        $reportData = [
            'player_id' => $this->player->id,
            'type' => 'system',
            'title' => 'System Notification',
            'message' => 'Welcome to the game!',
            'priority' => 'normal',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/game/reports', $reportData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'player_id',
                    'type',
                    'title',
                    'message',
                    'status',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('reports', [
            'player_id' => $this->player->id,
            'type' => 'system',
            'title' => 'System Notification',
        ]);
    }
}
