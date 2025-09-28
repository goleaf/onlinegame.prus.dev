<?php

namespace App\ValueObjects;

class Coordinates
{
    public function __construct(
        public readonly int $x,
        public readonly int $y,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            x: $data['x'] ?? $data['x_coordinate'] ?? 0,
            y: $data['y'] ?? $data['y_coordinate'] ?? 0,
            latitude: $data['latitude'] ?? $data['lat'] ?? null,
            longitude: $data['longitude'] ?? $data['lon'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'x_coordinate' => $this->x,
            'y_coordinate' => $this->y,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'lat' => $this->latitude,
            'lon' => $this->longitude,
        ];
    }

    public function distanceTo(Coordinates $other): float
    {
        return sqrt(
            pow($this->x - $other->x, 2) + pow($this->y - $other->y, 2)
        );
    }

    public function realWorldDistanceTo(Coordinates $other): ?float
    {
        if ($this->latitude === null || $this->longitude === null ||
            $other->latitude === null || $other->longitude === null) {
            return null;
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $lat1 = deg2rad($this->latitude);
        $lon1 = deg2rad($this->longitude);
        $lat2 = deg2rad($other->latitude);
        $lon2 = deg2rad($other->longitude);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function getBearingTo(Coordinates $other): float
    {
        if ($this->latitude === null || $this->longitude === null ||
            $other->latitude === null || $other->longitude === null) {
            return 0;
        }

        $lat1 = deg2rad($this->latitude);
        $lon1 = deg2rad($this->longitude);
        $lat2 = deg2rad($other->latitude);
        $lon2 = deg2rad($other->longitude);

        $deltaLon = $lon2 - $lon1;

        $y = sin($deltaLon) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($deltaLon);

        $bearing = atan2($y, $x);
        $bearing = rad2deg($bearing);
        $bearing = fmod($bearing + 360, 360);

        return $bearing;
    }

    public function getQuadrant(): string
    {
        if ($this->x >= 500 && $this->y >= 500) {
            return 'northeast';
        } elseif ($this->x < 500 && $this->y >= 500) {
            return 'northwest';
        } elseif ($this->x >= 500 && $this->y < 500) {
            return 'southeast';
        } else {
            return 'southwest';
        }
    }

    public function isWithinBounds(int $minX = 0, int $maxX = 999, int $minY = 0, int $maxY = 999): bool
    {
        return $this->x >= $minX && $this->x <= $maxX &&
               $this->y >= $minY && $this->y <= $maxY;
    }

    public function __toString(): string
    {
        return "({$this->x}|{$this->y})";
    }
}
