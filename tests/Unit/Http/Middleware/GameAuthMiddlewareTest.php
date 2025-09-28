<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\GameAuthMiddleware;
use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class GameAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private GameAuthMiddleware $middleware;

    private User $user;

    private Player $player;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new GameAuthMiddleware();
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * @test
     */
    public function it_allows_authenticated_user_with_player()
    {
        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * @test
     */
    public function it_redirects_unauthenticated_user()
    {
        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () {
            return null;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContains('login', $response->headers->get('Location'));
    }

    /**
     * @test
     */
    public function it_redirects_user_without_player()
    {
        $userWithoutPlayer = User::factory()->create();

        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () use ($userWithoutPlayer) {
            return $userWithoutPlayer;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContains('character-creation', $response->headers->get('Location'));
    }

    /**
     * @test
     */
    public function it_allows_ajax_request_for_unauthenticated_user()
    {
        $request = Request::create('/api/game', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setUserResolver(function () {
            return null;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /**
     * @test
     */
    public function it_allows_ajax_request_for_user_without_player()
    {
        $userWithoutPlayer = User::factory()->create();

        $request = Request::create('/api/game', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setUserResolver(function () use ($userWithoutPlayer) {
            return $userWithoutPlayer;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /**
     * @test
     */
    public function it_sets_player_in_request_for_authenticated_user()
    {
        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertInstanceOf(Player::class, $req->attributes->get('player'));
            $this->assertEquals($this->player->id, $req->attributes->get('player')->id);

            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_banned_user()
    {
        $bannedUser = User::factory()->create(['banned_at' => now()]);
        $bannedPlayer = Player::factory()->create(['user_id' => $bannedUser->id]);

        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () use ($bannedUser) {
            return $bannedUser;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContains('banned', $response->getContent());
    }

    /**
     * @test
     */
    public function it_handles_inactive_user()
    {
        $inactiveUser = User::factory()->create(['email_verified_at' => null]);
        $inactivePlayer = Player::factory()->create(['user_id' => $inactiveUser->id]);

        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () use ($inactiveUser) {
            return $inactiveUser;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContains('verify-email', $response->headers->get('Location'));
    }

    /**
     * @test
     */
    public function it_handles_maintenance_mode()
    {
        config(['game.maintenance_mode' => true]);

        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertStringContains('maintenance', $response->getContent());
    }

    /**
     * @test
     */
    public function it_allows_admin_during_maintenance()
    {
        config(['game.maintenance_mode' => true]);
        $adminUser = User::factory()->create(['is_admin' => true]);
        $adminPlayer = Player::factory()->create(['user_id' => $adminUser->id]);

        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * @test
     */
    public function it_handles_player_not_found()
    {
        $userWithoutPlayer = User::factory()->create();
        Player::where('user_id', $userWithoutPlayer->id)->delete();

        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () use ($userWithoutPlayer) {
            return $userWithoutPlayer;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContains('character-creation', $response->headers->get('Location'));
    }

    /**
     * @test
     */
    public function it_caches_player_lookup()
    {
        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // First request
        $response1 = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        // Second request with same user
        $response2 = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_multiple_players_for_user()
    {
        $player2 = Player::factory()->create(['user_id' => $this->user->id]);

        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            $player = $req->attributes->get('player');
            $this->assertInstanceOf(Player::class, $player);

            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_exception_gracefully()
    {
        $request = Request::create('/game', 'GET');
        $request->setUserResolver(function (): void {
            throw new \Exception('Database error');
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContains('error', $response->getContent());
    }
}
