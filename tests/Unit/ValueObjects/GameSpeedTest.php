<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\GameSpeed;
use Tests\TestCase;

class GameSpeedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_game_speed_with_defaults()
    {
        $speed = new GameSpeed();

        $this->assertEquals(1.0, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_speed_with_multiplier()
    {
        $speed = new GameSpeed(2.5);

        $this->assertEquals(2.5, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_speed_from_percentage()
    {
        $speed = GameSpeed::fromPercentage(150);

        $this->assertEquals(1.5, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_speed_from_percentage_with_100()
    {
        $speed = GameSpeed::fromPercentage(100);

        $this->assertEquals(1.0, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_speed_from_percentage_with_50()
    {
        $speed = GameSpeed::fromPercentage(50);

        $this->assertEquals(0.5, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_speed_from_percentage_with_200()
    {
        $speed = GameSpeed::fromPercentage(200);

        $this->assertEquals(2.0, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_speed_from_percentage_with_zero()
    {
        $speed = GameSpeed::fromPercentage(0);

        $this->assertEquals(0.0, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_speed_from_percentage_with_negative()
    {
        $speed = GameSpeed::fromPercentage(-50);

        $this->assertEquals(0.0, $speed->multiplier);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_can_create_game_speed_from_percentage_with_very_large()
    {
        $speed = GameSpeed::fromPercentage(1000);

        $this->assertEquals(10.0, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_calculates_percentage_correctly()
    {
        $speed = new GameSpeed(1.5);

        $this->assertEquals(150, $speed->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_normal_speed()
    {
        $speed = new GameSpeed(1.0);

        $this->assertEquals(100, $speed->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_half_speed()
    {
        $speed = new GameSpeed(0.5);

        $this->assertEquals(50, $speed->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_double_speed()
    {
        $speed = new GameSpeed(2.0);

        $this->assertEquals(200, $speed->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_zero_speed()
    {
        $speed = new GameSpeed(0.0);

        $this->assertEquals(0, $speed->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_decimal_speed()
    {
        $speed = new GameSpeed(1.25);

        $this->assertEquals(125, $speed->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_very_small_speed()
    {
        $speed = new GameSpeed(0.1);

        $this->assertEquals(10, $speed->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_very_large_speed()
    {
        $speed = new GameSpeed(5.0);

        $this->assertEquals(500, $speed->getPercentage());
    }

    /**
     * @test
     */
    public function it_identifies_normal_speed()
    {
        $normalSpeed = new GameSpeed(1.0);
        $fastSpeed = new GameSpeed(2.0);
        $slowSpeed = new GameSpeed(0.5);

        $this->assertTrue($normalSpeed->isNormal());
        $this->assertFalse($fastSpeed->isNormal());
        $this->assertFalse($slowSpeed->isNormal());
    }

    /**
     * @test
     */
    public function it_identifies_fast_speed()
    {
        $normalSpeed = new GameSpeed(1.0);
        $fastSpeed = new GameSpeed(2.0);
        $veryFastSpeed = new GameSpeed(5.0);

        $this->assertFalse($normalSpeed->isFast());
        $this->assertTrue($fastSpeed->isFast());
        $this->assertTrue($veryFastSpeed->isFast());
    }

    /**
     * @test
     */
    public function it_identifies_slow_speed()
    {
        $normalSpeed = new GameSpeed(1.0);
        $slowSpeed = new GameSpeed(0.5);
        $verySlowSpeed = new GameSpeed(0.1);

        $this->assertFalse($normalSpeed->isSlow());
        $this->assertTrue($slowSpeed->isSlow());
        $this->assertTrue($verySlowSpeed->isSlow());
    }

    /**
     * @test
     */
    public function it_identifies_zero_speed()
    {
        $zeroSpeed = new GameSpeed(0.0);
        $normalSpeed = new GameSpeed(1.0);

        $this->assertTrue($zeroSpeed->isZero());
        $this->assertFalse($normalSpeed->isZero());
    }

    /**
     * @test
     */
    public function it_identifies_positive_speed()
    {
        $positiveSpeed = new GameSpeed(1.5);
        $zeroSpeed = new GameSpeed(0.0);

        $this->assertTrue($positiveSpeed->isPositive());
        $this->assertFalse($zeroSpeed->isPositive());
    }

    /**
     * @test
     */
    public function it_multiplies_speed_correctly()
    {
        $speed = new GameSpeed(2.0);

        $result = $speed->multiply(1.5);

        $this->assertEquals(3.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_multiplies_speed_with_decimal()
    {
        $speed = new GameSpeed(1.5);

        $result = $speed->multiply(0.5);

        $this->assertEquals(0.75, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_multiplies_speed_with_zero()
    {
        $speed = new GameSpeed(2.0);

        $result = $speed->multiply(0);

        $this->assertEquals(0.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_multiplies_speed_with_negative()
    {
        $speed = new GameSpeed(2.0);

        $result = $speed->multiply(-1);

        $this->assertEquals(0.0, $result->multiplier);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_multiplies_speed_with_large_factor()
    {
        $speed = new GameSpeed(1.0);

        $result = $speed->multiply(10);

        $this->assertEquals(10.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_adds_speed_correctly()
    {
        $speed1 = new GameSpeed(1.0);
        $speed2 = new GameSpeed(0.5);

        $result = $speed1->add($speed2);

        $this->assertEquals(1.5, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_adds_speed_with_zeros()
    {
        $speed1 = new GameSpeed(1.0);
        $speed2 = new GameSpeed(0.0);

        $result = $speed1->add($speed2);

        $this->assertEquals(1.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_adds_speed_with_large_values()
    {
        $speed1 = new GameSpeed(5.0);
        $speed2 = new GameSpeed(3.0);

        $result = $speed1->add($speed2);

        $this->assertEquals(8.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_subtracts_speed_correctly()
    {
        $speed1 = new GameSpeed(2.0);
        $speed2 = new GameSpeed(0.5);

        $result = $speed1->subtract($speed2);

        $this->assertEquals(1.5, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_subtracts_speed_with_negative_result()
    {
        $speed1 = new GameSpeed(0.5);
        $speed2 = new GameSpeed(1.0);

        $result = $speed1->subtract($speed2);

        $this->assertEquals(0.0, $result->multiplier);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_subtracts_speed_with_zeros()
    {
        $speed1 = new GameSpeed(1.0);
        $speed2 = new GameSpeed(0.0);

        $result = $speed1->subtract($speed2);

        $this->assertEquals(1.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_compares_speed_correctly()
    {
        $speed1 = new GameSpeed(2.0);
        $speed2 = new GameSpeed(1.0);

        $this->assertTrue($speed1->isGreaterThan($speed2));
        $this->assertFalse($speed2->isGreaterThan($speed1));
        $this->assertTrue($speed2->isLessThan($speed1));
        $this->assertFalse($speed1->isLessThan($speed2));
        $this->assertTrue($speed1->isGreaterThanOrEqual($speed2));
        $this->assertTrue($speed2->isLessThanOrEqual($speed1));
    }

    /**
     * @test
     */
    public function it_compares_equal_speed()
    {
        $speed1 = new GameSpeed(1.5);
        $speed2 = new GameSpeed(1.5);

        $this->assertFalse($speed1->isGreaterThan($speed2));
        $this->assertFalse($speed2->isGreaterThan($speed1));
        $this->assertFalse($speed1->isLessThan($speed2));
        $this->assertFalse($speed2->isLessThan($speed1));
        $this->assertTrue($speed1->isGreaterThanOrEqual($speed2));
        $this->assertTrue($speed2->isLessThanOrEqual($speed1));
    }

    /**
     * @test
     */
    public function it_compares_speed_with_zeros()
    {
        $speed1 = new GameSpeed(0.0);
        $speed2 = new GameSpeed(1.0);

        $this->assertFalse($speed1->isGreaterThan($speed2));
        $this->assertTrue($speed2->isGreaterThan($speed1));
        $this->assertTrue($speed1->isLessThan($speed2));
        $this->assertFalse($speed2->isLessThan($speed1));
        $this->assertFalse($speed1->isGreaterThanOrEqual($speed2));
        $this->assertTrue($speed2->isGreaterThanOrEqual($speed1));
    }

    /**
     * @test
     */
    public function it_compares_speed_with_large_values()
    {
        $speed1 = new GameSpeed(10.0);
        $speed2 = new GameSpeed(5.0);

        $this->assertTrue($speed1->isGreaterThan($speed2));
        $this->assertFalse($speed2->isGreaterThan($speed1));
        $this->assertTrue($speed2->isLessThan($speed1));
        $this->assertFalse($speed1->isLessThan($speed2));
    }

    /**
     * @test
     */
    public function it_formats_speed_correctly()
    {
        $speed = new GameSpeed(1.5);

        $this->assertEquals('150%', $speed->format());
    }

    /**
     * @test
     */
    public function it_formats_speed_with_normal()
    {
        $speed = new GameSpeed(1.0);

        $this->assertEquals('100%', $speed->format());
    }

    /**
     * @test
     */
    public function it_formats_speed_with_zero()
    {
        $speed = new GameSpeed(0.0);

        $this->assertEquals('0%', $speed->format());
    }

    /**
     * @test
     */
    public function it_formats_speed_with_decimal()
    {
        $speed = new GameSpeed(1.25);

        $this->assertEquals('125%', $speed->format());
    }

    /**
     * @test
     */
    public function it_formats_speed_with_very_small()
    {
        $speed = new GameSpeed(0.1);

        $this->assertEquals('10%', $speed->format());
    }

    /**
     * @test
     */
    public function it_formats_speed_with_very_large()
    {
        $speed = new GameSpeed(5.0);

        $this->assertEquals('500%', $speed->format());
    }

    /**
     * @test
     */
    public function it_converts_to_array()
    {
        $speed = new GameSpeed(1.5);
        $array = $speed->toArray();

        $expected = [
            'multiplier' => 1.5,
            'percentage' => 150,
            'formatted' => '150%',
            'is_normal' => false,
            'is_fast' => true,
            'is_slow' => false,
            'is_zero' => false,
            'is_positive' => true,
        ];

        $this->assertEquals($expected, $array);
    }

    /**
     * @test
     */
    public function it_creates_from_array()
    {
        $data = [
            'multiplier' => 1.5,
        ];

        $speed = GameSpeed::fromArray($data);

        $this->assertEquals(1.5, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_creates_from_array_with_defaults()
    {
        $data = [];

        $speed = GameSpeed::fromArray($data);

        $this->assertEquals(1.0, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_creates_from_array_with_partial_data()
    {
        $data = [
            'percentage' => 150,
        ];

        $speed = GameSpeed::fromArray($data);

        $this->assertEquals(1.5, $speed->multiplier);
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_percentage()
    {
        $speed = new GameSpeed(1.5);

        $this->assertEquals(150, $speed->getPercentage());
        $this->assertEquals('150%', $speed->format());
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_operations()
    {
        $speed1 = new GameSpeed(2.0);
        $speed2 = new GameSpeed(0.5);

        $sum = $speed1->add($speed2);
        $this->assertEquals(2.5, $sum->multiplier);

        $difference = $speed1->subtract($speed2);
        $this->assertEquals(1.5, $difference->multiplier);

        $multiplied = $speed1->multiply(2);
        $this->assertEquals(4.0, $multiplied->multiplier);
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_comparisons()
    {
        $speed1 = new GameSpeed(2.0);
        $speed2 = new GameSpeed(1.0);

        $this->assertTrue($speed1->isGreaterThan($speed2));
        $this->assertFalse($speed2->isGreaterThan($speed1));
        $this->assertTrue($speed2->isLessThan($speed1));
        $this->assertFalse($speed1->isLessThan($speed2));
    }

    /**
     * @test
     */
    public function it_handles_very_large_speed_values()
    {
        $speed = new GameSpeed(100.0);

        $this->assertEquals(100.0, $speed->multiplier);
        $this->assertEquals(10000, $speed->getPercentage());
        $this->assertEquals('10000%', $speed->format());
        $this->assertTrue($speed->isFast());
        $this->assertFalse($speed->isSlow());
        $this->assertFalse($speed->isNormal());
    }

    /**
     * @test
     */
    public function it_handles_very_small_speed_values()
    {
        $speed = new GameSpeed(0.01);

        $this->assertEquals(0.01, $speed->multiplier);
        $this->assertEquals(1, $speed->getPercentage());
        $this->assertEquals('1%', $speed->format());
        $this->assertFalse($speed->isFast());
        $this->assertTrue($speed->isSlow());
        $this->assertFalse($speed->isNormal());
    }

    /**
     * @test
     */
    public function it_handles_complex_speed_operations()
    {
        $speed1 = new GameSpeed(1.5);
        $speed2 = new GameSpeed(0.5);

        // Add
        $sum = $speed1->add($speed2);
        $this->assertEquals(2.0, $sum->multiplier);
        $this->assertEquals(200, $sum->getPercentage());

        // Subtract
        $difference = $speed1->subtract($speed2);
        $this->assertEquals(1.0, $difference->multiplier);
        $this->assertEquals(100, $difference->getPercentage());

        // Multiply
        $multiplied = $speed1->multiply(2);
        $this->assertEquals(3.0, $multiplied->multiplier);
        $this->assertEquals(300, $multiplied->getPercentage());

        // Compare
        $this->assertTrue($speed1->isGreaterThan($speed2));
        $this->assertTrue($sum->isGreaterThan($speed1));
        $this->assertTrue($difference->isLessThan($speed1));
    }

    /**
     * @test
     */
    public function it_handles_speed_from_percentage_with_overflow()
    {
        $speed = GameSpeed::fromPercentage(1000);

        $this->assertEquals(10.0, $speed->multiplier);
        $this->assertEquals(1000, $speed->getPercentage());
    }

    /**
     * @test
     */
    public function it_handles_speed_from_percentage_with_negative()
    {
        $speed = GameSpeed::fromPercentage(-50);

        $this->assertEquals(0.0, $speed->multiplier);  // Should not go negative
        $this->assertEquals(0, $speed->getPercentage());
    }

    /**
     * @test
     */
    public function it_handles_speed_from_percentage_with_mixed_values()
    {
        $speed = GameSpeed::fromPercentage(150);

        $this->assertEquals(1.5, $speed->multiplier);
        $this->assertEquals(150, $speed->getPercentage());
        $this->assertTrue($speed->isFast());
        $this->assertFalse($speed->isSlow());
        $this->assertFalse($speed->isNormal());
    }
}
