<?php

namespace App\Livewire\Game;

use App\Models\Game\MarketOffer;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithPagination;

class MarketManager extends Component
{
    use WithPagination;

    #[Reactive]
    public $village;

    public $offers = [];
    public $myOffers = [];
    public $selectedOffer = null;
    public $offerTypes = ['buy', 'sell'];
    public $resourceTypes = ['wood', 'clay', 'iron', 'crop'];
    public $selectedType = 'buy';
    public $selectedResource = 'wood';
    public $offerQuantity = 1;
    public $offerPrice = 1;
    public $offerDuration = 24;  // hours
    public $notifications = [];
    public $isLoading = false;
    public $realTimeUpdates = true;
    public $autoRefresh = true;
    public $refreshInterval = 10;
    public $gameSpeed = 1;
    public $showDetails = false;
    public $selectedOfferId = null;
    public $filterByType = null;
    public $filterByResource = null;
    public $sortBy = 'created_at';
    public $sortOrder = 'desc';
    public $searchQuery = '';
    public $showOnlyActive = false;
    public $showOnlyMyOffers = false;
    public $showOnlyExpired = false;
    public $marketStats = [];
    public $tradingHistory = [];
    public $priceHistory = [];
    public $offerStats = [];
    public $tradingVolume = [];
    public $averagePrices = [];
    public $marketTrends = [];

    protected $listeners = [
        'offerCreated',
        'offerUpdated',
        'offerCancelled',
        'offerExpired',
        'tradeCompleted',
        'villageSelected',
        'gameTickProcessed',
    ];

    public function mount($villageId = null)
    {
        if ($villageId) {
            $this->village = Village::with(['player', 'resources'])->findOrFail($villageId);
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->village = $player?->villages()->with(['player', 'resources'])->first();
        }

        if ($this->village) {
            $this->loadMarketData();
            $this->initializeMarketFeatures();
        }
    }

    public function initializeMarketFeatures()
    {
        $this->calculateMarketStats();
        $this->calculateTradingHistory();
        $this->calculatePriceHistory();
        $this->calculateOfferStats();
        $this->calculateTradingVolume();
        $this->calculateAveragePrices();
        $this->calculateMarketTrends();

        $this->dispatch('initializeMarketRealTime', [
            'interval' => $this->refreshInterval * 1000,
            'autoRefresh' => $this->autoRefresh,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadMarketData()
    {
        $this->isLoading = true;

        try {
            // Create base query for reuse
            $baseQuery = MarketOffer::where('world_id', $this->village->world_id)
                ->with(['seller:id,name', 'buyer:id,name', 'village:id,name']);

            // Clone base query for active offers using QueryOptimizationService
            $activeOffersQuery = QueryOptimizationService::cloneQuery($baseQuery);

            $activeFilters = [
                $this->filterByType => function ($q) {
                    return $q->where('type', $this->filterByType);
                },
                $this->filterByResource => function ($q) {
                    return $q->where('resource_type', $this->filterByResource);
                },
                $this->searchQuery => function ($q) {
                    return $q->where(function ($subQ) {
                        $subQ
                            ->where('resource_type', 'like', '%' . $this->searchQuery . '%')
                            ->orWhereHas('seller', function ($sellerQ) {
                                $sellerQ->where('name', 'like', '%' . $this->searchQuery . '%');
                            });
                    });
                },
            ];

            $activeOffersQuery = QueryOptimizationService::applyConditionalFilters($activeOffersQuery, $activeFilters);
            $activeOffersQuery = QueryOptimizationService::applyConditionalOrdering($activeOffersQuery, $this->sortBy, $this->sortOrder);

            $this->offers = $activeOffersQuery
                ->where('status', 'active')
                ->get()
                ->toArray();

            // Clone base query for my offers using QueryOptimizationService
            $myOffersQuery = QueryOptimizationService::cloneQuery($baseQuery);

            $myFilters = [
                $this->showOnlyActive => function ($q) {
                    return $q->where('status', 'active');
                },
                $this->showOnlyExpired => function ($q) {
                    return $q->where('status', 'expired');
                },
            ];

            $myOffersQuery = QueryOptimizationService::applyConditionalFilters($myOffersQuery, $myFilters);
            $myOffersQuery = QueryOptimizationService::applyConditionalOrdering($myOffersQuery, 'created_at', 'desc');

            $this->myOffers = $myOffersQuery
                ->where('seller_id', $this->village->player_id)
                ->get()
                ->toArray();

            $this->addNotification('Market data loaded successfully', 'success');
        } catch (\Exception $e) {
            $this->addNotification('Failed to load market data: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectOffer($offerId)
    {
        $this->selectedOffer = MarketOffer::with(['seller', 'buyer', 'village'])->find($offerId);
        $this->selectedOfferId = $offerId;
        $this->showDetails = true;
        $this->addNotification('Offer selected', 'info');
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    public function setOfferType($type)
    {
        $this->selectedType = $type;
        $this->addNotification("Offer type set to: {$type}", 'info');
    }

    public function setResourceType($resource)
    {
        $this->selectedResource = $resource;
        $this->addNotification("Resource type set to: {$resource}", 'info');
    }

    public function setOfferQuantity($quantity)
    {
        $this->offerQuantity = max(1, min(10000, $quantity));
        $this->addNotification("Offer quantity set to: {$this->offerQuantity}", 'info');
    }

    public function setOfferPrice($price)
    {
        $this->offerPrice = max(1, min(1000000, $price));
        $this->addNotification("Offer price set to: {$this->offerPrice}", 'info');
    }

    public function setOfferDuration($duration)
    {
        $this->offerDuration = max(1, min(168, $duration));  // 1 hour to 1 week
        $this->addNotification("Offer duration set to: {$this->offerDuration} hours", 'info');
    }

    public function filterByType($type)
    {
        $this->filterByType = $type;
        $this->addNotification("Filtering by type: {$type}", 'info');
    }

    public function filterByResource($resource)
    {
        $this->filterByResource = $resource;
        $this->addNotification("Filtering by resource: {$resource}", 'info');
    }

    public function clearFilters()
    {
        $this->filterByType = null;
        $this->filterByResource = null;
        $this->searchQuery = '';
        $this->showOnlyActive = false;
        $this->showOnlyMyOffers = false;
        $this->showOnlyExpired = false;
        $this->addNotification('All filters cleared', 'info');
    }

    public function sortOffers($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortOrder = 'desc';
        }

        $this->addNotification("Sorted by {$sortBy} ({$this->sortOrder})", 'info');
    }

    public function searchOffers()
    {
        if (empty($this->searchQuery)) {
            $this->addNotification('Search cleared', 'info');

            return;
        }

        $this->addNotification("Searching for: {$this->searchQuery}", 'info');
    }

    public function toggleActiveFilter()
    {
        $this->showOnlyActive = !$this->showOnlyActive;
        $this->addNotification(
            $this->showOnlyActive ? 'Showing only active offers' : 'Showing all offers',
            'info'
        );
    }

    public function toggleMyOffersFilter()
    {
        $this->showOnlyMyOffers = !$this->showOnlyMyOffers;
        $this->addNotification(
            $this->showOnlyMyOffers ? 'Showing only my offers' : 'Showing all offers',
            'info'
        );
    }

    public function toggleExpiredFilter()
    {
        $this->showOnlyExpired = !$this->showOnlyExpired;
        $this->addNotification(
            $this->showOnlyExpired ? 'Showing only expired offers' : 'Showing all offers',
            'info'
        );
    }

    public function createOffer()
    {
        if ($this->offerQuantity <= 0 || $this->offerPrice <= 0) {
            $this->addNotification('Invalid offer parameters', 'error');

            return;
        }

        if ($this->selectedType === 'sell') {
            // Check if player has enough resources
            $resource = $this->village->resources->where('type', $this->selectedResource)->first();
            if (!$resource || $resource->amount < $this->offerQuantity) {
                $this->addNotification('Insufficient resources to create offer', 'error');

                return;
            }
        }

        try {
            $offer = MarketOffer::create([
                'seller_id' => $this->village->player_id,
                'village_id' => $this->village->id,
                'world_id' => $this->village->world_id,
                'type' => $this->selectedType,
                'resource_type' => $this->selectedResource,
                'quantity' => $this->offerQuantity,
                'price_per_unit' => $this->offerPrice,
                'total_price' => $this->offerQuantity * $this->offerPrice,
                'duration_hours' => $this->offerDuration,
                'expires_at' => now()->addHours($this->offerDuration),
                'status' => 'active',
                'created_at' => now(),
            ]);

            if ($this->selectedType === 'sell') {
                // Reserve resources
                $resource = $this->village->resources->where('type', $this->selectedResource)->first();
                $resource->decrement('amount', $this->offerQuantity);
            }

            $this->loadMarketData();
            $this->addNotification('Offer created successfully', 'success');

            $this->dispatch('offerCreated', [
                'offer_id' => $offer->id,
                'type' => $offer->type,
                'resource_type' => $offer->resource_type,
                'quantity' => $offer->quantity,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to create offer: ' . $e->getMessage(), 'error');
        }
    }

    public function acceptOffer($offerId)
    {
        $offer = MarketOffer::find($offerId);
        if (!$offer) {
            $this->addNotification('Offer not found', 'error');

            return;
        }

        if ($offer->status !== 'active') {
            $this->addNotification('Offer is no longer active', 'error');

            return;
        }

        if ($offer->seller_id === $this->village->player_id) {
            $this->addNotification('Cannot accept your own offer', 'error');

            return;
        }

        try {
            if ($offer->type === 'sell') {
                // Player is buying
                $this->buyFromOffer($offer);
            } else {
                // Player is selling
                $this->sellToOffer($offer);
            }
        } catch (\Exception $e) {
            $this->addNotification('Failed to accept offer: ' . $e->getMessage(), 'error');
        }
    }

    private function buyFromOffer($offer)
    {
        $totalCost = $offer->total_price;
        $player = $this->village->player;

        // Check if player has enough resources to pay
        $costPerResource = $totalCost / 4;  // Distribute cost across all resources
        $canAfford = true;

        foreach (['wood', 'clay', 'iron', 'crop'] as $resourceType) {
            $resource = $this->village->resources->where('type', $resourceType)->first();
            if (!$resource || $resource->amount < $costPerResource) {
                $canAfford = false;

                break;
            }
        }

        if (!$canAfford) {
            $this->addNotification('Insufficient resources to buy', 'error');

            return;
        }

        // Deduct payment
        foreach (['wood', 'clay', 'iron', 'crop'] as $resourceType) {
            $resource = $this->village->resources->where('type', $resourceType)->first();
            $resource->decrement('amount', $costPerResource);
        }

        // Add purchased resource
        $resource = $this->village->resources->where('type', $offer->resource_type)->first();
        $resource->increment('amount', $offer->quantity);

        // Update offer
        $offer->update([
            'buyer_id' => $this->village->player_id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->loadMarketData();
        $this->addNotification("Successfully bought {$offer->quantity} {$offer->resource_type}", 'success');

        $this->dispatch('tradeCompleted', [
            'offer_id' => $offer->id,
            'buyer_id' => $this->village->player_id,
            'seller_id' => $offer->seller_id,
        ]);
    }

    private function sellToOffer($offer)
    {
        // Check if player has enough resources to sell
        $resource = $this->village->resources->where('type', $offer->resource_type)->first();
        if (!$resource || $resource->amount < $offer->quantity) {
            $this->addNotification('Insufficient resources to sell', 'error');

            return;
        }

        // Deduct resources
        $resource->decrement('amount', $offer->quantity);

        // Add payment
        $paymentPerResource = $offer->total_price / 4;  // Distribute payment across all resources
        foreach (['wood', 'clay', 'iron', 'crop'] as $resourceType) {
            $resource = $this->village->resources->where('type', $resourceType)->first();
            $resource->increment('amount', $paymentPerResource);
        }

        // Update offer
        $offer->update([
            'buyer_id' => $this->village->player_id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->loadMarketData();
        $this->addNotification("Successfully sold {$offer->quantity} {$offer->resource_type}", 'success');

        $this->dispatch('tradeCompleted', [
            'offer_id' => $offer->id,
            'buyer_id' => $offer->buyer_id,
            'seller_id' => $this->village->player_id,
        ]);
    }

    public function cancelOffer($offerId)
    {
        $offer = MarketOffer::find($offerId);
        if (!$offer) {
            $this->addNotification('Offer not found', 'error');

            return;
        }

        if ($offer->seller_id !== $this->village->player_id) {
            $this->addNotification("Cannot cancel someone else's offer", 'error');

            return;
        }

        if ($offer->status !== 'active') {
            $this->addNotification('Offer is no longer active', 'error');

            return;
        }

        try {
            // Return reserved resources if it was a sell offer
            if ($offer->type === 'sell') {
                $resource = $this->village->resources->where('type', $offer->resource_type)->first();
                $resource->increment('amount', $offer->quantity);
            }

            $offer->update(['status' => 'cancelled']);
            $this->loadMarketData();
            $this->addNotification('Offer cancelled successfully', 'success');

            $this->dispatch('offerCancelled', [
                'offer_id' => $offer->id,
                'seller_id' => $offer->seller_id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to cancel offer: ' . $e->getMessage(), 'error');
        }
    }

    public function calculateMarketStats()
    {
        // Use single query with selectRaw to get all market stats at once
        $stats = MarketOffer::where('world_id', $this->village->world_id)
            ->selectRaw('
                COUNT(*) as total_offers,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_offers,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_offers,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_offers,
                SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired_offers,
                SUM(CASE WHEN status = "completed" THEN total_price ELSE 0 END) as total_volume,
                AVG(CASE WHEN status = "completed" THEN price_per_unit ELSE NULL END) as average_price
            ')
            ->first();

        $this->marketStats = [
            'total_offers' => $stats->total_offers ?? 0,
            'active_offers' => $stats->active_offers ?? 0,
            'completed_offers' => $stats->completed_offers ?? 0,
            'cancelled_offers' => $stats->cancelled_offers ?? 0,
            'expired_offers' => $stats->expired_offers ?? 0,
            'total_volume' => $stats->total_volume ?? 0,
            'average_price' => $stats->average_price ?? 0,
            'most_traded_resource' => $this->getMostTradedResource(),
        ];
    }

    public function calculateTradingHistory()
    {
        $this->tradingHistory = MarketOffer::where('world_id', $this->village->world_id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function calculatePriceHistory()
    {
        $this->priceHistory = [];
        foreach ($this->resourceTypes as $resource) {
            $this->priceHistory[$resource] = MarketOffer::where('world_id', $this->village->world_id)
                ->where('resource_type', $resource)
                ->where('status', 'completed')
                ->orderBy('completed_at', 'desc')
                ->limit(20)
                ->pluck('price_per_unit')
                ->toArray();
        }
    }

    public function calculateOfferStats()
    {
        // Use single query with selectRaw to get all offer stats at once
        $stats = MarketOffer::where('seller_id', $this->village->player_id)
            ->selectRaw('
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as my_active_offers,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as my_completed_offers,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as my_cancelled_offers,
                SUM(CASE WHEN status = "completed" THEN total_price ELSE 0 END) as my_total_volume,
                AVG(CASE WHEN status = "completed" THEN price_per_unit ELSE NULL END) as my_average_price
            ')
            ->first();

        $this->offerStats = [
            'my_active_offers' => $stats->my_active_offers ?? 0,
            'my_completed_offers' => $stats->my_completed_offers ?? 0,
            'my_cancelled_offers' => $stats->my_cancelled_offers ?? 0,
            'my_total_volume' => $stats->my_total_volume ?? 0,
            'my_average_price' => $stats->my_average_price ?? 0,
        ];
    }

    public function calculateTradingVolume()
    {
        // Use single query with selectRaw to get all trading volumes at once
        $volumes = MarketOffer::where('world_id', $this->village->world_id)
            ->where('status', 'completed')
            ->selectRaw('
                SUM(CASE WHEN resource_type = "wood" THEN quantity ELSE 0 END) as wood_volume,
                SUM(CASE WHEN resource_type = "clay" THEN quantity ELSE 0 END) as clay_volume,
                SUM(CASE WHEN resource_type = "iron" THEN quantity ELSE 0 END) as iron_volume,
                SUM(CASE WHEN resource_type = "crop" THEN quantity ELSE 0 END) as crop_volume
            ')
            ->first();

        $this->tradingVolume = [
            'wood' => $volumes->wood_volume ?? 0,
            'clay' => $volumes->clay_volume ?? 0,
            'iron' => $volumes->iron_volume ?? 0,
            'crop' => $volumes->crop_volume ?? 0,
        ];
    }

    public function calculateAveragePrices()
    {
        // Use single query with selectRaw to get all average prices at once
        $prices = MarketOffer::where('world_id', $this->village->world_id)
            ->where('status', 'completed')
            ->selectRaw('
                AVG(CASE WHEN resource_type = "wood" THEN price_per_unit ELSE NULL END) as wood_avg,
                AVG(CASE WHEN resource_type = "clay" THEN price_per_unit ELSE NULL END) as clay_avg,
                AVG(CASE WHEN resource_type = "iron" THEN price_per_unit ELSE NULL END) as iron_avg,
                AVG(CASE WHEN resource_type = "crop" THEN price_per_unit ELSE NULL END) as crop_avg
            ')
            ->first();

        $this->averagePrices = [
            'wood' => $prices->wood_avg ?? 0,
            'clay' => $prices->clay_avg ?? 0,
            'iron' => $prices->iron_avg ?? 0,
            'crop' => $prices->crop_avg ?? 0,
        ];
    }

    public function calculateMarketTrends()
    {
        $this->marketTrends = [];
        foreach ($this->resourceTypes as $resource) {
            $recentOffers = MarketOffer::where('world_id', $this->village->world_id)
                ->where('resource_type', $resource)
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->subDays(7))
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->pluck('price_per_unit')
                ->toArray();

            if (count($recentOffers) >= 2) {
                $trend = $recentOffers[0] > $recentOffers[count($recentOffers) - 1] ? 'up' : 'down';
                $this->marketTrends[$resource] = $trend;
            } else {
                $this->marketTrends[$resource] = 'stable';
            }
        }
    }

    public function getMostTradedResource()
    {
        $resourceCounts = [];
        foreach ($this->resourceTypes as $resource) {
            $resourceCounts[$resource] = MarketOffer::where('world_id', $this->village->world_id)
                ->where('resource_type', $resource)
                ->where('status', 'completed')
                ->count();
        }

        return array_search(max($resourceCounts), $resourceCounts);
    }

    public function getResourceIcon($resource)
    {
        $icons = [
            'wood' => '🪵',
            'clay' => '🏺',
            'iron' => '⛏️',
            'crop' => '🌾',
        ];

        return $icons[$resource] ?? '📦';
    }

    public function getResourceColor($resource)
    {
        $colors = [
            'wood' => 'brown',
            'clay' => 'orange',
            'iron' => 'gray',
            'crop' => 'green',
        ];

        return $colors[$resource] ?? 'blue';
    }

    public function getOfferStatus($offer)
    {
        if ($offer['status'] === 'active') {
            return 'Active';
        }

        if ($offer['status'] === 'completed') {
            return 'Completed';
        }

        if ($offer['status'] === 'cancelled') {
            return 'Cancelled';
        }

        if ($offer['status'] === 'expired') {
            return 'Expired';
        }

        return 'Unknown';
    }

    public function getOfferColor($offer)
    {
        if ($offer['status'] === 'active') {
            return 'green';
        }

        if ($offer['status'] === 'completed') {
            return 'blue';
        }

        if ($offer['status'] === 'cancelled') {
            return 'red';
        }

        if ($offer['status'] === 'expired') {
            return 'gray';
        }

        return 'yellow';
    }

    public function getTimeRemaining($offer)
    {
        if ($offer['status'] !== 'active') {
            return 'N/A';
        }

        $expiresAt = $offer['expires_at'];
        $now = now();

        if ($now->gt($expiresAt)) {
            return 'Expired';
        }

        return $now->diffForHumans($expiresAt, true);
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
        $this->refreshInterval = max(5, min(60, $interval));
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
            $this->loadMarketData();
            $this->calculateMarketStats();
            $this->calculateTradingHistory();
            $this->calculatePriceHistory();
            $this->calculateOfferStats();
            $this->calculateTradingVolume();
            $this->calculateAveragePrices();
            $this->calculateMarketTrends();
        }
    }

    #[On('offerCreated')]
    public function handleOfferCreated($data)
    {
        $this->loadMarketData();
        $this->addNotification('New offer created', 'info');
    }

    #[On('offerUpdated')]
    public function handleOfferUpdated($data)
    {
        $this->loadMarketData();
        $this->addNotification('Offer updated', 'info');
    }

    #[On('offerCancelled')]
    public function handleOfferCancelled($data)
    {
        $this->loadMarketData();
        $this->addNotification('Offer cancelled', 'info');
    }

    #[On('offerExpired')]
    public function handleOfferExpired($data)
    {
        $this->loadMarketData();
        $this->addNotification('Offer expired', 'info');
    }

    #[On('tradeCompleted')]
    public function handleTradeCompleted($data)
    {
        $this->loadMarketData();
        $this->addNotification('Trade completed', 'success');
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $this->village = Village::findOrFail($villageId);
        $this->loadMarketData();
        $this->addNotification('Village selected - market data updated', 'info');
    }

    public function render()
    {
        return view('livewire.game.market-manager', [
            'village' => $this->village,
            'offers' => $this->offers,
            'myOffers' => $this->myOffers,
            'selectedOffer' => $this->selectedOffer,
            'offerTypes' => $this->offerTypes,
            'resourceTypes' => $this->resourceTypes,
            'selectedType' => $this->selectedType,
            'selectedResource' => $this->selectedResource,
            'offerQuantity' => $this->offerQuantity,
            'offerPrice' => $this->offerPrice,
            'offerDuration' => $this->offerDuration,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading,
            'realTimeUpdates' => $this->realTimeUpdates,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
            'gameSpeed' => $this->gameSpeed,
            'showDetails' => $this->showDetails,
            'selectedOfferId' => $this->selectedOfferId,
            'filterByType' => $this->filterByType,
            'filterByResource' => $this->filterByResource,
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
            'searchQuery' => $this->searchQuery,
            'showOnlyActive' => $this->showOnlyActive,
            'showOnlyMyOffers' => $this->showOnlyMyOffers,
            'showOnlyExpired' => $this->showOnlyExpired,
            'marketStats' => $this->marketStats,
            'tradingHistory' => $this->tradingHistory,
            'priceHistory' => $this->priceHistory,
            'offerStats' => $this->offerStats,
            'tradingVolume' => $this->tradingVolume,
            'averagePrices' => $this->averagePrices,
            'marketTrends' => $this->marketTrends,
        ]);
    }
}
