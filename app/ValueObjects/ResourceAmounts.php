<?php

namespace App\ValueObjects;

readonly class ResourceAmounts
{
    public function __construct(
        public int $wood = 0,
        public int $clay = 0,
        public int $iron = 0,
        public int $crop = 0
    ) {
    }

    /**
     * Get total amount of all resources
     */
    public function getTotal(): int
    {
        return $this->wood + $this->clay + $this->iron + $this->crop;
    }

    /**
     * Add resources to current amounts
     */
    public function add(ResourceAmounts $other): self
    {
        return new self(
            wood: $this->wood + $other->wood,
            clay: $this->clay + $other->clay,
            iron: $this->iron + $other->iron,
            crop: $this->crop + $other->crop
        );
    }

    /**
     * Subtract resources from current amounts
     */
    public function subtract(ResourceAmounts $other): self
    {
        return new self(
            wood: max(0, $this->wood - $other->wood),
            clay: max(0, $this->clay - $other->clay),
            iron: max(0, $this->iron - $other->iron),
            crop: max(0, $this->crop - $other->crop)
        );
    }

    /**
     * Multiply all resources by a factor
     */
    public function multiply(float $factor): self
    {
        return new self(
            wood: (int) round($this->wood * $factor),
            clay: (int) round($this->clay * $factor),
            iron: (int) round($this->iron * $factor),
            crop: (int) round($this->crop * $factor)
        );
    }

    /**
     * Check if current resources can afford the required resources
     */
    public function canAfford(ResourceAmounts $required): bool
    {
        return $this->wood >= $required->wood &&
            $this->clay >= $required->clay &&
            $this->iron >= $required->iron &&
            $this->crop >= $required->crop;
    }

    /**
     * Get the resource type with the highest amount
     */
    public function getMostAbundantResource(): string
    {
        $resources = [
            'wood' => $this->wood,
            'clay' => $this->clay,
            'iron' => $this->iron,
            'crop' => $this->crop,
        ];

        return array_search(max($resources), $resources);
    }

    /**
     * Get the resource type with the lowest amount
     */
    public function getLeastAbundantResource(): string
    {
        $resources = [
            'wood' => $this->wood,
            'clay' => $this->clay,
            'iron' => $this->iron,
            'crop' => $this->crop,
        ];

        return array_search(min($resources), $resources);
    }

    /**
     * Check if all resources are at zero
     */
    public function isEmpty(): bool
    {
        return $this->getTotal() === 0;
    }

    /**
     * Get resources as array
     */
    public function toArray(): array
    {
        return [
            'wood' => $this->wood,
            'clay' => $this->clay,
            'iron' => $this->iron,
            'crop' => $this->crop,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            wood: $data['wood'] ?? 0,
            clay: $data['clay'] ?? 0,
            iron: $data['iron'] ?? 0,
            crop: $data['crop'] ?? 0
        );
    }
}
