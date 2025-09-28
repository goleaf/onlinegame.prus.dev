<?php

namespace Tests\Browser;

use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\Quest;
use App\Models\Game\Resource;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class GameIntegrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;

    protected $player;

    protected $world;

    protected $village;

    protected function setUp(): void
    {
        parent::setUp();

        // Create comprehensive test data
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

        // Create quests
        Quest::create([
            'name' => 'First Steps',
            'key' => 'first_steps',
            'description' => 'Build your first building',
            'type' => 'tutorial',
            'requirements' => json_encode(['buildings' => 1]),
            'rewards' => json_encode(['experience' => 100, 'wood' => 500]),
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

    public function test_complete_game_flow()
    {
        $this->browse(function (Browser $browser): void {
            // 1. Login and access game dashboard
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('Travian Online')
                ->assertSee('Test Player')
                ->assertSee('Test Village')
                ->assertSee('Live Updates Active');

            // 2. Navigate to village management
            $browser
                ->clickLink('Village')
                ->assertPathIs('/game/village/1')
                ->assertSee('Test Village')
                ->assertSee('Woodcutter')
                ->assertSee('Clay Pit')
                ->assertSee('Iron Mine')
                ->assertSee('Cropland');

            // 3. Test building interaction
            $browser
                ->click('.building-slot[data-slot="0"]')
                ->waitFor('.modal', 5)
                ->assertSee('Woodcutter')
                ->assertSee('Current Level: 1')
                ->assertSee('Production: +10/hour')
                ->click('.btn-close');

            // 4. Navigate to troop management
            $browser
                ->clickLink('Troops')
                ->assertPathIs('/game/troops')
                ->assertSee('Available Troops')
                ->assertSee('Legionnaire')
                ->assertSee('Attack: 40')
                ->assertSee('Defense: 35/50');

            // 5. Navigate to map
            $browser
                ->clickLink('Map')
                ->assertPathIs('/game/map')
                ->assertSee('Map Controls')
                ->assertSee('Center X')
                ->assertSee('Center Y')
                ->assertSee('Map Size');

            // 6. Test map navigation
            $browser
                ->type('#centerX', '5')
                ->type('#centerY', '5')
                ->select('#mapSize', '10')
                ->click('button[wire\:click="loadMapData"]')
                ->assertInputValue('#centerX', '5')
                ->assertInputValue('#centerY', '5');

            // 7. Navigate to battles
            $browser
                ->clickLink('Battles')
                ->assertPathIs('/game/battles')
                ->assertSee('Available Troops')
                ->assertSee('Launch Attack')
                ->assertSee('Target X Coordinate')
                ->assertSee('Target Y Coordinate');

            // 8. Test attack form
            $browser
                ->type('#targetX', '10')
                ->type('#targetY', '10')
                ->click('button[wire\:click="selectTarget"]')
                ->waitForText('Target Information', 5)
                ->assertSee('Coordinates: (10, 10)');

            // 9. Navigate to market
            $browser
                ->clickLink('Market')
                ->assertPathIs('/game/market')
                ->assertSee('Current Resources')
                ->assertSee('Create New Trade')
                ->assertSee('Wood')
                ->assertSee('Clay')
                ->assertSee('Iron')
                ->assertSee('Crop');

            // 10. Test trade form
            $browser
                ->select('#offerType', 'wood')
                ->type('#offerAmount', '100')
                ->select('#demandType', 'clay')
                ->type('#demandAmount', '50')
                ->assertSee('Trade Preview:')
                ->assertSee('100 Wood')
                ->assertSee('50 Clay');

            // 11. Navigate to quests
            $browser
                ->clickLink('Quests')
                ->assertPathIs('/game/quests')
                ->assertSee('Quest Progress')
                ->assertSee('Available Quests')
                ->assertSee('First Steps')
                ->assertSee('Build your first building');

            // 12. Test quest interaction
            $browser
                ->click('button:contains("Start Quest")')
                ->waitFor('.modal', 5)
                ->assertSee('First Steps')
                ->assertSee('Build your first building')
                ->assertSee('Requirements')
                ->assertSee('Rewards')
                ->click('.btn-close');

            // 13. Return to dashboard
            $browser
                ->clickLink('Dashboard')
                ->assertPathIs('/game')
                ->assertSee('Travian Online')
                ->assertSee('Test Player');
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

    public function test_navigation_consistency()
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->loginAs($this->user)
                ->visit('/game');

            // Test all navigation links
            $navigationLinks = [
                'Village' => '/game/village/1',
                'Troops' => '/game/troops',
                'Movements' => '/game/movements',
                'Alliance' => '/game/alliance',
                'Quests' => '/game/quests',
                'Technology' => '/game/technology',
                'Reports' => '/game/reports',
                'Map' => '/game/map',
                'Statistics' => '/game/statistics',
                'Battles' => '/game/battles',
                'Market' => '/game/market',
            ];

            foreach ($navigationLinks as $linkText => $expectedPath) {
                $browser
                    ->clickLink($linkText)
                    ->assertPathIs($expectedPath)
                    ->back();
            }
        });
    }

    public function test_responsive_design()
    {
        $this->browse(function (Browser $browser): void {
            // Test mobile viewport
            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->resize(375, 667)  // iPhone SE
                ->assertSee('Travian Online')
                ->assertSee('Test Player');

            // Test tablet viewport
            $browser
                ->resize(768, 1024)  // iPad
                ->assertSee('Travian Online')
                ->assertSee('Test Player');

            // Test desktop viewport
            $browser
                ->resize(1920, 1080)  // Desktop
                ->assertSee('Travian Online')
                ->assertSee('Test Player');
        });
    }

    public function test_game_performance()
    {
        $this->browse(function (Browser $browser): void {
            $startTime = microtime(true);

            $browser
                ->loginAs($this->user)
                ->visit('/game')
                ->assertSee('Travian Online')
                ->assertSee('Test Player');

            $endTime = microtime(true);
            $loadTime = $endTime - $startTime;

            // Assert page loads within 5 seconds
            $this->assertLessThan(5, $loadTime, 'Game dashboard should load within 5 seconds');
        });
    }
}
