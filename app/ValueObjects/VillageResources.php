<?php

namespace App\ValueObjects;

class VillageResources
{
    public function __construct(
        public readonly int $wood,
        public readonly int $clay,
        public readonly int $iron,
        public readonly int $crop,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            wood: $data['wood'] ?? 0,
            clay: $data['clay'] ?? 0,
            iron: $data['iron'] ?? 0,
            crop: $data['crop'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'wood' => $this->wood,
            'clay' => $this->clay,
            'iron' => $this->iron,
            'crop' => $this->crop,
        ];
    }

    public function getTotalResources(): int
    {
        return $this->wood + $this->clay + $this->iron + $this->crop;
    }

    public function getResourcePercentage(string $resource): float
    {
        $total = $this->getTotalResources();
        if ($total === 0) {
            return 0;
        }

        return match ($resource) {
            'wood' => ($this->wood / $total) * 100,
            'clay' => ($this->clay / $total) * 100,
            'iron' => ($this->iron / $total) * 100,
            'crop' => ($this->crop / $total) * 100,
            default => 0,
        };
    }

    public function getDominantResource(): string
    {
        $resources = [
            'wood' => $this->wood,
            'clay' => $this->clay,
            'iron' => $this->iron,
            'crop' => $this->crop,
        ];

        return array_search(max($resources), $resources);
    }

    public function add(VillageResources $other): self
    {
        return new self(
            wood: $this->wood + $other->wood,
            clay: $this->clay + $other->clay,
            iron: $this->iron + $other->iron,
            crop: $this->crop + $other->crop,
        );
    }

    public function subtract(VillageResources $other): self
    {
        return new self(
            wood: max(0, $this->wood - $other->wood),
            clay: max(0, $this->clay - $other->clay),
            iron: max(0, $this->iron - $other->iron),
            crop: max(0, $this->crop - $other->crop),
        );
    }

    public function multiply(float $factor): self
    {
        return new self(
            wood: (int) round($this->wood * $factor),
            clay: (int) round($this->clay * $factor),
            iron: (int) round($this->iron * $factor),
            crop: (int) round($this->crop * $factor),
        );
    }

    public function canAfford(VillageResources $cost): bool
    {
        return $this->wood >= $cost->wood &&
               $this->clay >= $cost->clay &&
               $this->iron >= $cost->iron &&
               $this->crop >= $cost->crop;
    }

    public function getResourceBalance(VillageResources $cost): array
    {
        return [
            'wood' => $this->wood - $cost->wood,
            'clay' => $this->clay - $cost->clay,
            'iron' => $this->iron - $cost->iron,
            'crop' => $this->crop - $cost->crop,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->wood === 0 && $this->clay === 0 &&
               $this->iron === 0 && $this->crop === 0;
    }

    public function isFull(int $capacity = 1000000): bool
    {
        return $this->getTotalResources() >= $capacity;
    }

    public function getStorageUtilization(int $capacity = 1000000): float
    {
        if ($capacity === 0) {
            return 0;
        }

        return min(100, ($this->getTotalResources() / $capacity) * 100);
    }

    public function __toString(): string
    {
        return "Wood: {$this->wood}, Clay: {$this->clay}, Iron: {$this->iron}, Crop: {$this->crop}";
    }
}
