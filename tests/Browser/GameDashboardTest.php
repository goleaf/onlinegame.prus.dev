<?php

namespace Tests\Browser;

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

    protected $world;

    protected $village;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create([
            'name' => 'Test Player',
            'email' => 'test@example.com',
        ]);

        $this->world = World::create([
            'name' => 'Test World',
            'description' => 'Test World Description',
            'is_active' => true,
        ]);

        $this->player = Player::create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
            'name' => 'Test Player',
            'tribe' => 'roman',
            'points' => 1000,
            'is_online' => true,
            'last_active_at' => now(),
        ]);

        $this->village = Village::create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'name' => 'Test Village',
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'population' => 100,
            'culture_points' => 1000,
            'is_capital' => true,
        ]);
    }

    public function test_game_dashboard_loads()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('Travian Online')
                ->assertSee('Test Player')
                ->assertSee('Test Village')
                ->assertSee('Wood')
                ->assertSee('Clay')
                ->assertSee('Iron')
                ->assertSee('Crop');
        });
    }

    public function test_navigation_works()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->clickLink('Village')
                ->assertPathIs('/game/village/1')
                ->back()
                ->clickLink('Troops')
                ->assertPathIs('/game/troops')
                ->back()
                ->clickLink('Map')
                ->assertPathIs('/game/map');
        });
    }

    public function test_real_time_updates()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('Live Updates Active')
                ->waitForText('Game updates every 5 seconds', 10)
                ->assertSee('Real-time indicator');
        });
    }

    public function test_resource_display()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('1,000')  // Wood amount
                ->assertSee('1,000')  // Clay amount
                ->assertSee('1,000')  // Iron amount
                ->assertSee('1,000')  // Crop amount
                ->assertSee('100');  // Population
        });
    }

    public function test_building_grid_display()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('Woodcutter')
                ->assertSee('Clay Pit')
                ->assertSee('Iron Mine')
                ->assertSee('Cropland');
        });
    }

    public function test_player_info_display()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('Test Player')
                ->assertSee('Roman')
                ->assertSee('1,234')  // Points
                ->assertSee('3');  // Villages
        });
    }
}
