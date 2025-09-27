<?php

namespace App\Livewire\Game;

use App\Models\Game\Village;
use App\Services\GeographicService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdvancedMapViewer extends Component
{
    public $world;
    public $centerX = 0;
    public $centerY = 0;
    public $zoom = 1;
    public $villages = [];
    public $selectedVillage = null;
    public $showVillageInfo = false;
    public $mapSize = 20;
    // Geographic features
    public $showRealWorldCoordinates = false;
    public $showGeohash = false;
    public $showDistance = false;
    public $showBearing = false;
    public $radiusFilter = 0;
    public $elevationFilter = null;
    // Map modes
    public $mapMode = 'game';  // 'game', 'real_world', 'hybrid'
    public $coordinateSystem = 'game';  // 'game', 'decimal', 'dms'

    protected $listeners = ['refreshMap', 'villageSelected', 'mapMoved'];

    public function mount()
    {
        $user = Auth::user();
        $player = $user->player;

        if ($player) {
            $this->world = $player->world;
            $this->centerX = $player->villages()->first()->x_coordinate ?? 0;
            $this->centerY = $player->villages()->first()->y_coordinate ?? 0;
            $this->loadMapData();
        }
    }

    public function loadMapData()
    {
        if (!$this->world) {
            return;
        }

        $minX = $this->centerX - $this->mapSize;
        $maxX = $this->centerX + $this->mapSize;
        $minY = $this->centerY - $this->mapSize;
        $maxY = $this->centerY + $this->mapSize;

        $query = Village::with(['player'])
            ->where('world_id', $this->world->id)
            ->whereBetween('x_coordinate', [$minX, $maxX])
            ->whereBetween('y_coordinate', [$minY, $maxY]);

        // Apply radius filter if set
        if ($this->radiusFilter > 0) {
            $query->withinRadius($this->centerX, $this->centerY, $this->radiusFilter);
        }

        // Apply elevation filter if set
        if ($this->elevationFilter !== null) {
            $query->where('elevation', '>=', $this->elevationFilter);
        }

        $this->villages = $query
            ->get()
            ->map(function ($village) {
                $geoService = app(GeographicService::class);
                $coords = $village->getRealWorldCoordinates();

                return [
                    'id' => $village->id,
                    'name' => $village->name,
                    'x' => $village->x_coordinate,
                    'y' => $village->y_coordinate,
                    'player_name' => $village->player->name,
                    'population' => $village->population,
                    'is_capital' => $village->is_capital,
                    'distance' => $this->calculateDistance($village->x_coordinate, $village->y_coordinate),
                    'real_world_coords' => $coords,
                    'geohash' => $village->geohash,
                    'elevation' => $village->elevation,
                    'bearing' => $this->calculateBearing($village->x_coordinate, $village->y_coordinate),
                    'real_world_distance' => $this->calculateRealWorldDistance($village->x_coordinate, $village->y_coordinate),
                ];
            })
            ->sortBy('distance');
    }

    public function calculateDistance($x, $y)
    {
        $geoService = app(GeographicService::class);
        return $geoService->calculateGameDistance($this->centerX, $this->centerY, $x, $y);
    }

    public function calculateRealWorldDistance($x, $y)
    {
        $geoService = app(GeographicService::class);

        $centerCoords = $geoService->gameToRealWorld($this->centerX, $this->centerY);
        $targetCoords = $geoService->gameToRealWorld($x, $y);

        return $geoService->calculateDistance(
            $centerCoords['lat'],
            $centerCoords['lon'],
            $targetCoords['lat'],
            $targetCoords['lon']
        );
    }

    public function calculateBearing($x, $y)
    {
        $geoService = app(GeographicService::class);

        $centerCoords = $geoService->gameToRealWorld($this->centerX, $this->centerY);
        $targetCoords = $geoService->gameToRealWorld($x, $y);

        return $geoService->getBearing(
            $centerCoords['lat'],
            $centerCoords['lon'],
            $targetCoords['lat'],
            $targetCoords['lon']
        );
    }

    public function selectVillage($villageId)
    {
        $this->selectedVillage = $this->villages->firstWhere('id', $villageId);
        $this->showVillageInfo = true;
    }

    public function moveMap($direction)
    {
        switch ($direction) {
            case 'north':
                $this->centerY -= 5;
                break;
            case 'south':
                $this->centerY += 5;
                break;
            case 'east':
                $this->centerX += 5;
                break;
            case 'west':
                $this->centerX -= 5;
                break;
        }

        $this->loadMapData();
        $this->dispatch('mapMoved', ['x' => $this->centerX, 'y' => $this->centerY]);
    }

    public function centerOnVillage($villageId)
    {
        $village = $this->villages->firstWhere('id', $villageId);
        if ($village) {
            $this->centerX = $village['x'];
            $this->centerY = $village['y'];
            $this->loadMapData();
        }
    }

    public function toggleRealWorldCoordinates()
    {
        $this->showRealWorldCoordinates = !$this->showRealWorldCoordinates;
    }

    public function toggleGeohash()
    {
        $this->showGeohash = !$this->showGeohash;
    }

    public function toggleDistance()
    {
        $this->showDistance = !$this->showDistance;
    }

    public function toggleBearing()
    {
        $this->showBearing = !$this->showBearing;
    }

    public function setMapMode($mode)
    {
        $this->mapMode = $mode;
    }

    public function setCoordinateSystem($system)
    {
        $this->coordinateSystem = $system;
    }

    public function setRadiusFilter($radius)
    {
        $this->radiusFilter = (float) $radius;
        $this->loadMapData();
    }

    public function setElevationFilter($elevation)
    {
        $this->elevationFilter = $elevation ? (float) $elevation : null;
        $this->loadMapData();
    }

    public function findVillagesInRadius($radiusKm)
    {
        $geoService = app(GeographicService::class);
        $centerCoords = $geoService->gameToRealWorld($this->centerX, $this->centerY);

        $villages = Village::with(['player'])
            ->where('world_id', $this->world->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function ($village) {
                return [
                    'id' => $village->id,
                    'name' => $village->name,
                    'lat' => (float) $village->latitude,
                    'lon' => (float) $village->longitude,
                    'player_name' => $village->player->name,
                    'population' => $village->population,
                ];
            })
            ->toArray();

        $villagesInRadius = $geoService->findVillagesInRadius(
            $centerCoords['lat'],
            $centerCoords['lon'],
            (float) $radiusKm,
            $villages
        );

        $this->villages = collect($villagesInRadius)->map(function ($village) {
            $geoService = app(GeographicService::class);
            $gameCoords = $geoService->realWorldToGame($village['lat'], $village['lon']);

            return [
                'id' => $village['id'],
                'name' => $village['name'],
                'x' => $gameCoords['x'],
                'y' => $gameCoords['y'],
                'player_name' => $village['player_name'],
                'population' => $village['population'],
                'distance' => $village['distance_km'],
                'real_world_coords' => ['lat' => $village['lat'], 'lon' => $village['lon']],
                'geohash' => $geoService->generateGeohash($village['lat'], $village['lon']),
                'elevation' => null,
                'bearing' => $geoService->getBearing($this->centerX, $this->centerY, $gameCoords['x'], $gameCoords['y']),
                'real_world_distance' => $village['distance_km'],
            ];
        });
    }

    public function render()
    {
        return view('livewire.game.advanced-map-viewer', [
            'village' => $this->selectedVillage,
            'villages' => $this->villages,
            'centerX' => $this->centerX,
            'centerY' => $this->centerY,
            'mapSize' => $this->mapSize,
            'showRealWorldCoordinates' => $this->showRealWorldCoordinates,
            'showGeohash' => $this->showGeohash,
            'showDistance' => $this->showDistance,
            'showBearing' => $this->showBearing,
            'mapMode' => $this->mapMode,
            'coordinateSystem' => $this->coordinateSystem,
        ]);
    }
}
