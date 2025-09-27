<?php

namespace Tests\Browser\Game;

use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class VillageManagerTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;
    protected $player;
    protected $village;
    protected $buildingType;

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

        $this->buildingType = BuildingType::factory()->create([
            'name' => 'Woodcutter',
            'key' => 'woodcutter',
            'category' => 'resource',
            'max_level' => 20,
        ]);
    }

    public function test_village_manager_loads_successfully()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village')
                ->assertSee('Village Manager')
                ->assertSee('Test Village')
                ->assertSee('Building Grid')
                ->assertSee('Resources');
        });
    }

    public function test_village_manager_shows_building_grid()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village')
                ->assertSee('Building Grid')
                ->assertSee('Empty')
                ->assertSee('36 slots');
        });
    }

    public function test_village_manager_shows_existing_buildings()
    {
        // Create a building
        Building::create([
            'village_id' => $this->village->id,
            'building_type_id' => $this->buildingType->id,
            'level' => 1,
            'position' => 0,
        ]);

        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village')
                ->assertSee('Woodcutter')
                ->assertSee('Level 1');
        });
    }

    public function test_village_manager_building_click_opens_modal()
    {
        // Create a building
        Building::create([
            'village_id' => $this->village->id,
            'building_type_id' => $this->buildingType->id,
            'level' => 1,
            'position' => 0,
        ]);

        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village')
                ->click('.building-slot.occupied')
                ->waitFor('.modal')
                ->assertSee('Woodcutter (Level 1)')
                ->assertSee('Upgrade')
                ->assertSee('Costs');
        });
    }

    public function test_village_manager_upgrade_building()
    {
        // Create a building
        Building::create([
            'village_id' => $this->village->id,
            'building_type_id' => $this->buildingType->id,
            'level' => 1,
            'position' => 0,
        ]);

        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village')
                ->click('.building-slot.occupied')
                ->waitFor('.modal')
                ->click('@upgrade-button')
                ->waitForText('Building upgrade started')
                ->assertSee('Building upgrade started');
        });
    }

    public function test_village_manager_shows_resources()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village')
                ->assertSee('Wood: 1,000')
                ->assertSee('Clay: 1,000')
                ->assertSee('Iron: 1,000')
                ->assertSee('Crop: 1,000');
        });
    }

    public function test_village_manager_refresh_button_works()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village')
                ->click('@refresh-button')
                ->waitForText('Village data refreshed')
                ->assertSee('Village data refreshed');
        });
    }

    public function test_village_manager_handles_insufficient_resources()
    {
        // Create a building
        Building::create([
            'village_id' => $this->village->id,
            'building_type_id' => $this->buildingType->id,
            'level' => 1,
            'position' => 0,
        ]);

        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village')
                ->click('.building-slot.occupied')
                ->waitFor('.modal')
                ->click('@upgrade-button')
                ->waitForText('Insufficient resources')
                ->assertSee('Insufficient resources');
        });
    }

    public function test_village_manager_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village')
                ->resize(375, 667)  // Mobile size
                ->assertSee('Village Manager')
                ->assertSee('Building Grid')
                ->resize(1024, 768)  // Tablet size
                ->assertSee('Village Manager')
                ->resize(1920, 1080)  // Desktop size
                ->assertSee('Village Manager');
        });
    }
}
