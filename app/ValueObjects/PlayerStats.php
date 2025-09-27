<?php

namespace App\ValueObjects;

readonly class PlayerStats
{
    public function __construct(
        public int $points = 0,
        public int $population = 0,
        public int $villagesCount = 0,
        public int $totalAttackPoints = 0,
        public int $totalDefensePoints = 0,
        public bool $isActive = true,
        public bool $isOnline = false
    ) {}

    /**
     * Get total military points
     */
    public function getTotalMilitaryPoints(): int
    {
        return $this->totalAttackPoints + $this->totalDefensePoints;
    }

    /**
     * Calculate points per village
     */
    public function getPointsPerVillage(): float
    {
        return $this->villagesCount > 0 ? $this->points / $this->villagesCount : 0;
    }

    /**
     * Calculate population per village
     */
    public function getPopulationPerVillage(): float
    {
        return $this->villagesCount > 0 ? $this->population / $this->villagesCount : 0;
    }

    /**
     * Check if player is considered active (online or recently active)
     */
    public function isActivePlayer(): bool
    {
        return $this->isActive && $this->isOnline;
    }

    /**
     * Get player ranking category based on points
     */
    public function getRankingCategory(): string
    {
        if ($this->points >= 1000000)
            return 'elite';
        if ($this->points >= 500000)
            return 'veteran';
        if ($this->points >= 100000)
            return 'experienced';
        if ($this->points >= 10000)
            return 'intermediate';
        if ($this->points >= 1000)
            return 'beginner';
        return 'newbie';
    }

    /**
     * Calculate military strength ratio (attack vs defense)
     */
    public function getMilitaryRatio(): float
    {
        if ($this->totalDefensePoints === 0) {
            return $this->totalAttackPoints > 0 ? 1.0 : 0.0;
        }

        return $this->totalAttackPoints / $this->totalDefensePoints;
    }

    /**
     * Check if player has balanced military
     */
    public function hasBalancedMilitary(): bool
    {
        $ratio = $this->getMilitaryRatio();
        return $ratio >= 0.5 && $ratio <= 2.0;
    }

    /**
     * Check if player is attack-focused
     */
    public function isAttackFocused(): bool
    {
        return $this->getMilitaryRatio() > 2.0;
    }

    /**
     * Check if player is defense-focused
     */
    public function isDefenseFocused(): bool
    {
        return $this->getMilitaryRatio() < 0.5;
    }

    /**
     * Get efficiency score (points per population)
     */
    public function getEfficiencyScore(): float
    {
        return $this->population > 0 ? $this->points / $this->population : 0;
    }

    /**
     * Check if player is efficient
     */
    public function isEfficient(): bool
    {
        return $this->getEfficiencyScore() >= 1.0;
    }

    /**
     * Update stats with new values
     */
    public function withStats(
        ?int $points = null,
        ?int $population = null,
        ?int $villagesCount = null,
        ?int $totalAttackPoints = null,
        ?int $totalDefensePoints = null,
        ?bool $isActive = null,
        ?bool $isOnline = null
    ): self {
        return new self(
            points: $points ?? $this->points,
            population: $population ?? $this->population,
            villagesCount: $villagesCount ?? $this->villagesCount,
            totalAttackPoints: $totalAttackPoints ?? $this->totalAttackPoints,
            totalDefensePoints: $totalDefensePoints ?? $this->totalDefensePoints,
            isActive: $isActive ?? $this->isActive,
            isOnline: $isOnline ?? $this->isOnline
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'points' => $this->points,
            'population' => $this->population,
            'villages_count' => $this->villagesCount,
            'total_attack_points' => $this->totalAttackPoints,
            'total_defense_points' => $this->totalDefensePoints,
            'is_active' => $this->isActive,
            'is_online' => $this->isOnline,
            'total_military_points' => $this->getTotalMilitaryPoints(),
            'points_per_village' => $this->getPointsPerVillage(),
            'population_per_village' => $this->getPopulationPerVillage(),
            'ranking_category' => $this->getRankingCategory(),
            'military_ratio' => $this->getMilitaryRatio(),
            'efficiency_score' => $this->getEfficiencyScore(),
        ];
    }
}
