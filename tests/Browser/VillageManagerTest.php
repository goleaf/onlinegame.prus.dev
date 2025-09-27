<?php

namespace Tests\Browser;

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

        // Create building types
        BuildingType::create([
            'name' => 'Woodcutter',
            'key' => 'woodcutter',
            'max_level' => 20,
            'costs' => json_encode(['wood' => 40, 'clay' => 100, 'iron' => 50, 'crop' => 60]),
            'production' => json_encode(['wood' => 10]),
        ]);
    }

    public function test_village_manager_loads()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village/1')
                ->assertSee('Test Village')
                ->assertSee('(0|0)')
                ->assertSee('100')  // Population
                ->assertSee('1,000');  // Culture Points
        });
    }

    public function test_building_grid_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village/1')
                ->assertSee('Woodcutter')
                ->assertSee('Clay Pit')
                ->assertSee('Iron Mine')
                ->assertSee('Cropland')
                ->assertSee('Lv.1');
        });
    }

    public function test_building_click_opens_modal()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village/1')
                ->click('.building-slot[data-slot="0"]')
                ->waitFor('.modal', 5)
                ->assertSee('Woodcutter')
                ->assertSee('Current Level: 1')
                ->assertSee('Production: +10/hour');
        });
    }

    public function test_empty_slot_click_opens_build_modal()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village/1')
                ->click('.building-slot[data-slot="10"]')
                ->waitFor('.modal', 5)
                ->assertSee('Build New Structure')
                ->assertSee('Available Buildings');
        });
    }

    public function test_building_upgrade_calculation()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village/1')
                ->click('.building-slot[data-slot="0"]')
                ->waitFor('.modal', 5)
                ->assertSee('Upgrade Cost:')
                ->assertSee('40')  // Wood cost
                ->assertSee('100')  // Clay cost
                ->assertSee('50')  // Iron cost
                ->assertSee('60');  // Crop cost
        });
    }

    public function test_building_queue_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/village/1')
                ->assertSee('Building Queue')
                ->assertSee('No buildings in queue');
        });
    }
}
