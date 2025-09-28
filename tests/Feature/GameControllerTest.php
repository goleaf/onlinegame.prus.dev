<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function it_can_show_game_dashboard()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/dashboard');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.dashboard');
    }

    /**
     * @test
     */
    public function it_can_show_game_worlds()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/worlds');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.worlds');
    }

    /**
     * @test
     */
    public function it_can_show_game_world_details()
    {
        $this->actingAs($this->user);

        $worldId = 1;

        $response = $this->get("/game/worlds/{$worldId}");

        $response
            ->assertStatus(200)
            ->assertViewIs('game.world-details')
            ->assertViewHas('worldId', $worldId);
    }

    /**
     * @test
     */
    public function it_can_show_game_village()
    {
        $this->actingAs($this->user);

        $villageId = 1;

        $response = $this->get("/game/village/{$villageId}");

        $response
            ->assertStatus(200)
            ->assertViewIs('game.village')
            ->assertViewHas('villageId', $villageId);
    }

    /**
     * @test
     */
    public function it_can_show_game_alliance()
    {
        $this->actingAs($this->user);

        $allianceId = 1;

        $response = $this->get("/game/alliance/{$allianceId}");

        $response
            ->assertStatus(200)
            ->assertViewIs('game.alliance')
            ->assertViewHas('allianceId', $allianceId);
    }

    /**
     * @test
     */
    public function it_can_show_game_market()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/market');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.market');
    }

    /**
     * @test
     */
    public function it_can_show_game_reports()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/reports');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.reports');
    }

    /**
     * @test
     */
    public function it_can_show_game_messages()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/messages');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.messages');
    }

    /**
     * @test
     */
    public function it_can_show_game_artifacts()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/artifacts');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.artifacts');
    }

    /**
     * @test
     */
    public function it_can_show_game_quests()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/quests');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.quests');
    }

    /**
     * @test
     */
    public function it_can_show_game_buildings()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/buildings');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.buildings');
    }

    /**
     * @test
     */
    public function it_can_show_game_troops()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/troops');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.troops');
    }

    /**
     * @test
     */
    public function it_can_show_game_battles()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/battles');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.battles');
    }

    /**
     * @test
     */
    public function it_can_show_game_notifications()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/notifications');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.notifications');
    }

    /**
     * @test
     */
    public function it_can_show_game_tasks()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/tasks');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.tasks');
    }

    /**
     * @test
     */
    public function it_can_show_game_files()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/files');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.files');
    }

    /**
     * @test
     */
    public function it_can_show_game_system()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/system');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.system');
    }

    /**
     * @test
     */
    public function it_can_show_game_larautilx()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/larautilx');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.larautilx');
    }

    /**
     * @test
     */
    public function it_can_show_game_larautilx_dashboard()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/larautilx/dashboard');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.larautilx-dashboard');
    }

    /**
     * @test
     */
    public function it_can_show_game_secure()
    {
        $this->actingAs($this->user);

        $response = $this->get('/game/secure');

        $response
            ->assertStatus(200)
            ->assertViewIs('game.secure');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_dashboard()
    {
        $response = $this->get('/game/dashboard');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_worlds()
    {
        $response = $this->get('/game/worlds');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_world_details()
    {
        $response = $this->get('/game/worlds/1');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_village()
    {
        $response = $this->get('/game/village/1');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_alliance()
    {
        $response = $this->get('/game/alliance/1');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_market()
    {
        $response = $this->get('/game/market');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_reports()
    {
        $response = $this->get('/game/reports');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_messages()
    {
        $response = $this->get('/game/messages');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_artifacts()
    {
        $response = $this->get('/game/artifacts');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_quests()
    {
        $response = $this->get('/game/quests');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_buildings()
    {
        $response = $this->get('/game/buildings');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_troops()
    {
        $response = $this->get('/game/troops');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_battles()
    {
        $response = $this->get('/game/battles');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_notifications()
    {
        $response = $this->get('/game/notifications');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_tasks()
    {
        $response = $this->get('/game/tasks');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_files()
    {
        $response = $this->get('/game/files');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_system()
    {
        $response = $this->get('/game/system');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_larautilx()
    {
        $response = $this->get('/game/larautilx');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_larautilx_dashboard()
    {
        $response = $this->get('/game/larautilx/dashboard');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_secure()
    {
        $response = $this->get('/game/secure');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }
}
