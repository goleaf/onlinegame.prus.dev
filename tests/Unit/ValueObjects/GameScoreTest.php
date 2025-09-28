<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\GameScore;
use Tests\TestCase;

class GameScoreTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_game_score_with_defaults()
    {
        $score = new GameScore();

        $this->assertEquals(0, $score->points);
    }

    /**
     * @test
     */
    public function it_can_create_game_score_with_points()
    {
        $score = new GameScore(1500);

        $this->assertEquals(1500, $score->points);
    }

    /**
     * @test
     */
    public function it_can_create_game_score_from_components()
    {
        $score = GameScore::fromComponents(1000, 300, 200);  // base, bonus, penalty

        $this->assertEquals(1100, $score->points);  // 1000 + 300 - 200
    }

    /**
     * @test
     */
    public function it_can_create_game_score_from_components_with_zeros()
    {
        $score = GameScore::fromComponents(0, 0, 0);

        $this->assertEquals(0, $score->points);
    }

    /**
     * @test
     */
    public function it_can_create_game_score_from_components_with_negative_result()
    {
        $score = GameScore::fromComponents(100, 50, 200);  // base, bonus, penalty

        $this->assertEquals(0, $score->points);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_can_create_game_score_from_components_with_large_values()
    {
        $score = GameScore::fromComponents(10000, 5000, 1000);  // base, bonus, penalty

        $this->assertEquals(14000, $score->points);  // 10000 + 5000 - 1000
    }

    /**
     * @test
     */
    public function it_calculates_grade_correctly()
    {
        $score = new GameScore(1500);

        $this->assertEquals('A', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_calculates_grade_with_perfect_score()
    {
        $score = new GameScore(2000);

        $this->assertEquals('S', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_calculates_grade_with_good_score()
    {
        $score = new GameScore(1200);

        $this->assertEquals('B', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_calculates_grade_with_average_score()
    {
        $score = new GameScore(800);

        $this->assertEquals('C', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_calculates_grade_with_poor_score()
    {
        $score = new GameScore(400);

        $this->assertEquals('D', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_calculates_grade_with_failing_score()
    {
        $score = new GameScore(100);

        $this->assertEquals('F', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_calculates_grade_with_zero_score()
    {
        $score = new GameScore(0);

        $this->assertEquals('F', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_calculates_grade_with_negative_score()
    {
        $score = new GameScore(-100);

        $this->assertEquals('F', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_calculates_grade_with_very_high_score()
    {
        $score = new GameScore(5000);

        $this->assertEquals('S', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_identifies_perfect_score()
    {
        $perfect = new GameScore(2000);
        $good = new GameScore(1500);
        $poor = new GameScore(500);

        $this->assertTrue($perfect->isPerfect());
        $this->assertFalse($good->isPerfect());
        $this->assertFalse($poor->isPerfect());
    }

    /**
     * @test
     */
    public function it_identifies_excellent_score()
    {
        $excellent = new GameScore(1800);
        $good = new GameScore(1500);
        $poor = new GameScore(500);

        $this->assertTrue($excellent->isExcellent());
        $this->assertFalse($good->isExcellent());
        $this->assertFalse($poor->isExcellent());
    }

    /**
     * @test
     */
    public function it_identifies_good_score()
    {
        $excellent = new GameScore(1800);
        $good = new GameScore(1500);
        $poor = new GameScore(500);

        $this->assertFalse($excellent->isGood());
        $this->assertTrue($good->isGood());
        $this->assertFalse($poor->isGood());
    }

    /**
     * @test
     */
    public function it_identifies_average_score()
    {
        $good = new GameScore(1500);
        $average = new GameScore(1000);
        $poor = new GameScore(500);

        $this->assertFalse($good->isAverage());
        $this->assertTrue($average->isAverage());
        $this->assertFalse($poor->isAverage());
    }

    /**
     * @test
     */
    public function it_identifies_poor_score()
    {
        $good = new GameScore(1500);
        $average = new GameScore(1000);
        $poor = new GameScore(500);

        $this->assertFalse($good->isPoor());
        $this->assertFalse($average->isPoor());
        $this->assertTrue($poor->isPoor());
    }

    /**
     * @test
     */
    public function it_identifies_failing_score()
    {
        $good = new GameScore(1500);
        $poor = new GameScore(500);
        $failing = new GameScore(100);

        $this->assertFalse($good->isFailing());
        $this->assertFalse($poor->isFailing());
        $this->assertTrue($failing->isFailing());
    }

    /**
     * @test
     */
    public function it_identifies_zero_score()
    {
        $zero = new GameScore(0);
        $positive = new GameScore(1000);

        $this->assertTrue($zero->isZero());
        $this->assertFalse($positive->isZero());
    }

    /**
     * @test
     */
    public function it_identifies_positive_score()
    {
        $positive = new GameScore(1000);
        $zero = new GameScore(0);

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($zero->isPositive());
    }

    /**
     * @test
     */
    public function it_adds_score_correctly()
    {
        $score1 = new GameScore(1000);
        $score2 = new GameScore(500);

        $result = $score1->add($score2);

        $this->assertEquals(1500, $result->points);
    }

    /**
     * @test
     */
    public function it_adds_score_with_zeros()
    {
        $score1 = new GameScore(1000);
        $score2 = new GameScore(0);

        $result = $score1->add($score2);

        $this->assertEquals(1000, $result->points);
    }

    /**
     * @test
     */
    public function it_adds_score_with_large_values()
    {
        $score1 = new GameScore(5000);
        $score2 = new GameScore(3000);

        $result = $score1->add($score2);

        $this->assertEquals(8000, $result->points);
    }

    /**
     * @test
     */
    public function it_subtracts_score_correctly()
    {
        $score1 = new GameScore(1500);
        $score2 = new GameScore(500);

        $result = $score1->subtract($score2);

        $this->assertEquals(1000, $result->points);
    }

    /**
     * @test
     */
    public function it_subtracts_score_with_negative_result()
    {
        $score1 = new GameScore(500);
        $score2 = new GameScore(1000);

        $result = $score1->subtract($score2);

        $this->assertEquals(0, $result->points);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_subtracts_score_with_zeros()
    {
        $score1 = new GameScore(1000);
        $score2 = new GameScore(0);

        $result = $score1->subtract($score2);

        $this->assertEquals(1000, $result->points);
    }

    /**
     * @test
     */
    public function it_multiplies_score_correctly()
    {
        $score = new GameScore(1000);

        $result = $score->multiply(1.5);

        $this->assertEquals(1500, $result->points);
    }

    /**
     * @test
     */
    public function it_multiplies_score_with_decimal()
    {
        $score = new GameScore(1000);

        $result = $score->multiply(0.5);

        $this->assertEquals(500, $result->points);
    }

    /**
     * @test
     */
    public function it_multiplies_score_with_zero()
    {
        $score = new GameScore(1000);

        $result = $score->multiply(0);

        $this->assertEquals(0, $result->points);
    }

    /**
     * @test
     */
    public function it_multiplies_score_with_negative()
    {
        $score = new GameScore(1000);

        $result = $score->multiply(-1);

        $this->assertEquals(0, $result->points);  // Should not go negative
    }

    /**
     * @test
     */
    public function it_multiplies_score_with_large_factor()
    {
        $score = new GameScore(1000);

        $result = $score->multiply(10);

        $this->assertEquals(10000, $result->points);
    }

    /**
     * @test
     */
    public function it_compares_score_correctly()
    {
        $score1 = new GameScore(1500);
        $score2 = new GameScore(1000);

        $this->assertTrue($score1->isGreaterThan($score2));
        $this->assertFalse($score2->isGreaterThan($score1));
        $this->assertTrue($score2->isLessThan($score1));
        $this->assertFalse($score1->isLessThan($score2));
        $this->assertTrue($score1->isGreaterThanOrEqual($score2));
        $this->assertTrue($score2->isLessThanOrEqual($score1));
    }

    /**
     * @test
     */
    public function it_compares_equal_score()
    {
        $score1 = new GameScore(1000);
        $score2 = new GameScore(1000);

        $this->assertFalse($score1->isGreaterThan($score2));
        $this->assertFalse($score2->isGreaterThan($score1));
        $this->assertFalse($score1->isLessThan($score2));
        $this->assertFalse($score2->isLessThan($score1));
        $this->assertTrue($score1->isGreaterThanOrEqual($score2));
        $this->assertTrue($score2->isLessThanOrEqual($score1));
    }

    /**
     * @test
     */
    public function it_compares_score_with_zeros()
    {
        $score1 = new GameScore(0);
        $score2 = new GameScore(1000);

        $this->assertFalse($score1->isGreaterThan($score2));
        $this->assertTrue($score2->isGreaterThan($score1));
        $this->assertTrue($score1->isLessThan($score2));
        $this->assertFalse($score2->isLessThan($score1));
        $this->assertFalse($score1->isGreaterThanOrEqual($score2));
        $this->assertTrue($score2->isGreaterThanOrEqual($score1));
    }

    /**
     * @test
     */
    public function it_compares_score_with_large_values()
    {
        $score1 = new GameScore(10000);
        $score2 = new GameScore(5000);

        $this->assertTrue($score1->isGreaterThan($score2));
        $this->assertFalse($score2->isGreaterThan($score1));
        $this->assertTrue($score2->isLessThan($score1));
        $this->assertFalse($score1->isLessThan($score2));
    }

    /**
     * @test
     */
    public function it_formats_score_correctly()
    {
        $score = new GameScore(1500);

        $this->assertEquals('1,500', $score->format());
    }

    /**
     * @test
     */
    public function it_formats_score_with_zeros()
    {
        $score = new GameScore(0);

        $this->assertEquals('0', $score->format());
    }

    /**
     * @test
     */
    public function it_formats_score_with_large_values()
    {
        $score = new GameScore(1500000);

        $this->assertEquals('1,500,000', $score->format());
    }

    /**
     * @test
     */
    public function it_formats_score_with_negative_values()
    {
        $score = new GameScore(-500);

        $this->assertEquals('0', $score->format());  // Should not show negative
    }

    /**
     * @test
     */
    public function it_converts_to_array()
    {
        $score = new GameScore(1500);
        $array = $score->toArray();

        $expected = [
            'points' => 1500,
            'grade' => 'A',
            'formatted' => '1,500',
            'is_perfect' => false,
            'is_excellent' => false,
            'is_good' => true,
            'is_average' => false,
            'is_poor' => false,
            'is_failing' => false,
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
            'points' => 1500,
        ];

        $score = GameScore::fromArray($data);

        $this->assertEquals(1500, $score->points);
    }

    /**
     * @test
     */
    public function it_creates_from_array_with_defaults()
    {
        $data = [];

        $score = GameScore::fromArray($data);

        $this->assertEquals(0, $score->points);
    }

    /**
     * @test
     */
    public function it_creates_from_array_with_partial_data()
    {
        $data = [
            'base' => 1000,
            'bonus' => 300,
            'penalty' => 200,
        ];

        $score = GameScore::fromArray($data);

        $this->assertEquals(1100, $score->points);  // 1000 + 300 - 200
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_grade()
    {
        $score = new GameScore(1500);

        $this->assertEquals('A', $score->getGrade());
        $this->assertTrue($score->isGood());
        $this->assertFalse($score->isPerfect());
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_operations()
    {
        $score1 = new GameScore(1000);
        $score2 = new GameScore(500);

        $sum = $score1->add($score2);
        $this->assertEquals(1500, $sum->points);

        $difference = $score1->subtract($score2);
        $this->assertEquals(500, $difference->points);

        $multiplied = $score1->multiply(2);
        $this->assertEquals(2000, $multiplied->points);
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_comparisons()
    {
        $score1 = new GameScore(1500);
        $score2 = new GameScore(1000);

        $this->assertTrue($score1->isGreaterThan($score2));
        $this->assertFalse($score2->isGreaterThan($score1));
        $this->assertTrue($score2->isLessThan($score1));
        $this->assertFalse($score1->isLessThan($score2));
    }

    /**
     * @test
     */
    public function it_handles_very_large_score_values()
    {
        $score = new GameScore(1000000);

        $this->assertEquals(1000000, $score->points);
        $this->assertEquals('S', $score->getGrade());
        $this->assertEquals('1,000,000', $score->format());
        $this->assertTrue($score->isPerfect());
        $this->assertTrue($score->isExcellent());
        $this->assertTrue($score->isGood());
        $this->assertFalse($score->isAverage());
        $this->assertFalse($score->isPoor());
        $this->assertFalse($score->isFailing());
    }

    /**
     * @test
     */
    public function it_handles_very_small_score_values()
    {
        $score = new GameScore(1);

        $this->assertEquals(1, $score->points);
        $this->assertEquals('F', $score->getGrade());
        $this->assertEquals('1', $score->format());
        $this->assertFalse($score->isPerfect());
        $this->assertFalse($score->isExcellent());
        $this->assertFalse($score->isGood());
        $this->assertFalse($score->isAverage());
        $this->assertFalse($score->isPoor());
        $this->assertTrue($score->isFailing());
    }

    /**
     * @test
     */
    public function it_handles_complex_score_operations()
    {
        $score1 = new GameScore(1000);
        $score2 = new GameScore(500);

        // Add
        $sum = $score1->add($score2);
        $this->assertEquals(1500, $sum->points);
        $this->assertEquals('A', $sum->getGrade());

        // Subtract
        $difference = $score1->subtract($score2);
        $this->assertEquals(500, $difference->points);
        $this->assertEquals('D', $difference->getGrade());

        // Multiply
        $multiplied = $score1->multiply(2);
        $this->assertEquals(2000, $multiplied->points);
        $this->assertEquals('S', $multiplied->getGrade());

        // Compare
        $this->assertTrue($score1->isGreaterThan($score2));
        $this->assertTrue($sum->isGreaterThan($score1));
        $this->assertTrue($difference->isLessThan($score1));
    }

    /**
     * @test
     */
    public function it_handles_score_from_components_with_overflow()
    {
        $score = GameScore::fromComponents(10000, 5000, 1000);

        $this->assertEquals(14000, $score->points);
        $this->assertEquals('S', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_handles_score_from_components_with_negative()
    {
        $score = GameScore::fromComponents(100, 50, 200);

        $this->assertEquals(0, $score->points);  // Should not go negative
        $this->assertEquals('F', $score->getGrade());
    }

    /**
     * @test
     */
    public function it_handles_score_from_components_with_mixed_values()
    {
        $score = GameScore::fromComponents(1000, 300, 200);

        $this->assertEquals(1100, $score->points);
        $this->assertEquals('B', $score->getGrade());
        $this->assertFalse($score->isPerfect());
        $this->assertFalse($score->isExcellent());
        $this->assertFalse($score->isGood());
        $this->assertTrue($score->isAverage());
        $this->assertFalse($score->isPoor());
        $this->assertFalse($score->isFailing());
    }
}
