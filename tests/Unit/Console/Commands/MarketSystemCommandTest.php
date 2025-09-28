<?php

namespace Tests\Unit\Console\Commands;

use App\Models\Game\Player;
use App\Models\Game\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class MarketSystemCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_generate_action()
    {
        // Create test data
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Mock the resources relationship
        $village->shouldReceive('resources')->andReturn(
            collect([
                (object) ['type' => 'wood', 'amount' => 10000],
                (object) ['type' => 'clay', 'amount' => 5000],
                (object) ['type' => 'iron', 'amount' => 3000],
                (object) ['type' => 'crop', 'amount' => 2000],
            ])
        );

        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $this
            ->artisan('market:manage', ['action' => 'generate'])
            ->expectsOutput('🏪 Market System Management')
            ->expectsOutput('===========================')
            ->expectsOutput('📈 Generating market offers...')
            ->expectsOutput('✅ Generated')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_generate_action_with_specific_player()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        $village->shouldReceive('resources')->andReturn(
            collect([
                (object) ['type' => 'wood', 'amount' => 10000],
            ])
        );

        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $this
            ->artisan('market:manage', [
                'action' => 'generate',
                '--player-id' => $player->id,
            ])
            ->expectsOutput('🏪 Market System Management')
            ->expectsOutput('===========================')
            ->expectsOutput('📈 Generating market offers...')
            ->expectsOutput('✅ Generated')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_generate_action_with_specific_world()
    {
        $player = Player::factory()->create(['world_id' => 1]);
        $village = Village::factory()->create(['player_id' => $player->id]);

        $village->shouldReceive('resources')->andReturn(
            collect([
                (object) ['type' => 'wood', 'amount' => 10000],
            ])
        );

        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $this
            ->artisan('market:manage', [
                'action' => 'generate',
                '--world-id' => 1,
            ])
            ->expectsOutput('🏪 Market System Management')
            ->expectsOutput('===========================')
            ->expectsOutput('📈 Generating market offers...')
            ->expectsOutput('✅ Generated')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_generate_action_with_specific_resource_type()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        $village->shouldReceive('resources')->andReturn(
            collect([
                (object) ['type' => 'wood', 'amount' => 10000],
            ])
        );

        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $this
            ->artisan('market:manage', [
                'action' => 'generate',
                '--resource-type' => 'wood',
            ])
            ->expectsOutput('🏪 Market System Management')
            ->expectsOutput('===========================')
            ->expectsOutput('📈 Generating market offers...')
            ->expectsOutput('✅ Generated')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_process_action()
    {
        // Create test market trades
        DB::table('market_trades')->insert([
            'player_id' => 1,
            'village_id' => 1,
            'offer_type' => 'wood',
            'offer_amount' => 1000,
            'demand_type' => 'clay',
            'demand_amount' => 1000,
            'ratio' => 1.0,
            'status' => 'active',
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->artisan('market:manage', ['action' => 'process'])
            ->expectsOutput('🏪 Market System Management')
            ->expectsOutput('===========================')
            ->expectsOutput('🔄 Processing market trades...')
            ->expectsOutput('✅ Processed')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_cleanup_action()
    {
        // Create expired market trades
        DB::table('market_trades')->insert([
            'player_id' => 1,
            'village_id' => 1,
            'offer_type' => 'wood',
            'offer_amount' => 1000,
            'demand_type' => 'clay',
            'demand_amount' => 1000,
            'ratio' => 1.0,
            'status' => 'active',
            'expires_at' => now()->subHours(1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->artisan('market:manage', ['action' => 'cleanup'])
            ->expectsOutput('🏪 Market System Management')
            ->expectsOutput('===========================')
            ->expectsOutput('🧹 Cleaning up expired offers...')
            ->expectsOutput('✅ Cleaned up')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_stats_action()
    {
        // Create test data
        DB::table('market_trades')->insert([
            'player_id' => 1,
            'village_id' => 1,
            'offer_type' => 'wood',
            'offer_amount' => 1000,
            'demand_type' => 'clay',
            'demand_amount' => 1000,
            'ratio' => 1.0,
            'status' => 'active',
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('trade_offers')->insert([
            'market_trade_id' => 1,
            'buyer_id' => 2,
            'seller_id' => 1,
            'amount_traded' => 500,
            'resources_exchanged' => '{}',
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->artisan('market:manage', ['action' => 'stats'])
            ->expectsOutput('🏪 Market System Management')
            ->expectsOutput('===========================')
            ->expectsOutput('📊 Market Statistics')
            ->expectsOutput('===================')
            ->expectsOutput('  Active Offers:')
            ->expectsOutput('  Completed Trades:')
            ->expectsOutput('  Cancelled Offers:')
            ->expectsOutput('  Total Trade Volume:')
            ->expectsOutput('Resource Breakdown:')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_unknown_action()
    {
        $this
            ->artisan('market:manage', ['action' => 'unknown'])
            ->expectsOutput('🏪 Market System Management')
            ->expectsOutput('===========================')
            ->expectsOutput('Unknown action: unknown')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_generate_offers_for_player_with_excess_resources()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        $village->shouldReceive('resources')->andReturn(
            collect([
                (object) ['type' => 'wood', 'amount' => 10000],
            ])
        );

        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $command = new \App\Console\Commands\MarketSystemCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('generateOffersForPlayer');
        $method->setAccessible(true);

        $result = $method->invoke($command, $player, 'wood');
        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_generate_offers_for_player_with_insufficient_resources()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        $village->shouldReceive('resources')->andReturn(
            collect([
                (object) ['type' => 'wood', 'amount' => 500],  // Less than 1000
            ])
        );

        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $command = new \App\Console\Commands\MarketSystemCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('generateOffersForPlayer');
        $method->setAccessible(true);

        $result = $method->invoke($command, $player, 'wood');
        $this->assertEquals(0, $result);
    }

    /**
     * @test
     */
    public function it_can_get_random_resource_type()
    {
        $command = new \App\Console\Commands\MarketSystemCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getRandomResourceType');
        $method->setAccessible(true);

        $result = $method->invoke($command, 'wood');
        $this->assertContains($result, ['clay', 'iron', 'crop']);
        $this->assertNotEquals('wood', $result);
    }

    /**
     * @test
     */
    public function it_can_calculate_demand_amount()
    {
        $command = new \App\Console\Commands\MarketSystemCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('calculateDemandAmount');
        $method->setAccessible(true);

        $result = $method->invoke($command, 1000, 'wood', 'clay');
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * @test
     */
    public function it_can_calculate_offer_amount()
    {
        $command = new \App\Console\Commands\MarketSystemCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('calculateOfferAmount');
        $method->setAccessible(true);

        $result = $method->invoke($command, 1000, 'wood', 'clay');
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * @test
     */
    public function it_can_find_matching_offers()
    {
        // Create test offers
        DB::table('market_trades')->insert([
            'player_id' => 1,
            'village_id' => 1,
            'offer_type' => 'wood',
            'offer_amount' => 1000,
            'demand_type' => 'clay',
            'demand_amount' => 1000,
            'ratio' => 1.0,
            'status' => 'active',
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $offer = (object) [
            'id' => 1,
            'player_id' => 1,
            'offer_type' => 'wood',
            'demand_type' => 'clay',
            'offer_amount' => 1000,
            'demand_amount' => 1000,
        ];

        $command = new \App\Console\Commands\MarketSystemCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('findMatchingOffers');
        $method->setAccessible(true);

        $result = $method->invoke($command, $offer);
        $this->assertIsArray($result);
    }

    /**
     * @test
     */
    public function it_can_transfer_resources()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        $resource = Mockery::mock();
        $resource->shouldReceive('increment')->once();
        $resource->shouldReceive('decrement')->once();

        $village->shouldReceive('resources')->andReturn(
            collect([$resource])
        );

        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $command = new \App\Console\Commands\MarketSystemCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('transferResources');
        $method->setAccessible(true);

        $method->invoke($command, $player->id, 'wood', 100, 'add');
        $method->invoke($command, $player->id, 'wood', 100, 'subtract');
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new \App\Console\Commands\MarketSystemCommand();
        $this->assertEquals('market:manage', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new \App\Console\Commands\MarketSystemCommand();
        $this->assertEquals('Manage market system - generate offers, process trades, cleanup expired offers, and show statistics', $command->getDescription());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
