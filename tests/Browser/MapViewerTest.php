<?php

namespace Tests\Browser;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MapViewerTest extends DuskTestCase
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

    public function test_map_viewer_loads()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->assertSee('Map Controls')
                ->assertSee('Center X')
                ->assertSee('Center Y')
                ->assertSee('Map Size')
                ->assertSee('Refresh Map');
        });
    }

    public function test_map_controls_work()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->type('#centerX', '10')
                ->type('#centerY', '10')
                ->select('#mapSize', '10')
                ->click('button[wire\:click="loadMapData"]')
                ->assertInputValue('#centerX', '10')
                ->assertInputValue('#centerY', '10');
        });
    }

    public function test_map_display()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->assertSee('Map (0, 0)')
                ->assertSee('0,0');  // Center coordinates
        });
    }

    public function test_map_navigation_buttons()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->assertSee('↑ North')
                ->assertSee('→ East')
                ->assertSee('↓ South')
                ->assertSee('← West')
                ->assertSee('Center (0,0)')
                ->assertSee('Refresh');
        });
    }

    public function test_map_tile_click()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->click('.map-tile[data-x="0"][data-y="0"]')
                ->waitFor('.modal', 5)
                ->assertSee('Village Details');
        });
    }

    public function test_map_navigation_works()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->click('button:contains("↑ North")')
                ->waitForText('Map (0, -5)', 5)
                ->click('button:contains("→ East")')
                ->waitForText('Map (5, -5)', 5)
                ->click('button:contains("↓ South")')
                ->waitForText('Map (5, 0)', 5)
                ->click('button:contains("← West")')
                ->waitForText('Map (0, 0)', 5);
        });
    }

    public function test_center_map_button()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->click('button:contains("Center (0,0)")')
                ->waitForText('Map (0, 0)', 5);
        });
    }
}
