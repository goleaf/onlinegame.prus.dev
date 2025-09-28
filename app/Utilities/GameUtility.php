<?php

namespace App\Utilities;

/**
 * Game Utility Functions
 * Common utility functions for game operations
 */
class GameUtility
{
    /**
     * Format number with appropriate suffix
     */
    public static function formatNumber(int $number): string
    {
        if ($number >= 1000000000) {
            return round($number / 1000000000, 1).'B';
        } elseif ($number >= 1000000) {
            return round($number / 1000000, 1).'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1).'K';
        }

        return (string) $number;
    }

    /**
     * Calculate battle points based on unit composition
     */
    public static function calculateBattlePoints(array $units): int
    {
        $points = 0;
        $unitPoints = [
            'infantry' => 10,
            'archer' => 15,
            'cavalry' => 25,
            'siege' => 50,
            'hero' => 100,
        ];

        foreach ($units as $unitType => $count) {
            if (isset($unitPoints[$unitType])) {
                $points += $count * $unitPoints[$unitType];
            }
        }

        return $points;
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate travel time between two points
     */
    public static function calculateTravelTime(float $lat1, float $lon1, float $lat2, float $lon2, float $speed): int
    {
        $distance = self::calculateDistance($lat1, $lon1, $lat2, $lon2);

        return (int) round(($distance / $speed) * 3600); // Convert to seconds
    }

    /**
     * Format duration in seconds to human readable format
     */
    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;

            return $minutes.'m'.($remainingSeconds > 0 ? ' '.$remainingSeconds.'s' : '');
        } elseif ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            $remainingMinutes = floor(($seconds % 3600) / 60);

            return $hours.'h'.($remainingMinutes > 0 ? ' '.$remainingMinutes.'m' : '');
        } else {
            $days = floor($seconds / 86400);
            $remainingHours = floor(($seconds % 86400) / 3600);

            return $days.'d'.($remainingHours > 0 ? ' '.$remainingHours.'h' : '');
        }
    }

    /**
     * Generate random event
     */
    public static function generateRandomEvent(): array
    {
        $events = [
            [
                'type' => 'resource_bonus',
                'title' => 'Resource Discovery',
                'description' => 'Your villagers found extra resources!',
                'effect' => ['wood' => 100, 'clay' => 100, 'iron' => 100, 'crop' => 100],
                'probability' => 0.3,
            ],
            [
                'type' => 'troop_bonus',
                'title' => 'Training Bonus',
                'description' => 'Your troops trained harder today!',
                'effect' => ['training_speed' => 1.2],
                'probability' => 0.2,
            ],
            [
                'type' => 'building_bonus',
                'title' => 'Construction Efficiency',
                'description' => 'Your builders worked more efficiently!',
                'effect' => ['construction_speed' => 1.15],
                'probability' => 0.25,
            ],
            [
                'type' => 'attack_bonus',
                'title' => 'Battle Readiness',
                'description' => 'Your troops are more prepared for battle!',
                'effect' => ['attack_power' => 1.1],
                'probability' => 0.15,
            ],
            [
                'type' => 'defense_bonus',
                'title' => 'Defensive Preparation',
                'description' => 'Your defenses are stronger!',
                'effect' => ['defense_power' => 1.1],
                'probability' => 0.1,
            ],
        ];

        $random = mt_rand() / mt_getrandmax();
        $cumulativeProbability = 0;

        foreach ($events as $event) {
            $cumulativeProbability += $event['probability'];
            if ($random <= $cumulativeProbability) {
                return $event;
            }
        }

        // Fallback to first event
        return $events[0];
    }

    /**
     * Calculate resource production rate
     */
    public static function calculateResourceProduction(array $buildings, array $upgrades = []): array
    {
        $baseProduction = [
            'wood' => 10,
            'clay' => 10,
            'iron' => 10,
            'crop' => 10,
        ];

        $production = $baseProduction;

        // Apply building bonuses
        foreach ($buildings as $buildingType => $level) {
            switch ($buildingType) {
                case 'woodcutter':
                    $production['wood'] += $level * 5;

                    break;
                case 'clay_pit':
                    $production['clay'] += $level * 5;

                    break;
                case 'iron_mine':
                    $production['iron'] += $level * 5;

                    break;
                case 'crop_field':
                    $production['crop'] += $level * 5;

                    break;
            }
        }

        // Apply upgrade bonuses
        foreach ($upgrades as $upgradeType => $level) {
            switch ($upgradeType) {
                case 'production_bonus':
                    foreach ($production as $resource => $amount) {
                        $production[$resource] = (int) ($amount * (1 + $level * 0.1));
                    }

                    break;
            }
        }

        return $production;
    }

    /**
     * Calculate building cost
     */
    public static function calculateBuildingCost(string $buildingType, int $level): array
    {
        $baseCosts = [
            'woodcutter' => ['wood' => 50, 'clay' => 30, 'iron' => 20, 'crop' => 10],
            'clay_pit' => ['wood' => 30, 'clay' => 50, 'iron' => 20, 'crop' => 10],
            'iron_mine' => ['wood' => 20, 'clay' => 30, 'iron' => 50, 'crop' => 10],
            'crop_field' => ['wood' => 10, 'clay' => 20, 'iron' => 30, 'crop' => 50],
            'warehouse' => ['wood' => 100, 'clay' => 100, 'iron' => 100, 'crop' => 50],
            'barracks' => ['wood' => 200, 'clay' => 150, 'iron' => 100, 'crop' => 50],
        ];

        $baseCost = $baseCosts[$buildingType] ?? ['wood' => 100, 'clay' => 100, 'iron' => 100, 'crop' => 100];
        $cost = [];

        foreach ($baseCost as $resource => $amount) {
            $cost[$resource] = (int) ($amount * pow(1.5, $level - 1));
        }

        return $cost;
    }

    /**
     * Calculate troop training cost
     */
    public static function calculateTroopCost(string $troopType, int $count): array
    {
        $baseCosts = [
            'infantry' => ['wood' => 10, 'clay' => 5, 'iron' => 5, 'crop' => 10],
            'archer' => ['wood' => 15, 'clay' => 10, 'iron' => 5, 'crop' => 15],
            'cavalry' => ['wood' => 20, 'clay' => 15, 'iron' => 10, 'crop' => 25],
            'siege' => ['wood' => 50, 'clay' => 30, 'iron' => 40, 'crop' => 20],
        ];

        $baseCost = $baseCosts[$troopType] ?? ['wood' => 10, 'clay' => 10, 'iron' => 10, 'crop' => 10];
        $cost = [];

        foreach ($baseCost as $resource => $amount) {
            $cost[$resource] = $amount * $count;
        }

        return $cost;
    }

    /**
     * Calculate battle outcome
     */
    public static function calculateBattleOutcome(array $attackerUnits, array $defenderUnits): array
    {
        $attackerPower = self::calculateBattlePoints($attackerUnits);
        $defenderPower = self::calculateBattlePoints($defenderUnits);

        // Add some randomness
        $attackerPower *= (0.8 + (mt_rand() / mt_getrandmax()) * 0.4);
        $defenderPower *= (0.8 + (mt_rand() / mt_getrandmax()) * 0.4);

        $totalPower = $attackerPower + $defenderPower;
        $attackerWinProbability = $attackerPower / $totalPower;

        $random = mt_rand() / mt_getrandmax();
        $attackerWins = $random <= $attackerWinProbability;

        // Calculate losses
        $attackerLosses = $attackerWins ? 0.1 : 0.3;
        $defenderLosses = $attackerWins ? 0.3 : 0.1;

        return [
            'attacker_wins' => $attackerWins,
            'attacker_power' => (int) $attackerPower,
            'defender_power' => (int) $defenderPower,
            'attacker_losses' => $attackerLosses,
            'defender_losses' => $defenderLosses,
            'loot' => $attackerWins ? self::calculateLoot($defenderUnits) : [],
        ];
    }

    /**
     * Calculate loot from battle
     */
    private static function calculateLoot(array $defenderUnits): array
    {
        $totalUnits = array_sum($defenderUnits);
        $lootFactor = min($totalUnits / 100, 1.0); // Cap at 100%

        return [
            'wood' => (int) (100 * $lootFactor),
            'clay' => (int) (100 * $lootFactor),
            'iron' => (int) (100 * $lootFactor),
            'crop' => (int) (100 * $lootFactor),
        ];
    }

    /**
     * Generate unique reference number
     */
    public static function generateReference(string $prefix = 'REF'): string
    {
        return $prefix.'-'.now()->format('Ymd').'-'.str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Validate game coordinates
     */
    public static function validateCoordinates(float $lat, float $lon): bool
    {
        return $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180;
    }

    /**
     * Convert game coordinates to real-world coordinates
     */
    public static function gameToRealWorld(float $gameX, float $gameY): array
    {
        // Simple conversion - adjust based on your game world size
        $lat = ($gameY - 500) / 10; // Adjust based on your world size
        $lon = ($gameX - 500) / 10;

        return [
            'lat' => max(-90, min(90, $lat)),
            'lon' => max(-180, min(180, $lon)),
        ];
    }

    /**
     * Convert real-world coordinates to game coordinates
     */
    public static function realWorldToGame(float $lat, float $lon): array
    {
        $gameX = ($lon * 10) + 500;
        $gameY = ($lat * 10) + 500;

        return [
            'x' => max(0, min(1000, $gameX)),
            'y' => max(0, min(1000, $gameY)),
        ];
    }
}
