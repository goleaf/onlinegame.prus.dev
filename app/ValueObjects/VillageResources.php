<?php

namespace App\ValueObjects;

readonly class VillageResources
{
    public function __construct(
        public ResourceAmounts $amounts,
        public ResourceAmounts $production,
        public ResourceAmounts $capacity,
        public array $levels = []
    ) {
        $this->levels = $levels ?: [
            'wood' => 1,
            'clay' => 1,
            'iron' => 1,
            'crop' => 1,
        ];
    }

    /**
     * Get total amount of all resources
     */
    public function getTotalAmount(): int
    {
        return $this->amounts->getTotal();
    }

    /**
     * Get total production per hour
     */
    public function getTotalProduction(): int
    {
        return $this->production->getTotal();
    }

    /**
     * Get total storage capacity
     */
    public function getTotalCapacity(): int
    {
        return $this->capacity->getTotal();
    }

    /**
     * Check if any resource is at capacity
     */
    public function isAnyResourceAtCapacity(): bool
    {
        return $this->amounts->wood >= $this->capacity->wood ||
            $this->amounts->clay >= $this->capacity->clay ||
            $this->amounts->iron >= $this->capacity->iron ||
            $this->amounts->crop >= $this->capacity->crop;
    }

    /**
     * Get resource utilization percentage
     */
    public function getUtilizationPercentage(): float
    {
        $totalCapacity = $this->getTotalCapacity();
        if ($totalCapacity === 0)
            return 0;

        return ($this->getTotalAmount() / $totalCapacity) * 100;
    }

    /**
     * Check if storage is nearly full (90% or more)
     */
    public function isStorageNearlyFull(): bool
    {
        return $this->getUtilizationPercentage() >= 90;
    }

    /**
     * Get resource efficiency (production vs storage)
     */
    public function getEfficiency(): float
    {
        $totalCapacity = $this->getTotalCapacity();
        if ($totalCapacity === 0)
            return 0;

        return $this->getTotalProduction() / $totalCapacity;
    }

    /**
     * Add resources to current amounts
     */
    public function addResources(ResourceAmounts $additional): self
    {
        return new self(
            amounts: $this->amounts->add($additional),
            production: $this->production,
            capacity: $this->capacity,
            levels: $this->levels
        );
    }

    /**
     * Subtract resources from current amounts
     */
    public function subtractResources(ResourceAmounts $subtract): self
    {
        return new self(
            amounts: $this->amounts->subtract($subtract),
            production: $this->production,
            capacity: $this->capacity,
            levels: $this->levels
        );
    }

    /**
     * Check if village can afford the required resources
     */
    public function canAfford(ResourceAmounts $required): bool
    {
        return $this->amounts->canAfford($required);
    }

    /**
     * Get the most abundant resource
     */
    public function getMostAbundantResource(): string
    {
        return $this->amounts->getMostAbundantResource();
    }

    /**
     * Get the least abundant resource
     */
    public function getLeastAbundantResource(): string
    {
        return $this->amounts->getLeastAbundantResource();
    }

    /**
     * Get resource balance (ratio of most to least abundant)
     */
    public function getResourceBalance(): float
    {
        $most = $this->amounts->{$this->getMostAbundantResource()};
        $least = $this->amounts->{$this->getLeastAbundantResource()};

        return $least > 0 ? $most / $least : 0;
    }

    /**
     * Check if resources are balanced
     */
    public function isBalanced(): bool
    {
        return $this->getResourceBalance() <= 3.0;  // Most abundant is no more than 3x least abundant
    }

    /**
     * Get production time to fill storage (in hours)
     */
    public function getTimeToFillStorage(): float
    {
        $totalProduction = $this->getTotalProduction();
        if ($totalProduction === 0)
            return 0;

        $availableCapacity = $this->getTotalCapacity() - $this->getTotalAmount();
        return max(0, $availableCapacity / $totalProduction);
    }

    /**
     * Get resource levels as array
     */
    public function getLevels(): array
    {
        return $this->levels;
    }

    /**
     * Get level for specific resource
     */
    public function getLevel(string $resource): int
    {
        return $this->levels[$resource] ?? 1;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'amounts' => $this->amounts->toArray(),
            'production' => $this->production->toArray(),
            'capacity' => $this->capacity->toArray(),
            'levels' => $this->levels,
            'total_amount' => $this->getTotalAmount(),
            'total_production' => $this->getTotalProduction(),
            'total_capacity' => $this->getTotalCapacity(),
            'utilization_percentage' => $this->getUtilizationPercentage(),
            'efficiency' => $this->getEfficiency(),
            'resource_balance' => $this->getResourceBalance(),
            'time_to_fill_storage' => $this->getTimeToFillStorage(),
            'is_balanced' => $this->isBalanced(),
            'is_storage_nearly_full' => $this->isStorageNearlyFull(),
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amounts: ResourceAmounts::fromArray($data['amounts'] ?? []),
            production: ResourceAmounts::fromArray($data['production'] ?? []),
            capacity: ResourceAmounts::fromArray($data['capacity'] ?? []),
            levels: $data['levels'] ?? []
        );
    }
}
