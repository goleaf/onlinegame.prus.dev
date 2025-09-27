<?php

namespace App\ValueObjects;

use Bag\Bag;

readonly class Coordinates extends Bag
{
    public function __construct(
        public int $x,
        public int $y,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $elevation = null,
        public ?string $geohash = null
    ) {}

    /**
     * Get coordinates as string representation
     */
    public function toString(): string
    {
        return "({$this->x}|{$this->y})";
    }

    /**
     * Check if coordinates have real-world data
     */
    public function hasRealWorldData(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Calculate distance to another coordinate set
     */
    public function distanceTo(Coordinates $other): float
    {
        return sqrt(pow($this->x - $other->x, 2) + pow($this->y - $other->y, 2));
    }

    /**
     * Calculate real-world distance to another coordinate set
     */
    public function realWorldDistanceTo(Coordinates $other): ?float
    {
        if (!$this->hasRealWorldData() || !$other->hasRealWorldData()) {
            return null;
        }

        $earthRadius = 6371; // km
        
        $lat1 = deg2rad($this->latitude);
        $lat2 = deg2rad($other->latitude);
        $deltaLat = deg2rad($other->latitude - $this->latitude);
        $deltaLon = deg2rad($other->longitude - $this->longitude);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get bearing to another coordinate set
     */
    public function bearingTo(Coordinates $other): ?float
    {
        if (!$this->hasRealWorldData() || !$other->hasRealWorldData()) {
            return null;
        }

        $lat1 = deg2rad($this->latitude);
        $lat2 = deg2rad($other->latitude);
        $deltaLon = deg2rad($other->longitude - $this->longitude);

        $y = sin($deltaLon) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($deltaLon);

        $bearing = atan2($y, $x);
        $bearing = fmod(rad2deg($bearing) + 360, 360);

        return $bearing;
    }

    /**
     * Check if coordinates are within radius of another coordinate set
     */
    public function isWithinRadius(Coordinates $center, float $radius): bool
    {
        return $this->distanceTo($center) <= $radius;
    }

    /**
     * Check if coordinates are within real-world radius of another coordinate set
     */
    public function isWithinRealWorldRadius(Coordinates $center, float $radiusKm): bool
    {
        $distance = $this->realWorldDistanceTo($center);
        return $distance !== null && $distance <= $radiusKm;
    }
}
