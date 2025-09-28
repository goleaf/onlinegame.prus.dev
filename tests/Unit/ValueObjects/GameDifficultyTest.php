<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\GameDifficulty;
use Tests\TestCase;

class GameDifficultyTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_game_difficulty_with_defaults()
    {
        $difficulty = new GameDifficulty();

        $this->assertEquals(1.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_with_multiplier()
    {
        $difficulty = new GameDifficulty(2.5);

        $this->assertEquals(2.5, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_level()
    {
        $difficulty = GameDifficulty::fromLevel(3);

        $this->assertEquals(3.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_level_with_one()
    {
        $difficulty = GameDifficulty::fromLevel(1);

        $this->assertEquals(1.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_level_with_zero()
    {
        $difficulty = GameDifficulty::fromLevel(0);

        $this->assertEquals(0.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_level_with_negative()
    {
        $difficulty = GameDifficulty::fromLevel(-1);

        $this->assertEquals(0.0, $difficulty->multiplier);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_level_with_large_value()
    {
        $difficulty = GameDifficulty::fromLevel(10);

        $this->assertEquals(10.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_percentage()
    {
        $difficulty = GameDifficulty::fromPercentage(150);

        $this->assertEquals(1.5, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_percentage_with_100()
    {
        $difficulty = GameDifficulty::fromPercentage(100);

        $this->assertEquals(1.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_percentage_with_50()
    {
        $difficulty = GameDifficulty::fromPercentage(50);

        $this->assertEquals(0.5, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_percentage_with_200()
    {
        $difficulty = GameDifficulty::fromPercentage(200);

        $this->assertEquals(2.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_percentage_with_zero()
    {
        $difficulty = GameDifficulty::fromPercentage(0);

        $this->assertEquals(0.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_percentage_with_negative()
    {
        $difficulty = GameDifficulty::fromPercentage(-50);

        $this->assertEquals(0.0, $difficulty->multiplier);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_can_create_game_difficulty_from_percentage_with_very_large()
    {
        $difficulty = GameDifficulty::fromPercentage(1000);

        $this->assertEquals(10.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_calculates_level_correctly()
    {
        $difficulty = new GameDifficulty(3.0);

        $this->assertEquals(3, $difficulty->getLevel());
    }

    /**
     * @test
     */
    public function it_calculates_level_with_decimal()
    {
        $difficulty = new GameDifficulty(2.5);

        $this->assertEquals(2, $difficulty->getLevel());  // Should round down
    }

    /**
     * @test
     */
    public function it_calculates_level_with_zero()
    {
        $difficulty = new GameDifficulty(0.0);

        $this->assertEquals(0, $difficulty->getLevel());
    }

    /**
     * @test
     */
    public function it_calculates_level_with_very_small()
    {
        $difficulty = new GameDifficulty(0.1);

        $this->assertEquals(0, $difficulty->getLevel());
    }

    /**
     * @test
     */
    public function it_calculates_level_with_very_large()
    {
        $difficulty = new GameDifficulty(15.0);

        $this->assertEquals(15, $difficulty->getLevel());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_correctly()
    {
        $difficulty = new GameDifficulty(1.5);

        $this->assertEquals(150, $difficulty->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_normal()
    {
        $difficulty = new GameDifficulty(1.0);

        $this->assertEquals(100, $difficulty->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_half()
    {
        $difficulty = new GameDifficulty(0.5);

        $this->assertEquals(50, $difficulty->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_double()
    {
        $difficulty = new GameDifficulty(2.0);

        $this->assertEquals(200, $difficulty->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_zero()
    {
        $difficulty = new GameDifficulty(0.0);

        $this->assertEquals(0, $difficulty->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_decimal()
    {
        $difficulty = new GameDifficulty(1.25);

        $this->assertEquals(125, $difficulty->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_very_small()
    {
        $difficulty = new GameDifficulty(0.1);

        $this->assertEquals(10, $difficulty->getPercentage());
    }

    /**
     * @test
     */
    public function it_calculates_percentage_with_very_large()
    {
        $difficulty = new GameDifficulty(5.0);

        $this->assertEquals(500, $difficulty->getPercentage());
    }

    /**
     * @test
     */
    public function it_identifies_easy_difficulty()
    {
        $easy = new GameDifficulty(0.5);
        $normal = new GameDifficulty(1.0);
        $hard = new GameDifficulty(2.0);

        $this->assertTrue($easy->isEasy());
        $this->assertFalse($normal->isEasy());
        $this->assertFalse($hard->isEasy());
    }

    /**
     * @test
     */
    public function it_identifies_normal_difficulty()
    {
        $easy = new GameDifficulty(0.5);
        $normal = new GameDifficulty(1.0);
        $hard = new GameDifficulty(2.0);

        $this->assertFalse($easy->isNormal());
        $this->assertTrue($normal->isNormal());
        $this->assertFalse($hard->isNormal());
    }

    /**
     * @test
     */
    public function it_identifies_hard_difficulty()
    {
        $easy = new GameDifficulty(0.5);
        $normal = new GameDifficulty(1.0);
        $hard = new GameDifficulty(2.0);

        $this->assertFalse($easy->isHard());
        $this->assertFalse($normal->isHard());
        $this->assertTrue($hard->isHard());
    }

    /**
     * @test
     */
    public function it_identifies_very_hard_difficulty()
    {
        $normal = new GameDifficulty(1.0);
        $hard = new GameDifficulty(2.0);
        $veryHard = new GameDifficulty(5.0);

        $this->assertFalse($normal->isVeryHard());
        $this->assertFalse($hard->isVeryHard());
        $this->assertTrue($veryHard->isVeryHard());
    }

    /**
     * @test
     */
    public function it_identifies_zero_difficulty()
    {
        $zero = new GameDifficulty(0.0);
        $normal = new GameDifficulty(1.0);

        $this->assertTrue($zero->isZero());
        $this->assertFalse($normal->isZero());
    }

    /**
     * @test
     */
    public function it_identifies_positive_difficulty()
    {
        $positive = new GameDifficulty(1.5);
        $zero = new GameDifficulty(0.0);

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($zero->isPositive());
    }

    /**
     * @test
     */
    public function it_multiplies_difficulty_correctly()
    {
        $difficulty = new GameDifficulty(2.0);

        $result = $difficulty->multiply(1.5);

        $this->assertEquals(3.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_multiplies_difficulty_with_decimal()
    {
        $difficulty = new GameDifficulty(1.5);

        $result = $difficulty->multiply(0.5);

        $this->assertEquals(0.75, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_multiplies_difficulty_with_zero()
    {
        $difficulty = new GameDifficulty(2.0);

        $result = $difficulty->multiply(0);

        $this->assertEquals(0.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_multiplies_difficulty_with_negative()
    {
        $difficulty = new GameDifficulty(2.0);

        $result = $difficulty->multiply(-1);

        $this->assertEquals(0.0, $result->multiplier);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_multiplies_difficulty_with_large_factor()
    {
        $difficulty = new GameDifficulty(1.0);

        $result = $difficulty->multiply(10);

        $this->assertEquals(10.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_adds_difficulty_correctly()
    {
        $difficulty1 = new GameDifficulty(1.0);
        $difficulty2 = new GameDifficulty(0.5);

        $result = $difficulty1->add($difficulty2);

        $this->assertEquals(1.5, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_adds_difficulty_with_zeros()
    {
        $difficulty1 = new GameDifficulty(1.0);
        $difficulty2 = new GameDifficulty(0.0);

        $result = $difficulty1->add($difficulty2);

        $this->assertEquals(1.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_adds_difficulty_with_large_values()
    {
        $difficulty1 = new GameDifficulty(5.0);
        $difficulty2 = new GameDifficulty(3.0);

        $result = $difficulty1->add($difficulty2);

        $this->assertEquals(8.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_subtracts_difficulty_correctly()
    {
        $difficulty1 = new GameDifficulty(2.0);
        $difficulty2 = new GameDifficulty(0.5);

        $result = $difficulty1->subtract($difficulty2);

        $this->assertEquals(1.5, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_subtracts_difficulty_with_negative_result()
    {
        $difficulty1 = new GameDifficulty(0.5);
        $difficulty2 = new GameDifficulty(1.0);

        $result = $difficulty1->subtract($difficulty2);

        $this->assertEquals(0.0, $result->multiplier);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_subtracts_difficulty_with_zeros()
    {
        $difficulty1 = new GameDifficulty(1.0);
        $difficulty2 = new GameDifficulty(0.0);

        $result = $difficulty1->subtract($difficulty2);

        $this->assertEquals(1.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function it_compares_difficulty_correctly()
    {
        $difficulty1 = new GameDifficulty(2.0);
        $difficulty2 = new GameDifficulty(1.0);

        $this->assertTrue($difficulty1->isGreaterThan($difficulty2));
        $this->assertFalse($difficulty2->isGreaterThan($difficulty1));
        $this->assertTrue($difficulty2->isLessThan($difficulty1));
        $this->assertFalse($difficulty1->isLessThan($difficulty2));
        $this->assertTrue($difficulty1->isGreaterThanOrEqual($difficulty2));
        $this->assertTrue($difficulty2->isLessThanOrEqual($difficulty1));
    }

    /**
     * @test
     */
    public function it_compares_equal_difficulty()
    {
        $difficulty1 = new GameDifficulty(1.5);
        $difficulty2 = new GameDifficulty(1.5);

        $this->assertFalse($difficulty1->isGreaterThan($difficulty2));
        $this->assertFalse($difficulty2->isGreaterThan($difficulty1));
        $this->assertFalse($difficulty1->isLessThan($difficulty2));
        $this->assertFalse($difficulty2->isLessThan($difficulty1));
        $this->assertTrue($difficulty1->isGreaterThanOrEqual($difficulty2));
        $this->assertTrue($difficulty2->isLessThanOrEqual($difficulty1));
    }

    /**
     * @test
     */
    public function it_compares_difficulty_with_zeros()
    {
        $difficulty1 = new GameDifficulty(0.0);
        $difficulty2 = new GameDifficulty(1.0);

        $this->assertFalse($difficulty1->isGreaterThan($difficulty2));
        $this->assertTrue($difficulty2->isGreaterThan($difficulty1));
        $this->assertTrue($difficulty1->isLessThan($difficulty2));
        $this->assertFalse($difficulty2->isLessThan($difficulty1));
        $this->assertFalse($difficulty1->isGreaterThanOrEqual($difficulty2));
        $this->assertTrue($difficulty2->isGreaterThanOrEqual($difficulty1));
    }

    /**
     * @test
     */
    public function it_compares_difficulty_with_large_values()
    {
        $difficulty1 = new GameDifficulty(10.0);
        $difficulty2 = new GameDifficulty(5.0);

        $this->assertTrue($difficulty1->isGreaterThan($difficulty2));
        $this->assertFalse($difficulty2->isGreaterThan($difficulty1));
        $this->assertTrue($difficulty2->isLessThan($difficulty1));
        $this->assertFalse($difficulty1->isLessThan($difficulty2));
    }

    /**
     * @test
     */
    public function it_formats_difficulty_correctly()
    {
        $difficulty = new GameDifficulty(1.5);

        $this->assertEquals('150%', $difficulty->format());
    }

    /**
     * @test
     */
    public function it_formats_difficulty_with_normal()
    {
        $difficulty = new GameDifficulty(1.0);

        $this->assertEquals('100%', $difficulty->format());
    }

    /**
     * @test
     */
    public function it_formats_difficulty_with_zero()
    {
        $difficulty = new GameDifficulty(0.0);

        $this->assertEquals('0%', $difficulty->format());
    }

    /**
     * @test
     */
    public function it_formats_difficulty_with_decimal()
    {
        $difficulty = new GameDifficulty(1.25);

        $this->assertEquals('125%', $difficulty->format());
    }

    /**
     * @test
     */
    public function it_formats_difficulty_with_very_small()
    {
        $difficulty = new GameDifficulty(0.1);

        $this->assertEquals('10%', $difficulty->format());
    }

    /**
     * @test
     */
    public function it_formats_difficulty_with_very_large()
    {
        $difficulty = new GameDifficulty(5.0);

        $this->assertEquals('500%', $difficulty->format());
    }

    /**
     * @test
     */
    public function it_converts_to_array()
    {
        $difficulty = new GameDifficulty(1.5);
        $array = $difficulty->toArray();

        $expected = [
            'multiplier' => 1.5,
            'level' => 1,
            'percentage' => 150,
            'formatted' => '150%',
            'is_easy' => false,
            'is_normal' => false,
            'is_hard' => true,
            'is_very_hard' => false,
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

        $difficulty = GameDifficulty::fromArray($data);

        $this->assertEquals(1.5, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_creates_from_array_with_defaults()
    {
        $data = [];

        $difficulty = GameDifficulty::fromArray($data);

        $this->assertEquals(1.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_creates_from_array_with_partial_data()
    {
        $data = [
            'level' => 3,
        ];

        $difficulty = GameDifficulty::fromArray($data);

        $this->assertEquals(3.0, $difficulty->multiplier);
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_level()
    {
        $difficulty = new GameDifficulty(2.5);

        $this->assertEquals(2, $difficulty->getLevel());  // Should round down
        $this->assertEquals(250, $difficulty->getPercentage());
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_operations()
    {
        $difficulty1 = new GameDifficulty(2.0);
        $difficulty2 = new GameDifficulty(0.5);

        $sum = $difficulty1->add($difficulty2);
        $this->assertEquals(2.5, $sum->multiplier);

        $difference = $difficulty1->subtract($difficulty2);
        $this->assertEquals(1.5, $difference->multiplier);

        $multiplied = $difficulty1->multiply(2);
        $this->assertEquals(4.0, $multiplied->multiplier);
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_comparisons()
    {
        $difficulty1 = new GameDifficulty(2.0);
        $difficulty2 = new GameDifficulty(1.0);

        $this->assertTrue($difficulty1->isGreaterThan($difficulty2));
        $this->assertFalse($difficulty2->isGreaterThan($difficulty1));
        $this->assertTrue($difficulty2->isLessThan($difficulty1));
        $this->assertFalse($difficulty1->isLessThan($difficulty2));
    }

    /**
     * @test
     */
    public function it_handles_very_large_difficulty_values()
    {
        $difficulty = new GameDifficulty(100.0);

        $this->assertEquals(100.0, $difficulty->multiplier);
        $this->assertEquals(100, $difficulty->getLevel());
        $this->assertEquals(10000, $difficulty->getPercentage());
        $this->assertEquals('10000%', $difficulty->format());
        $this->assertTrue($difficulty->isVeryHard());
        $this->assertFalse($difficulty->isHard());
        $this->assertFalse($difficulty->isNormal());
        $this->assertFalse($difficulty->isEasy());
    }

    /**
     * @test
     */
    public function it_handles_very_small_difficulty_values()
    {
        $difficulty = new GameDifficulty(0.01);

        $this->assertEquals(0.01, $difficulty->multiplier);
        $this->assertEquals(0, $difficulty->getLevel());
        $this->assertEquals(1, $difficulty->getPercentage());
        $this->assertEquals('1%', $difficulty->format());
        $this->assertFalse($difficulty->isVeryHard());
        $this->assertFalse($difficulty->isHard());
        $this->assertFalse($difficulty->isNormal());
        $this->assertTrue($difficulty->isEasy());
    }

    /**
     * @test
     */
    public function it_handles_complex_difficulty_operations()
    {
        $difficulty1 = new GameDifficulty(1.5);
        $difficulty2 = new GameDifficulty(0.5);

        // Add
        $sum = $difficulty1->add($difficulty2);
        $this->assertEquals(2.0, $sum->multiplier);
        $this->assertEquals(200, $sum->getPercentage());

        // Subtract
        $difference = $difficulty1->subtract($difficulty2);
        $this->assertEquals(1.0, $difference->multiplier);
        $this->assertEquals(100, $difference->getPercentage());

        // Multiply
        $multiplied = $difficulty1->multiply(2);
        $this->assertEquals(3.0, $multiplied->multiplier);
        $this->assertEquals(300, $multiplied->getPercentage());

        // Compare
        $this->assertTrue($difficulty1->isGreaterThan($difficulty2));
        $this->assertTrue($sum->isGreaterThan($difficulty1));
        $this->assertTrue($difference->isLessThan($difficulty1));
    }

    /**
     * @test
     */
    public function it_handles_difficulty_from_level_with_overflow()
    {
        $difficulty = GameDifficulty::fromLevel(100);

        $this->assertEquals(100.0, $difficulty->multiplier);
        $this->assertEquals(100, $difficulty->getLevel());
    }

    /**
     * @test
     */
    public function it_handles_difficulty_from_level_with_negative()
    {
        $difficulty = GameDifficulty::fromLevel(-1);

        $this->assertEquals(0.0, $difficulty->multiplier);  // Should not go negative
        $this->assertEquals(0, $difficulty->getLevel());
    }

    /**
     * @test
     */
    public function it_handles_difficulty_from_percentage_with_mixed_values()
    {
        $difficulty = GameDifficulty::fromPercentage(150);

        $this->assertEquals(1.5, $difficulty->multiplier);
        $this->assertEquals(1, $difficulty->getLevel());
        $this->assertEquals(150, $difficulty->getPercentage());
        $this->assertFalse($difficulty->isEasy());
        $this->assertFalse($difficulty->isNormal());
        $this->assertTrue($difficulty->isHard());
        $this->assertFalse($difficulty->isVeryHard());
    }
}
