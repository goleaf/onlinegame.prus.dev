<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\VillageResources;
use Tests\TestCase;

class VillageResourcesTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_with_valid_resources()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 5000,
            maxClay: 4000,
            maxIron: 3000,
            maxCrop: 2000
        );

        $this->assertEquals(1000, $resources->wood);
        $this->assertEquals(800, $resources->clay);
        $this->assertEquals(600, $resources->iron);
        $this->assertEquals(400, $resources->crop);
        $this->assertEquals(5000, $resources->maxWood);
        $this->assertEquals(4000, $resources->maxClay);
        $this->assertEquals(3000, $resources->maxIron);
        $this->assertEquals(2000, $resources->maxCrop);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_zero_resources()
    {
        $resources = new VillageResources(
            wood: 0,
            clay: 0,
            iron: 0,
            crop: 0,
            maxWood: 1000,
            maxClay: 1000,
            maxIron: 1000,
            maxCrop: 1000
        );

        $this->assertEquals(0, $resources->wood);
        $this->assertEquals(0, $resources->clay);
        $this->assertEquals(0, $resources->iron);
        $this->assertEquals(0, $resources->crop);
    }

    /**
     * @test
     */
    public function it_can_calculate_total_current_resources()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 5000,
            maxClay: 4000,
            maxIron: 3000,
            maxCrop: 2000
        );

        $total = $resources->getTotalCurrent();

        $this->assertEquals(2800, $total);  // 1000 + 800 + 600 + 400
    }

    /**
     * @test
     */
    public function it_can_calculate_total_max_capacity()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 5000,
            maxClay: 4000,
            maxIron: 3000,
            maxCrop: 2000
        );

        $totalMax = $resources->getTotalMaxCapacity();

        $this->assertEquals(14000, $totalMax);  // 5000 + 4000 + 3000 + 2000
    }

    /**
     * @test
     */
    public function it_can_calculate_overall_fill_percentage()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $fillPercentage = $resources->getOverallFillPercentage();

        // Total current: 2800, Total max: 5600, Percentage: 50%
        $this->assertEquals(50.0, $fillPercentage);
    }

    /**
     * @test
     */
    public function it_can_calculate_individual_fill_percentages()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 200,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 1000
        );

        $percentages = $resources->getFillPercentages();

        $this->assertEquals(50.0, $percentages['wood']);  // 1000/2000 = 50%
        $this->assertEquals(50.0, $percentages['clay']);  // 800/1600 = 50%
        $this->assertEquals(50.0, $percentages['iron']);  // 600/1200 = 50%
        $this->assertEquals(20.0, $percentages['crop']);  // 200/1000 = 20%
    }

    /**
     * @test
     */
    public function it_handles_zero_max_capacity_in_fill_percentages()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 0,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $percentages = $resources->getFillPercentages();

        $this->assertEquals(0.0, $percentages['wood']);  // Division by zero handled
        $this->assertEquals(50.0, $percentages['clay']);
        $this->assertEquals(50.0, $percentages['iron']);
        $this->assertEquals(50.0, $percentages['crop']);
    }

    /**
     * @test
     */
    public function it_can_check_if_resource_is_full()
    {
        $resources = new VillageResources(
            wood: 2000,
            clay: 800,
            iron: 1200,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $this->assertTrue($resources->isResourceFull('wood'));
        $this->assertFalse($resources->isResourceFull('clay'));
        $this->assertTrue($resources->isResourceFull('iron'));
        $this->assertFalse($resources->isResourceFull('crop'));
    }

    /**
     * @test
     */
    public function it_can_check_if_any_resource_is_full()
    {
        $resourcesWithFull = new VillageResources(
            wood: 2000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $resourcesWithoutFull = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $this->assertTrue($resourcesWithFull->hasAnyResourceFull());
        $this->assertFalse($resourcesWithoutFull->hasAnyResourceFull());
    }

    /**
     * @test
     */
    public function it_can_get_available_capacity()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $availableCapacity = $resources->getAvailableCapacity();

        $this->assertEquals(1000, $availableCapacity['wood']);  // 2000 - 1000
        $this->assertEquals(800, $availableCapacity['clay']);  // 1600 - 800
        $this->assertEquals(600, $availableCapacity['iron']);  // 1200 - 600
        $this->assertEquals(400, $availableCapacity['crop']);  // 800 - 400
    }

    /**
     * @test
     */
    public function it_can_add_resources_within_capacity()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $result = $resources->addResources(500, 300, 200, 100);

        $this->assertEquals(1500, $result->wood);
        $this->assertEquals(1100, $result->clay);
        $this->assertEquals(800, $result->iron);
        $this->assertEquals(500, $result->crop);

        // Original should remain unchanged
        $this->assertEquals(1000, $resources->wood);
        $this->assertEquals(800, $resources->clay);
        $this->assertEquals(600, $resources->iron);
        $this->assertEquals(400, $resources->crop);
    }

    /**
     * @test
     */
    public function it_caps_resources_at_max_capacity_when_adding()
    {
        $resources = new VillageResources(
            wood: 1800,
            clay: 1500,
            iron: 1100,
            crop: 700,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $result = $resources->addResources(500, 300, 200, 200);

        $this->assertEquals(2000, $result->wood);  // Capped at max
        $this->assertEquals(1600, $result->clay);  // Capped at max
        $this->assertEquals(1200, $result->iron);  // Capped at max
        $this->assertEquals(800, $result->crop);  // Capped at max
    }

    /**
     * @test
     */
    public function it_can_subtract_resources()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $result = $resources->subtractResources(200, 100, 50, 25);

        $this->assertEquals(800, $result->wood);
        $this->assertEquals(700, $result->clay);
        $this->assertEquals(550, $result->iron);
        $this->assertEquals(375, $result->crop);

        // Original should remain unchanged
        $this->assertEquals(1000, $resources->wood);
        $this->assertEquals(800, $resources->clay);
        $this->assertEquals(600, $resources->iron);
        $this->assertEquals(400, $resources->crop);
    }

    /**
     * @test
     */
    public function it_can_subtract_resources_below_zero()
    {
        $resources = new VillageResources(
            wood: 100,
            clay: 80,
            iron: 60,
            crop: 40,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $result = $resources->subtractResources(200, 100, 50, 60);

        $this->assertEquals(-100, $result->wood);
        $this->assertEquals(-20, $result->clay);
        $this->assertEquals(10, $result->iron);
        $this->assertEquals(-20, $result->crop);
    }

    /**
     * @test
     */
    public function it_can_check_if_can_afford_cost()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $this->assertTrue($resources->canAfford(500, 400, 300, 200));
        $this->assertFalse($resources->canAfford(1500, 400, 300, 200));  // Not enough wood
        $this->assertFalse($resources->canAfford(500, 900, 300, 200));  // Not enough clay
        $this->assertFalse($resources->canAfford(500, 400, 700, 200));  // Not enough iron
        $this->assertFalse($resources->canAfford(500, 400, 300, 500));  // Not enough crop
    }

    /**
     * @test
     */
    public function it_can_check_if_can_afford_exact_amounts()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $this->assertTrue($resources->canAfford(1000, 800, 600, 400));
    }

    /**
     * @test
     */
    public function it_can_get_resource_shortage()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $shortage = $resources->getResourceShortage(1200, 900, 700, 500);

        $this->assertEquals(200, $shortage['wood']);  // Need 200 more wood
        $this->assertEquals(100, $shortage['clay']);  // Need 100 more clay
        $this->assertEquals(100, $shortage['iron']);  // Need 100 more iron
        $this->assertEquals(100, $shortage['crop']);  // Need 100 more crop
    }

    /**
     * @test
     */
    public function it_returns_zero_shortage_when_sufficient_resources()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $shortage = $resources->getResourceShortage(500, 400, 300, 200);

        $this->assertEquals(0, $shortage['wood']);
        $this->assertEquals(0, $shortage['clay']);
        $this->assertEquals(0, $shortage['iron']);
        $this->assertEquals(0, $shortage['crop']);
    }

    /**
     * @test
     */
    public function it_can_convert_to_array()
    {
        $resources = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $array = $resources->toArray();

        $expected = [
            'wood' => 1000,
            'clay' => 800,
            'iron' => 600,
            'crop' => 400,
            'max_wood' => 2000,
            'max_clay' => 1600,
            'max_iron' => 1200,
            'max_crop' => 800,
        ];

        $this->assertEquals($expected, $array);
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
            'max_wood' => 2000,
            'max_clay' => 1600,
            'max_iron' => 1200,
            'max_crop' => 800,
        ];

        $resources = VillageResources::fromArray($array);

        $this->assertEquals(1000, $resources->wood);
        $this->assertEquals(800, $resources->clay);
        $this->assertEquals(600, $resources->iron);
        $this->assertEquals(400, $resources->crop);
        $this->assertEquals(2000, $resources->maxWood);
        $this->assertEquals(1600, $resources->maxClay);
        $this->assertEquals(1200, $resources->maxIron);
        $this->assertEquals(800, $resources->maxCrop);
    }

    /**
     * @test
     */
    public function it_can_create_from_partial_array()
    {
        $array = [
            'wood' => 1000,
            'iron' => 600,
            'max_wood' => 2000,
            'max_iron' => 1200,
        ];

        $resources = VillageResources::fromArray($array);

        $this->assertEquals(1000, $resources->wood);
        $this->assertEquals(0, $resources->clay);
        $this->assertEquals(600, $resources->iron);
        $this->assertEquals(0, $resources->crop);
        $this->assertEquals(2000, $resources->maxWood);
        $this->assertEquals(0, $resources->maxClay);
        $this->assertEquals(1200, $resources->maxIron);
        $this->assertEquals(0, $resources->maxCrop);
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_unserialized()
    {
        $original = new VillageResources(
            wood: 1234,
            clay: 5678,
            iron: 9012,
            crop: 3456,
            maxWood: 12340,
            maxClay: 56780,
            maxIron: 90120,
            maxCrop: 34560
        );

        $serialized = serialize($original);
        $unserialized = unserialize($serialized);

        $this->assertEquals($original->wood, $unserialized->wood);
        $this->assertEquals($original->clay, $unserialized->clay);
        $this->assertEquals($original->iron, $unserialized->iron);
        $this->assertEquals($original->crop, $unserialized->crop);
        $this->assertEquals($original->maxWood, $unserialized->maxWood);
        $this->assertEquals($original->maxClay, $unserialized->maxClay);
        $this->assertEquals($original->maxIron, $unserialized->maxIron);
        $this->assertEquals($original->maxCrop, $unserialized->maxCrop);
    }

    /**
     * @test
     */
    public function it_maintains_immutability()
    {
        $original = new VillageResources(
            wood: 1000,
            clay: 800,
            iron: 600,
            crop: 400,
            maxWood: 2000,
            maxClay: 1600,
            maxIron: 1200,
            maxCrop: 800
        );

        $result = $original->addResources(100, 100, 100, 100);

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
