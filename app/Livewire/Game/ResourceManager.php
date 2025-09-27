<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ResourceManager extends Component
{
    public $village;

    public $resources = [
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0,
    ];

    public $productionRates = [
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0,
    ];

    public $capacities = [
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0,
    ];

    public $lastUpdate;
    public $autoUpdate = true;
    public $updateInterval = 5;  // seconds
    public $realTimeUpdates = true;
    public $showNotifications = true;
    public $gameSpeed = 1;
    public $notifications = [];
    public $resourceHistory = [];
    public $productionHistory = [];
    public $storageWarnings = [];
    public $isLoading = false;
    public $showDetails = false;
    public $selectedResource = null;

    protected $listeners = [
        'resources-updated',
        'gameTickProcessed',
        'buildingUpgraded',
        'villageSelected',
    ];

    public function mount($villageId = null)
    {
        if ($villageId) {
            $this->village = Village::withStats()
                ->with(['resources:id,village_id,type,amount,production_rate,capacity', 'player:id,name,level'])
                ->findOrFail($villageId);
        } else {
            $player = Player::where('user_id', Auth::id())
                ->with(['villages' => function ($query) {
                    $query
                        ->withStats()
                        ->with(['resources:id,village_id,type,amount,production_rate,capacity']);
                }])
                ->first();
            $this->village = $player?->villages->first();
        }

        $this->loadResources();
        $this->lastUpdate = now();
        $this->initializeResourceFeatures();
        $this->startResourcePolling();
    }

    public function initializeResourceFeatures()
    {
        $this->calculateStorageWarnings();
        $this->initializeResourceHistory();

        $this->dispatch('initializeResourceRealTime', [
            'interval' => $this->updateInterval * 1000,
            'autoUpdate' => $this->autoUpdate,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadResources()
    {
        if (!$this->village) {
            return;
        }

        // Load resources with optimized query
        $villageResources = $this->village->resources()
            ->selectRaw('
                resources.*,
                (SELECT SUM(amount) FROM resources r2 WHERE r2.village_id = resources.village_id) as total_resources,
                (SELECT SUM(production_rate) FROM resources r3 WHERE r3.village_id = resources.village_id) as total_production,
                (SELECT SUM(capacity) FROM resources r4 WHERE r4.village_id = resources.village_id) as total_capacity
            ')
            ->get();

        $this->resources = [
            'wood' => $villageResources->where('type', 'wood')->first()->amount ?? 0,
            'clay' => $villageResources->where('type', 'clay')->first()->amount ?? 0,
            'iron' => $villageResources->where('type', 'iron')->first()->amount ?? 0,
            'crop' => $villageResources->where('type', 'crop')->first()->amount ?? 0,
        ];

        // Load capacities from resources
        $this->capacities = [
            'wood' => $villageResources->where('type', 'wood')->first()->capacity ?? 800,
            'clay' => $villageResources->where('type', 'clay')->first()->capacity ?? 800,
            'iron' => $villageResources->where('type', 'iron')->first()->capacity ?? 800,
            'crop' => $villageResources->where('type', 'crop')->first()->capacity ?? 800,
        ];

        // Calculate production rates based on building levels
        $this->calculateProductionRates();
    }

    public function calculateProductionRates()
    {
        if (!$this->village) {
            return;
        }

        // Base production rates (would be calculated from building levels)
        $this->productionRates = [
            'wood' => 10,
            'clay' => 10,
            'iron' => 10,
            'crop' => 10,
        ];
    }

    #[On('resources-updated')]
    public function updateResources()
    {
        $this->loadResources();
        $this->lastUpdate = now();
        $this->dispatch('resources-updated');
    }

    public function startResourcePolling()
    {
        if ($this->autoUpdate) {
            $this->dispatch('start-resource-polling', ['interval' => $this->updateInterval * 1000]);
        }
    }

    public function stopResourcePolling()
    {
        $this->dispatch('stop-resource-polling');
    }

    #[On('tick')]
    public function processTick()
    {
        if (!$this->village || !$this->autoUpdate) {
            return;
        }

        $this->updateResourceProduction();
        $this->loadResources();
        $this->lastUpdate = now();

        // Dispatch event to update other components
        $this->dispatch('resources-updated');
    }

    public function updateResourceProduction()
    {
        if (!$this->village) {
            return;
        }

        $timeSinceLastUpdate = now()->diffInSeconds($this->lastUpdate);

        foreach ($this->productionRates as $resource => $rate) {
            $production = $rate * $timeSinceLastUpdate;
            $resourceModel = $this->village->resources()->where('type', $resource)->first();

            if ($resourceModel) {
                $currentAmount = $resourceModel->amount;
                $capacity = $this->capacities[$resource];
                $newAmount = min($currentAmount + $production, $capacity);
                $resourceModel->update(['amount' => $newAmount]);
            }
        }
    }

    public function spendResources($costs)
    {
        if (!$this->village) {
            return false;
        }

        foreach ($costs as $resource => $amount) {
            if ($this->resources[$resource] < $amount) {
                $this->dispatch('insufficient-resources', [
                    'resource' => $resource,
                    'required' => $amount,
                    'available' => $this->resources[$resource],
                ]);

                return false;
            }
        }

        // Deduct resources
        foreach ($costs as $resource => $amount) {
            $resourceModel = $this->village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $resourceModel->decrement('amount', $amount);
            }
        }

        $this->loadResources();
        $this->dispatch('resources-updated');

        return true;
    }

    public function addResources($amounts)
    {
        if (!$this->village) {
            return;
        }

        foreach ($amounts as $resource => $amount) {
            $resourceModel = $this->village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $currentAmount = $resourceModel->amount;
                $capacity = $this->capacities[$resource];
                $newAmount = min($currentAmount + $amount, $capacity);
                $resourceModel->update(['amount' => $newAmount]);
            }
        }

        $this->loadResources();
        $this->dispatch('resources-updated');
    }

    public function calculateStorageWarnings()
    {
        $this->storageWarnings = [];

        foreach ($this->resources as $type => $amount) {
            $capacity = $this->capacities[$type];
            $percentage = ($amount / $capacity) * 100;

            if ($percentage >= 90) {
                $this->storageWarnings[$type] = [
                    'level' => 'critical',
                    'message' => "{$type} storage is {$percentage}% full!",
                    'percentage' => $percentage,
                ];
            } elseif ($percentage >= 75) {
                $this->storageWarnings[$type] = [
                    'level' => 'warning',
                    'message' => "{$type} storage is {$percentage}% full",
                    'percentage' => $percentage,
                ];
            }
        }
    }

    public function initializeResourceHistory()
    {
        $this->resourceHistory = [
            'wood' => [],
            'clay' => [],
            'iron' => [],
            'crop' => [],
        ];

        $this->productionHistory = [
            'wood' => [],
            'clay' => [],
            'iron' => [],
            'crop' => [],
        ];
    }

    public function updateResourceHistory()
    {
        $timestamp = now()->timestamp;

        foreach ($this->resources as $type => $amount) {
            $this->resourceHistory[$type][] = [
                'timestamp' => $timestamp,
                'amount' => $amount,
            ];

            // Keep only last 50 data points
            $this->resourceHistory[$type] = array_slice($this->resourceHistory[$type], -50);
        }

        foreach ($this->productionRates as $type => $rate) {
            $this->productionHistory[$type][] = [
                'timestamp' => $timestamp,
                'rate' => $rate,
            ];

            // Keep only last 50 data points
            $this->productionHistory[$type] = array_slice($this->productionHistory[$type], -50);
        }
    }

    public function toggleRealTimeUpdates()
    {
        $this->realTimeUpdates = !$this->realTimeUpdates;
        $this->addNotification(
            $this->realTimeUpdates ? 'Real-time updates enabled' : 'Real-time updates disabled',
            'info'
        );
    }

    public function toggleAutoUpdate()
    {
        $this->autoUpdate = !$this->autoUpdate;

        if ($this->autoUpdate) {
            $this->startResourcePolling();
        } else {
            $this->stopResourcePolling();
        }

        $this->addNotification(
            $this->autoUpdate ? 'Auto-update enabled' : 'Auto-update disabled',
            'info'
        );
    }

    public function setUpdateInterval($interval)
    {
        $this->updateInterval = max(1, min(60, $interval));

        if ($this->autoUpdate) {
            $this->stopResourcePolling();
            $this->startResourcePolling();
        }

        $this->addNotification("Update interval set to {$this->updateInterval} seconds", 'info');
    }

    public function setGameSpeed($speed)
    {
        $this->gameSpeed = max(0.5, min(3.0, $speed));
        $this->addNotification("Game speed set to {$this->gameSpeed}x", 'info');
    }

    public function selectResource($type)
    {
        $this->selectedResource = $type;
        $this->showDetails = true;
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    public function getResourceIcon($type)
    {
        $icons = [
            'wood' => '🌲',
            'clay' => '🏺',
            'iron' => '⚒️',
            'crop' => '🌾',
        ];

        return $icons[$type] ?? '📦';
    }

    public function getResourceColor($type)
    {
        $colors = [
            'wood' => 'green',
            'clay' => 'orange',
            'iron' => 'gray',
            'crop' => 'yellow',
        ];

        return $colors[$type] ?? 'blue';
    }

    public function getResourcePercentage($type)
    {
        $amount = $this->resources[$type];
        $capacity = $this->capacities[$type];

        return min(100, ($amount / $capacity) * 100);
    }

    public function getTimeToFull($type)
    {
        $amount = $this->resources[$type];
        $capacity = $this->capacities[$type];
        $production = $this->productionRates[$type];

        if ($production <= 0) {
            return '∞';
        }

        $remaining = $capacity - $amount;
        $timeInSeconds = $remaining / $production;

        return gmdate('H:i:s', $timeInSeconds);
    }

    public function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
        ];

        // Keep only last 10 notifications
        $this->notifications = array_slice($this->notifications, -10);
    }

    public function removeNotification($notificationId)
    {
        $this->notifications = array_filter($this->notifications, function ($notification) use ($notificationId) {
            return $notification['id'] !== $notificationId;
        });
    }

    public function clearNotifications()
    {
        $this->notifications = [];
    }

    #[On('gameTickProcessed')]
    public function handleGameTickProcessed()
    {
        if ($this->realTimeUpdates) {
            $this->loadResources();
            $this->calculateStorageWarnings();
            $this->updateResourceHistory();
        }
    }

    #[On('buildingUpgraded')]
    public function handleBuildingUpgraded($data)
    {
        $this->loadResources();
        $this->calculateProductionRates();
        $this->addNotification('Building upgraded - resource production updated', 'success');
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $this->village = Village::findOrFail($villageId);
        $this->loadResources();
        $this->calculateStorageWarnings();
        $this->addNotification('Village selected - resources updated', 'info');
    }

    public function render()
    {
        return view('livewire.game.resource-manager', [
            'village' => $this->village,
            'resources' => $this->resources,
            'productionRates' => $this->productionRates,
            'capacities' => $this->capacities,
            'lastUpdate' => $this->lastUpdate,
            'autoUpdate' => $this->autoUpdate,
            'updateInterval' => $this->updateInterval,
            'realTimeUpdates' => $this->realTimeUpdates,
            'showNotifications' => $this->showNotifications,
            'gameSpeed' => $this->gameSpeed,
            'notifications' => $this->notifications,
            'resourceHistory' => $this->resourceHistory,
            'productionHistory' => $this->productionHistory,
            'storageWarnings' => $this->storageWarnings,
            'isLoading' => $this->isLoading,
            'showDetails' => $this->showDetails,
            'selectedResource' => $this->selectedResource,
        ]);
    }
}
