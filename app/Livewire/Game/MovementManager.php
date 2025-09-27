<?php

namespace App\Livewire\Game;

use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Troop;
use App\Models\Game\Village;
use App\Services\GeographicService;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\FilteringUtil;
use LaraUtilX\Utilities\PaginationUtil;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use SmartCache\Facades\SmartCache;

class MovementManager extends Component
{
    use WithPagination, ApiResponseTrait;

    public $village;

    public $movements;

    public $selectedMovement = null;

    public $notifications = [];

    public $isLoading = false;

    // Real-time features
    public $realTimeUpdates = true;

    public $autoRefresh = true;

    public $refreshInterval = 10;  // seconds

    public $gameSpeed = 1;

    // Movement creation
    public $targetVillageId = null;

    public $movementType = 'attack';  // 'attack', 'reinforce', 'support', 'return'

    public $selectedTroops = [];

    public $troopQuantities = [];

    public $movementTime = null;

    public $arrivalTime = null;

    // Filtering and Sorting
    public $filterByType = null;  // 'attack', 'reinforce', 'support', 'return'

    public $filterByStatus = null;  // 'travelling', 'arrived', 'returning', 'completed'

    public $sortBy = 'created_at';

    public $sortOrder = 'desc';

    public $searchQuery = '';

    public $showOnlyMyMovements = true;

    public $showOnlyTravelling = false;

    public $showOnlyCompleted = false;

    // Stats
    public $movementStats = [];

    public $movementHistory = [];

    public $troopStats = [];

    public $distanceStats = [];

    public $timeStats = [];

    // Available troops for selection
    public $availableTroops = [];

    public $troopCapacity = 0;

    public $totalAttackPower = 0;

    public $totalDefensePower = 0;

    public $travelTime = 0;

    protected $listeners = [
        'refreshMovements',
        'movementCreated',
        'movementUpdated',
        'movementArrived',
        'movementReturned',
        'movementCancelled',
        'gameTickProcessed',
        'villageSelected',
    ];

    public function mount(Village $village = null)
    {
        if ($village) {
            $this->village = $village;
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->village = $player?->village;
        }

        // Laradumps debugging
        $startTime = microtime(true);
        ds('MovementManager mounted', [
            'village_id' => $this->village?->id,
            'village_name' => $this->village?->name,
            'player_id' => $this->village?->player_id,
            'user_id' => Auth::id(),
            'mount_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
        ])->label('MovementManager Mount');

        if ($this->village) {
            $this->movements = collect();
            $this->loadMovementData();
            $this->initializeMovementFeatures();
        }
    }

    public function initializeMovementFeatures()
    {
        $this->loadAvailableTroops();
        $this->calculateMovementStats();
        $this->calculateMovementHistory();
        $this->calculateTroopStats();
        $this->calculateDistanceStats();
        $this->calculateTimeStats();

        $this->dispatch('initializeMovementRealTime', [
            'interval' => $this->refreshInterval * 1000,
            'autoRefresh' => $this->autoRefresh,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadMovementData()
    {
        $this->isLoading = true;

        // Laradumps debugging
        ds('Loading movement data', [
            'village_id' => $this->village->id,
            'filter_by_type' => $this->filterByType,
            'filter_by_status' => $this->filterByStatus,
            'show_only_my_movements' => $this->showOnlyMyMovements,
            'search_query' => $this->searchQuery,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder
        ])->label('MovementManager Load Movement Data');

        try {
            // Use SmartCache for movement data with automatic optimization
            $cacheKey = "village_{$this->village->id}_movements_{$this->filterByType}_{$this->filterByStatus}_{$this->showOnlyMyMovements}_{$this->sortBy}_{$this->sortOrder}";

            $this->movements = SmartCache::remember($cacheKey, now()->addMinutes(2), function () {
                // Use optimized scopes from Movement model
                $query = Movement::byVillage($this->village->id)
                    ->withVillageInfo()
                    ->byType($this->filterByType)
                    ->byStatus($this->filterByStatus)
                    ->when($this->showOnlyMyMovements, function ($q) {
                        return $q->byPlayer($this->village->player_id);
                    })
                    ->search($this->searchQuery)
                    ->orderBy($this->sortBy, $this->sortOrder);

                return $query->get();
            });

            // Apply additional filtering using FilteringUtil for complex filters
            if (!empty($this->searchQuery)) {
                $this->movements = FilteringUtil::filter(
                    $this->movements,
                    'type',
                    'contains',
                    $this->searchQuery
                );
            }

            if ($this->showOnlyTravelling) {
                $this->movements = FilteringUtil::filter(
                    $this->movements,
                    'status',
                    'equals',
                    'travelling'
                );
            }

            if ($this->showOnlyCompleted) {
                $this->movements = FilteringUtil::filter(
                    $this->movements,
                    'status',
                    'equals',
                    'completed'
                );
            }

            ds('Movement data loaded successfully', [
                'total_movements' => $this->movements->count(),
                'travelling_movements' => $this->movements->where('status', 'travelling')->count(),
                'completed_movements' => $this->movements->where('status', 'completed')->count(),
                'movement_types' => $this->movements->groupBy('type')->map->count()
            ])->label('MovementManager Movement Data Loaded');
        } catch (\Exception $e) {
            ds('Error loading movement data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ])->label('MovementManager Load Movement Data Error');
            $this->addNotification('Error loading movement data: ' . $e->getMessage(), 'error');
            $this->movements = collect();
        }

        $this->isLoading = false;
        $this->addNotification('Movement data loaded successfully', 'info');
    }

    public function loadAvailableTroops()
    {
        $this->availableTroops = Troop::where('village_id', $this->village->id)
            ->where('quantity', '>', 0)
            ->with('unitType:id,name,attack_power,defense_power,speed')
            ->selectRaw('
                troops.*,
                (quantity * unit_types.attack_power) as total_attack_power,
                (quantity * unit_types.defense_power) as total_defense_power
            ')
            ->join('unit_types', 'troops.unit_type_id', '=', 'unit_types.id')
            ->get();

        $this->calculateTroopCapacity();
    }

    public function calculateTroopCapacity()
    {
        // Use optimized calculation with pre-calculated values from selectRaw
        $this->troopCapacity = $this->availableTroops->sum('quantity');
        $this->totalAttackPower = $this->availableTroops->sum('total_attack_power');
        $this->totalDefensePower = $this->availableTroops->sum('total_defense_power');
    }

    public function createMovement()
    {
        $this->validate([
            'targetVillageId' => 'required|exists:villages,id',
            'movementType' => 'required|in:attack,reinforce,support,return',
            'selectedTroops' => 'required|array|min:1',
            'troopQuantities' => 'required|array',
        ]);

        $targetVillage = Village::find($this->targetVillageId);
        if (!$targetVillage) {
            ds('Movement creation failed - target village not found', [
                'target_village_id' => $this->targetVillageId,
                'village_id' => $this->village->id
            ])->label('MovementManager Create Movement Failed');
            $this->addNotification('Target village not found.', 'error');
            return;
        }

        if ($targetVillage->id === $this->village->id) {
            ds('Movement creation failed - same village', [
                'village_id' => $this->village->id,
                'target_village_id' => $targetVillage->id
            ])->label('MovementManager Create Movement Failed');
            $this->addNotification('Cannot move to the same village.', 'error');
            return;
        }

        // Calculate travel time based on distance
        $distance = $this->calculateDistance($this->village, $targetVillage);
        $this->travelTime = $this->calculateTravelTime($distance);

        $this->movementTime = now();
        $this->arrivalTime = now()->addSeconds($this->travelTime);

        // Calculate real-world distance for additional context
        $realWorldDistance = $this->calculateRealWorldDistance($this->village, $targetVillage);

        // Laradumps debugging
        ds('Creating movement', [
            'from_village' => $this->village->name,
            'to_village' => $targetVillage->name,
            'movement_type' => $this->movementType,
            'game_distance' => $distance,
            'real_world_distance_km' => $realWorldDistance,
            'travel_time' => $this->travelTime,
            'selected_troops' => $this->selectedTroops,
            'troop_quantities' => $this->troopQuantities,
            'from_coordinates' => "({$this->village->x_coordinate}|{$this->village->y_coordinate})",
            'to_coordinates' => "({$targetVillage->x_coordinate}|{$targetVillage->y_coordinate})"
        ])->label('MovementManager Create Movement');

        // Create movement
        $movement = Movement::create([
            'player_id' => $this->village->player_id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
            'type' => $this->movementType,
            'status' => 'travelling',
            'started_at' => $this->movementTime,
            'arrives_at' => $this->arrivalTime,
            'travel_time' => $this->travelTime,
        ]);

        // Add troops to movement
        $troopsData = [];
        foreach ($this->selectedTroops as $troopId) {
            $quantity = $this->troopQuantities[$troopId] ?? 0;
            if ($quantity > 0) {
                $troop = Troop::find($troopId);
                if ($troop) {
                    $troopsData[$troop->unitType->key] = $quantity;

                    // Deduct troops from village
                    if ($troop->count >= $quantity) {
                        $troop->decrement('count', $quantity);
                    }
                }
            }
        }

        $movement->update(['troops' => $troopsData]);

        // Generate reference number for the movement
        $movement->generateReferenceNumber();

        ds('Movement created successfully', [
            'movement_id' => $movement->id,
            'reference_number' => $movement->reference_number,
            'troops_data' => $troopsData,
            'arrives_at' => $this->arrivalTime
        ])->label('MovementManager Movement Created');

        $this->reset(['targetVillageId', 'selectedTroops', 'troopQuantities', 'movementTime', 'arrivalTime']);
        $this->loadMovementData();
        $this->addNotification('Movement created successfully!', 'success');
        $this->dispatch('movementCreated', ['movementId' => $movement->id]);

        // Track movement creation
        $totalTroops = array_sum($this->troopQuantities);
        $this->dispatch('fathom-track', name: 'movement created', value: $totalTroops);
    }

    public function cancelMovement($movementId)
    {
        $movement = Movement::find($movementId);
        if (!$movement || $movement->status !== 'travelling') {
            $this->addNotification('Movement not found or cannot be cancelled.', 'error');

            return;
        }

        if ($movement->player_id !== $this->village->player_id) {
            $this->addNotification('You can only cancel your own movements.', 'error');

            return;
        }

        // Return troops to village
        if ($movement->troops && is_array($movement->troops)) {
            foreach ($movement->troops as $unitType => $quantity) {
                $unitTypeModel = \App\Models\Game\UnitType::where('key', $unitType)->first();
                if ($unitTypeModel) {
                    $villageTroop = Troop::where('village_id', $this->village->id)
                        ->where('unit_type_id', $unitTypeModel->id)
                        ->first();

                    if ($villageTroop) {
                        $villageTroop->increment('count', $quantity);
                    }
                }
            }
        }

        $movement->update(['status' => 'cancelled']);
        $this->loadMovementData();
        $this->addNotification("Movement {$movementId} cancelled.", 'info');
        $this->dispatch('movementCancelled', ['movementId' => $movementId]);

        // Track movement cancellation
        $this->dispatch('fathom-track', name: 'movement cancelled', value: $movementId);
    }

    public function selectMovement($movementId)
    {
        $this->selectedMovement = Movement::with(['fromVillage', 'toVillage', 'player'])->find($movementId);
        $this->addNotification("Selected movement: {$movementId}", 'info');
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    public function setTargetVillage($villageId)
    {
        $this->targetVillageId = $villageId;
        $this->addNotification("Target village set: {$villageId}", 'info');
    }

    public function setMovementType($type)
    {
        $this->movementType = $type;
        $this->addNotification("Movement type set: {$type}", 'info');
    }

    public function selectTroop($troopId, $quantity = 1)
    {
        $troop = Troop::find($troopId);
        if (!$troop || $troop->village_id !== $this->village->id) {
            $this->addNotification("Invalid troop ID: {$troopId}", 'error');

            return;
        }

        $quantity = (int) $quantity;
        if ($quantity <= 0) {
            $this->addNotification("Invalid troop quantity: {$quantity}", 'error');

            return;
        }

        $this->selectedTroops[$troopId] = $troopId;
        $this->troopQuantities[$troopId] = $quantity;
        $this->addNotification("Troop selected: {$troopId} x{$quantity}", 'info');
    }

    public function setTroopQuantity($troopId, $quantity)
    {
        $this->troopQuantities[$troopId] = max(0, (int) $quantity);
        if ($quantity <= 0) {
            unset($this->selectedTroops[$troopId]);
        }
    }

    public function clearTroopSelection()
    {
        $this->selectedTroops = [];
        $this->troopQuantities = [];
        $this->addNotification('Troop selection cleared', 'info');
    }

    public function filterByType($type)
    {
        $this->filterByType = $type;
        $this->addNotification("Filtered by type: {$type}", 'info');
    }

    public function filterByStatus($status)
    {
        $this->filterByStatus = $status;
        $this->addNotification("Filtered by status: {$status}", 'info');
    }

    public function clearFilters()
    {
        $this->filterByType = null;
        $this->filterByStatus = null;
        $this->searchQuery = '';
        $this->showOnlyMyMovements = true;
        $this->showOnlyTravelling = false;
        $this->showOnlyCompleted = false;
        $this->addNotification('All filters cleared', 'info');
    }

    public function sortMovements($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortOrder = 'desc';
        }
        $this->addNotification("Sorted by {$sortBy} ({$this->sortOrder})", 'info');
    }

    public function searchMovements()
    {
        if (empty($this->searchQuery)) {
            $this->addNotification('Search cleared', 'info');

            return;
        }
        $this->addNotification("Searching for: {$this->searchQuery}", 'info');
    }

    public function toggleMyMovementsFilter()
    {
        $this->showOnlyMyMovements = !$this->showOnlyMyMovements;
        $this->addNotification(
            $this->showOnlyMyMovements ? 'Showing only my movements' : 'Showing all movements',
            'info'
        );
    }

    public function toggleTravellingFilter()
    {
        $this->showOnlyTravelling = !$this->showOnlyTravelling;
        $this->addNotification(
            $this->showOnlyTravelling ? 'Showing only travelling movements' : 'Showing all movements',
            'info'
        );
    }

    public function toggleCompletedFilter()
    {
        $this->showOnlyCompleted = !$this->showOnlyCompleted;
        $this->addNotification(
            $this->showOnlyCompleted ? 'Showing only completed movements' : 'Showing all movements',
            'info'
        );
    }

    public function calculateMovementStats()
    {
        // Use optimized scope with selectRaw to get all movement stats at once
        $stats = Movement::byVillage($this->village->id)
            ->selectRaw('
                COUNT(*) as total_movements,
                SUM(CASE WHEN status = "travelling" THEN 1 ELSE 0 END) as travelling_movements,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_movements,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_movements,
                SUM(CASE WHEN status = "arrived" THEN 1 ELSE 0 END) as arrived_movements,
                AVG(travel_time) as avg_travel_time,
                MAX(travel_time) as max_travel_time,
                MIN(travel_time) as min_travel_time
            ')
            ->first();

        $this->movementStats = [
            'total_movements' => $stats->total_movements ?? 0,
            'travelling_movements' => $stats->travelling_movements ?? 0,
            'completed_movements' => $stats->completed_movements ?? 0,
            'cancelled_movements' => $stats->cancelled_movements ?? 0,
            'arrived_movements' => $stats->arrived_movements ?? 0,
            'avg_travel_time' => round($stats->avg_travel_time ?? 0, 2),
            'max_travel_time' => $stats->max_travel_time ?? 0,
            'min_travel_time' => $stats->min_travel_time ?? 0,
            'total_distance_travelled' => $this->calculateTotalDistance(),
        ];
    }

    public function calculateMovementHistory()
    {
        $this->movementHistory = Movement::byVillage($this->village->id)
            ->withVillageInfo()
            ->recent(30)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
    }

    public function calculateTroopStats()
    {
        $this->troopStats = [
            'total_attack_power' => $this->totalAttackPower,
            'total_defense_power' => $this->totalDefensePower,
            'troop_capacity' => $this->troopCapacity,
            'available_troops' => $this->availableTroops->count(),
        ];
    }

    public function calculateDistanceStats()
    {
        $this->distanceStats = [
            'average_distance' => $this->calculateAverageDistance(),
            'longest_distance' => $this->calculateLongestDistance(),
            'shortest_distance' => $this->calculateShortestDistance(),
        ];
    }

    public function calculateTimeStats()
    {
        $this->timeStats = [
            'average_travel_time' => $this->calculateAverageTravelTime(),
            'longest_travel_time' => $this->calculateLongestTravelTime(),
            'shortest_travel_time' => $this->calculateShortestTravelTime(),
        ];
    }

    private function calculateTotalDistance()
    {
        $totalDistance = 0;
        foreach ($this->movements as $movement) {
            if ($movement->from_village_id === $this->village->id) {
                $totalDistance += $this->calculateDistance($this->village, $movement->toVillage);
            }
        }

        return $totalDistance;
    }

    private function calculateAverageDistance()
    {
        if ($this->movements->count() === 0) {
            return 0;
        }

        return $this->calculateTotalDistance() / $this->movements->count();
    }

    private function calculateLongestDistance()
    {
        $longestDistance = 0;
        foreach ($this->movements as $movement) {
            if ($movement->from_village_id === $this->village->id) {
                $distance = $this->calculateDistance($this->village, $movement->toVillage);
                $longestDistance = max($longestDistance, $distance);
            }
        }

        return $longestDistance;
    }

    private function calculateShortestDistance()
    {
        $shortestDistance = PHP_FLOAT_MAX;
        foreach ($this->movements as $movement) {
            if ($movement->from_village_id === $this->village->id) {
                $distance = $this->calculateDistance($this->village, $movement->toVillage);
                $shortestDistance = min($shortestDistance, $distance);
            }
        }

        return $shortestDistance === PHP_FLOAT_MAX ? 0 : $shortestDistance;
    }

    private function calculateAverageTravelTime()
    {
        if ($this->movements->count() === 0) {
            return 0;
        }
        $totalTime = $this->movements->sum('travel_time');

        return $totalTime / $this->movements->count();
    }

    private function calculateLongestTravelTime()
    {
        return $this->movements->max('travel_time') ?? 0;
    }

    private function calculateShortestTravelTime()
    {
        return $this->movements->min('travel_time') ?? 0;
    }

    private function calculateDistance(Village $village1, Village $village2)
    {
        $geoService = app(GeographicService::class);
        return $geoService->calculateGameDistance(
            $village1->x_coordinate,
            $village1->y_coordinate,
            $village2->x_coordinate,
            $village2->y_coordinate
        );
    }

    /**
     * Calculate real-world distance between villages
     *
     * @param Village $village1
     * @param Village $village2
     * @return float
     */
    private function calculateRealWorldDistance(Village $village1, Village $village2)
    {
        return $village1->realWorldDistanceTo($village2);
    }

    private function calculateTravelTime($distance)
    {
        // Use geographic service for more accurate travel time calculation
        $geoService = app(GeographicService::class);

        // Convert game distance to approximate real-world distance (km)
        $realWorldDistanceKm = $distance * 0.1;  // Rough conversion: 1 game unit = 0.1 km

        // Use average troop speed (can be made configurable)
        $averageSpeedKmh = 20;  // 20 km/h average speed

        return $geoService->calculateTravelTime($realWorldDistanceKm, $averageSpeedKmh);
    }

    public function getMovementIcon($movement)
    {
        return match ($movement['type']) {
            'attack' => '⚔️',
            'reinforce' => '🛡️',
            'support' => '🤝',
            'return' => '↩️',
            default => '🚶',
        };
    }

    public function getMovementColor($movement)
    {
        return match ($movement['status']) {
            'travelling' => 'blue',
            'arrived' => 'green',
            'returning' => 'orange',
            'completed' => 'gray',
            'cancelled' => 'red',
            default => 'black',
        };
    }

    public function getMovementStatus($movement)
    {
        return ucfirst($movement['status']);
    }

    public function getTimeRemaining($movement)
    {
        if ($movement['status'] === 'travelling' && $movement['arrives_at']) {
            $arrivalTime = \Carbon\Carbon::parse($movement['arrives_at']);
            $now = now();

            if ($now->lt($arrivalTime)) {
                return $now->diffForHumans($arrivalTime, true);
            }
        }

        if ($movement['status'] === 'returning' && $movement['returns_at']) {
            $returnTime = \Carbon\Carbon::parse($movement['returns_at']);
            $now = now();

            if ($now->lt($returnTime)) {
                return $now->diffForHumans($returnTime, true);
            }
        }

        return 'N/A';
    }

    public function getTroopIcon($troop)
    {
        return match ($troop['unit_type']['name'] ?? '') {
            'Legionnaire' => '🛡️',
            'Praetorian' => '⚔️',
            'Imperian' => '🏹',
            'Equites Legati' => '🐎',
            'Equites Imperatoris' => '🐎',
            'Equites Caesaris' => '🐎',
            'Battering Ram' => '🔨',
            'Fire Catapult' => '🔥',
            'Senator' => '👑',
            'Settler' => '🏘️',
            default => '⚔️',
        };
    }

    public function getTroopColor($troop)
    {
        return match ($troop['unit_type']['name'] ?? '') {
            'Legionnaire' => 'blue',
            'Praetorian' => 'red',
            'Imperian' => 'green',
            'Equites Legati' => 'purple',
            'Equites Imperatoris' => 'purple',
            'Equites Caesaris' => 'purple',
            'Battering Ram' => 'brown',
            'Fire Catapult' => 'orange',
            'Senator' => 'gold',
            'Settler' => 'gray',
            default => 'black',
        };
    }

    public function toggleRealTimeUpdates()
    {
        $this->realTimeUpdates = !$this->realTimeUpdates;
        $this->addNotification(
            $this->realTimeUpdates ? 'Real-time updates enabled' : 'Real-time updates disabled',
            'info'
        );
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        $this->addNotification(
            $this->autoRefresh ? 'Auto-refresh enabled' : 'Auto-refresh disabled',
            'info'
        );
    }

    public function setRefreshInterval($interval)
    {
        $this->refreshInterval = max(1, min(60, $interval));
        $this->addNotification("Refresh interval set to {$this->refreshInterval} seconds", 'info');
    }

    public function setGameSpeed($speed)
    {
        $this->gameSpeed = max(0.5, min(3.0, $speed));
        $this->addNotification("Game speed set to {$this->gameSpeed}x", 'info');
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
            $this->loadMovementData();
            $this->calculateMovementStats();
            $this->calculateMovementHistory();
            $this->calculateTroopStats();
            $this->calculateDistanceStats();
            $this->calculateTimeStats();
        }
    }

    #[On('movementCreated')]
    public function handleMovementCreated($data)
    {
        $this->loadMovementData();
        $this->addNotification('New movement created', 'success');
    }

    #[On('movementUpdated')]
    public function handleMovementUpdated($data)
    {
        $this->loadMovementData();
        $this->addNotification('Movement updated', 'info');
    }

    #[On('movementArrived')]
    public function handleMovementArrived($data)
    {
        $this->loadMovementData();
        $this->addNotification('Movement arrived', 'success');
    }

    #[On('movementReturned')]
    public function handleMovementReturned($data)
    {
        $this->loadMovementData();
        $this->addNotification('Movement returned', 'success');
    }

    #[On('movementCancelled')]
    public function handleMovementCancelled($data)
    {
        $this->loadMovementData();
        $this->addNotification('Movement cancelled', 'info');
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $this->village = Village::find($villageId);
        $this->loadMovementData();
        $this->addNotification('Village selected - movement data updated', 'info');
    }

    public function render()
    {
        // Use PaginationUtil for consistent pagination
        $paginatedMovements = PaginationUtil::paginate(
            $this->movements->toArray(),
            15,  // per page
            request()->get('page', 1),
            ['path' => request()->url()]
        );

        return view('livewire.game.movement-manager', [
            'movements' => $this->movements,
            'paginatedMovements' => $paginatedMovements,
            'selectedMovement' => $this->selectedMovement,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading,
            'realTimeUpdates' => $this->realTimeUpdates,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
            'gameSpeed' => $this->gameSpeed,
            'targetVillageId' => $this->targetVillageId,
            'movementType' => $this->movementType,
            'selectedTroops' => $this->selectedTroops,
            'troopQuantities' => $this->troopQuantities,
            'movementTime' => $this->movementTime,
            'arrivalTime' => $this->arrivalTime,
            'filterByType' => $this->filterByType,
            'filterByStatus' => $this->filterByStatus,
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
            'searchQuery' => $this->searchQuery,
            'showOnlyMyMovements' => $this->showOnlyMyMovements,
            'showOnlyTravelling' => $this->showOnlyTravelling,
            'showOnlyCompleted' => $this->showOnlyCompleted,
            'movementStats' => $this->movementStats,
            'movementHistory' => $this->movementHistory,
            'troopStats' => $this->troopStats,
            'distanceStats' => $this->distanceStats,
            'timeStats' => $this->timeStats,
            'availableTroops' => $this->availableTroops,
            'troopCapacity' => $this->troopCapacity,
            'totalAttackPower' => $this->totalAttackPower,
            'totalDefensePower' => $this->totalDefensePower,
            'travelTime' => $this->travelTime,
        ]);
    }
}
