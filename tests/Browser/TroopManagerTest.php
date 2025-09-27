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

class TroopManagerTest extends DuskTestCase
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

    public function test_troop_manager_loads()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/troops')
                ->assertSee('Test Village')
                ->assertSee('(0|0)')
                ->assertSee('100');  // Population
        });
    }

    public function test_current_troops_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/troops')
                ->assertSee('Available Troops')
                ->assertSee('0');  // No troops initially
        });
    }

    public function test_training_queue_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/troops')
                ->assertSee('Training Queue')
                ->assertSee('No troops in training queue');
        });
    }

    public function test_train_new_troops_section()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/troops')
                ->assertSee('Train New Troops')
                ->assertSee('Legionnaire')
                ->assertSee('Attack: 40')
                ->assertSee('Defense: 35/50')
                ->assertSee('Speed: 6')
                ->assertSee('Carry: 50');
        });
    }

    public function test_troop_training_form()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/troops')
                ->type('input[wire\:model="trainingCount.1"]', '5')
                ->assertInputValue('input[wire\:model="trainingCount.1"]', '5');
        });
    }

    public function test_troop_cost_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/troops')
                ->assertSee('Cost:')
                ->assertSee('120')  // Wood cost
                ->assertSee('100')  // Clay cost
                ->assertSee('150')  // Iron cost
                ->assertSee('30');  // Crop cost
        });
    }
}
