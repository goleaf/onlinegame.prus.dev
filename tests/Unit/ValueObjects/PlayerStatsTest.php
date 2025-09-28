<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\PlayerStats;
use Tests\TestCase;

class PlayerStatsTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_with_valid_stats()
    {
        $stats = new PlayerStats(
            points: 1000,
            villages: 5,
            population: 2500,
            rank: 42,
            attackPoints: 150,
            defensePoints: 200
        );

        $this->assertEquals(1000, $stats->points);
        $this->assertEquals(5, $stats->villages);
        $this->assertEquals(2500, $stats->population);
        $this->assertEquals(42, $stats->rank);
        $this->assertEquals(150, $stats->attackPoints);
        $this->assertEquals(200, $stats->defensePoints);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_zero_values()
    {
        $stats = new PlayerStats(
            points: 0,
            villages: 0,
            population: 0,
            rank: 0,
            attackPoints: 0,
            defensePoints: 0
        );

        $this->assertEquals(0, $stats->points);
        $this->assertEquals(0, $stats->villages);
        $this->assertEquals(0, $stats->population);
        $this->assertEquals(0, $stats->rank);
        $this->assertEquals(0, $stats->attackPoints);
        $this->assertEquals(0, $stats->defensePoints);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_large_values()
    {
        $stats = new PlayerStats(
            points: 1000000,
            villages: 500,
            population: 50000000,
            rank: 1,
            attackPoints: 999999,
            defensePoints: 888888
        );

        $this->assertEquals(1000000, $stats->points);
        $this->assertEquals(500, $stats->villages);
        $this->assertEquals(50000000, $stats->population);
        $this->assertEquals(1, $stats->rank);
        $this->assertEquals(999999, $stats->attackPoints);
        $this->assertEquals(888888, $stats->defensePoints);
    }

    /**
     * @test
     */
    public function it_can_calculate_average_points_per_village()
    {
        $stats = new PlayerStats(
            points: 1000,
            villages: 5,
            population: 2500,
            rank: 42,
            attackPoints: 150,
            defensePoints: 200
        );

        $averagePoints = $stats->getAveragePointsPerVillage();

        $this->assertEquals(200.0, $averagePoints);  // 1000 / 5 = 200
    }

    /**
     * @test
     */
    public function it_returns_zero_average_points_when_no_villages()
    {
        $stats = new PlayerStats(
            points: 1000,
            villages: 0,
            population: 0,
            rank: 999,
            attackPoints: 0,
            defensePoints: 0
        );

        $averagePoints = $stats->getAveragePointsPerVillage();

        $this->assertEquals(0.0, $averagePoints);
    }

    /**
     * @test
     */
    public function it_can_calculate_average_population_per_village()
    {
        $stats = new PlayerStats(
            points: 1000,
            villages: 4,
            population: 2000,
            rank: 42,
            attackPoints: 150,
            defensePoints: 200
        );

        $averagePopulation = $stats->getAveragePopulationPerVillage();

        $this->assertEquals(500.0, $averagePopulation);  // 2000 / 4 = 500
    }

    /**
     * @test
     */
    public function it_returns_zero_average_population_when_no_villages()
    {
        $stats = new PlayerStats(
            points: 1000,
            villages: 0,
            population: 2000,
            rank: 999,
            attackPoints: 150,
            defensePoints: 200
        );

        $averagePopulation = $stats->getAveragePopulationPerVillage();

        $this->assertEquals(0.0, $averagePopulation);
    }

    /**
     * @test
     */
    public function it_can_calculate_total_combat_points()
    {
        $stats = new PlayerStats(
            points: 1000,
            villages: 5,
            population: 2500,
            rank: 42,
            attackPoints: 150,
            defensePoints: 200
        );

        $totalCombatPoints = $stats->getTotalCombatPoints();

        $this->assertEquals(350, $totalCombatPoints);  // 150 + 200 = 350
    }

    /**
     * @test
     */
    public function it_can_check_if_player_is_top_ranked()
    {
        $topRankedStats = new PlayerStats(
            points: 1000000,
            villages: 100,
            population: 5000000,
            rank: 5,
            attackPoints: 50000,
            defensePoints: 60000
        );

        $lowRankedStats = new PlayerStats(
            points: 1000,
            villages: 2,
            population: 1000,
            rank: 500,
            attackPoints: 10,
            defensePoints: 20
        );

        $this->assertTrue($topRankedStats->isTopRanked(10));  // Rank 5 is in top 10
        $this->assertFalse($lowRankedStats->isTopRanked(10));  // Rank 500 is not in top 10
        $this->assertFalse($topRankedStats->isTopRanked(3));  // Rank 5 is not in top 3
    }

    /**
     * @test
     */
    public function it_can_check_if_player_has_high_combat_experience()
    {
        $highCombatStats = new PlayerStats(
            points: 10000,
            villages: 10,
            population: 50000,
            rank: 50,
            attackPoints: 5000,
            defensePoints: 3000
        );

        $lowCombatStats = new PlayerStats(
            points: 10000,
            villages: 10,
            population: 50000,
            rank: 50,
            attackPoints: 50,
            defensePoints: 30
        );

        $this->assertTrue($highCombatStats->hasHighCombatExperience(1000));  // 8000 > 1000
        $this->assertFalse($lowCombatStats->hasHighCombatExperience(1000));  // 80 < 1000
    }

    /**
     * @test
     */
    public function it_can_compare_with_another_player_stats()
    {
        $player1Stats = new PlayerStats(
            points: 2000,
            villages: 8,
            population: 4000,
            rank: 20,
            attackPoints: 300,
            defensePoints: 400
        );

        $player2Stats = new PlayerStats(
            points: 1500,
            villages: 6,
            population: 3000,
            rank: 30,
            attackPoints: 200,
            defensePoints: 250
        );

        $this->assertTrue($player1Stats->hasMorePointsThan($player2Stats));
        $this->assertFalse($player2Stats->hasMorePointsThan($player1Stats));

        $this->assertTrue($player1Stats->hasMoreVillagesThan($player2Stats));
        $this->assertFalse($player2Stats->hasMoreVillagesThan($player1Stats));

        $this->assertTrue($player1Stats->isBetterRankedThan($player2Stats));  // Lower rank number = better
        $this->assertFalse($player2Stats->isBetterRankedThan($player1Stats));
    }

    /**
     * @test
     */
    public function it_can_get_efficiency_metrics()
    {
        $stats = new PlayerStats(
            points: 2000,
            villages: 4,
            population: 8000,
            rank: 25,
            attackPoints: 400,
            defensePoints: 600
        );

        $efficiency = $stats->getEfficiencyMetrics();

        $this->assertArrayHasKey('points_per_village', $efficiency);
        $this->assertArrayHasKey('population_per_village', $efficiency);
        $this->assertArrayHasKey('points_per_population', $efficiency);
        $this->assertArrayHasKey('combat_points_ratio', $efficiency);

        $this->assertEquals(500.0, $efficiency['points_per_village']);  // 2000 / 4
        $this->assertEquals(2000.0, $efficiency['population_per_village']);  // 8000 / 4
        $this->assertEquals(0.25, $efficiency['points_per_population']);  // 2000 / 8000
        $this->assertEquals(0.5, $efficiency['combat_points_ratio']);  // 1000 / 2000
    }

    /**
     * @test
     */
    public function it_handles_division_by_zero_in_efficiency_metrics()
    {
        $stats = new PlayerStats(
            points: 0,
            villages: 0,
            population: 0,
            rank: 999,
            attackPoints: 0,
            defensePoints: 0
        );

        $efficiency = $stats->getEfficiencyMetrics();

        $this->assertEquals(0.0, $efficiency['points_per_village']);
        $this->assertEquals(0.0, $efficiency['population_per_village']);
        $this->assertEquals(0.0, $efficiency['points_per_population']);
        $this->assertEquals(0.0, $efficiency['combat_points_ratio']);
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_unserialized()
    {
        $original = new PlayerStats(
            points: 1500,
            villages: 7,
            population: 3500,
            rank: 33,
            attackPoints: 250,
            defensePoints: 350
        );

        $serialized = serialize($original);
        $unserialized = unserialize($serialized);

        $this->assertEquals($original->points, $unserialized->points);
        $this->assertEquals($original->villages, $unserialized->villages);
        $this->assertEquals($original->population, $unserialized->population);
        $this->assertEquals($original->rank, $unserialized->rank);
        $this->assertEquals($original->attackPoints, $unserialized->attackPoints);
        $this->assertEquals($original->defensePoints, $unserialized->defensePoints);
    }

    /**
     * @test
     */
    public function it_can_create_updated_stats()
    {
        $original = new PlayerStats(
            points: 1000,
            villages: 5,
            population: 2500,
            rank: 42,
            attackPoints: 150,
            defensePoints: 200
        );

        $updated = $original->withUpdatedPoints(1500);

        // Original should remain unchanged
        $this->assertEquals(1000, $original->points);

        // New instance should have updated points
        $this->assertEquals(1500, $updated->points);
        $this->assertEquals(5, $updated->villages);
        $this->assertEquals(2500, $updated->population);
        $this->assertEquals(42, $updated->rank);
        $this->assertEquals(150, $updated->attackPoints);
        $this->assertEquals(200, $updated->defensePoints);
    }

    /**
     * @test
     */
    public function it_can_create_stats_with_additional_villages()
    {
        $original = new PlayerStats(
            points: 1000,
            villages: 5,
            population: 2500,
            rank: 42,
            attackPoints: 150,
            defensePoints: 200
        );

        $updated = $original->withAdditionalVillages(3);

        // Original should remain unchanged
        $this->assertEquals(5, $original->villages);

        // New instance should have additional villages
        $this->assertEquals(8, $updated->villages);
        $this->assertEquals(1000, $updated->points);
        $this->assertEquals(2500, $updated->population);
        $this->assertEquals(42, $updated->rank);
        $this->assertEquals(150, $updated->attackPoints);
        $this->assertEquals(200, $updated->defensePoints);
    }
}
