<?php

namespace Tests\Browser;

use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MarketManagerTest extends DuskTestCase
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

        // Create resources
        Resource::create([
            'village_id' => $this->village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10,
            'storage_capacity' => 10000,
            'level' => 1,
        ]);
    }

    public function test_market_manager_loads()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/market')
                ->assertSee('Test Village')
                ->assertSee('(0|0)')
                ->assertSee('100');  // Population
        });
    }

    public function test_current_resources_display()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/market')
                ->assertSee('Current Resources')
                ->assertSee('Wood')
                ->assertSee('Clay')
                ->assertSee('Iron')
                ->assertSee('Crop')
                ->assertSee('1,000');  // Wood amount
        });
    }

    public function test_create_trade_form()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/market')
                ->assertSee('Create New Trade')
                ->assertSee('Offer Resource')
                ->assertSee('Offer Amount')
                ->assertSee('Demand Resource')
                ->assertSee('Demand Amount');
        });
    }

    public function test_trade_form_filling()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/market')
                ->select('#offerType', 'wood')
                ->type('#offerAmount', '100')
                ->select('#demandType', 'clay')
                ->type('#demandAmount', '50')
                ->assertSee('Trade Preview:')
                ->assertSee('100 Wood')
                ->assertSee('50 Clay')
                ->assertSee('Ratio: 1 Wood = 0.50 Clay');
        });
    }

    public function test_my_trades_display()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/market')
                ->assertSee('My Trades')
                ->assertSee('No active trades');
        });
    }

    public function test_available_trades_display()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/market')
                ->assertSee('Available Trades')
                ->assertSee('No available trades');
        });
    }

    public function test_trade_history_display()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/market')
                ->assertSee('Trade History')
                ->assertSee('No trade history');
        });
    }

    public function test_create_trade_button()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game/market')
                ->select('#offerType', 'wood')
                ->type('#offerAmount', '100')
                ->select('#demandType', 'clay')
                ->type('#demandAmount', '50')
                ->assertSee('Create Trade');
        });
    }
}
