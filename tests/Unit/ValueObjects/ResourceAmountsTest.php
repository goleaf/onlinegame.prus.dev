<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\ResourceAmounts;
use Tests\TestCase;

class ResourceAmountsTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_with_valid_amounts()
    {
        $resources = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $this->assertEquals(1000, $resources->wood);
        $this->assertEquals(800, $resources->clay);
        $this->assertEquals(600, $resources->iron);
        $this->assertEquals(400, $resources->crop);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_zero_amounts()
    {
        $resources = new ResourceAmounts(
            wood: 0,
            clay: 0,
            iron: 0,
            crop: 0
        );

        $this->assertEquals(0, $resources->wood);
        $this->assertEquals(0, $resources->clay);
        $this->assertEquals(0, $resources->iron);
        $this->assertEquals(0, $resources->crop);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_large_amounts()
    {
        $resources = new ResourceAmounts(
            wood: 1000000,
            clay: 2000000,
            iron: 3000000,
            crop: 4000000
        );

        $this->assertEquals(1000000, $resources->wood);
        $this->assertEquals(2000000, $resources->clay);
        $this->assertEquals(3000000, $resources->iron);
        $this->assertEquals(4000000, $resources->crop);
    }

    /**
     * @test
     */
    public function it_can_calculate_total_resources()
    {
        $resources = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $total = $resources->getTotal();

        $this->assertEquals(2800, $total);  // 1000 + 800 + 600 + 400
    }

    /**
     * @test
     */
    public function it_can_add_resources()
    {
        $resources1 = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $resources2 = new ResourceAmounts(
            wood: 500,
            clay: 300,
            iron: 200,
            crop: 100
        );

        $result = $resources1->add($resources2);

        $this->assertEquals(1500, $result->wood);
        $this->assertEquals(1100, $result->clay);
        $this->assertEquals(800, $result->iron);
        $this->assertEquals(500, $result->crop);

        // Original should remain unchanged
        $this->assertEquals(1000, $resources1->wood);
        $this->assertEquals(800, $resources1->clay);
        $this->assertEquals(600, $resources1->iron);
        $this->assertEquals(400, $resources1->crop);
    }

    /**
     * @test
     */
    public function it_can_subtract_resources()
    {
        $resources1 = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $resources2 = new ResourceAmounts(
            wood: 200,
            clay: 100,
            iron: 50,
            crop: 25
        );

        $result = $resources1->subtract($resources2);

        $this->assertEquals(800, $result->wood);
        $this->assertEquals(700, $result->clay);
        $this->assertEquals(550, $result->iron);
        $this->assertEquals(375, $result->crop);

        // Original should remain unchanged
        $this->assertEquals(1000, $resources1->wood);
        $this->assertEquals(800, $resources1->clay);
        $this->assertEquals(600, $resources1->iron);
        $this->assertEquals(400, $resources1->crop);
    }

    /**
     * @test
     */
    public function it_can_subtract_resources_resulting_in_negative_values()
    {
        $resources1 = new ResourceAmounts(
            wood: 100,
            clay: 80,
            iron: 60,
            crop: 40
        );

        $resources2 = new ResourceAmounts(
            wood: 200,
            clay: 100,
            iron: 50,
            crop: 60
        );

        $result = $resources1->subtract($resources2);

        $this->assertEquals(-100, $result->wood);
        $this->assertEquals(-20, $result->clay);
        $this->assertEquals(10, $result->iron);
        $this->assertEquals(-20, $result->crop);
    }

    /**
     * @test
     */
    public function it_can_multiply_resources_by_factor()
    {
        $resources = new ResourceAmounts(
            wood: 100,
            clay: 80,
            iron: 60,
            crop: 40
        );

        $result = $resources->multiplyBy(2.5);

        $this->assertEquals(250, $result->wood);
        $this->assertEquals(200, $result->clay);
        $this->assertEquals(150, $result->iron);
        $this->assertEquals(100, $result->crop);

        // Original should remain unchanged
        $this->assertEquals(100, $resources->wood);
        $this->assertEquals(80, $resources->clay);
        $this->assertEquals(60, $resources->iron);
        $this->assertEquals(40, $resources->crop);
    }

    /**
     * @test
     */
    public function it_can_multiply_resources_by_zero()
    {
        $resources = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $result = $resources->multiplyBy(0);

        $this->assertEquals(0, $result->wood);
        $this->assertEquals(0, $result->clay);
        $this->assertEquals(0, $result->iron);
        $this->assertEquals(0, $result->crop);
    }

    /**
     * @test
     */
    public function it_can_check_if_sufficient_resources_available()
    {
        $available = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $required1 = new ResourceAmounts(
            wood: 500,
            clay: 400,
            iron: 300,
            crop: 200
        );

        $required2 = new ResourceAmounts(
            wood: 1500,
            clay: 400,
            iron: 300,
            crop: 200
        );

        $this->assertTrue($available->canAfford($required1));
        $this->assertFalse($available->canAfford($required2));  // Not enough wood
    }

    /**
     * @test
     */
    public function it_can_check_if_sufficient_resources_available_with_exact_amounts()
    {
        $available = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $required = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $this->assertTrue($available->canAfford($required));
    }

    /**
     * @test
     */
    public function it_can_get_missing_resources()
    {
        $available = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $required = new ResourceAmounts(
            wood: 1200,
            clay: 700,
            iron: 800,
            crop: 500
        );

        $missing = $available->getMissingResources($required);

        $this->assertEquals(200, $missing->wood);  // Need 200 more wood
        $this->assertEquals(0, $missing->clay);  // Have enough clay
        $this->assertEquals(200, $missing->iron);  // Need 200 more iron
        $this->assertEquals(100, $missing->crop);  // Need 100 more crop
    }

    /**
     * @test
     */
    public function it_returns_zero_missing_when_sufficient_resources()
    {
        $available = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $required = new ResourceAmounts(
            wood: 500,
            clay: 400,
            iron: 300,
            crop: 200
        );

        $missing = $available->getMissingResources($required);

        $this->assertEquals(0, $missing->wood);
        $this->assertEquals(0, $missing->clay);
        $this->assertEquals(0, $missing->iron);
        $this->assertEquals(0, $missing->crop);
    }

    /**
     * @test
     */
    public function it_can_get_resource_as_array()
    {
        $resources = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $array = $resources->toArray();

        $this->assertEquals([
            'wood' => 1000,
            'clay' => 800,
            'iron' => 600,
            'crop' => 400,
        ], $array);
    }

    /**
     * @test
     */
    public function it_can_create_from_array()
    {
        $array = [
            'wood' => 1000,
            'clay' => 800,
            'iron' => 600,
            'crop' => 400,
        ];

        $resources = ResourceAmounts::fromArray($array);

        $this->assertEquals(1000, $resources->wood);
        $this->assertEquals(800, $resources->clay);
        $this->assertEquals(600, $resources->iron);
        $this->assertEquals(400, $resources->crop);
    }

    /**
     * @test
     */
    public function it_can_create_from_partial_array()
    {
        $array = [
            'wood' => 1000,
            'iron' => 600,
        ];

        $resources = ResourceAmounts::fromArray($array);

        $this->assertEquals(1000, $resources->wood);
        $this->assertEquals(0, $resources->clay);
        $this->assertEquals(600, $resources->iron);
        $this->assertEquals(0, $resources->crop);
    }

    /**
     * @test
     */
    public function it_can_check_if_empty()
    {
        $emptyResources = new ResourceAmounts(
            wood: 0,
            clay: 0,
            iron: 0,
            crop: 0
        );

        $nonEmptyResources = new ResourceAmounts(
            wood: 1,
            clay: 0,
            iron: 0,
            crop: 0
        );

        $this->assertTrue($emptyResources->isEmpty());
        $this->assertFalse($nonEmptyResources->isEmpty());
    }

    /**
     * @test
     */
    public function it_can_get_percentage_of_another_resource_amount()
    {
        $resources = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $percentage = $resources->getPercentageOf(50);  // 50% of each resource

        $this->assertEquals(500, $percentage->wood);
        $this->assertEquals(400, $percentage->clay);
        $this->assertEquals(300, $percentage->iron);
        $this->assertEquals(200, $percentage->crop);
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_unserialized()
    {
        $original = new ResourceAmounts(
            wood: 1234,
            clay: 5678,
            iron: 9012,
            crop: 3456
        );

        $serialized = serialize($original);
        $unserialized = unserialize($serialized);

        $this->assertEquals($original->wood, $unserialized->wood);
        $this->assertEquals($original->clay, $unserialized->clay);
        $this->assertEquals($original->iron, $unserialized->iron);
        $this->assertEquals($original->crop, $unserialized->crop);
    }

    /**
     * @test
     */
    public function it_maintains_immutability()
    {
        $original = new ResourceAmounts(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400
        );

        $added = new ResourceAmounts(
            wood: 100,
            clay: 100,
            iron: 100,
            crop: 100
        );

        $result = $original->add($added);

        // Original should not be modified
        $this->assertEquals(1000, $original->wood);
        $this->assertEquals(800, $original->clay);
        $this->assertEquals(600, $original->iron);
        $this->assertEquals(400, $original->crop);

        // Result should have new values
        $this->assertEquals(1100, $result->wood);
        $this->assertEquals(900, $result->clay);
        $this->assertEquals(700, $result->iron);
        $this->assertEquals(500, $result->crop);
    }
}
