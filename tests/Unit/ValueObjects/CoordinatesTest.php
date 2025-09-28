<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Coordinates;
use Tests\TestCase;

class CoordinatesTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_with_valid_coordinates()
    {
        $coordinates = new Coordinates(100, 200);

        $this->assertEquals(100, $coordinates->x);
        $this->assertEquals(200, $coordinates->y);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_zero_coordinates()
    {
        $coordinates = new Coordinates(0, 0);

        $this->assertEquals(0, $coordinates->x);
        $this->assertEquals(0, $coordinates->y);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_negative_coordinates()
    {
        $coordinates = new Coordinates(-50, -75);

        $this->assertEquals(-50, $coordinates->x);
        $this->assertEquals(-75, $coordinates->y);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_large_coordinates()
    {
        $coordinates = new Coordinates(999999, 888888);

        $this->assertEquals(999999, $coordinates->x);
        $this->assertEquals(888888, $coordinates->y);
    }

    /**
     * @test
     */
    public function it_can_calculate_distance_to_another_coordinate()
    {
        $coord1 = new Coordinates(0, 0);
        $coord2 = new Coordinates(3, 4);

        $distance = $coord1->distanceTo($coord2);

        $this->assertEquals(5.0, $distance);  // 3-4-5 triangle
    }

    /**
     * @test
     */
    public function it_can_calculate_distance_to_same_coordinate()
    {
        $coord1 = new Coordinates(100, 200);
        $coord2 = new Coordinates(100, 200);

        $distance = $coord1->distanceTo($coord2);

        $this->assertEquals(0.0, $distance);
    }

    /**
     * @test
     */
    public function it_can_calculate_distance_with_negative_coordinates()
    {
        $coord1 = new Coordinates(-3, -4);
        $coord2 = new Coordinates(0, 0);

        $distance = $coord1->distanceTo($coord2);

        $this->assertEquals(5.0, $distance);
    }

    /**
     * @test
     */
    public function it_can_check_if_coordinates_are_equal()
    {
        $coord1 = new Coordinates(100, 200);
        $coord2 = new Coordinates(100, 200);
        $coord3 = new Coordinates(101, 200);

        $this->assertTrue($coord1->equals($coord2));
        $this->assertFalse($coord1->equals($coord3));
    }

    /**
     * @test
     */
    public function it_can_check_if_coordinates_are_within_range()
    {
        $center = new Coordinates(100, 100);
        $nearbyCoord = new Coordinates(103, 104);
        $farCoord = new Coordinates(200, 200);

        $this->assertTrue($center->isWithinRange($nearbyCoord, 10));
        $this->assertFalse($center->isWithinRange($farCoord, 10));
    }

    /**
     * @test
     */
    public function it_can_check_if_coordinates_are_within_exact_range()
    {
        $center = new Coordinates(0, 0);
        $exactRangeCoord = new Coordinates(3, 4);  // Distance = 5

        $this->assertTrue($center->isWithinRange($exactRangeCoord, 5));
        $this->assertFalse($center->isWithinRange($exactRangeCoord, 4.9));
    }

    /**
     * @test
     */
    public function it_can_get_manhattan_distance()
    {
        $coord1 = new Coordinates(0, 0);
        $coord2 = new Coordinates(3, 4);

        $manhattanDistance = $coord1->manhattanDistanceTo($coord2);

        $this->assertEquals(7, $manhattanDistance);  // |3-0| + |4-0| = 7
    }

    /**
     * @test
     */
    public function it_can_get_manhattan_distance_with_negative_coordinates()
    {
        $coord1 = new Coordinates(-2, -3);
        $coord2 = new Coordinates(1, 2);

        $manhattanDistance = $coord1->manhattanDistanceTo($coord2);

        $this->assertEquals(8, $manhattanDistance);  // |1-(-2)| + |2-(-3)| = 3 + 5 = 8
    }

    /**
     * @test
     */
    public function it_can_move_by_offset()
    {
        $original = new Coordinates(100, 200);
        $moved = $original->moveBy(50, -30);

        $this->assertEquals(150, $moved->x);
        $this->assertEquals(170, $moved->y);

        // Original should remain unchanged
        $this->assertEquals(100, $original->x);
        $this->assertEquals(200, $original->y);
    }

    /**
     * @test
     */
    public function it_can_move_by_zero_offset()
    {
        $original = new Coordinates(100, 200);
        $moved = $original->moveBy(0, 0);

        $this->assertEquals(100, $moved->x);
        $this->assertEquals(200, $moved->y);
        $this->assertTrue($original->equals($moved));
    }

    /**
     * @test
     */
    public function it_can_get_neighbors()
    {
        $center = new Coordinates(100, 100);
        $neighbors = $center->getNeighbors();

        $expectedNeighbors = [
            new Coordinates(99, 100),  // Left
            new Coordinates(101, 100),  // Right
            new Coordinates(100, 99),  // Up
            new Coordinates(100, 101),  // Down
            new Coordinates(99, 99),  // Top-left
            new Coordinates(101, 99),  // Top-right
            new Coordinates(99, 101),  // Bottom-left
            new Coordinates(101, 101),  // Bottom-right
        ];

        $this->assertCount(8, $neighbors);

        foreach ($expectedNeighbors as $expected) {
            $found = false;
            foreach ($neighbors as $neighbor) {
                if ($neighbor->equals($expected)) {
                    $found = true;

                    break;
                }
            }
            $this->assertTrue($found, "Expected neighbor ({$expected->x}, {$expected->y}) not found");
        }
    }

    /**
     * @test
     */
    public function it_can_get_neighbors_within_radius()
    {
        $center = new Coordinates(0, 0);
        $neighbors = $center->getNeighborsWithinRadius(2);

        // Should include all coordinates within distance 2
        $this->assertGreaterThan(8, count($neighbors));  // More than just adjacent neighbors

        // Check that all neighbors are within radius
        foreach ($neighbors as $neighbor) {
            $this->assertLessThanOrEqual(2, $center->distanceTo($neighbor));
        }

        // Check that center is not included
        foreach ($neighbors as $neighbor) {
            $this->assertFalse($neighbor->equals($center));
        }
    }

    /**
     * @test
     */
    public function it_can_convert_to_string()
    {
        $coordinates = new Coordinates(100, 200);
        $string = $coordinates->toString();

        $this->assertEquals('(100, 200)', $string);
    }

    /**
     * @test
     */
    public function it_can_convert_to_string_with_negative_coordinates()
    {
        $coordinates = new Coordinates(-50, -75);
        $string = $coordinates->toString();

        $this->assertEquals('(-50, -75)', $string);
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_unserialized()
    {
        $original = new Coordinates(123, 456);

        $serialized = serialize($original);
        $unserialized = unserialize($serialized);

        $this->assertEquals($original->x, $unserialized->x);
        $this->assertEquals($original->y, $unserialized->y);
        $this->assertTrue($original->equals($unserialized));
    }

    /**
     * @test
     */
    public function it_maintains_immutability()
    {
        $original = new Coordinates(100, 200);
        $moved = $original->moveBy(50, 30);

        // Original should not be modified
        $this->assertEquals(100, $original->x);
        $this->assertEquals(200, $original->y);

        // New instance should have new values
        $this->assertEquals(150, $moved->x);
        $this->assertEquals(230, $moved->y);
    }
}
