<?php

namespace Tests\Unit;

use App\ValueObjects\BattleResult;
use App\ValueObjects\Coordinates;
use App\ValueObjects\PlayerStats;
use App\ValueObjects\ResourceAmounts;
use App\ValueObjects\TroopCounts;
use App\ValueObjects\VillageResources;
use PHPUnit\Framework\TestCase;

class ValueObjectsTest extends TestCase
{
    public function test_coordinates_can_calculate_distance()
    {
        $coord1 = new Coordinates(x: 0, y: 0);
        $coord2 = new Coordinates(x: 3, y: 4);

        $this->assertEquals(5.0, $coord1->distanceTo($coord2));
    }

    public function test_coordinates_can_calculate_real_world_distance()
    {
        $coord1 = new Coordinates(x: 0, y: 0, latitude: 0, longitude: 0);
        $coord2 = new Coordinates(x: 0, y: 0, latitude: 0, longitude: 1);

        // Approximately 111 km for 1 degree longitude at equator
        $distance = $coord1->realWorldDistanceTo($coord2);
        $this->assertGreaterThan(100, $distance);
        $this->assertLessThan(120, $distance);
    }

    public function test_resource_amounts_can_add_and_subtract()
    {
        $resources1 = new ResourceAmounts(wood: 100, clay: 200, iron: 300, crop: 400);
        $resources2 = new ResourceAmounts(wood: 50, clay: 100, iron: 150, crop: 200);

        $added = $resources1->add($resources2);
        $this->assertEquals(150, $added->wood);
        $this->assertEquals(300, $added->clay);
        $this->assertEquals(450, $added->iron);
        $this->assertEquals(600, $added->crop);

        $subtracted = $resources1->subtract($resources2);
        $this->assertEquals(50, $subtracted->wood);
        $this->assertEquals(100, $subtracted->clay);
        $this->assertEquals(150, $subtracted->iron);
        $this->assertEquals(200, $subtracted->crop);
    }

    public function test_resource_amounts_can_check_affordability()
    {
        $available = new ResourceAmounts(wood: 100, clay: 200, iron: 300, crop: 400);
        $required = new ResourceAmounts(wood: 50, clay: 100, iron: 150, crop: 200);
        $tooMuch = new ResourceAmounts(wood: 150, clay: 100, iron: 150, crop: 200);

        $this->assertTrue($available->canAfford($required));
        $this->assertFalse($available->canAfford($tooMuch));
    }

    public function test_player_stats_can_calculate_metrics()
    {
        $stats = new PlayerStats(
            points: 100000,
            population: 5000,
            villagesCount: 10,
            totalAttackPoints: 8000,
            totalDefensePoints: 6000,
            isActive: true,
            isOnline: true
        );

        $this->assertEquals(14000, $stats->getTotalMilitaryPoints());
        $this->assertEquals(10000.0, $stats->getPointsPerVillage());
        $this->assertEquals(500.0, $stats->getPopulationPerVillage());
        $this->assertEquals('experienced', $stats->getRankingCategory());
        $this->assertTrue($stats->hasBalancedMilitary());
    }

    public function test_battle_result_can_calculate_efficiency()
    {
        $loot = new ResourceAmounts(wood: 1000, clay: 2000, iron: 1500, crop: 800);
        $result = new BattleResult(
            status: 'victory',
            attackerLosses: 100,
            defenderLosses: 200,
            loot: $loot,
            duration: 3600 // 1 hour
        );

        $this->assertTrue($result->isVictory());
        $this->assertEquals(300, $result->getTotalLosses());
        $this->assertEquals(17.67, round($result->getBattleEfficiency(), 2));
        $this->assertTrue($result->isEfficient());
        $this->assertEquals('moderate', $result->getSeverity());
    }

    public function test_troop_counts_can_calculate_composition()
    {
        $troops = new TroopCounts(
            spearmen: 100,
            swordsmen: 50,
            archers: 75,
            cavalry: 25,
            mountedArchers: 10,
            catapults: 5,
            rams: 3,
            spies: 2,
            settlers: 1
        );

        $this->assertEquals(271, $troops->getTotal());
        $this->assertEquals(225, $troops->getInfantryCount());
        $this->assertEquals(35, $troops->getCavalryCount());
        $this->assertEquals(8, $troops->getSiegeCount());
        $this->assertEquals(3, $troops->getSupportCount());
        $this->assertEquals('infantry-heavy', $troops->getArmyType());
    }

    public function test_village_resources_can_calculate_utilization()
    {
        $amounts = new ResourceAmounts(wood: 1000, clay: 2000, iron: 1500, crop: 800);
        $production = new ResourceAmounts(wood: 100, clay: 200, iron: 150, crop: 80);
        $capacity = new ResourceAmounts(wood: 2000, clay: 4000, iron: 3000, crop: 1600);

        $resources = new VillageResources($amounts, $production, $capacity);

        $this->assertEquals(5300, $resources->getTotalAmount());
        $this->assertEquals(530, $resources->getTotalProduction());
        $this->assertEquals(10600, $resources->getTotalCapacity());
        $this->assertEquals(50.0, $resources->getUtilizationPercentage());
        $this->assertFalse($resources->isStorageNearlyFull());
        $this->assertEquals(10.0, $resources->getTimeToFillStorage());
    }

    public function test_value_objects_are_immutable()
    {
        $original = new ResourceAmounts(wood: 100, clay: 200);
        $modified = $original->add(new ResourceAmounts(wood: 50, clay: 100));

        // Original should remain unchanged
        $this->assertEquals(100, $original->wood);
        $this->assertEquals(200, $original->clay);

        // Modified should have new values
        $this->assertEquals(150, $modified->wood);
        $this->assertEquals(300, $modified->clay);
    }

    public function test_coordinates_can_check_radius()
    {
        $center = new Coordinates(x: 0, y: 0);
        $nearby = new Coordinates(x: 3, y: 4);
        $far = new Coordinates(x: 10, y: 10);

        $this->assertTrue($nearby->isWithinRadius($center, 10));
        $this->assertFalse($far->isWithinRadius($center, 10));
    }
}
