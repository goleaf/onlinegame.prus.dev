<?php

namespace App\ValueObjects;

readonly class BattleResult
{
    public function __construct(
        public string $status,  // victory, defeat, draw
        public int $attackerLosses = 0,
        public int $defenderLosses = 0,
        public ?ResourceAmounts $loot = null,
        public int $attackerPoints = 0,
        public int $defenderPoints = 0,
        public ?string $battleType = null,  // attack, raid, scout
        public ?int $duration = null,  // in seconds
        public array $attackerTroops = [],
        public array $defenderTroops = []
    ) {
        $this->loot = $loot ?? new ResourceAmounts();
    }

    /**
     * Check if battle was a victory
     */
    public function isVictory(): bool
    {
        return $this->status === 'victory';
    }

    /**
     * Check if battle was a defeat
     */
    public function isDefeat(): bool
    {
        return $this->status === 'defeat';
    }

    /**
     * Check if battle was a draw
     */
    public function isDraw(): bool
    {
        return $this->status === 'draw';
    }

    /**
     * Get total losses for both sides
     */
    public function getTotalLosses(): int
    {
        return $this->attackerLosses + $this->defenderLosses;
    }

    /**
     * Get battle efficiency (loot value vs losses)
     */
    public function getBattleEfficiency(): float
    {
        if ($this->getTotalLosses() === 0) {
            return $this->loot->getTotal() > 0 ? 1.0 : 0.0;
        }

        return $this->loot->getTotal() / $this->getTotalLosses();
    }

    /**
     * Check if battle was efficient
     */
    public function isEfficient(): bool
    {
        return $this->getBattleEfficiency() > 1.0;
    }

    /**
     * Get battle outcome severity
     */
    public function getSeverity(): string
    {
        $totalLosses = $this->getTotalLosses();

        if ($totalLosses >= 10000) {
            return 'devastating';
        }
        if ($totalLosses >= 5000) {
            return 'major';
        }
        if ($totalLosses >= 1000) {
            return 'significant';
        }
        if ($totalLosses >= 100) {
            return 'moderate';
        }
        if ($totalLosses >= 10) {
            return 'minor';
        }

        return 'minimal';
    }

    /**
     * Calculate battle duration in minutes
     */
    public function getDurationInMinutes(): ?float
    {
        return $this->duration ? $this->duration / 60 : null;
    }

    /**
     * Get battle intensity (losses per minute)
     */
    public function getIntensity(): float
    {
        if (! $this->duration || $this->duration === 0) {
            return $this->getTotalLosses();
        }

        return $this->getTotalLosses() / ($this->duration / 60);
    }

    /**
     * Check if battle was intense
     */
    public function isIntense(): bool
    {
        return $this->getIntensity() > 100;  // More than 100 losses per minute
    }

    /**
     * Get loot value as percentage of total resources
     */
    public function getLootPercentage(ResourceAmounts $totalResources): float
    {
        if ($totalResources->isEmpty()) {
            return 0.0;
        }

        return ($this->loot->getTotal() / $totalResources->getTotal()) * 100;
    }

    /**
     * Check if loot was significant
     */
    public function hasSignificantLoot(): bool
    {
        return $this->loot->getTotal() >= 10000;
    }

    /**
     * Get battle summary
     */
    public function getSummary(): array
    {
        return [
            'status' => $this->status,
            'attacker_losses' => $this->attackerLosses,
            'defender_losses' => $this->defenderLosses,
            'total_losses' => $this->getTotalLosses(),
            'loot_value' => $this->loot->getTotal(),
            'battle_efficiency' => $this->getBattleEfficiency(),
            'severity' => $this->getSeverity(),
            'duration_minutes' => $this->getDurationInMinutes(),
            'intensity' => $this->getIntensity(),
            'is_efficient' => $this->isEfficient(),
            'is_intense' => $this->isIntense(),
            'has_significant_loot' => $this->hasSignificantLoot(),
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? 'draw',
            attackerLosses: $data['attacker_losses'] ?? 0,
            defenderLosses: $data['defender_losses'] ?? 0,
            loot: isset($data['loot']) ? ResourceAmounts::fromArray($data['loot']) : new ResourceAmounts(),
            attackerPoints: $data['attacker_points'] ?? 0,
            defenderPoints: $data['defender_points'] ?? 0,
            battleType: $data['battle_type'] ?? null,
            duration: $data['duration'] ?? null,
            attackerTroops: $data['attacker_troops'] ?? [],
            defenderTroops: $data['defender_troops'] ?? []
        );
    }
}
