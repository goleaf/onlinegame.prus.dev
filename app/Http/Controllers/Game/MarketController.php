<?php

namespace App\Http\Controllers\Game;

use App\Models\Game\MarketOffer;
use App\Models\Game\Player;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Traits\FileProcessingTrait;
use LaraUtilX\Traits\ValidationHelperTrait;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\FilteringUtil;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;

/**
 * @group Market & Trading Management
 *
 * API endpoints for managing market offers, trading, and resource exchange.
 * The market system allows players to trade resources with each other.
 *
 * @authenticated
 *
 * @tag Market System
 * @tag Trading
 * @tag Resource Exchange
 */
class MarketController extends CrudController
{
    use ApiResponseTrait;
    use FileProcessingTrait;
    use ValidationHelperTrait;

    protected Model $model;
    protected RateLimiterUtil $rateLimiter;
    protected array $validationRules = [];
    protected array $searchableFields = ['offer_type', 'resource_type', 'description'];
    protected array $relationships = ['player'];
    protected int $perPage = 15;

    protected function getValidationRules(): array
    {
        return [
            'offer_type' => 'required|in:buy,sell',
            'resource_type' => 'required|in:wood,clay,iron,crop',
            'resource_amount' => 'required|integer|min:1|max:100000',
            'exchange_rate' => 'required|numeric|min:0.1|max:10.0',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function __construct(MarketOffer $marketOffer, RateLimiterUtil $rateLimiter)
    {
        $this->model = $marketOffer;
        $this->rateLimiter = $rateLimiter;
        $this->validationRules = $this->getValidationRules();
        parent::__construct($this->model);
    }

    /**
     * Get all market offers
     *
     * @authenticated
     *
     * @description Retrieve a paginated list of all active market offers.
     *
     * @queryParam page int The page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam resource_type string Filter by resource type. Example: "wood"
     * @queryParam offer_type string Filter by offer type (buy, sell). Example: "sell"
     * @queryParam min_rate float Filter by minimum exchange rate. Example: 1.5
     * @queryParam max_rate float Filter by maximum exchange rate. Example: 2.0
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "player_id": 1,
     *       "player_name": "PlayerOne",
     *       "offer_type": "sell",
     *       "resource_type": "wood",
     *       "resource_amount": 1000,
     *       "exchange_rate": 1.5,
     *       "total_amount": 1500,
     *       "status": "active",
     *       "created_at": "2023-01-01T12:00:00.000000Z"
     *     }
   *   ]
   * }
   *
   * @tag Market System
   */
    public function index(Request $request): JsonResponse
    {
        try {
            // Rate limiting for market offers
            $rateLimitKey = 'market_offers_' . ($request->ip() ?? 'unknown');
            if (!$this->rateLimiter->attempt($rateLimitKey, 100, 1)) {
                return $this->errorResponse('Too many requests. Please try again later.', 429);
            }

            $cacheKey = 'market_offers_' . md5(serialize($request->all()));
            
            $offers = CachingUtil::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
                $query = MarketOffer::with($this->relationships)
                    ->where('status', 'active');

                // Apply filters using FilteringUtil
                $filters = [];
                
                if ($request->has('resource_type')) {
                    $filters[] = ['target' => 'resource_type', 'type' => '$eq', 'value' => $request->input('resource_type')];
                }

                if ($request->has('offer_type')) {
                    $filters[] = ['target' => 'offer_type', 'type' => '$eq', 'value' => $request->input('offer_type')];
                }

                if ($request->has('min_rate')) {
                    $filters[] = ['target' => 'exchange_rate', 'type' => '$gte', 'value' => $request->input('min_rate')];
                }

                if ($request->has('max_rate')) {
                    $filters[] = ['target' => 'exchange_rate', 'type' => '$lte', 'value' => $request->input('max_rate')];
                }

                if (!empty($filters)) {
                    $query = $query->filter($filters);
                }

                // Apply search if provided
                if ($request->has('search')) {
                    $searchTerm = $request->get('search');
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('description', 'like', "%{$searchTerm}%")
                          ->orWhereHas('player', function ($playerQuery) use ($searchTerm) {
                              $playerQuery->where('name', 'like', "%{$searchTerm}%");
                          });
                    });
                }

                return $query->orderBy('created_at', 'desc')
                    ->paginate($request->input('per_page', $this->perPage));
            });

            // Add player names to the response
            $offers->getCollection()->transform(function ($offer) {
                $offer->player_name = $offer->player->name ?? 'Unknown';
                return $offer;
            });

            LoggingUtil::info('Market offers retrieved', [
                'user_id' => auth()->id(),
                'filters' => $request->all(),
                'total_offers' => $offers->total(),
            ], 'market_system');

            return $this->paginatedResponse($offers, 'Market offers retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving market offers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'market_system');

            return $this->errorResponse('Failed to retrieve market offers.', 500);
        }
    }

    /**
     * Get specific market offer
     *
     * @authenticated
   *
   * @description Retrieve detailed information about a specific market offer.
   *
   * @urlParam id int required The ID of the market offer. Example: 1
   *
   * @response 200 {
   *   "id": 1,
   *   "player_id": 1,
   *   "player_name": "PlayerOne",
   *   "offer_type": "sell",
   *   "resource_type": "wood",
   *   "resource_amount": 1000,
   *   "exchange_rate": 1.5,
   *   "total_amount": 1500,
   *   "status": "active",
   *   "description": "Selling wood for clay",
   *   "created_at": "2023-01-01T12:00:00.000000Z",
   *   "updated_at": "2023-01-01T12:00:00.000000Z"
   * }
   *
   * @response 404 {
   *   "message": "Market offer not found"
   * }
   *
   * @tag Market System
   */
    public function show(int $id): JsonResponse
    {
        try {
            $cacheKey = "market_offer_{$id}";
            
            $offer = CachingUtil::remember($cacheKey, now()->addMinutes(10), function () use ($id) {
                return MarketOffer::with($this->relationships)->findOrFail($id);
            });
            
            $offer->player_name = $offer->player->name ?? 'Unknown';

            LoggingUtil::info('Market offer retrieved', [
                'user_id' => auth()->id(),
                'offer_id' => $id,
            ], 'market_system');

            return $this->successResponse($offer, 'Market offer retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving market offer', [
                'error' => $e->getMessage(),
                'offer_id' => $id,
            ], 'market_system');

            return $this->errorResponse('Market offer not found.', 404);
        }
    }

    /**
     * Get player's market offers
     *
     * @authenticated
   *
   * @description Retrieve all market offers created by the authenticated player.
   *
   * @queryParam status string Filter by offer status (active, completed, cancelled). Example: "active"
   *
   * @response 200 {
   *   "data": [
   *     {
   *       "id": 1,
   *       "offer_type": "sell",
   *       "resource_type": "wood",
   *       "resource_amount": 1000,
   *       "exchange_rate": 1.5,
   *       "total_amount": 1500,
   *       "status": "active",
   *       "created_at": "2023-01-01T12:00:00.000000Z"
   *     }
   *   ]
   * }
   *
   * @tag Market System
   */
    public function myOffers(Request $request): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            
            $query = MarketOffer::where('player_id', $playerId);

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $offers = $query->orderBy('created_at', 'desc')->get();

            return response()->json(['data' => $offers]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve your market offers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create market offer
     *
     * @authenticated
   *
   * @description Create a new market offer for trading resources.
   *
   * @bodyParam offer_type string required The type of offer (buy, sell). Example: "sell"
   * @bodyParam resource_type string required The resource type to trade. Example: "wood"
   * @bodyParam resource_amount int required The amount of resource to trade. Example: 1000
   * @bodyParam exchange_rate float required The exchange rate. Example: 1.5
   * @bodyParam description string Optional description for the offer. Example: "Selling wood for clay"
   *
   * @response 201 {
   *   "success": true,
   *   "message": "Market offer created successfully",
   *   "offer": {
   *     "id": 1,
   *     "player_id": 1,
   *     "offer_type": "sell",
   *     "resource_type": "wood",
   *     "resource_amount": 1000,
   *     "exchange_rate": 1.5,
   *     "total_amount": 1500,
   *     "status": "active"
   *   }
   * }
   *
   * @response 422 {
   *   "message": "The given data was invalid.",
   *   "errors": {
   *     "offer_type": ["The offer type field is required."],
   *     "resource_type": ["The resource type field is required."]
   *   }
   * }
   *
   * @tag Market System
   */
    public function store(Request $request): JsonResponse
    {
        try {
            // Rate limiting for creating offers
            $rateLimitKey = 'create_market_offer_' . (auth()->id() ?? 'unknown');
            if (!$this->rateLimiter->attempt($rateLimitKey, 10, 1)) {
                return $this->errorResponse('Too many requests. Please try again later.', 429);
            }

            $validated = $this->validateRequest($request, $this->validationRules);

            $playerId = Auth::user()->player->id;
            $player = Player::findOrFail($playerId);

            // Calculate total amount based on exchange rate
            $totalAmount = $request->input('resource_amount') * $request->input('exchange_rate');

            // For sell offers, check if player has enough resources
            if ($request->input('offer_type') === 'sell') {
                $resourceType = $request->input('resource_type');
                if ($player->{$resourceType} < $request->input('resource_amount')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient resources for this offer'
                    ], 400);
                }
            }

            DB::beginTransaction();

            // If it's a sell offer, reserve the resources
            if ($request->input('offer_type') === 'sell') {
                $player->decrement($request->input('resource_type'), $request->input('resource_amount'));
            }

            $offer = MarketOffer::create([
                'player_id' => $playerId,
                'offer_type' => $request->input('offer_type'),
                'resource_type' => $request->input('resource_type'),
                'resource_amount' => $request->input('resource_amount'),
                'exchange_rate' => $request->input('exchange_rate'),
                'total_amount' => $totalAmount,
                'description' => $request->input('description'),
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Market offer created successfully',
                'offer' => $offer
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create market offer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept market offer
     *
     * @authenticated
   *
   * @description Accept a market offer and complete the trade.
   *
   * @urlParam id int required The ID of the market offer to accept. Example: 1
   * @bodyParam accept_amount int required The amount to accept (cannot exceed offer amount). Example: 500
   *
   * @response 200 {
   *   "success": true,
   *   "message": "Trade completed successfully",
   *   "trade_details": {
   *     "offer_id": 1,
   *     "accepted_amount": 500,
   *     "received_amount": 750,
   *     "cost": 500
   *   }
   * }
   *
   * @response 400 {
   *   "success": false,
   *   "message": "Cannot accept your own offer or insufficient resources"
   * }
   *
   * @tag Market System
   */
    public function acceptOffer(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'accept_amount' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $playerId = Auth::user()->player->id;
            $acceptAmount = $request->input('accept_amount');

            $offer = MarketOffer::with(['player'])
                ->where('status', 'active')
                ->findOrFail($id);

            // Cannot accept your own offer
            if ($offer->player_id === $playerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot accept your own offer'
                ], 400);
            }

            // Check if accept amount is valid
            if ($acceptAmount > $offer->resource_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accept amount cannot exceed offer amount'
                ], 400);
            }

            $player = Player::findOrFail($playerId);
            $offerPlayer = $offer->player;

            // Calculate trade details
            $receivedAmount = $acceptAmount * $offer->exchange_rate;

            DB::beginTransaction();

            if ($offer->offer_type === 'sell') {
                // Player is buying from the offer
                // Check if player has enough resources to pay
                $paymentResource = $this->getPaymentResource($offer->resource_type);
                if ($player->{$paymentResource} < $receivedAmount) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient resources to complete trade'
                    ], 400);
                }

                // Transfer resources
                $player->decrement($paymentResource, $receivedAmount);
                $player->increment($offer->resource_type, $acceptAmount);
                $offerPlayer->increment($paymentResource, $receivedAmount);
            } else {
                // Player is selling to the offer
                // Check if player has enough resources to sell
                if ($player->{$offer->resource_type} < $acceptAmount) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient resources to complete trade'
                    ], 400);
                }

                // Transfer resources
                $player->decrement($offer->resource_type, $acceptAmount);
                $player->increment($this->getPaymentResource($offer->resource_type), $receivedAmount);
                $offerPlayer->increment($offer->resource_type, $acceptAmount);
            }

            // Update offer
            $remainingAmount = $offer->resource_amount - $acceptAmount;
            if ($remainingAmount <= 0) {
                $offer->update(['status' => 'completed']);
            } else {
                $offer->decrement('resource_amount', $acceptAmount);
                $offer->decrement('total_amount', $receivedAmount);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Trade completed successfully',
                'trade_details' => [
                    'offer_id' => $id,
                    'accepted_amount' => $acceptAmount,
                    'received_amount' => $receivedAmount,
                    'cost' => $receivedAmount,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept offer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel market offer
     *
     * @authenticated
   *
   * @description Cancel a market offer and refund resources if applicable.
   *
   * @urlParam id int required The ID of the market offer to cancel. Example: 1
   *
   * @response 200 {
   *   "success": true,
   *   "message": "Market offer cancelled successfully"
   * }
   *
   * @response 400 {
   *   "success": false,
   *   "message": "Cannot cancel this offer"
   * }
   *
   * @tag Market System
   */
    public function cancelOffer(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            
            $offer = MarketOffer::where('player_id', $playerId)
                ->where('status', 'active')
                ->findOrFail($id);

            $player = Player::findOrFail($playerId);

            DB::beginTransaction();

            // If it's a sell offer, refund the reserved resources
            if ($offer->offer_type === 'sell') {
                $player->increment($offer->resource_type, $offer->resource_amount);
            }

            $offer->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Market offer cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel offer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get market statistics
     *
     * @authenticated
   *
   * @description Get market statistics and trends.
   *
   * @response 200 {
   *   "total_offers": 150,
   *   "active_offers": 120,
   *   "completed_offers": 25,
   *   "cancelled_offers": 5,
   *   "average_exchange_rates": {
   *     "wood": 1.2,
   *     "clay": 1.1,
   *     "iron": 1.3,
   *     "crop": 1.0
   *   },
   *   "recent_trades": [
   *     {
   *       "id": 1,
   *       "resource_type": "wood",
   *       "amount": 1000,
   *       "rate": 1.5,
   *       "created_at": "2023-01-01T12:00:00.000000Z"
   *     }
   *   ]
   * }
   *
   * @tag Market System
   */
    public function statistics(): JsonResponse
    {
        try {
            $totalOffers = MarketOffer::count();
            $activeOffers = MarketOffer::where('status', 'active')->count();
            $completedOffers = MarketOffer::where('status', 'completed')->count();
            $cancelledOffers = MarketOffer::where('status', 'cancelled')->count();

            // Calculate average exchange rates
            $averageRates = [];
            $resourceTypes = ['wood', 'clay', 'iron', 'crop'];
            
            foreach ($resourceTypes as $resourceType) {
                $avgRate = MarketOffer::where('resource_type', $resourceType)
                    ->where('status', 'active')
                    ->avg('exchange_rate');
                $averageRates[$resourceType] = round($avgRate ?? 1.0, 2);
            }

            // Get recent trades (completed offers)
            $recentTrades = MarketOffer::where('status', 'completed')
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get(['id', 'resource_type', 'resource_amount', 'exchange_rate', 'updated_at']);

            return response()->json([
                'total_offers' => $totalOffers,
                'active_offers' => $activeOffers,
                'completed_offers' => $completedOffers,
                'cancelled_offers' => $cancelledOffers,
                'average_exchange_rates' => $averageRates,
                'recent_trades' => $recentTrades,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve market statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to get payment resource for a given resource type
     */
    private function getPaymentResource(string $resourceType): string
    {
        $paymentMap = [
            'wood' => 'clay',
            'clay' => 'iron',
            'iron' => 'crop',
            'crop' => 'wood',
        ];

        return $paymentMap[$resourceType] ?? 'wood';
    }
}


        return $paymentMap[$resourceType] ?? 'wood';
    }
}
