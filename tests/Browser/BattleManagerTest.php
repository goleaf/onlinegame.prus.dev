<?php

namespace Tests\Browser;

use App\Models\Game\Player;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BattleManagerTest extends DuskTestCase
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

        // Create unit types
        UnitType::create([
            'name' => 'Legionnaire',
            'key' => 'legionnaire',
            'tribe' => 'roman',
            'attack' => 40,
            'defense_infantry' => 35,
            'defense_cavalry' => 50,
            'speed' => 6,
            'carry_capacity' => 50,
            'costs' => json_encode(['wood' => 120, 'clay' => 100, 'iron' => 150, 'crop' => 30]),
        ]);
    }

    public function test_battle_manager_loads()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/battles')
                ->assertSee('Test Village')
                ->assertSee('(0|0)')
                ->assertSee('100');  // Population
        });
    }

    public function test_available_troops_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/battles')
                ->assertSee('Available Troops')
                ->assertSee('0');  // No troops initially
        });
    }

    public function test_attack_form_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/battles')
                ->assertSee('Launch Attack')
                ->assertSee('Target X Coordinate')
                ->assertSee('Target Y Coordinate')
                ->assertSee('Find Target');
        });
    }

    public function test_attack_form_submission()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/battles')
                ->type('#targetX', '10')
                ->type('#targetY', '10')
                ->click('button[wire\:click="selectTarget"]')
                ->waitForText('Target Information', 5)
                ->assertSee('Coordinates: (10, 10)')
                ->assertSee('Distance:')
                ->assertSee('Travel Time:');
        });
    }

    public function test_troop_selection_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/battles')
                ->type('#targetX', '10')
                ->type('#targetY', '10')
                ->click('button[wire\:click="selectTarget"]')
                ->waitForText('Select Troops for Attack', 5)
                ->assertSee('Legionnaire')
                ->assertSee('Available: 0');
        });
    }

    public function test_active_movements_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/battles')
                ->assertSee('Active Movements')
                ->assertSee('No active movements');
        });
    }

    public function test_battle_history_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/battles')
                ->assertSee('Recent Battles')
                ->assertSee('No recent battles');
        });
    }

    public function test_launch_attack_button()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/battles')
                ->type('#targetX', '10')
                ->type('#targetY', '10')
                ->click('button[wire\:click="selectTarget"]')
                ->waitForText('Select Troops for Attack', 5)
                ->assertSee('Launch Attack');
        });
    }
}
