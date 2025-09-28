<?php

namespace App\ValueObjects;

class PlayerStats
{
    public function __construct(
        public readonly int $points,
        public readonly int $population,
        public readonly int $villagesCount,
        public readonly int $totalAttackPoints,
        public readonly int $totalDefensePoints,
        public readonly bool $isActive,
        public readonly bool $isOnline,
        public readonly int $rank = 0,
        public readonly ?int $alliance_id = null,
        public readonly string $tribe = '',
        public readonly ?\DateTime $last_active_at = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            points: $data['points'] ?? 0,
            population: $data['population'] ?? 0,
            villagesCount: $data['villagesCount'] ?? $data['villages_count'] ?? 0,
            totalAttackPoints: $data['totalAttackPoints'] ?? $data['total_attack_points'] ?? 0,
            totalDefensePoints: $data['totalDefensePoints'] ?? $data['total_defense_points'] ?? 0,
            isActive: $data['isActive'] ?? $data['is_active'] ?? true,
            isOnline: $data['isOnline'] ?? $data['is_online'] ?? false,
            rank: $data['rank'] ?? 0,
            alliance_id: $data['alliance_id'] ?? null,
            tribe: $data['tribe'] ?? '',
            last_active_at: isset($data['last_active_at']) ? new \DateTime($data['last_active_at']) : null,
        );
    }

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
            'rank' => $this->rank,
            'alliance_id' => $this->alliance_id,
            'tribe' => $this->tribe,
            'last_active_at' => $this->last_active_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function getTotalPower(): int
    {
        return $this->points + $this->population;
    }

    public function isInAlliance(): bool
    {
        return $this->alliance_id !== null;
    }

    public function getActivityStatus(): string
    {
        if (! $this->isActive) {
            return 'inactive';
        }

        if ($this->isOnline) {
            return 'online';
        }

        if ($this->last_active_at && $this->last_active_at > new \DateTime('-15 minutes')) {
            return 'recently_active';
        }

        return 'offline';
    }
}
