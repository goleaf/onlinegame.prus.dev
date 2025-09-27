<?php

namespace App\ValueObjects;

class PlayerStats
{
    public function __construct(
        public readonly int $points,
        public readonly int $rank,
        public readonly int $population,
        public readonly int $villages,
        public readonly int $alliance_id = null,
        public readonly string $tribe = '',
        public readonly bool $is_online = false,
        public readonly bool $is_active = true,
        public readonly ?\DateTime $last_active_at = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            points: $data['points'] ?? 0,
            rank: $data['rank'] ?? 0,
            population: $data['population'] ?? 0,
            villages: $data['villages'] ?? 0,
            alliance_id: $data['alliance_id'] ?? null,
            tribe: $data['tribe'] ?? '',
            is_online: $data['is_online'] ?? false,
            is_active: $data['is_active'] ?? true,
            last_active_at: isset($data['last_active_at']) ? new \DateTime($data['last_active_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'points' => $this->points,
            'rank' => $this->rank,
            'population' => $this->population,
            'villages' => $this->villages,
            'alliance_id' => $this->alliance_id,
            'tribe' => $this->tribe,
            'is_online' => $this->is_online,
            'is_active' => $this->is_active,
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
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->is_online) {
            return 'online';
        }

        if ($this->last_active_at && $this->last_active_at > new \DateTime('-15 minutes')) {
            return 'recently_active';
        }

        return 'offline';
    }
}