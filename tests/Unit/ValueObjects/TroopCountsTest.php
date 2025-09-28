<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\TroopCounts;
use Tests\TestCase;

class TroopCountsTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_with_valid_counts()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $this->assertEquals(100, $troops->infantry);
        $this->assertEquals(80, $troops->archer);
        $this->assertEquals(50, $troops->cavalry);
        $this->assertEquals(10, $troops->siege);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_zero_counts()
    {
        $troops = new TroopCounts(
            infantry: 0,
            archer: 0,
            cavalry: 0,
            siege: 0
        );

        $this->assertEquals(0, $troops->infantry);
        $this->assertEquals(0, $troops->archer);
        $this->assertEquals(0, $troops->cavalry);
        $this->assertEquals(0, $troops->siege);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_large_counts()
    {
        $troops = new TroopCounts(
            infantry: 100000,
            archer: 80000,
            cavalry: 50000,
            siege: 10000
        );

        $this->assertEquals(100000, $troops->infantry);
        $this->assertEquals(80000, $troops->archer);
        $this->assertEquals(50000, $troops->cavalry);
        $this->assertEquals(10000, $troops->siege);
    }

    /**
     * @test
     */
    public function it_can_calculate_total_troops()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $total = $troops->getTotal();

        $this->assertEquals(240, $total);  // 100 + 80 + 50 + 10
    }

    /**
     * @test
     */
    public function it_can_add_troops()
    {
        $troops1 = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $troops2 = new TroopCounts(
            infantry: 50,
            archer: 30,
            cavalry: 20,
            siege: 5
        );

        $result = $troops1->add($troops2);

        $this->assertEquals(150, $result->infantry);
        $this->assertEquals(110, $result->archer);
        $this->assertEquals(70, $result->cavalry);
        $this->assertEquals(15, $result->siege);

        // Original should remain unchanged
        $this->assertEquals(100, $troops1->infantry);
        $this->assertEquals(80, $troops1->archer);
        $this->assertEquals(50, $troops1->cavalry);
        $this->assertEquals(10, $troops1->siege);
    }

    /**
     * @test
     */
    public function it_can_subtract_troops()
    {
        $troops1 = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $troops2 = new TroopCounts(
            infantry: 20,
            archer: 15,
            cavalry: 10,
            siege: 2
        );

        $result = $troops1->subtract($troops2);

        $this->assertEquals(80, $result->infantry);
        $this->assertEquals(65, $result->archer);
        $this->assertEquals(40, $result->cavalry);
        $this->assertEquals(8, $result->siege);

        // Original should remain unchanged
        $this->assertEquals(100, $troops1->infantry);
        $this->assertEquals(80, $troops1->archer);
        $this->assertEquals(50, $troops1->cavalry);
        $this->assertEquals(10, $troops1->siege);
    }

    /**
     * @test
     */
    public function it_can_subtract_troops_resulting_in_negative_values()
    {
        $troops1 = new TroopCounts(
            infantry: 10,
            archer: 8,
            cavalry: 5,
            siege: 1
        );

        $troops2 = new TroopCounts(
            infantry: 20,
            archer: 5,
            cavalry: 10,
            siege: 0
        );

        $result = $troops1->subtract($troops2);

        $this->assertEquals(-10, $result->infantry);
        $this->assertEquals(3, $result->archer);
        $this->assertEquals(-5, $result->cavalry);
        $this->assertEquals(1, $result->siege);
    }

    /**
     * @test
     */
    public function it_can_multiply_troops_by_factor()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $result = $troops->multiplyBy(2.5);

        $this->assertEquals(250, $result->infantry);
        $this->assertEquals(200, $result->archer);
        $this->assertEquals(125, $result->cavalry);
        $this->assertEquals(25, $result->siege);

        // Original should remain unchanged
        $this->assertEquals(100, $troops->infantry);
        $this->assertEquals(80, $troops->archer);
        $this->assertEquals(50, $troops->cavalry);
        $this->assertEquals(10, $troops->siege);
    }

    /**
     * @test
     */
    public function it_can_multiply_troops_by_zero()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $result = $troops->multiplyBy(0);

        $this->assertEquals(0, $result->infantry);
        $this->assertEquals(0, $result->archer);
        $this->assertEquals(0, $result->cavalry);
        $this->assertEquals(0, $result->siege);
    }

    /**
     * @test
     */
    public function it_can_calculate_battle_power()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        // Assuming power values: infantry=1, archer=1.5, cavalry=2, siege=5
        $power = $troops->getBattlePower([
            'infantry' => 1,
            'archer' => 1.5,
            'cavalry' => 2,
            'siege' => 5,
        ]);

        $expectedPower = (100 * 1) + (80 * 1.5) + (50 * 2) + (10 * 5);
        $this->assertEquals($expectedPower, $power);  // 100 + 120 + 100 + 50 = 370
    }

    /**
     * @test
     */
    public function it_can_calculate_battle_power_with_default_values()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $power = $troops->getBattlePower();

        // Default power values: infantry=10, archer=15, cavalry=25, siege=50
        $expectedPower = (100 * 10) + (80 * 15) + (50 * 25) + (10 * 50);
        $this->assertEquals($expectedPower, $power);  // 1000 + 1200 + 1250 + 500 = 3950
    }

    /**
     * @test
     */
    public function it_can_apply_losses_percentage()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $result = $troops->applyLosses(0.2);  // 20% losses

        $this->assertEquals(80, $result->infantry);  // 100 - (100 * 0.2) = 80
        $this->assertEquals(64, $result->archer);  // 80 - (80 * 0.2) = 64
        $this->assertEquals(40, $result->cavalry);  // 50 - (50 * 0.2) = 40
        $this->assertEquals(8, $result->siege);  // 10 - (10 * 0.2) = 8

        // Original should remain unchanged
        $this->assertEquals(100, $troops->infantry);
        $this->assertEquals(80, $troops->archer);
        $this->assertEquals(50, $troops->cavalry);
        $this->assertEquals(10, $troops->siege);
    }

    /**
     * @test
     */
    public function it_can_apply_100_percent_losses()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $result = $troops->applyLosses(1.0);  // 100% losses

        $this->assertEquals(0, $result->infantry);
        $this->assertEquals(0, $result->archer);
        $this->assertEquals(0, $result->cavalry);
        $this->assertEquals(0, $result->siege);
    }

    /**
     * @test
     */
    public function it_can_apply_zero_losses()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $result = $troops->applyLosses(0.0);  // 0% losses

        $this->assertEquals(100, $result->infantry);
        $this->assertEquals(80, $result->archer);
        $this->assertEquals(50, $result->cavalry);
        $this->assertEquals(10, $result->siege);
    }

    /**
     * @test
     */
    public function it_can_get_troop_as_array()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $array = $troops->toArray();

        $this->assertEquals([
            'infantry' => 100,
            'archer' => 80,
            'cavalry' => 50,
            'siege' => 10,
        ], $array);
    }

    /**
     * @test
     */
    public function it_can_create_from_array()
    {
        $array = [
            'infantry' => 100,
            'archer' => 80,
            'cavalry' => 50,
            'siege' => 10,
        ];

        $troops = TroopCounts::fromArray($array);

        $this->assertEquals(100, $troops->infantry);
        $this->assertEquals(80, $troops->archer);
        $this->assertEquals(50, $troops->cavalry);
        $this->assertEquals(10, $troops->siege);
    }

    /**
     * @test
     */
    public function it_can_create_from_partial_array()
    {
        $array = [
            'infantry' => 100,
            'cavalry' => 50,
        ];

        $troops = TroopCounts::fromArray($array);

        $this->assertEquals(100, $troops->infantry);
        $this->assertEquals(0, $troops->archer);
        $this->assertEquals(50, $troops->cavalry);
        $this->assertEquals(0, $troops->siege);
    }

    /**
     * @test
     */
    public function it_can_check_if_empty()
    {
        $emptyTroops = new TroopCounts(
            infantry: 0,
            archer: 0,
            cavalry: 0,
            siege: 0
        );

        $nonEmptyTroops = new TroopCounts(
            infantry: 1,
            archer: 0,
            cavalry: 0,
            siege: 0
        );

        $this->assertTrue($emptyTroops->isEmpty());
        $this->assertFalse($nonEmptyTroops->isEmpty());
    }

    /**
     * @test
     */
    public function it_can_check_if_has_troops_of_type()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 0,
            cavalry: 50,
            siege: 0
        );

        $this->assertTrue($troops->hasTroopsOfType('infantry'));
        $this->assertFalse($troops->hasTroopsOfType('archer'));
        $this->assertTrue($troops->hasTroopsOfType('cavalry'));
        $this->assertFalse($troops->hasTroopsOfType('siege'));
    }

    /**
     * @test
     */
    public function it_can_get_strongest_troop_type()
    {
        $troops = new TroopCounts(
            infantry: 50,
            archer: 100,
            cavalry: 30,
            siege: 5
        );

        $strongestType = $troops->getStrongestTroopType();

        $this->assertEquals('archer', $strongestType);
    }

    /**
     * @test
     */
    public function it_returns_null_for_strongest_troop_type_when_empty()
    {
        $troops = new TroopCounts(
            infantry: 0,
            archer: 0,
            cavalry: 0,
            siege: 0
        );

        $strongestType = $troops->getStrongestTroopType();

        $this->assertNull($strongestType);
    }

    /**
     * @test
     */
    public function it_can_get_percentage_of_troop_counts()
    {
        $troops = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $percentage = $troops->getPercentageOf(50);  // 50% of each troop type

        $this->assertEquals(50, $percentage->infantry);
        $this->assertEquals(40, $percentage->archer);
        $this->assertEquals(25, $percentage->cavalry);
        $this->assertEquals(5, $percentage->siege);
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_unserialized()
    {
        $original = new TroopCounts(
            infantry: 1234,
            archer: 5678,
            cavalry: 9012,
            siege: 3456
        );

        $serialized = serialize($original);
        $unserialized = unserialize($serialized);

        $this->assertEquals($original->infantry, $unserialized->infantry);
        $this->assertEquals($original->archer, $unserialized->archer);
        $this->assertEquals($original->cavalry, $unserialized->cavalry);
        $this->assertEquals($original->siege, $unserialized->siege);
    }

    /**
     * @test
     */
    public function it_maintains_immutability()
    {
        $original = new TroopCounts(
            infantry: 100,
            archer: 80,
            cavalry: 50,
            siege: 10
        );

        $added = new TroopCounts(
            infantry: 10,
            archer: 10,
            cavalry: 10,
            siege: 10
        );

        $result = $original->add($added);

        // Original should not be modified
        $this->assertEquals(100, $original->infantry);
        $this->assertEquals(80, $original->archer);
        $this->assertEquals(50, $original->cavalry);
        $this->assertEquals(10, $original->siege);

        // Result should have new values
        $this->assertEquals(110, $result->infantry);
        $this->assertEquals(90, $result->archer);
        $this->assertEquals(60, $result->cavalry);
        $this->assertEquals(20, $result->siege);
    }
}
