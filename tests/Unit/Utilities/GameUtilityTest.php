<?php

namespace Tests\Unit\Utilities;

use App\Utilities\GameUtility;
use Tests\TestCase;

class GameUtilityTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_format_small_numbers()
    {
        $this->assertEquals('123', GameUtility::formatNumber(123));
        $this->assertEquals('999', GameUtility::formatNumber(999));
    }

    /**
     * @test
     */
    public function it_can_format_thousands()
    {
        $this->assertEquals('1.0K', GameUtility::formatNumber(1000));
        $this->assertEquals('1.5K', GameUtility::formatNumber(1500));
        $this->assertEquals('999.0K', GameUtility::formatNumber(999000));
    }

    /**
     * @test
     */
    public function it_can_format_millions()
    {
        $this->assertEquals('1.0M', GameUtility::formatNumber(1000000));
        $this->assertEquals('1.5M', GameUtility::formatNumber(1500000));
        $this->assertEquals('999.0M', GameUtility::formatNumber(999000000));
    }

    /**
     * @test
     */
    public function it_can_format_billions()
    {
        $this->assertEquals('1.0B', GameUtility::formatNumber(1000000000));
        $this->assertEquals('1.5B', GameUtility::formatNumber(1500000000));
        $this->assertEquals('2.3B', GameUtility::formatNumber(2300000000));
    }

    /**
     * @test
     */
    public function it_can_calculate_battle_points()
    {
        $units = [
            'infantry' => 10,
            'archer' => 5,
            'cavalry' => 2,
            'siege' => 1,
            'hero' => 1,
        ];

        $points = GameUtility::calculateBattlePoints($units);

        // 10*10 + 5*15 + 2*25 + 1*50 + 1*100 = 100 + 75 + 50 + 50 + 100 = 375
        $this->assertEquals(375, $points);
    }

    /**
     * @test
     */
    public function it_ignores_unknown_unit_types()
    {
        $units = [
            'infantry' => 10,
            'unknown_unit' => 5,
        ];

        $points = GameUtility::calculateBattlePoints($units);

        // Only infantry: 10*10 = 100
        $this->assertEquals(100, $points);
    }

    /**
     * @test
     */
    public function it_can_calculate_distance_between_coordinates()
    {
        // Distance between New York and Los Angeles (approximately 3944 km)
        $distance = GameUtility::calculateDistance(40.7128, -74.006, 34.0522, -118.2437);

        $this->assertGreaterThan(3900, $distance);
        $this->assertLessThan(4000, $distance);
    }

    /**
     * @test
     */
    public function it_can_calculate_travel_time()
    {
        $time = GameUtility::calculateTravelTime(0, 0, 1, 1, 100);  // 100 km/h

        $this->assertIsInt($time);
        $this->assertGreaterThan(0, $time);
    }

    /**
     * @test
     */
    public function it_can_format_duration_in_seconds()
    {
        $this->assertEquals('30s', GameUtility::formatDuration(30));
        $this->assertEquals('1m 30s', GameUtility::formatDuration(90));
        $this->assertEquals('2m', GameUtility::formatDuration(120));
        $this->assertEquals('1h 30m', GameUtility::formatDuration(5400));
        $this->assertEquals('2h', GameUtility::formatDuration(7200));
        $this->assertEquals('1d 2h', GameUtility::formatDuration(93600));
        $this->assertEquals('2d', GameUtility::formatDuration(172800));
    }

    /**
     * @test
     */
    public function it_can_generate_random_event()
    {
        $event = GameUtility::generateRandomEvent();

        $this->assertIsArray($event);
        $this->assertArrayHasKey('type', $event);
        $this->assertArrayHasKey('title', $event);
        $this->assertArrayHasKey('description', $event);
        $this->assertArrayHasKey('effect', $event);
        $this->assertArrayHasKey('probability', $event);
    }

    /**
     * @test
     */
    public function it_can_calculate_resource_production()
    {
        $buildings = [
            'woodcutter' => 5,
            'clay_pit' => 3,
            'iron_mine' => 2,
            'crop_field' => 4,
        ];

        $production = GameUtility::calculateResourceProduction($buildings);

        $this->assertEquals(35, $production['wood']);  // 10 + 5*5
        $this->assertEquals(25, $production['clay']);  // 10 + 3*5
        $this->assertEquals(20, $production['iron']);  // 10 + 2*5
        $this->assertEquals(30, $production['crop']);  // 10 + 4*5
    }

    /**
     * @test
     */
    public function it_can_calculate_resource_production_with_upgrades()
    {
        $buildings = [
            'woodcutter' => 5,
        ];

        $upgrades = [
            'production_bonus' => 2,  // 20% bonus
        ];

        $production = GameUtility::calculateResourceProduction($buildings, $upgrades);

        $this->assertEquals(42, $production['wood']);  // (10 + 5*5) * 1.2
    }

    /**
     * @test
     */
    public function it_can_calculate_building_cost()
    {
        $cost = GameUtility::calculateBuildingCost('woodcutter', 3);

        $this->assertEquals(112, $cost['wood']);  // 50 * 1.5^2
        $this->assertEquals(67, $cost['clay']);  // 30 * 1.5^2
        $this->assertEquals(45, $cost['iron']);  // 20 * 1.5^2
        $this->assertEquals(22, $cost['crop']);  // 10 * 1.5^2
    }

    /**
     * @test
     */
    public function it_uses_default_cost_for_unknown_building()
    {
        $cost = GameUtility::calculateBuildingCost('unknown_building', 2);

        $this->assertEquals(150, $cost['wood']);  // 100 * 1.5^1
        $this->assertEquals(150, $cost['clay']);  // 100 * 1.5^1
        $this->assertEquals(150, $cost['iron']);  // 100 * 1.5^1
        $this->assertEquals(150, $cost['crop']);  // 100 * 1.5^1
    }

    /**
     * @test
     */
    public function it_can_calculate_troop_cost()
    {
        $cost = GameUtility::calculateTroopCost('infantry', 10);

        $this->assertEquals(100, $cost['wood']);  // 10 * 10
        $this->assertEquals(50, $cost['clay']);  // 5 * 10
        $this->assertEquals(50, $cost['iron']);  // 5 * 10
        $this->assertEquals(100, $cost['crop']);  // 10 * 10
    }

    /**
     * @test
     */
    public function it_uses_default_cost_for_unknown_troop()
    {
        $cost = GameUtility::calculateTroopCost('unknown_troop', 5);

        $this->assertEquals(50, $cost['wood']);  // 10 * 5
        $this->assertEquals(50, $cost['clay']);  // 10 * 5
        $this->assertEquals(50, $cost['iron']);  // 10 * 5
        $this->assertEquals(50, $cost['crop']);  // 10 * 5
    }

    /**
     * @test
     */
    public function it_can_calculate_battle_outcome()
    {
        $attackerUnits = ['infantry' => 10, 'archer' => 5];
        $defenderUnits = ['infantry' => 8, 'cavalry' => 2];

        $outcome = GameUtility::calculateBattleOutcome($attackerUnits, $defenderUnits);

        $this->assertArrayHasKey('attacker_wins', $outcome);
        $this->assertArrayHasKey('attacker_power', $outcome);
        $this->assertArrayHasKey('defender_power', $outcome);
        $this->assertArrayHasKey('attacker_losses', $outcome);
        $this->assertArrayHasKey('defender_losses', $outcome);
        $this->assertArrayHasKey('loot', $outcome);
    }

    /**
     * @test
     */
    public function it_can_generate_reference_number()
    {
        $ref = GameUtility::generateReference('TEST');

        $this->assertStringStartsWith('TEST-', $ref);
        $this->assertStringEndsWith('-', $ref);
        $this->assertEquals(16, strlen($ref));  // TEST-YYYYMMDD-XXXX
    }

    /**
     * @test
     */
    public function it_can_validate_coordinates()
    {
        $this->assertTrue(GameUtility::validateCoordinates(0, 0));
        $this->assertTrue(GameUtility::validateCoordinates(90, 180));
        $this->assertTrue(GameUtility::validateCoordinates(-90, -180));
        $this->assertFalse(GameUtility::validateCoordinates(91, 0));
        $this->assertFalse(GameUtility::validateCoordinates(0, 181));
        $this->assertFalse(GameUtility::validateCoordinates(-91, 0));
        $this->assertFalse(GameUtility::validateCoordinates(0, -181));
    }

    /**
     * @test
     */
    public function it_can_convert_game_to_real_world_coordinates()
    {
        $coords = GameUtility::gameToRealWorld(500, 500);

        $this->assertEquals(0, $coords['lat']);
        $this->assertEquals(0, $coords['lon']);
    }

    /**
     * @test
     */
    public function it_can_convert_real_world_to_game_coordinates()
    {
        $coords = GameUtility::realWorldToGame(0, 0);

        $this->assertEquals(500, $coords['x']);
        $this->assertEquals(500, $coords['y']);
    }

    /**
     * @test
     */
    public function it_clamps_coordinates_to_valid_ranges()
    {
        $coords = GameUtility::gameToRealWorld(0, 0);

        $this->assertGreaterThanOrEqual(-90, $coords['lat']);
        $this->assertLessThanOrEqual(90, $coords['lat']);
        $this->assertGreaterThanOrEqual(-180, $coords['lon']);
        $this->assertLessThanOrEqual(180, $coords['lon']);
    }

    /**
     * @test
     */
    public function it_clamps_game_coordinates_to_valid_ranges()
    {
        $coords = GameUtility::realWorldToGame(100, 200);

        $this->assertGreaterThanOrEqual(0, $coords['x']);
        $this->assertLessThanOrEqual(1000, $coords['x']);
        $this->assertGreaterThanOrEqual(0, $coords['y']);
        $this->assertLessThanOrEqual(1000, $coords['y']);
    }
}
