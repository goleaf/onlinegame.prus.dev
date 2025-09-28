<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Player;
use App\Models\Game\World;
use App\Models\User;
use App\Services\GameQueryEnrichService;
use App\Services\ValueObjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraUtilX\Utilities\RateLimiterUtil;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->mock(RateLimiterUtil::class);
        $this->mock(GameQueryEnrichService::class);
        $this->mock(ValueObjectService::class);
    }

    /**
     * @test
     */
    public function it_can_show_dashboard_for_authenticated_user_with_player()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        // Mock GameQueryEnrichService
        GameQueryEnrichService::shouldReceive('getPlayerDashboardData')
            ->with($player->id, $world->id)
            ->andReturn([
                'player' => $player,
                'villages' => [],
                'statistics' => [],
            ]);

        // Mock ValueObjectService
        $valueObjectService = $this->mock(ValueObjectService::class);
        $valueObjectService->shouldReceive('getPlayerStats')->andReturn(new \App\ValueObjects\PlayerStats(
            points: 1000,
            population: 500,
            villagesCount: 1,
            totalAttackPoints: 800,
            totalDefensePoints: 600,
            isActive: true,
            isOnline: false
        ));

        $response = $this->actingAs($user)->get('/game/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('game.dashboard');
        $response->assertViewHas('dashboardData');
    }

    /**
     * @test
     */
    public function it_redirects_to_login_for_unauthenticated_user()
    {
        $response = $this->get('/game/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_shows_no_player_view_when_user_has_no_player()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('game.no-player');
        $response->assertViewHas('user', $user);
    }

    /**
     * @test
     */
    public function it_can_get_player_stats()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        // Mock rate limiter
        $rateLimiter = $this->mock(RateLimiterUtil::class);
        $rateLimiter->shouldReceive('attempt')->andReturn(true);

        // Mock GameQueryEnrichService
        GameQueryEnrichService::shouldReceive('getPlayerDashboardData')
            ->with($player->id)
            ->andReturn(['player' => $player, 'stats' => []]);

        $response = $this->actingAs($user)->get("/game/player/{$player->id}/stats");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'player',
                'stats',
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_returns_rate_limit_error_when_exceeded()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        // Mock rate limiter to return false (rate limited)
        $rateLimiter = $this->mock(RateLimiterUtil::class);
        $rateLimiter->shouldReceive('attempt')->andReturn(false);

        $response = $this->actingAs($user)->get("/game/player/{$player->id}/stats");

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_world_leaderboard()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $players = Player::factory()->count(5)->create(['world_id' => $world->id]);

        // Mock rate limiter
        $rateLimiter = $this->mock(RateLimiterUtil::class);
        $rateLimiter->shouldReceive('attempt')->andReturn(true);

        // Mock GameQueryEnrichService
        GameQueryEnrichService::shouldReceive('getWorldLeaderboard')
            ->with($world->id, 100)
            ->andReturn(collect($players));

        $response = $this->actingAs($user)->get("/game/world/{$world->id}/leaderboard");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_building_stats()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        // Mock GameQueryEnrichService
        GameQueryEnrichService::shouldReceive('getBuildingStatistics')
            ->with($player->id)
            ->andReturn(collect([]));

        $response = $this->actingAs($user)->get("/game/player/{$player->id}/building-stats");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_resource_warnings()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        // Mock GameQueryEnrichService
        GameQueryEnrichService::shouldReceive('getResourceCapacityWarnings')
            ->with($player->id, 24)
            ->andReturn(collect([]));

        $response = $this->actingAs($user)->get("/game/player/{$player->id}/resource-warnings");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
    }

    /**
     * @test
     */
    public function it_can_show_village_view()
    {
        $user = User::factory()->create();
        $village = \App\Models\Game\Village::factory()->create();

        $response = $this->actingAs($user)->get('/game/village');

        $response->assertStatus(200);
        $response->assertViewIs('game.village');
    }

    /**
     * @test
     */
    public function it_can_show_troops_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/troops');

        $response->assertStatus(200);
        $response->assertViewIs('game.troops');
    }

    /**
     * @test
     */
    public function it_can_show_movements_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/movements');

        $response->assertStatus(200);
        $response->assertViewIs('game.movements');
    }

    /**
     * @test
     */
    public function it_can_show_alliance_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/alliance');

        $response->assertStatus(200);
        $response->assertViewIs('game.alliance');
    }

    /**
     * @test
     */
    public function it_can_show_quests_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/quests');

        $response->assertStatus(200);
        $response->assertViewIs('game.quests');
    }

    /**
     * @test
     */
    public function it_can_show_technology_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/technology');

        $response->assertStatus(200);
        $response->assertViewIs('game.technology');
    }

    /**
     * @test
     */
    public function it_can_show_reports_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/reports');

        $response->assertStatus(200);
        $response->assertViewIs('game.reports');
    }

    /**
     * @test
     */
    public function it_can_show_map_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/map');

        $response->assertStatus(200);
        $response->assertViewIs('game.map');
    }

    /**
     * @test
     */
    public function it_can_show_statistics_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/statistics');

        $response->assertStatus(200);
        $response->assertViewIs('game.statistics');
    }

    /**
     * @test
     */
    public function it_can_show_real_time_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/real-time');

        $response->assertStatus(200);
        $response->assertViewIs('game.real-time');
    }

    /**
     * @test
     */
    public function it_can_show_battles_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/battles');

        $response->assertStatus(200);
        $response->assertViewIs('game.battles');
    }

    /**
     * @test
     */
    public function it_can_show_market_view()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/market');

        $response->assertStatus(200);
        $response->assertViewIs('game.market');
    }

    /**
     * @test
     */
    public function it_handles_dashboard_error_gracefully()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        // Mock GameQueryEnrichService to throw an exception
        GameQueryEnrichService::shouldReceive('getPlayerDashboardData')
            ->andThrow(new \Exception('Test error'));

        $response = $this->actingAs($user)->get('/game/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('game.error');
        $response->assertViewHas('error', 'Test error');
    }

    /**
     * @test
     */
    public function it_handles_player_stats_error_gracefully()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        // Mock rate limiter
        $rateLimiter = $this->mock(RateLimiterUtil::class);
        $rateLimiter->shouldReceive('attempt')->andReturn(true);

        // Mock GameQueryEnrichService to throw an exception
        GameQueryEnrichService::shouldReceive('getPlayerDashboardData')
            ->andThrow(new \Exception('Test error'));

        $response = $this->actingAs($user)->get("/game/player/{$player->id}/stats");

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to retrieve player statistics.',
        ]);
    }

    /**
     * @test
     */
    public function it_handles_world_leaderboard_error_gracefully()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();

        // Mock rate limiter
        $rateLimiter = $this->mock(RateLimiterUtil::class);
        $rateLimiter->shouldReceive('attempt')->andReturn(true);

        // Mock GameQueryEnrichService to throw an exception
        GameQueryEnrichService::shouldReceive('getWorldLeaderboard')
            ->andThrow(new \Exception('Test error'));

        $response = $this->actingAs($user)->get("/game/world/{$world->id}/leaderboard");

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to retrieve world leaderboard.',
        ]);
    }

    /**
     * @test
     */
    public function it_handles_building_stats_error_gracefully()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        // Mock GameQueryEnrichService to throw an exception
        GameQueryEnrichService::shouldReceive('getBuildingStatistics')
            ->andThrow(new \Exception('Test error'));

        $response = $this->actingAs($user)->get("/game/player/{$player->id}/building-stats");

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to retrieve building statistics.',
        ]);
    }

    /**
     * @test
     */
    public function it_handles_resource_warnings_error_gracefully()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        // Mock GameQueryEnrichService to throw an exception
        GameQueryEnrichService::shouldReceive('getResourceCapacityWarnings')
            ->andThrow(new \Exception('Test error'));

        $response = $this->actingAs($user)->get("/game/player/{$player->id}/resource-warnings");

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to retrieve resource warnings.',
        ]);
    }
}
