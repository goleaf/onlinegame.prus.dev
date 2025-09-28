<?php

namespace Tests\Browser\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class GameDashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;

    protected $player;

    protected $village;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();

        $world = World::factory()->create([
            'name' => 'Test World',
            'is_active' => true,
        ]);

        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $world->id,
            'name' => 'Test Player',
            'tribe' => 'roman',
        ]);

        $this->village = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $world->id,
            'name' => 'Test Village',
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'is_capital' => true,
        ]);
    }

    public function test_game_dashboard_loads_successfully()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('Game Dashboard')
                ->assertSee('Test Player')
                ->assertSee('Test Village')
                ->assertSee('Resources')
                ->assertSee('Buildings')
                ->assertSee('World Map');
        });
    }

    public function test_game_dashboard_shows_player_stats()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('Total Villages: 1')
                ->assertSee('Total Points: 0')
                ->assertSee('Population: 0')
                ->assertSee('Tribe: Roman');
        });
    }

    public function test_game_dashboard_shows_village_resources()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('Wood: 1,000')
                ->assertSee('Clay: 1,000')
                ->assertSee('Iron: 1,000')
                ->assertSee('Crop: 1,000');
        });
    }

    public function test_game_dashboard_navigation_works()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->clickLink('Village Manager')
                ->assertPathIs('/game/village')
                ->assertSee('Village Manager')
                ->back()
                ->clickLink('World Map')
                ->assertPathIs('/game/map')
                ->assertSee('World Map');
        });
    }

    public function test_game_dashboard_refresh_button_works()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->click('@refresh-button')
                ->waitForText('Game data refreshed')
                ->assertSee('Game data refreshed');
        });
    }

    public function test_game_dashboard_shows_notifications()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->click('@refresh-button')
                ->waitForText('Game data refreshed')
                ->assertSee('Game data refreshed');
        });
    }

    public function test_game_dashboard_handles_errors_gracefully()
    {
        // Simulate an error by making the player inactive
        $this->player->update(['is_active' => false]);

        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('Player not found or inactive')
                ->assertSee('Please contact support');
        });
    }

    public function test_game_dashboard_responsive_design()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->resize(375, 667)  // Mobile size
                ->assertSee('Game Dashboard')
                ->assertSee('Test Player')
                ->resize(1024, 768)  // Tablet size
                ->assertSee('Game Dashboard')
                ->resize(1920, 1080)  // Desktop size
                ->assertSee('Game Dashboard');
        });
    }
}
