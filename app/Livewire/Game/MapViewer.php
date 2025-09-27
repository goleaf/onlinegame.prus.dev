<?php

namespace App\Livewire\Game;

use App\Models\Game\Village;
use App\Services\GeographicService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MapViewer extends Component
{
    public $world;
    public $centerX = 0;
    public $centerY = 0;
    public $zoom = 1;
    public $villages = [];
    public $selectedVillage = null;
    public $showVillageInfo = false;
    public $mapSize = 20;  // 20x20 grid around center

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

        $this->villages = Village::with(['player'])
            ->where('world_id', $this->world->id)
            ->whereBetween('x_coordinate', [$minX, $maxX])
            ->whereBetween('y_coordinate', [$minY, $maxY])
            ->get()
            ->map(function ($village) {
                return [
                    'id' => $village->id,
                    'name' => $village->name,
                    'x' => $village->x_coordinate,
                    'y' => $village->y_coordinate,
                    'player_name' => $village->player->name,
                    'population' => $village->population,
                    'is_capital' => $village->is_capital,
                    'distance' => $this->calculateDistance($village->x_coordinate, $village->y_coordinate),
                ];
            })
            ->sortBy('distance');
    }

    public function calculateDistance($x, $y)
    {
        $geoService = app(GeographicService::class);
        return $geoService->calculateGameDistance($this->centerX, $this->centerY, $x, $y);
    }

    /**
     * Calculate real-world distance from center to a point
     *
     * @param int $x
     * @param int $y
     * @return float
     */
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

    public function zoomIn()
    {
        if ($this->mapSize > 5) {
            $this->mapSize -= 5;
            $this->loadMapData();
        }
    }

    public function zoomOut()
    {
        if ($this->mapSize < 50) {
            $this->mapSize += 5;
            $this->loadMapData();
        }
    }

    public function refreshMap()
    {
        $this->loadMapData();
    }

    public function render()
    {
        return view('livewire.game.map-viewer', [
            'world' => $this->world,
            'villages' => $this->villages,
            'selectedVillage' => $this->selectedVillage,
            'centerX' => $this->centerX,
            'centerY' => $this->centerY,
            'mapSize' => $this->mapSize,
        ]);
    }
}
