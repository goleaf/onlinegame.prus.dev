<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\GameTime;
use Tests\TestCase;

class GameTimeTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_game_time_with_defaults()
    {
        $gameTime = new GameTime();

        $this->assertEquals(0, $gameTime->seconds);
    }

    /**
     * @test
     */
    public function it_can_create_game_time_with_seconds()
    {
        $gameTime = new GameTime(3661);  // 1 hour, 1 minute, 1 second

        $this->assertEquals(3661, $gameTime->seconds);
    }

    /**
     * @test
     */
    public function it_can_create_game_time_from_components()
    {
        $gameTime = GameTime::fromComponents(1, 1, 1);  // 1 hour, 1 minute, 1 second

        $this->assertEquals(3661, $gameTime->seconds);
    }

    /**
     * @test
     */
    public function it_can_create_game_time_from_components_with_zeros()
    {
        $gameTime = GameTime::fromComponents(0, 0, 0);

        $this->assertEquals(0, $gameTime->seconds);
    }

    /**
     * @test
     */
    public function it_can_create_game_time_from_components_with_large_values()
    {
        $gameTime = GameTime::fromComponents(25, 61, 61);  // 25 hours, 61 minutes, 61 seconds

        $this->assertEquals(93661, $gameTime->seconds);
    }

    /**
     * @test
     */
    public function it_calculates_hours_correctly()
    {
        $gameTime = new GameTime(3661);  // 1 hour, 1 minute, 1 second

        $this->assertEquals(1, $gameTime->getHours());
    }

    /**
     * @test
     */
    public function it_calculates_hours_with_zero()
    {
        $gameTime = new GameTime(61);  // 1 minute, 1 second

        $this->assertEquals(0, $gameTime->getHours());
    }

    /**
     * @test
     */
    public function it_calculates_hours_with_large_value()
    {
        $gameTime = new GameTime(90000);  // 25 hours

        $this->assertEquals(25, $gameTime->getHours());
    }

    /**
     * @test
     */
    public function it_calculates_minutes_correctly()
    {
        $gameTime = new GameTime(3661);  // 1 hour, 1 minute, 1 second

        $this->assertEquals(1, $gameTime->getMinutes());
    }

    /**
     * @test
     */
    public function it_calculates_minutes_with_zero()
    {
        $gameTime = new GameTime(3600);  // 1 hour

        $this->assertEquals(0, $gameTime->getMinutes());
    }

    /**
     * @test
     */
    public function it_calculates_minutes_with_large_value()
    {
        $gameTime = new GameTime(90000);  // 25 hours

        $this->assertEquals(0, $gameTime->getMinutes());  // 25 hours = 0 minutes in the hour component
    }

    /**
     * @test
     */
    public function it_calculates_seconds_correctly()
    {
        $gameTime = new GameTime(3661);  // 1 hour, 1 minute, 1 second

        $this->assertEquals(1, $gameTime->getSeconds());
    }

    /**
     * @test
     */
    public function it_calculates_seconds_with_zero()
    {
        $gameTime = new GameTime(3600);  // 1 hour

        $this->assertEquals(0, $gameTime->getSeconds());
    }

    /**
     * @test
     */
    public function it_calculates_seconds_with_large_value()
    {
        $gameTime = new GameTime(90000);  // 25 hours

        $this->assertEquals(0, $gameTime->getSeconds());  // 25 hours = 0 seconds in the second component
    }

    /**
     * @test
     */
    public function it_formats_time_correctly()
    {
        $gameTime = new GameTime(3661);  // 1 hour, 1 minute, 1 second

        $this->assertEquals('01:01:01', $gameTime->format());
    }

    /**
     * @test
     */
    public function it_formats_time_with_zeros()
    {
        $gameTime = new GameTime(0);

        $this->assertEquals('00:00:00', $gameTime->format());
    }

    /**
     * @test
     */
    public function it_formats_time_with_large_values()
    {
        $gameTime = new GameTime(90000);  // 25 hours

        $this->assertEquals('25:00:00', $gameTime->format());
    }

    /**
     * @test
     */
    public function it_formats_time_with_custom_format()
    {
        $gameTime = new GameTime(3661);  // 1 hour, 1 minute, 1 second

        $this->assertEquals('1h 1m 1s', $gameTime->format('H\h i\m s\s'));
    }

    /**
     * @test
     */
    public function it_formats_time_with_compact_format()
    {
        $gameTime = new GameTime(3661);  // 1 hour, 1 minute, 1 second

        $this->assertEquals('1h 1m', $gameTime->formatCompact());
    }

    /**
     * @test
     */
    public function it_formats_time_with_compact_format_seconds_only()
    {
        $gameTime = new GameTime(61);  // 1 minute, 1 second

        $this->assertEquals('1m 1s', $gameTime->formatCompact());
    }

    /**
     * @test
     */
    public function it_formats_time_with_compact_format_hours_only()
    {
        $gameTime = new GameTime(3600);  // 1 hour

        $this->assertEquals('1h', $gameTime->formatCompact());
    }

    /**
     * @test
     */
    public function it_formats_time_with_compact_format_seconds_only_small()
    {
        $gameTime = new GameTime(30);  // 30 seconds

        $this->assertEquals('30s', $gameTime->formatCompact());
    }

    /**
     * @test
     */
    public function it_adds_time_correctly()
    {
        $gameTime1 = new GameTime(3600);  // 1 hour
        $gameTime2 = new GameTime(1800);  // 30 minutes

        $result = $gameTime1->add($gameTime2);

        $this->assertEquals(5400, $result->seconds);  // 1.5 hours
    }

    /**
     * @test
     */
    public function it_adds_time_with_zeros()
    {
        $gameTime1 = new GameTime(3600);  // 1 hour
        $gameTime2 = new GameTime(0);  // 0 seconds

        $result = $gameTime1->add($gameTime2);

        $this->assertEquals(3600, $result->seconds);
    }

    /**
     * @test
     */
    public function it_adds_time_with_large_values()
    {
        $gameTime1 = new GameTime(90000);  // 25 hours
        $gameTime2 = new GameTime(3600);  // 1 hour

        $result = $gameTime1->add($gameTime2);

        $this->assertEquals(93600, $result->seconds);  // 26 hours
    }

    /**
     * @test
     */
    public function it_subtracts_time_correctly()
    {
        $gameTime1 = new GameTime(5400);  // 1.5 hours
        $gameTime2 = new GameTime(1800);  // 30 minutes

        $result = $gameTime1->subtract($gameTime2);

        $this->assertEquals(3600, $result->seconds);  // 1 hour
    }

    /**
     * @test
     */
    public function it_subtracts_time_with_negative_result()
    {
        $gameTime1 = new GameTime(1800);  // 30 minutes
        $gameTime2 = new GameTime(3600);  // 1 hour

        $result = $gameTime1->subtract($gameTime2);

        $this->assertEquals(0, $result->seconds);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_subtracts_time_with_zeros()
    {
        $gameTime1 = new GameTime(3600);  // 1 hour
        $gameTime2 = new GameTime(0);  // 0 seconds

        $result = $gameTime1->subtract($gameTime2);

        $this->assertEquals(3600, $result->seconds);
    }

    /**
     * @test
     */
    public function it_multiplies_time_correctly()
    {
        $gameTime = new GameTime(3600);  // 1 hour

        $result = $gameTime->multiply(2);

        $this->assertEquals(7200, $result->seconds);  // 2 hours
    }

    /**
     * @test
     */
    public function it_multiplies_time_with_decimal()
    {
        $gameTime = new GameTime(3600);  // 1 hour

        $result = $gameTime->multiply(1.5);

        $this->assertEquals(5400, $result->seconds);  // 1.5 hours
    }

    /**
     * @test
     */
    public function it_multiplies_time_with_zero()
    {
        $gameTime = new GameTime(3600);  // 1 hour

        $result = $gameTime->multiply(0);

        $this->assertEquals(0, $result->seconds);
    }

    /**
     * @test
     */
    public function it_multiplies_time_with_large_factor()
    {
        $gameTime = new GameTime(3600);  // 1 hour

        $result = $gameTime->multiply(24);

        $this->assertEquals(86400, $result->seconds);  // 24 hours
    }

    /**
     * @test
     */
    public function it_compares_time_correctly()
    {
        $gameTime1 = new GameTime(3600);  // 1 hour
        $gameTime2 = new GameTime(1800);  // 30 minutes

        $this->assertTrue($gameTime1->isGreaterThan($gameTime2));
        $this->assertFalse($gameTime2->isGreaterThan($gameTime1));
        $this->assertTrue($gameTime2->isLessThan($gameTime1));
        $this->assertFalse($gameTime1->isLessThan($gameTime2));
        $this->assertTrue($gameTime1->isGreaterThanOrEqual($gameTime2));
        $this->assertTrue($gameTime2->isLessThanOrEqual($gameTime1));
    }

    /**
     * @test
     */
    public function it_compares_equal_time()
    {
        $gameTime1 = new GameTime(3600);  // 1 hour
        $gameTime2 = new GameTime(3600);  // 1 hour

        $this->assertFalse($gameTime1->isGreaterThan($gameTime2));
        $this->assertFalse($gameTime2->isGreaterThan($gameTime1));
        $this->assertFalse($gameTime1->isLessThan($gameTime2));
        $this->assertFalse($gameTime2->isLessThan($gameTime1));
        $this->assertTrue($gameTime1->isGreaterThanOrEqual($gameTime2));
        $this->assertTrue($gameTime2->isLessThanOrEqual($gameTime1));
    }

    /**
     * @test
     */
    public function it_compares_time_with_zeros()
    {
        $gameTime1 = new GameTime(0);
        $gameTime2 = new GameTime(3600);  // 1 hour

        $this->assertFalse($gameTime1->isGreaterThan($gameTime2));
        $this->assertTrue($gameTime2->isGreaterThan($gameTime1));
        $this->assertTrue($gameTime1->isLessThan($gameTime2));
        $this->assertFalse($gameTime2->isLessThan($gameTime1));
        $this->assertFalse($gameTime1->isGreaterThanOrEqual($gameTime2));
        $this->assertTrue($gameTime2->isGreaterThanOrEqual($gameTime1));
    }

    /**
     * @test
     */
    public function it_compares_time_with_large_values()
    {
        $gameTime1 = new GameTime(90000);  // 25 hours
        $gameTime2 = new GameTime(86400);  // 24 hours

        $this->assertTrue($gameTime1->isGreaterThan($gameTime2));
        $this->assertFalse($gameTime2->isGreaterThan($gameTime1));
        $this->assertTrue($gameTime2->isLessThan($gameTime1));
        $this->assertFalse($gameTime1->isLessThan($gameTime2));
    }

    /**
     * @test
     */
    public function it_identifies_zero_time()
    {
        $zeroTime = new GameTime(0);
        $nonZeroTime = new GameTime(3600);  // 1 hour

        $this->assertTrue($zeroTime->isZero());
        $this->assertFalse($nonZeroTime->isZero());
    }

    /**
     * @test
     */
    public function it_identifies_positive_time()
    {
        $positiveTime = new GameTime(3600);  // 1 hour
        $zeroTime = new GameTime(0);

        $this->assertTrue($positiveTime->isPositive());
        $this->assertFalse($zeroTime->isPositive());
    }

    /**
     * @test
     */
    public function it_converts_to_array()
    {
        $gameTime = new GameTime(3661);  // 1 hour, 1 minute, 1 second
        $array = $gameTime->toArray();

        $expected = [
            'seconds' => 3661,
            'hours' => 1,
            'minutes' => 1,
            'seconds_component' => 1,
            'formatted' => '01:01:01',
            'formatted_compact' => '1h 1m',
        ];

        $this->assertEquals($expected, $array);
    }

    /**
     * @test
     */
    public function it_creates_from_array()
    {
        $data = [
            'seconds' => 3661,
        ];

        $gameTime = GameTime::fromArray($data);

        $this->assertEquals(3661, $gameTime->seconds);
    }

    /**
     * @test
     */
    public function it_creates_from_array_with_defaults()
    {
        $data = [];

        $gameTime = GameTime::fromArray($data);

        $this->assertEquals(0, $gameTime->seconds);
    }

    /**
     * @test
     */
    public function it_creates_from_array_with_partial_data()
    {
        $data = [
            'hours' => 1,
            'minutes' => 1,
            'seconds_component' => 1,
        ];

        $gameTime = GameTime::fromArray($data);

        $this->assertEquals(3661, $gameTime->seconds);
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_components()
    {
        $gameTime = new GameTime(3661);  // 1 hour, 1 minute, 1 second

        $this->assertEquals(1, $gameTime->getHours());
        $this->assertEquals(1, $gameTime->getMinutes());
        $this->assertEquals(1, $gameTime->getSeconds());
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_formatting()
    {
        $gameTime = new GameTime(3661);  // 1 hour, 1 minute, 1 second

        $this->assertEquals('01:01:01', $gameTime->format());
        $this->assertEquals('1h 1m', $gameTime->formatCompact());
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_operations()
    {
        $gameTime1 = new GameTime(3600);  // 1 hour
        $gameTime2 = new GameTime(1800);  // 30 minutes

        $sum = $gameTime1->add($gameTime2);
        $this->assertEquals(5400, $sum->seconds);

        $difference = $gameTime1->subtract($gameTime2);
        $this->assertEquals(1800, $difference->seconds);

        $multiplied = $gameTime1->multiply(2);
        $this->assertEquals(7200, $multiplied->seconds);
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_comparisons()
    {
        $gameTime1 = new GameTime(3600);  // 1 hour
        $gameTime2 = new GameTime(1800);  // 30 minutes

        $this->assertTrue($gameTime1->isGreaterThan($gameTime2));
        $this->assertFalse($gameTime2->isGreaterThan($gameTime1));
        $this->assertTrue($gameTime2->isLessThan($gameTime1));
        $this->assertFalse($gameTime1->isLessThan($gameTime2));
    }

    /**
     * @test
     */
    public function it_handles_very_large_time_values()
    {
        $gameTime = new GameTime(86400 * 365);  // 1 year in seconds

        $this->assertEquals(31536000, $gameTime->seconds);
        $this->assertEquals(8760, $gameTime->getHours());  // 365 * 24 hours
        $this->assertEquals(0, $gameTime->getMinutes());
        $this->assertEquals(0, $gameTime->getSeconds());
    }

    /**
     * @test
     */
    public function it_handles_very_small_time_values()
    {
        $gameTime = new GameTime(1);  // 1 second

        $this->assertEquals(1, $gameTime->seconds);
        $this->assertEquals(0, $gameTime->getHours());
        $this->assertEquals(0, $gameTime->getMinutes());
        $this->assertEquals(1, $gameTime->getSeconds());
    }

    /**
     * @test
     */
    public function it_handles_complex_time_operations()
    {
        $gameTime1 = new GameTime(3661);  // 1 hour, 1 minute, 1 second
        $gameTime2 = new GameTime(1800);  // 30 minutes

        // Add
        $sum = $gameTime1->add($gameTime2);
        $this->assertEquals(5461, $sum->seconds);
        $this->assertEquals('01:31:01', $sum->format());

        // Subtract
        $difference = $gameTime1->subtract($gameTime2);
        $this->assertEquals(1861, $difference->seconds);
        $this->assertEquals('00:31:01', $difference->format());

        // Multiply
        $multiplied = $gameTime1->multiply(2);
        $this->assertEquals(7322, $multiplied->seconds);
        $this->assertEquals('02:02:02', $multiplied->format());

        // Compare
        $this->assertTrue($gameTime1->isGreaterThan($gameTime2));
        $this->assertTrue($sum->isGreaterThan($gameTime1));
        $this->assertTrue($difference->isLessThan($gameTime1));
    }

    /**
     * @test
     */
    public function it_handles_time_from_components_with_overflow()
    {
        $gameTime = GameTime::fromComponents(25, 61, 61);  // 25 hours, 61 minutes, 61 seconds

        $this->assertEquals(93661, $gameTime->seconds);
        $this->assertEquals(26, $gameTime->getHours());  // 25 + 1 (from 61 minutes)
        $this->assertEquals(1, $gameTime->getMinutes());  // 61 - 60 = 1
        $this->assertEquals(1, $gameTime->getSeconds());  // 61 - 60 = 1
    }

    /**
     * @test
     */
    public function it_handles_time_from_components_with_negative_values()
    {
        $gameTime = GameTime::fromComponents(-1, -1, -1);  // Negative values

        $this->assertEquals(0, $gameTime->seconds);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_handles_time_from_components_with_mixed_values()
    {
        $gameTime = GameTime::fromComponents(1, -30, 90);  // 1 hour, -30 minutes, 90 seconds

        $this->assertEquals(3600, $gameTime->seconds);  // Should handle negative minutes
    }
}
