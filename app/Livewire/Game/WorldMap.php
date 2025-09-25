<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class WorldMap extends Component
{
    #[Reactive]
    public $world;
    
    public $mapData = [];
    public $selectedVillage = null;
    public $mapSize = 400; // 400x400 grid
    public $viewCenter = ['x' => 200, 'y' => 200];
    public $zoomLevel = 1;
    public $showCoordinates = true;
    public $showVillageNames = true;
    public $showAlliances = true;
    public $filterAlliance = null;
    public $filterTribe = null;
    public $isLoading = false;
    public $notifications = [];

    protected $listeners = [
        'refreshMap',
        'villageSelected',
        'mapUpdated',
        'centerOnVillage'
    ];

    public function mount($worldId = null)
    {
        if ($worldId) {
            $this->world = World::findOrFail($worldId);
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->world = $player?->world;
        }

        if ($this->world) {
            $this->loadMapData();
        }
    }

    public function loadMapData()
    {
        $this->isLoading = true;
        
        try {
            $villages = Village::where('world_id', $this->world->id)
                ->with(['player', 'alliance'])
                ->get();

            $this->mapData = $villages->map(function ($village) {
                return [
                    'id' => $village->id,
                    'name' => $village->name,
                    'x' => $village->x_coordinate,
                    'y' => $village->y_coordinate,
                    'population' => $village->population,
                    'player_name' => $village->player->name,
                    'player_id' => $village->player_id,
                    'alliance_name' => $village->player->alliance?->name,
                    'alliance_tag' => $village->player->alliance?->tag,
                    'tribe' => $village->player->tribe,
                    'is_capital' => $village->is_capital,
                    'is_active' => $village->is_active,
                ];
            })->toArray();

            $this->addNotification('Map data loaded successfully', 'success');
        } catch (\Exception $e) {
            $this->addNotification('Failed to load map data: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectVillage($villageId)
    {
        $village = Village::with(['player', 'alliance'])->find($villageId);
        
        if ($village) {
            $this->selectedVillage = [
                'id' => $village->id,
                'name' => $village->name,
                'x' => $village->x_coordinate,
                'y' => $village->y_coordinate,
                'population' => $village->population,
                'player_name' => $village->player->name,
                'alliance_name' => $village->player->alliance?->name,
                'tribe' => $village->player->tribe,
                'is_capital' => $village->is_capital,
            ];

            $this->dispatch('villageSelected', ['villageId' => $villageId]);
            $this->addNotification("Selected village: {$village->name}", 'info');
        }
    }

    public function centerOnVillage($villageId)
    {
        $village = Village::find($villageId);
        
        if ($village) {
            $this->viewCenter = [
                'x' => $village->x_coordinate,
                'y' => $village->y_coordinate
            ];
            
            $this->addNotification("Centered on village: {$village->name}", 'info');
        }
    }

    public function moveMap($direction)
    {
        $moveDistance = 50 / $this->zoomLevel;
        
        switch ($direction) {
            case 'north':
                $this->viewCenter['y'] = max(0, $this->viewCenter['y'] - $moveDistance);
                break;
            case 'south':
                $this->viewCenter['y'] = min($this->mapSize, $this->viewCenter['y'] + $moveDistance);
                break;
            case 'east':
                $this->viewCenter['x'] = min($this->mapSize, $this->viewCenter['x'] + $moveDistance);
                break;
            case 'west':
                $this->viewCenter['x'] = max(0, $this->viewCenter['x'] - $moveDistance);
                break;
        }
    }

    public function zoomIn()
    {
        $this->zoomLevel = min(5, $this->zoomLevel + 1);
        $this->addNotification("Zoom level: {$this->zoomLevel}", 'info');
    }

    public function zoomOut()
    {
        $this->zoomLevel = max(0.5, $this->zoomLevel - 1);
        $this->addNotification("Zoom level: {$this->zoomLevel}", 'info');
    }

    public function resetZoom()
    {
        $this->zoomLevel = 1;
        $this->addNotification("Zoom reset to 1x", 'info');
    }

    public function toggleCoordinates()
    {
        $this->showCoordinates = !$this->showCoordinates;
    }

    public function toggleVillageNames()
    {
        $this->showVillageNames = !$this->showVillageNames;
    }

    public function toggleAlliances()
    {
        $this->showAlliances = !$this->showAlliances;
    }

    public function setFilterAlliance($allianceId)
    {
        $this->filterAlliance = $allianceId;
        $this->addNotification("Filtered by alliance", 'info');
    }

    public function setFilterTribe($tribe)
    {
        $this->filterTribe = $tribe;
        $this->addNotification("Filtered by tribe: {$tribe}", 'info');
    }

    public function clearFilters()
    {
        $this->filterAlliance = null;
        $this->filterTribe = null;
        $this->addNotification("Filters cleared", 'info');
    }

    public function getFilteredMapData()
    {
        $data = $this->mapData;

        if ($this->filterAlliance) {
            $data = array_filter($data, function ($village) {
                return $village['alliance_name'] === $this->filterAlliance;
            });
        }

        if ($this->filterTribe) {
            $data = array_filter($data, function ($village) {
                return $village['tribe'] === $this->filterTribe;
            });
        }

        return $data;
    }

    public function getVisibleVillages()
    {
        $data = $this->getFilteredMapData();
        $viewSize = 100 / $this->zoomLevel;
        
        return array_filter($data, function ($village) use ($viewSize) {
            return abs($village['x'] - $this->viewCenter['x']) <= $viewSize &&
                   abs($village['y'] - $this->viewCenter['y']) <= $viewSize;
        });
    }

    #[On('refreshMap')]
    public function refreshMap()
    {
        $this->loadMapData();
    }

    #[On('mapUpdated')]
    public function handleMapUpdated()
    {
        $this->loadMapData();
    }

    public function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now()
        ];
        
        // Keep only last 10 notifications
        $this->notifications = array_slice($this->notifications, -10);
    }

    public function removeNotification($notificationId)
    {
        $this->notifications = array_filter($this->notifications, function($notification) use ($notificationId) {
            return $notification['id'] !== $notificationId;
        });
    }

    public function clearNotifications()
    {
        $this->notifications = [];
    }

    public function render()
    {
        return view('livewire.game.world-map', [
            'world' => $this->world,
            'mapData' => $this->mapData,
            'selectedVillage' => $this->selectedVillage,
            'visibleVillages' => $this->getVisibleVillages(),
            'viewCenter' => $this->viewCenter,
            'zoomLevel' => $this->zoomLevel,
            'showCoordinates' => $this->showCoordinates,
            'showVillageNames' => $this->showVillageNames,
            'showAlliances' => $this->showAlliances,
            'filterAlliance' => $this->filterAlliance,
            'filterTribe' => $this->filterTribe,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading
        ]);
    }
}
