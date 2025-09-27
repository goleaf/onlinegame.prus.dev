<?php

namespace App\Utilities;

class GameUtility
{
    /**
     * Format large numbers for display (e.g., 1000 -> 1K, 1000000 -> 1M)
     */
    public static function formatNumber(int $number): string
    {
        if ($number >= 1000000000) {
            return round($number / 1000000000, 1) . 'B';
        } elseif ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }

        return (string) $number;
    }

    /**
     * Calculate battle points based on units
     */
    public static function calculateBattlePoints(array $units): int
    {
        $totalPoints = 0;
        $unitPoints = [
            'infantry' => 1,
            'archer' => 2,
            'cavalry' => 3,
            'siege' => 5,
        ];

        foreach ($units as $type => $count) {
            $totalPoints += ($unitPoints[$type] ?? 1) * $count;
        }

        return $totalPoints;
    }

    /**
     * Calculate resource production rate
     */
    public static function calculateResourceProduction(array $buildings): array
    {
        $production = [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ];

        $buildingProduction = [
            'woodcutter' => ['wood' => 10],
            'clay_pit' => ['clay' => 10],
            'iron_mine' => ['iron' => 10],
            'crop_field' => ['crop' => 10],
        ];

        foreach ($buildings as $building => $level) {
            if (isset($buildingProduction[$building])) {
                foreach ($buildingProduction[$building] as $resource => $rate) {
                    $production[$resource] += $rate * $level;
                }
            }
        }

        return $production;
    }

    /**
     * Calculate travel time between coordinates
     */
    public static function calculateTravelTime(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2,
        float $speed = 10.0
    ): int {
        $distance = self::calculateDistance($lat1, $lon1, $lat2, $lon2);
        return (int) ($distance / $speed * 3600);  // Convert to seconds
    }

    /**
     * Calculate distance between two coordinates in kilometers
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;  // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
                * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Generate random coordinates within a given area
     */
    public static function generateRandomCoordinates(
        float $centerLat,
        float $centerLon,
        float $radiusKm = 10.0
    ): array {
        $angle = deg2rad(rand(0, 360));
        $distance = rand(0, $radiusKm * 1000) / 1000;  // Convert to km

        $lat = $centerLat + ($distance / 111.32) * cos($angle);
        $lon = $centerLon + ($distance / (111.32 * cos(deg2rad($centerLat)))) * sin($angle);

        return [
            'lat' => round($lat, 6),
            'lon' => round($lon, 6),
        ];
    }

    /**
     * Format time duration in a human-readable format
     */
    public static function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }

    /**
     * Calculate experience points for an action
     */
    public static function calculateExperience(string $action, int $level = 1): int
    {
        $baseExp = [
            'battle' => 100,
            'building' => 50,
            'research' => 75,
            'trade' => 25,
        ];

        $base = $baseExp[$action] ?? 50;
        return $base * $level;
    }

    /**
     * Generate a random game event
     */
    public static function generateRandomEvent(): array
    {
        $events = [
            [
                'type' => 'resource_bonus',
                'title' => 'Resource Discovery',
                'description' => 'Your scouts found additional resources!',
                'effect' => ['wood' => 1000, 'clay' => 1000, 'iron' => 1000],
            ],
            [
                'type' => 'attack_bonus',
                'title' => 'Tactical Advantage',
                'description' => 'Your army gains a temporary attack bonus!',
                'effect' => ['attack_bonus' => 0.1],
            ],
            [
                'type' => 'defense_bonus',
                'title' => 'Fortification',
                'description' => 'Your defenses are strengthened!',
                'effect' => ['defense_bonus' => 0.1],
            ],
        ];

        return $events[array_rand($events)];
    }

    /**
     * Validate game coordinates
     */
    public static function isValidCoordinate(float $lat, float $lon): bool
    {
        return $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180;
    }

    /**
     * Calculate alliance score
     */
    public static function calculateAllianceScore(array $members): int
    {
        $totalScore = 0;
        foreach ($members as $member) {
            $totalScore += $member['points'] ?? 0;
        }
        return $totalScore;
    }
}
