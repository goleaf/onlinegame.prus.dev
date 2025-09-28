<?php

namespace Tests\Unit\Services\Game;

use App\Models\Game\Alliance;
use App\Models\Game\Battle;
use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Hero;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\Game\CombatService;
use App\Services\Game\MovementService;
use App\Services\Game\ResourceService;
use App\Services\PerformanceMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CombatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CombatService $service;

    protected $resourceService;

    protected $movementService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->resourceService = $this->mock(ResourceService::class);
        $this->movementService = $this->mock(MovementService::class);

        $this->service = new CombatService($this->resourceService, $this->movementService);
    }

    /**
     * @test
     */
    public function it_can_execute_battle()
    {
        $world = World::factory()->create();
        $attackerPlayer = Player::factory()->create(['world_id' => $world->id]);
        $defenderPlayer = Player::factory()->create(['world_id' => $world->id]);

        $attackerVillage = Village::factory()->create(['player_id' => $attackerPlayer->id]);
        $defenderVillage = Village::factory()->create(['player_id' => $defenderPlayer->id]);

        // Create unit types
        $swordsman = UnitType::factory()->create([
            'name' => 'Swordsman',
            'attack' => 100,
            'defense_infantry' => 80,
        ]);
        $archer = UnitType::factory()->create([
            'name' => 'Archer',
            'attack' => 80,
            'defense_infantry' => 100,
        ]);

        // Create attacker troops
        $attackerTroops = [
            $swordsman->id => 10,
            $archer->id => 5,
        ];

        // Create defender troops
        $defenderTroops = [
            $swordsman->id => 8,
            $archer->id => 3,
        ];

        // Mock PerformanceMonitoringService
        PerformanceMonitoringService::shouldReceive('monitorQueries')->once();

        // Mock resource service
        $this->resourceService->shouldReceive('getVillageResources')->andReturn([
            'wood' => 1000,
            'clay' => 800,
            'iron' => 600,
            'crop' => 400,
        ]);
        $this->resourceService->shouldReceive('deductResources')->once();
        $this->resourceService->shouldReceive('addResources')->once();

        $result = $this->service->executeBattle($attackerVillage, $defenderVillage, $attackerTroops, $defenderTroops);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('battle', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('attacker_casualties', $result);
        $this->assertArrayHasKey('defender_casualties', $result);
        $this->assertArrayHasKey('loot', $result);

        // Verify battle was created
        $this->assertDatabaseHas('battles', [
            'attacker_id' => $attackerPlayer->id,
            'defender_id' => $defenderPlayer->id,
            'village_id' => $defenderVillage->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_calculate_battle_strength()
    {
        $swordsman = UnitType::factory()->create([
            'name' => 'Swordsman',
            'attack' => 100,
            'defense_infantry' => 80,
        ]);
        $archer = UnitType::factory()->create([
            'name' => 'Archer',
            'attack' => 80,
            'defense_infantry' => 100,
        ]);

        $troops = [
            $swordsman->id => 10,
            $archer->id => 5,
        ];

        $attackStrength = $this->service->calculateBattleStrength($troops, 'attack');
        $defenseStrength = $this->service->calculateBattleStrength($troops, 'defense');

        // Attack: (100 * 10) + (80 * 5) = 1000 + 400 = 1400
        $this->assertEquals(1400, $attackStrength);
        // Defense: (80 * 10) + (100 * 5) = 800 + 500 = 1300
        $this->assertEquals(1300, $defenseStrength);
    }

    /**
     * @test
     */
    public function it_handles_zero_quantity_troops()
    {
        $swordsman = UnitType::factory()->create([
            'name' => 'Swordsman',
            'attack' => 100,
            'defense_infantry' => 80,
        ]);

        $troops = [
            $swordsman->id => 0,
        ];

        $strength = $this->service->calculateBattleStrength($troops, 'attack');

        $this->assertEquals(0, $strength);
    }

    /**
     * @test
     */
    public function it_handles_non_existent_unit_types()
    {
        $troops = [
            999 => 10,  // Non-existent unit type
        ];

        $strength = $this->service->calculateBattleStrength($troops, 'attack');

        $this->assertEquals(0, $strength);
    }

    /**
     * @test
     */
    public function it_can_get_battle_statistics()
    {
        $player = Player::factory()->create();

        // Create battles
        $attackerBattle = Battle::factory()->create([
            'attacker_id' => $player->id,
            'result' => 'attacker_victory',
            'loot' => json_encode(['wood' => 1000]),
        ]);
        $defenderBattle = Battle::factory()->create([
            'defender_id' => $player->id,
            'result' => 'defender_victory',
        ]);
        $defeatBattle = Battle::factory()->create([
            'attacker_id' => $player->id,
            'result' => 'defender_victory',
        ]);

        $stats = $this->service->getBattleStatistics($player);

        $this->assertArrayHasKey('total_battles', $stats);
        $this->assertArrayHasKey('victories', $stats);
        $this->assertArrayHasKey('defeats', $stats);
        $this->assertArrayHasKey('victory_rate', $stats);
        $this->assertArrayHasKey('total_attacks', $stats);
        $this->assertArrayHasKey('total_defenses', $stats);
        $this->assertArrayHasKey('attack_victories', $stats);
        $this->assertArrayHasKey('defense_victories', $stats);
        $this->assertArrayHasKey('total_loot', $stats);
        $this->assertArrayHasKey('total_casualties', $stats);

        $this->assertEquals(3, $stats['total_battles']);
        $this->assertEquals(2, $stats['victories']);  // 1 attack victory + 1 defense victory
        $this->assertEquals(1, $stats['defeats']);
        $this->assertEquals(66.67, round($stats['victory_rate'], 2));
        $this->assertEquals(2, $stats['total_attacks']);
        $this->assertEquals(1, $stats['total_defenses']);
        $this->assertEquals(1, $stats['attack_victories']);
        $this->assertEquals(1, $stats['defense_victories']);
    }

    /**
     * @test
     */
    public function it_can_get_recent_battles()
    {
        $player = Player::factory()->create();
        $otherPlayer = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $otherPlayer->id]);

        // Create battles
        $battle1 = Battle::factory()->create([
            'attacker_id' => $player->id,
            'defender_id' => $otherPlayer->id,
            'village_id' => $village->id,
            'result' => 'attacker_victory',
            'occurred_at' => now()->subHours(1),
        ]);
        $battle2 = Battle::factory()->create([
            'attacker_id' => $otherPlayer->id,
            'defender_id' => $player->id,
            'village_id' => $village->id,
            'result' => 'defender_victory',
            'occurred_at' => now()->subHours(2),
        ]);

        $recentBattles = $this->service->getRecentBattles($player, 10);

        $this->assertCount(2, $recentBattles);
        $this->assertEquals($battle1->id, $recentBattles[0]['id']);
        $this->assertEquals($battle2->id, $recentBattles[1]['id']);
        $this->assertTrue($recentBattles[0]['is_attacker']);
        $this->assertFalse($recentBattles[1]['is_attacker']);
        $this->assertEquals($otherPlayer->name, $recentBattles[0]['opponent']);
        $this->assertEquals($otherPlayer->name, $recentBattles[1]['opponent']);
    }

    /**
     * @test
     */
    public function it_can_clear_battle_cache()
    {
        $player = Player::factory()->create();

        // This should not throw an exception
        $this->service->clearBattleCache($player);

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_handles_battle_with_no_defender_troops()
    {
        $world = World::factory()->create();
        $attackerPlayer = Player::factory()->create(['world_id' => $world->id]);
        $defenderPlayer = Player::factory()->create(['world_id' => $world->id]);

        $attackerVillage = Village::factory()->create(['player_id' => $attackerPlayer->id]);
        $defenderVillage = Village::factory()->create(['player_id' => $defenderPlayer->id]);

        $swordsman = UnitType::factory()->create([
            'name' => 'Swordsman',
            'attack' => 100,
            'defense_infantry' => 80,
        ]);

        $attackerTroops = [$swordsman->id => 10];

        // Mock PerformanceMonitoringService
        PerformanceMonitoringService::shouldReceive('monitorQueries')->once();

        // Mock resource service
        $this->resourceService->shouldReceive('getVillageResources')->andReturn([]);
        $this->resourceService->shouldReceive('deductResources')->once();
        $this->resourceService->shouldReceive('addResources')->once();

        $result = $this->service->executeBattle($attackerVillage, $defenderVillage, $attackerTroops);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('battle', $result);
    }

    /**
     * @test
     */
    public function it_calculates_casualties_correctly()
    {
        $swordsman = UnitType::factory()->create();
        $archer = UnitType::factory()->create();

        $troops = [
            $swordsman->id => 100,
            $archer->id => 50,
        ];

        $lossPercentage = 20.0;  // 20% casualties

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateCasualties');
        $method->setAccessible(true);

        $casualties = $method->invoke($this->service, $troops, $lossPercentage);

        $this->assertEquals(20, $casualties[$swordsman->id]);  // 100 * 0.2
        $this->assertEquals(10, $casualties[$archer->id]);  // 50 * 0.2
    }

    /**
     * @test
     */
    public function it_handles_battle_result_calculation()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateBattleResult');
        $method->setAccessible(true);

        // Test attacker victory
        $result = $method->invoke($this->service, 1000, 500);
        $this->assertTrue($result['success']);
        $this->assertEquals('attacker_victory', $result['victory_type']);

        // Test defender victory
        $result = $method->invoke($this->service, 500, 1000);
        $this->assertFalse($result['success']);
        $this->assertEquals('defender_victory', $result['victory_type']);

        // Test draw
        $result = $method->invoke($this->service, 0, 0);
        $this->assertFalse($result['success']);
        $this->assertEquals('draw', $result['victory_type']);
    }

    /**
     * @test
     */
    public function it_applies_village_bonuses_correctly()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create wall building
        $wallType = BuildingType::factory()->create(['key' => 'wall']);
        $wall = Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $wallType->id,
            'level' => 5,
        ]);

        // Create hero
        $hero = Hero::factory()->create(['player_id' => $player->id]);

        // Create alliance
        $alliance = Alliance::factory()->create();
        $player->update(['alliance_id' => $alliance->id]);

        $baseStrength = 1000;

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('applyVillageBonuses');
        $method->setAccessible(true);

        $bonusedStrength = $method->invoke($this->service, $village, $baseStrength, 'defense');

        // Should be higher than base strength due to bonuses
        $this->assertGreaterThan($baseStrength, $bonusedStrength);
    }

    /**
     * @test
     */
    public function it_handles_empty_troop_arrays()
    {
        $troops = [];

        $strength = $this->service->calculateBattleStrength($troops, 'attack');

        $this->assertEquals(0, $strength);
    }

    /**
     * @test
     */
    public function it_handles_negative_quantities()
    {
        $swordsman = UnitType::factory()->create([
            'name' => 'Swordsman',
            'attack' => 100,
            'defense_infantry' => 80,
        ]);

        $troops = [
            $swordsman->id => -10,  // Negative quantity
        ];

        $strength = $this->service->calculateBattleStrength($troops, 'attack');

        $this->assertEquals(0, $strength);
    }
}
