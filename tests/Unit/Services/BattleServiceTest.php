<?php

namespace Tests\Unit\Services;

use App\Models\Game\Battle;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\BattleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BattleServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_battle()
    {
        $attacker = Player::factory()->create();
        $defender = Player::factory()->create();
        $attackerVillage = Village::factory()->create(['player_id' => $attacker->id]);
        $defenderVillage = Village::factory()->create(['player_id' => $defender->id]);
        $data = [
            'attacker_id' => $attacker->id,
            'defender_id' => $defender->id,
            'attacker_village_id' => $attackerVillage->id,
            'defender_village_id' => $defenderVillage->id,
            'attacker_units' => ['infantry' => 100, 'archer' => 50],
            'defender_units' => ['infantry' => 80, 'archer' => 40],
        ];

        $service = new BattleService();
        $result = $service->createBattle($data);

        $this->assertInstanceOf(Battle::class, $result);
        $this->assertEquals($data['attacker_id'], $result->attacker_id);
        $this->assertEquals($data['defender_id'], $result->defender_id);
        $this->assertEquals($data['attacker_village_id'], $result->attacker_village_id);
        $this->assertEquals($data['defender_village_id'], $result->defender_village_id);
    }

    /**
     * @test
     */
    public function it_can_simulate_battle()
    {
        $attacker = Player::factory()->create();
        $defender = Player::factory()->create();
        $attackerVillage = Village::factory()->create(['player_id' => $attacker->id]);
        $defenderVillage = Village::factory()->create(['player_id' => $defender->id]);
        $battle = Battle::factory()->create([
            'attacker_id' => $attacker->id,
            'defender_id' => $defender->id,
            'attacker_village_id' => $attackerVillage->id,
            'defender_village_id' => $defenderVillage->id,
        ]);

        $service = new BattleService();
        $result = $service->simulateBattle($battle);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('attacker_wins', $result);
        $this->assertArrayHasKey('attacker_losses', $result);
        $this->assertArrayHasKey('defender_losses', $result);
        $this->assertArrayHasKey('loot', $result);
    }

    /**
     * @test
     */
    public function it_can_execute_battle()
    {
        $attacker = Player::factory()->create();
        $defender = Player::factory()->create();
        $attackerVillage = Village::factory()->create(['player_id' => $attacker->id]);
        $defenderVillage = Village::factory()->create(['player_id' => $defender->id]);
        $battle = Battle::factory()->create([
            'attacker_id' => $attacker->id,
            'defender_id' => $defender->id,
            'attacker_village_id' => $attackerVillage->id,
            'defender_village_id' => $defenderVillage->id,
        ]);

        $service = new BattleService();
        $result = $service->executeBattle($battle);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_results()
    {
        $battle = Battle::factory()->create();
        $service = new BattleService();
        $result = $service->getBattleResults($battle);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('attacker_wins', $result);
        $this->assertArrayHasKey('attacker_losses', $result);
        $this->assertArrayHasKey('defender_losses', $result);
        $this->assertArrayHasKey('loot', $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_statistics()
    {
        $service = new BattleService();
        $result = $service->getBattleStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_battles', $result);
        $this->assertArrayHasKey('attacker_wins', $result);
        $this->assertArrayHasKey('defender_wins', $result);
        $this->assertArrayHasKey('draws', $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_leaderboard()
    {
        $service = new BattleService();
        $result = $service->getBattleLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_battles()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id]),
            Battle::factory()->create(['defender_id' => $player->id]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getPlayerBattles($player);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_battles()
    {
        $village = Village::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_village_id' => $village->id]),
            Battle::factory()->create(['defender_village_id' => $village->id]),
        ]);

        $village->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getVillageBattles($village);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_status()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'status' => 'pending']),
            Battle::factory()->create(['attacker_id' => $player->id, 'status' => 'completed']),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByStatus($player, 'pending');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_type()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'type' => 'attack']),
            Battle::factory()->create(['attacker_id' => $player->id, 'type' => 'raid']),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByType($player, 'attack');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_result()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'result' => 'attacker_wins']),
            Battle::factory()->create(['attacker_id' => $player->id, 'result' => 'defender_wins']),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByResult($player, 'attacker_wins');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_date_range()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'created_at' => now()]),
            Battle::factory()->create(['attacker_id' => $player->id, 'created_at' => now()->subDays(1)]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByDateRange($player, now()->subDays(1), now());

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_attacker()
    {
        $attacker = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $attacker->id]),
            Battle::factory()->create(['attacker_id' => $attacker->id]),
        ]);

        $attacker->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByAttacker($attacker);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_defender()
    {
        $defender = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['defender_id' => $defender->id]),
            Battle::factory()->create(['defender_id' => $defender->id]),
        ]);

        $defender->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByDefender($defender);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_attacker_village()
    {
        $village = Village::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_village_id' => $village->id]),
            Battle::factory()->create(['attacker_village_id' => $village->id]),
        ]);

        $village->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByAttackerVillage($village);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_defender_village()
    {
        $village = Village::factory()->create();
        $battles = collect([
            Battle::factory()->create(['defender_village_id' => $village->id]),
            Battle::factory()->create(['defender_village_id' => $village->id]),
        ]);

        $village->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByDefenderVillage($village);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_attacker_units()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_units' => ['infantry' => 100]]),
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_units' => ['archer' => 50]]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByAttackerUnits($player, 'infantry', 100);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_defender_units()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['defender_id' => $player->id, 'defender_units' => ['infantry' => 100]]),
            Battle::factory()->create(['defender_id' => $player->id, 'defender_units' => ['archer' => 50]]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByDefenderUnits($player, 'infantry', 100);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_attacker_losses()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_losses' => 10]),
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_losses' => 20]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByAttackerLosses($player, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_defender_losses()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['defender_id' => $player->id, 'defender_losses' => 10]),
            Battle::factory()->create(['defender_id' => $player->id, 'defender_losses' => 20]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByDefenderLosses($player, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_loot()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'loot' => ['wood' => 1000]]),
            Battle::factory()->create(['attacker_id' => $player->id, 'loot' => ['clay' => 500]]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByLoot($player, 'wood', 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_attacker_power()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_power' => 1000]),
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_power' => 2000]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByAttackerPower($player, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_defender_power()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['defender_id' => $player->id, 'defender_power' => 1000]),
            Battle::factory()->create(['defender_id' => $player->id, 'defender_power' => 2000]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByDefenderPower($player, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_attacker_power_range()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_power' => 1000]),
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_power' => 2000]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByAttackerPowerRange($player, 500, 1500);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_defender_power_range()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['defender_id' => $player->id, 'defender_power' => 1000]),
            Battle::factory()->create(['defender_id' => $player->id, 'defender_power' => 2000]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByDefenderPowerRange($player, 500, 1500);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_attacker_losses_range()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_losses' => 10]),
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_losses' => 20]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByAttackerLossesRange($player, 5, 15);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_defender_losses_range()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['defender_id' => $player->id, 'defender_losses' => 10]),
            Battle::factory()->create(['defender_id' => $player->id, 'defender_losses' => 20]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByDefenderLossesRange($player, 5, 15);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_loot_range()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'loot' => ['wood' => 1000]]),
            Battle::factory()->create(['attacker_id' => $player->id, 'loot' => ['clay' => 500]]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByLootRange($player, 'wood', 500, 1500);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_combined_filters()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'status' => 'completed', 'result' => 'attacker_wins']),
            Battle::factory()->create(['attacker_id' => $player->id, 'status' => 'pending', 'result' => 'defender_wins']),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByCombinedFilters($player, [
            'status' => 'completed',
            'result' => 'attacker_wins',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_search()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'description' => 'Test Battle']),
            Battle::factory()->create(['attacker_id' => $player->id, 'description' => 'Another Battle']),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesBySearch($player, 'Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_sort()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_power' => 1000]),
            Battle::factory()->create(['attacker_id' => $player->id, 'attacker_power' => 2000]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesBySort($player, 'attacker_power', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_battle_by_pagination()
    {
        $player = Player::factory()->create();
        $battles = collect([
            Battle::factory()->create(['attacker_id' => $player->id]),
            Battle::factory()->create(['attacker_id' => $player->id]),
        ]);

        $player->shouldReceive('battles')->andReturn($battles);

        $service = new BattleService();
        $result = $service->getBattlesByPagination($player, 1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
