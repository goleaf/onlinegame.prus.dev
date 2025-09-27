<?php

namespace Tests\Feature\Game;

use App\Models\Game\Player;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use LaraUtilX\Utilities\RateLimiterUtil;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Player $player;
    protected World $world;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->world = World::factory()->create(['is_active' => true]);
        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
            'is_active' => true,
        ]);

        // Mock rate limiter
        $this->mock(RateLimiterUtil::class, function ($mock) {
            $mock->shouldReceive('attempt')->andReturn(true);
        });
    }

    /**
     * @test
     */
    public function it_can_access_dashboard()
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/game/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('game.dashboard');
        $response->assertViewHas('dashboardData');
    }

    /**
     * @test
     */
    public function it_redirects_unauthenticated_users_to_login()
    {
        $response = $this->get('/game/dashboard');

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function it_shows_no_player_page_when_user_has_no_player()
    {
        // Create user without player
        $userWithoutPlayer = User::factory()->create();

        $response = $this
            ->actingAs($userWithoutPlayer)
            ->get('/game/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('game.no-player');
        $response->assertViewHas('user', $userWithoutPlayer);
    }

    /**
     * @test
     */
    public function it_can_get_player_statistics()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/stats");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'player_id',
                    'world_id',
                    'villages',
                    'resources',
                    'buildings',
                    'troops',
                    'alliance',
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_world_leaderboard()
    {
        // Create additional players for leaderboard
        $otherPlayers = Player::factory()->count(5)->create([
            'world_id' => $this->world->id,
            'points' => $this->faker->numberBetween(100, 1000),
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/world/{$this->world->id}/leaderboard");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'points',
                        'rank',
                        'villages_count',
                        'alliance_name',
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_building_statistics()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/buildings");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'player_id',
                    'buildings' => [
                        '*' => [
                            'building_type',
                            'level',
                            'count',
                            'total_production',
                        ]
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_resource_warnings()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/resource-warnings");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'player_id',
                    'warnings' => [
                        '*' => [
                            'village_id',
                            'resource_type',
                            'current_amount',
                            'capacity',
                            'warning_type',
                            'hours_until_full',
                        ]
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_handles_dashboard_errors_gracefully()
    {
        // Mock an error in the dashboard data loading
        $this->mock(\App\Services\GameQueryEnrichService::class, function ($mock) {
            $mock->shouldReceive('getPlayerDashboardData')->andThrow(new \Exception('Database error'));
        });

        $response = $this
            ->actingAs($this->user)
            ->get('/game/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('game.error');
        $response->assertViewHas('error', 'Database error');
    }

    /**
     * @test
     */
    public function it_handles_api_errors_gracefully()
    {
        // Mock an error in the API
        $this->mock(\App\Services\GameQueryEnrichService::class, function ($mock) {
            $mock->shouldReceive('getPlayerDashboardData')->andThrow(new \Exception('API error'));
        });

        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/stats");

        $response
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to retrieve player statistics.',
            ]);
    }

    /**
     * @test
     */
    public function it_respects_rate_limiting_for_player_stats()
    {
        // Mock rate limiter to return false
        $this->mock(RateLimiterUtil::class, function ($mock) {
            $mock->shouldReceive('attempt')->andReturn(false);
        });

        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/stats");

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ]);
    }

    /**
     * @test
     */
    public function it_respects_rate_limiting_for_leaderboard()
    {
        // Mock rate limiter to return false
        $this->mock(RateLimiterUtil::class, function ($mock) {
            $mock->shouldReceive('attempt')->andReturn(false);
        });

        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/world/{$this->world->id}/leaderboard");

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ]);
    }

    /**
     * @test
     */
    public function it_caches_player_statistics()
    {
        // First request
        $response1 = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/stats");

        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/stats");

        $response2->assertStatus(200);

        // Both responses should be identical (cached)
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * @test
     */
    public function it_caches_world_leaderboard()
    {
        // Create additional players for leaderboard
        Player::factory()->count(3)->create([
            'world_id' => $this->world->id,
            'points' => $this->faker->numberBetween(100, 1000),
        ]);

        // First request
        $response1 = $this
            ->actingAs($this->user)
            ->getJson("/game/api/world/{$this->world->id}/leaderboard");

        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this
            ->actingAs($this->user)
            ->getJson("/game/api/world/{$this->world->id}/leaderboard");

        $response2->assertStatus(200);

        // Both responses should be identical (cached)
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * @test
     */
    public function it_caches_building_statistics()
    {
        // First request
        $response1 = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/buildings");

        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/buildings");

        $response2->assertStatus(200);

        // Both responses should be identical (cached)
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * @test
     */
    public function it_caches_resource_warnings()
    {
        // First request
        $response1 = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/resource-warnings");

        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this
            ->actingAs($this->user)
            ->getJson("/game/api/player/{$this->player->id}/resource-warnings");

        $response2->assertStatus(200);

        // Both responses should be identical (cached)
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * @test
     */
    public function it_caches_dashboard_data()
    {
        // First request
        $response1 = $this
            ->actingAs($this->user)
            ->get('/game/dashboard');

        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this
            ->actingAs($this->user)
            ->get('/game/dashboard');

        $response2->assertStatus(200);

        // Both responses should have the same dashboard data (cached)
        $this->assertEquals(
            $response1->viewData('dashboardData'),
            $response2->viewData('dashboardData')
        );
    }

    /**
     * @test
     */
    public function it_includes_performance_metrics_in_dashboard()
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/game/dashboard');

        $response->assertStatus(200);

        $dashboardData = $response->viewData('dashboardData');

        $this->assertArrayHasKey('query_time_ms', $dashboardData);
        $this->assertArrayHasKey('total_time_ms', $dashboardData);
        $this->assertIsNumeric($dashboardData['query_time_ms']);
        $this->assertIsNumeric($dashboardData['total_time_ms']);
    }

    /**
     * @test
     */
    public function it_includes_value_objects_integration_in_dashboard()
    {
        $response = $this
            ->actingAs($this->user)
            ->get('/game/dashboard');

        $response->assertStatus(200);

        $dashboardData = $response->viewData('dashboardData');

        $this->assertArrayHasKey('value_objects_integration', $dashboardData);
        $this->assertTrue($dashboardData['value_objects_integration']);
    }

    /**
     * @test
     */
    public function it_handles_missing_player_gracefully_in_api()
    {
        // Create user without player
        $userWithoutPlayer = User::factory()->create();

        $response = $this
            ->actingAs($userWithoutPlayer)
            ->getJson('/game/api/player/999/stats');

        $response->assertStatus(500);
    }

    /**
     * @test
     */
    public function it_handles_invalid_world_id_gracefully()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/world/999/leaderboard');

        $response->assertStatus(500);
    }

    /**
     * @test
     */
    public function it_handles_invalid_player_id_gracefully()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/player/999/buildings');

        $response->assertStatus(500);
    }
}
