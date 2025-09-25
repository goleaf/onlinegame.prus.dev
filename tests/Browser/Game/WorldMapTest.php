<?php

namespace Tests\Browser\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WorldMapTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;
    protected $player;
    protected $village;
    protected $world;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();

        $this->world = World::factory()->create([
            'name' => 'Test World',
            'is_active' => true,
        ]);

        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
            'name' => 'Test Player',
            'tribe' => 'roman',
        ]);

        $this->village = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'name' => 'Test Village',
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'is_capital' => true,
        ]);
    }

    public function test_world_map_loads_successfully()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->assertSee('World Map')
                ->assertSee('Test World')
                ->assertSee('Map Controls')
                ->assertSee('Zoom In')
                ->assertSee('Zoom Out');
        });
    }

    public function test_world_map_shows_villages()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->assertSee('Test Village')
                ->assertSee('(0, 0)')
                ->assertSee('Population: 0');
        });
    }

    public function test_world_map_zoom_controls_work()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->click('@zoom-in-button')
                ->assertSee('Zoom level: 2x')
                ->click('@zoom-out-button')
                ->assertSee('Zoom level: 1x')
                ->click('@reset-zoom-button')
                ->assertSee('Zoom reset to 1x');
        });
    }

    public function test_world_map_navigation_controls_work()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->click('@north-button')
                ->assertSee('Moved north')
                ->click('@south-button')
                ->assertSee('Moved south')
                ->click('@east-button')
                ->assertSee('Moved east')
                ->click('@west-button')
                ->assertSee('Moved west');
        });
    }

    public function test_world_map_village_selection_works()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->click('.village-marker')
                ->waitFor('.village-details')
                ->assertSee('Selected Village')
                ->assertSee('Test Village')
                ->assertSee('Coordinates: (0, 0)')
                ->assertSee('Population: 0')
                ->assertSee('Player: Test Player');
        });
    }

    public function test_world_map_filters_work()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->select('@tribe-filter', 'roman')
                ->assertSee('Filtered by tribe: roman')
                ->click('@clear-filters-button')
                ->assertSee('Filters cleared');
        });
    }

    public function test_world_map_toggle_controls_work()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->click('@toggle-coordinates-button')
                ->assertSee('Coordinates hidden')
                ->click('@toggle-village-names-button')
                ->assertSee('Village names hidden')
                ->click('@toggle-alliances-button')
                ->assertSee('Alliances hidden');
        });
    }

    public function test_world_map_center_on_village_works()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->click('.village-marker')
                ->waitFor('.village-details')
                ->click('@center-on-village-button')
                ->assertSee('Centered on village: Test Village');
        });
    }

    public function test_world_map_shows_map_statistics()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->assertSee('View Center: (0, 0)')
                ->assertSee('Zoom: 1x')
                ->assertSee('Villages: 1');
        });
    }

    public function test_world_map_handles_loading_state()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->click('@refresh-map-button')
                ->waitForText('Loading map data...')
                ->assertSee('Loading map data...');
        });
    }

    public function test_world_map_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/map')
                ->resize(375, 667)  // Mobile size
                ->assertSee('World Map')
                ->assertSee('Map Controls')
                ->resize(1024, 768)  // Tablet size
                ->assertSee('World Map')
                ->resize(1920, 1080)  // Desktop size
                ->assertSee('World Map');
        });
    }
}

