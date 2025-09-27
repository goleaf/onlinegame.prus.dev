<?php

namespace Tests\Feature\Game;

use App\Models\Game\Report;
use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Player $player;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function it_can_list_reports()
    {
        $reports = Report::factory()->count(3)->create([
            'player_id' => $this->player->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'player_id',
                        'type',
                        'title',
                        'content',
                        'status',
                        'priority',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ]
            ]);
    }

    /** @test */
    public function it_can_filter_reports_by_type()
    {
        Report::factory()->create([
            'player_id' => $this->player->id,
            'type' => 'battle',
        ]);

        Report::factory()->create([
            'player_id' => $this->player->id,
            'type' => 'resource',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/reports?type=battle');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('battle', $responseData[0]['type']);
    }

    /** @test */
    public function it_can_filter_reports_by_status()
    {
        Report::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'unread',
        ]);

        Report::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'read',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/reports?status=unread');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('unread', $responseData[0]['status']);
    }

    /** @test */
    public function it_can_create_a_report()
    {
        $reportData = [
            'player_id' => $this->player->id,
            'type' => 'battle',
            'title' => 'Battle Report: Victory',
            'content' => 'You successfully attacked PlayerTwo\'s village',
            'status' => 'unread',
            'priority' => 'medium',
            'is_important' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/game/api/reports', $reportData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'player_id',
                    'type',
                    'title',
                    'content',
                    'status',
                    'priority',
                    'is_important',
                ]
            ]);

        $this->assertDatabaseHas('reports', [
            'player_id' => $this->player->id,
            'type' => 'battle',
            'title' => 'Battle Report: Victory',
            'status' => 'unread',
        ]);
    }

    /** @test */
    public function it_validates_report_creation_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/game/api/reports', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'player_id',
                'type',
                'title',
                'content',
                'status',
                'priority',
            ]);
    }

    /** @test */
    public function it_can_show_a_report()
    {
        $report = Report::factory()->create([
            'player_id' => $this->player->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/game/api/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'player_id',
                    'type',
                    'title',
                    'content',
                    'status',
                    'priority',
                    'player',
                ]
            ]);
    }

    /** @test */
    public function it_can_update_a_report()
    {
        $report = Report::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'unread',
        ]);

        $updateData = [
            'status' => 'read',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/game/api/reports/{$report->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'read',
                ]
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'read',
        ]);
    }

    /** @test */
    public function it_can_delete_a_report()
    {
        $report = Report::factory()->create([
            'player_id' => $this->player->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/game/api/reports/{$report->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('reports', [
            'id' => $report->id,
        ]);
    }

    /** @test */
    public function it_can_search_reports()
    {
        Report::factory()->create([
            'player_id' => $this->player->id,
            'title' => 'Battle Report: Victory',
            'content' => 'You successfully attacked PlayerTwo\'s village',
        ]);

        Report::factory()->create([
            'player_id' => $this->player->id,
            'title' => 'Resource Update',
            'content' => 'Your resources have been updated',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/reports?search=Victory');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertStringContainsString('Victory', $responseData[0]['title']);
    }

    /** @test */
    public function it_can_filter_reports_by_priority()
    {
        Report::factory()->create([
            'player_id' => $this->player->id,
            'priority' => 'high',
        ]);

        Report::factory()->create([
            'player_id' => $this->player->id,
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/reports?priority=high');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('high', $responseData[0]['priority']);
    }
}
