<?php

namespace Tests\Feature\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GameIntegrationControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected Player $player;

    protected Village $village;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $this->village = Village::factory()->create([
            'player_id' => $this->player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_player_dashboard_data()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game-integration/dashboard');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'player' => [
                        'id',
                        'name',
                        'experience',
                        'level',
                    ],
                    'villages',
                    'active_queues',
                    'market_offers',
                    'statistics',
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_get_village_overview()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/api/game-integration/village/{$this->village->id}/overview");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'village' => [
                        'id',
                        'name',
                        'population',
                        'coordinates',
                    ],
                    'buildings',
                    'queues',
                    'resources',
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_get_player_statistics()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game-integration/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'player_stats',
                    'battle_stats',
                    'quest_stats',
                    'alliance_stats',
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_get_system_status()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game-integration/system-status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'system_status',
                    'server_info',
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/game-integration/dashboard');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_handles_village_not_found()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game-integration/village/99999/overview');

        $response
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Village not found',
            ]);
    }
}
