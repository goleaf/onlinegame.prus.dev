<?php

namespace App\Services;

use League\Geotools\Convert\Convert;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Distance\Distance;
use League\Geotools\Geohash\Geohash;
use League\Geotools\Vertex\Vertex;

class GeographicService
{
    /**
     * Calculate the distance between two coordinates using Haversine formula
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @param string $unit 'km', 'm', 'mi', 'ft'
     * @return float
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2, string $unit = 'km'): float
    {
        // Use Haversine formula for distance calculation
        $earthRadius = $unit === 'km' ? 6371 : ($unit === 'mi' ? 3959 : 6371000);

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2)
            + cos($lat1Rad) * cos($lat2Rad)
                * sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate distance between two game coordinates (x, y grid system)
     *
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @return float
     */
    public function calculateGameDistance(int $x1, int $y1, int $x2, int $y2): float
    {
        return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
    }

    /**
     * Convert game coordinates to approximate real-world coordinates
     * This is a simplified conversion for game world mapping
     *
     * @param int $x
     * @param int $y
     * @param int $worldSize
     * @return array ['lat' => float, 'lon' => float]
     */
    public function gameToRealWorld(int $x, int $y, int $worldSize = 1000): array
    {
        // Convert game coordinates to approximate lat/lon
        // This assumes the game world is roughly 1000x1000 units
        // and maps to a small region (e.g., 1 degree x 1 degree)

        $lat = 50.0 + ($y / $worldSize) * 1.0;  // Base latitude + offset
        $lon = 8.0 + ($x / $worldSize) * 1.0;  // Base longitude + offset

        return [
            'lat' => $lat,
            'lon' => $lon
        ];
    }

    /**
     * Convert real-world coordinates to game coordinates
     *
     * @param float $lat
     * @param float $lon
     * @param int $worldSize
     * @return array ['x' => int, 'y' => int]
     */
    public function realWorldToGame(float $lat, float $lon, int $worldSize = 1000): array
    {
        $x = (int) (($lon - 8.0) * $worldSize);
        $y = (int) (($lat - 50.0) * $worldSize);

        return [
            'x' => max(0, min($worldSize - 1, $x)),
            'y' => max(0, min($worldSize - 1, $y))
        ];
    }

    /**
     * Calculate bearing between two points
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float
     */
    public function calculateBearing(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLon = deg2rad($lon2 - $lon1);

        $y = sin($deltaLon) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($deltaLon);

        $bearing = atan2($y, $x);
        $bearing = rad2deg($bearing);

        return ($bearing + 360) % 360;  // Normalize to 0-360
    }

    /**
     * Generate a geohash for a coordinate
     *
     * @param float $lat
     * @param float $lon
     * @param int $precision
     * @return string
     */
    public function generateGeohash(float $lat, float $lon, int $precision = 8): string
    {
        // Simple geohash implementation for now
        // This is a basic implementation - for production use a proper geohash library
        $chars = '0123456789bcdefghjkmnpqrstuvwxyz';
        $geohash = '';

        $latMin = -90.0;
        $latMax = 90.0;
        $lonMin = -180.0;
        $lonMax = 180.0;

        $bit = 0;
        $ch = 0;
        $even = true;

        while (strlen($geohash) < $precision) {
            if ($even) {
                $mid = ($lonMin + $lonMax) / 2;
                if ($lon >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $lonMin = $mid;
                } else {
                    $lonMax = $mid;
                }
            } else {
                $mid = ($latMin + $latMax) / 2;
                if ($lat >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $latMin = $mid;
                } else {
                    $latMax = $mid;
                }
            }

            $even = !$even;

            if ($bit < 4) {
                $bit++;
            } else {
                $geohash .= $chars[$ch];
                $bit = 0;
                $ch = 0;
            }
        }

        return $geohash;
    }

    /**
     * Decode a geohash to coordinates
     *
     * @param string $geohash
     * @return array ['lat' => float, 'lon' => float]
     */
    public function decodeGeohash(string $geohash): array
    {
        $geohashObj = new Geohash();
        $geohashObj->decode($geohash);
        $coordinate = $geohashObj->getCoordinate();

        return [
            'lat' => $coordinate->getLatitude(),
            'lon' => $coordinate->getLongitude()
        ];
    }

    /**
     * Find villages within a radius of a given coordinate
     *
     * @param float $centerLat
     * @param float $centerLon
     * @param float $radiusKm
     * @param array $villages
     * @return array
     */
    public function findVillagesInRadius(float $centerLat, float $centerLon, float $radiusKm, array $villages): array
    {
        $centerCoord = new Coordinate([$centerLat, $centerLon]);
        $distance = new Distance();
        $distance->setFrom($centerCoord);

        $villagesInRadius = [];

        foreach ($villages as $village) {
            $villageCoord = new Coordinate([$village['lat'], $village['lon']]);
            $distance->setTo($villageCoord);

            if ($distance->in('km') <= $radiusKm) {
                $villagesInRadius[] = array_merge($village, [
                    'distance_km' => $distance->in('km')
                ]);
            }
        }

        // Sort by distance
        usort($villagesInRadius, function ($a, $b) {
            return $a['distance_km'] <=> $b['distance_km'];
        });

        return $villagesInRadius;
    }

    /**
     * Calculate travel time between two points based on distance and speed
     *
     * @param float $distance
     * @param float $speedKmh
     * @return int Travel time in seconds
     */

    public function calculateTravelTime(float $distance, float $speedKmh): int
    {
        if ($speedKmh <= 0) {
            return 0;
        }

        $timeHours = $distance / $speedKmh;
        return (int) ($timeHours * 3600);  // Convert to seconds
    }

    /**
     * Get bearing (direction) from one point to another
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Bearing in degrees (0-360)
     */
    public function getBearing(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $coord1 = new Coordinate([$lat1, $lon1]);
        $coord2 = new Coordinate([$lat2, $lon2]);

        $vertex = new Vertex();
        $vertex->setFrom($coord1);
        $vertex->setTo($coord2);

        return $vertex->initialBearing();
    }

    /**
     * Convert coordinates to different formats
     *
     * @param float $lat
     * @param float $lon
     * @param string $format 'decimal', 'dms', 'utm'
     * @return array|string
     */
    public function convertCoordinateFormat(float $lat, float $lon, string $format = 'decimal')
    {
        $coordinate = new Coordinate([$lat, $lon]);
        $convert = new Convert($coordinate);

        switch ($format) {
            case 'dms':
                return $convert->toDegreesMinutesSeconds();
            case 'utm':
                return $convert->toUniversalTransverseMercator();
            case 'decimal':
            default:
                return [
                    'lat' => $coordinate->getLatitude(),
                    'lon' => $coordinate->getLongitude()
                ];
        }
    }

    /**
     * Check if a point is within a bounding box
     *
     * @param float $lat
     * @param float $lon
     * @param float $minLat
     * @param float $maxLat
     * @param float $minLon
     * @param float $maxLon
     * @return bool
     */
    public function isPointInBounds(float $lat, float $lon, float $minLat, float $maxLat, float $minLon, float $maxLon): bool
    {
        return $lat >= $minLat && $lat <= $maxLat && $lon >= $minLon && $lon <= $maxLon;
    }

    /**
     * Calculate the center point of multiple coordinates
     *
     * @param array $coordinates Array of ['lat' => float, 'lon' => float]
     * @return array ['lat' => float, 'lon' => float]
     */
    public function calculateCenterPoint(array $coordinates): array
    {
        if (empty($coordinates)) {
            return ['lat' => 0, 'lon' => 0];
        }

        $totalLat = 0;
        $totalLon = 0;
        $count = count($coordinates);

        foreach ($coordinates as $coord) {
            $totalLat += $coord['lat'];
            $totalLon += $coord['lon'];
        }

        return [
            'lat' => $totalLat / $count,
            'lon' => $totalLon / $count
        ];
    }

    /**
     * Generate a random coordinate within a bounding box
     *
     * @param float $minLat
     * @param float $maxLat
     * @param float $minLon
     * @param float $maxLon
     * @return array ['lat' => float, 'lon' => float]
     */
    public function generateRandomCoordinate(float $minLat, float $maxLat, float $minLon, float $maxLon): array
    {
        return [
            'lat' => $minLat + (mt_rand() / mt_getrandmax()) * ($maxLat - $minLat),
            'lon' => $minLon + (mt_rand() / mt_getrandmax()) * ($maxLon - $minLon)
        ];
    }
}
