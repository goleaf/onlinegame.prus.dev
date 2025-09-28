<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
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
        $player = Player::where('user_id', Auth::id())
            ->with(['world:id,name', 'villages:id,player_id,x_coordinate,y_coordinate'])
            ->selectRaw('
                players.*,
                (SELECT COUNT(*) FROM villages WHERE player_id = players.id) as village_count,
                (SELECT AVG(x_coordinate) FROM villages WHERE player_id = players.id) as avg_x,
                (SELECT AVG(y_coordinate) FROM villages WHERE player_id = players.id) as avg_y
            ')
            ->first();

        if ($player) {
            $this->world = $player->world;
            $this->centerX = $player->villages->first()->x_coordinate ?? $player->avg_x ?? 0;
            $this->centerY = $player->villages->first()->y_coordinate ?? $player->avg_y ?? 0;
            $this->loadMapData();
        }
    }

    public function loadMapData()
    {
        if (! $this->world) {
            return;
        }

        $minX = $this->centerX - $this->mapSize;
        $maxX = $this->centerX + $this->mapSize;
        $minY = $this->centerY - $this->mapSize;
        $maxY = $this->centerY + $this->mapSize;

        $this->villages = Village::with(['player:id,name,alliance_id'])
            ->where('world_id', $this->world->id)
            ->whereBetween('x_coordinate', [$minX, $maxX])
            ->whereBetween('y_coordinate', [$minY, $maxY])
            ->selectRaw('
                villages.*,
                (SELECT COUNT(*) FROM buildings WHERE village_id = villages.id) as building_count,
                (SELECT COUNT(*) FROM troops WHERE village_id = villages.id AND quantity > 0) as troop_count,
                (SELECT SUM(wood + clay + iron + crop) FROM resources WHERE village_id = villages.id) as total_resources,
                SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2)) as distance_from_center
            ', [$this->centerX, $this->centerY])
            ->orderBy('distance_from_center')
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
                    'distance' => $village->distance_from_center,
                    'building_count' => $village->building_count ?? 0,
                    'troop_count' => $village->troop_count ?? 0,
                    'total_resources' => $village->total_resources ?? 0,
                ];
            });
    }

    public function calculateDistance($x, $y)
    {
        $geoService = app(GeographicService::class);

        return $geoService->calculateGameDistance($this->centerX, $this->centerY, $x, $y);
    }

    /**
     * Calculate real-world distance from center to a point
     *
     * @param  int  $x
     * @param  int  $y
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
