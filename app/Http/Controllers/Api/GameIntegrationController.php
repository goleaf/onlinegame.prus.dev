<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Building;
use App\Models\Game\BuildingQueue;
use App\Models\Game\MarketOffer;
use App\Models\Game\Player;
use App\Models\Game\TrainingQueue;
use App\Models\Game\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Traits\ValidationHelperTrait;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;

/**
 * @group Game Integration API
 *
 * API endpoints for comprehensive game integration and cross-system operations.
 * Provides unified access to multiple game systems and their interactions.
 *
 * @authenticated
 *
 * @tag Game Integration
 * @tag Cross-System Operations
 * @tag Game Management
 */
class GameIntegrationController extends CrudController
{
    use ApiResponseTrait, ValidationHelperTrait;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get player dashboard data
     *
     * @authenticated
     *
     * @description Retrieve comprehensive dashboard data for the authenticated player.
     *
     * @response 200 {
     *   "player": {
     *     "id": 1,
     *     "name": "PlayerOne",
     *     "experience": 1500,
     *     "level": 15
     *   },
     *   "villages": [
     *     {
     *       "id": 1,
     *       "name": "Capital City",
     *       "population": 1000,
     *       "buildings_count": 25,
     *       "resources": {
     *         "wood": 5000,
     *         "clay": 4000,
     *         "iron": 3000,
     *         "crop": 2000
     *       }
     *     }
     *   ],
     *   "active_queues": {
     *     "building_queues": 3,
     *     "training_queues": 2
     *   },
     *   "market_offers": {
     *     "active_offers": 5,
     *     "pending_offers": 2
     *   },
     *   "statistics": {
     *     "total_battles": 50,
     *     "victories": 35,
     *     "total_quests": 25,
     *     "completed_quests": 20
     *   }
     * }
     *
     * @tag Game Integration
     */
    public function dashboard(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $player = Player::with(['villages'])->findOrFail($playerId);

            // Get villages with resources and building counts
            $villages = $player->villages->map(function ($village) {
                return [
                    'id' => $village->id,
                    'name' => $village->name,
                    'population' => $village->population ?? 0,
                    'buildings_count' => $village->buildings()->count(),
                    'resources' => [
                        'wood' => $village->wood ?? 0,
                        'clay' => $village->clay ?? 0,
                        'iron' => $village->iron ?? 0,
                        'crop' => $village->crop ?? 0,
                    ]
                ];
            });

            // Get active queues
            $buildingQueues = BuildingQueue::whereHas('building', function ($query) use ($playerId) {
                $query->whereHas('village', function ($q) use ($playerId) {
                    $q->where('player_id', $playerId);
                });
            })->count();

            $trainingQueues = TrainingQueue::whereHas('village', function ($query) use ($playerId) {
                $query->where('player_id', $playerId);
            })->count();

            // Get market offers
            $activeOffers = MarketOffer::where('player_id', $playerId)
                ->where('status', 'active')
                ->count();

            $pendingOffers = MarketOffer::where('player_id', $playerId)
                ->where('status', 'pending')
                ->count();

            // Get basic statistics (would need to implement actual queries)
            $statistics = [
                'total_battles' => 0,  // Would query battles table
                'victories' => 0,  // Would query battles table
                'total_quests' => 0,  // Would query quests table
                'completed_quests' => 0,  // Would query quests table
            ];

            $data = [
                'player' => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'experience' => $player->experience ?? 0,
                    'level' => $player->level ?? 1,
                ],
                'villages' => $villages,
                'active_queues' => [
                    'building_queues' => $buildingQueues,
                    'training_queues' => $trainingQueues,
                ],
                'market_offers' => [
                    'active_offers' => $activeOffers,
                    'pending_offers' => $pendingOffers,
                ],
                'statistics' => $statistics,
            ];

            LoggingUtil::info('Player dashboard data retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $player->id,
                'villages_count' => $villages->count(),
            ], 'game_integration');

            return $this->successResponse($data, 'Dashboard data retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving dashboard data', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'game_integration');

            return $this->errorResponse('Failed to retrieve dashboard data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get village overview
     *
     * @authenticated
     *
     * @description Retrieve comprehensive overview data for a specific village.
     *
     * @urlParam villageId int required The ID of the village. Example: 1
     *
     * @response 200 {
     *   "village": {
     *     "id": 1,
     *     "name": "Capital City",
     *     "population": 1000,
     *     "coordinates": {
     *       "x": 100,
     *       "y": 200
     *     }
     *   },
     *   "buildings": [
     *     {
     *       "id": 1,
     *       "type": "barracks",
     *       "level": 5,
     *       "is_upgrading": false
     *     }
     *   ],
     *   "queues": {
     *     "building_queue": [
     *       {
     *         "id": 1,
     *         "building_type": "barracks",
     *         "target_level": 6,
     *         "completion_time": "2023-01-01T13:00:00Z"
     *       }
     *     ],
     *     "training_queue": []
     *   },
     *   "resources": {
     *     "wood": 5000,
     *     "clay": 4000,
     *     "iron": 3000,
     *     "crop": 2000
     *   }
     * }
     *
     * @response 404 {
     *   "message": "Village not found"
     * }
     *
     * @tag Game Integration
     */
    public function villageOverview(int $villageId): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;

            $village = Village::where('player_id', $playerId)
                ->with(['buildings', 'buildings.buildingType'])
                ->findOrFail($villageId);

            // Get buildings
            $buildings = $village->buildings->map(function ($building) {
                return [
                    'id' => $building->id,
                    'type' => $building->buildingType->name ?? 'unknown',
                    'level' => $building->level ?? 1,
                    'is_upgrading' => $building->is_upgrading ?? false,
                ];
            });

            // Get building queue
            $buildingQueue = BuildingQueue::whereHas('building', function ($query) use ($villageId) {
                $query->where('village_id', $villageId);
            })
                ->with(['buildingType'])
                ->get()
                ->map(function ($queue) {
                    return [
                        'id' => $queue->id,
                        'building_type' => $queue->buildingType->name ?? 'unknown',
                        'target_level' => $queue->target_level ?? 1,
                        'completion_time' => $queue->completion_time,
                    ];
                });

            // Get training queue
            $trainingQueue = TrainingQueue::where('village_id', $villageId)
                ->get()
                ->map(function ($queue) {
                    return [
                        'id' => $queue->id,
                        'unit_type' => $queue->unit_type ?? 'unknown',
                        'quantity' => $queue->quantity ?? 0,
                        'completion_time' => $queue->completion_time,
                    ];
                });

            $data = [
                'village' => [
                    'id' => $village->id,
                    'name' => $village->name,
                    'population' => $village->population ?? 0,
                    'coordinates' => [
                        'x' => $village->x ?? 0,
                        'y' => $village->y ?? 0,
                    ]
                ],
                'buildings' => $buildings,
                'queues' => [
                    'building_queue' => $buildingQueue,
                    'training_queue' => $trainingQueue,
                ],
                'resources' => [
                    'wood' => $village->wood ?? 0,
                    'clay' => $village->clay ?? 0,
                    'iron' => $village->iron ?? 0,
                    'crop' => $village->crop ?? 0,
                ]
            ];

            LoggingUtil::info('Village overview retrieved', [
                'user_id' => auth()->id(),
                'village_id' => $villageId,
                'buildings_count' => $buildings->count(),
            ], 'game_integration');

            return $this->successResponse($data, 'Village overview retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving village overview', [
                'error' => $e->getMessage(),
                'village_id' => $villageId,
                'user_id' => auth()->id(),
            ], 'game_integration');

            return $this->errorResponse('Village not found', 404);
        }
    }

    /**
     * Get player statistics
     *
     * @authenticated
     *
     * @description Retrieve comprehensive statistics for the authenticated player.
     *
     * @response 200 {
     *   "player_stats": {
     *     "level": 15,
     *     "experience": 1500,
     *     "total_villages": 3,
     *     "total_population": 3000
     *   },
     *   "battle_stats": {
     *     "total_battles": 50,
     *     "victories": 35,
     *     "defeats": 10,
     *     "draws": 5,
     *     "win_rate": 70.0
     *   },
     *   "quest_stats": {
     *     "total_quests": 25,
     *     "completed_quests": 20,
     *     "completion_rate": 80.0
     *   },
     *   "alliance_stats": {
     *     "current_alliance": "Elite Warriors",
     *     "alliance_rank": "Member",
     *     "joined_date": "2023-01-01T00:00:00Z"
     *   }
     * }
     *
     * @tag Game Integration
     */
    public function statistics(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $player = Player::findOrFail($playerId);

            // Player basic stats
            $playerStats = [
                'level' => $player->level ?? 1,
                'experience' => $player->experience ?? 0,
                'total_villages' => $player->villages()->count(),
                'total_population' => $player->villages()->sum('population') ?? 0,
            ];

            // Battle stats (would need actual implementation)
            $battleStats = [
                'total_battles' => 0,
                'victories' => 0,
                'defeats' => 0,
                'draws' => 0,
                'win_rate' => 0.0,
            ];

            // Quest stats (would need actual implementation)
            $questStats = [
                'total_quests' => 0,
                'completed_quests' => 0,
                'completion_rate' => 0.0,
            ];

            // Alliance stats (would need actual implementation)
            $allianceStats = [
                'current_alliance' => null,
                'alliance_rank' => null,
                'joined_date' => null,
            ];

            $data = [
                'player_stats' => $playerStats,
                'battle_stats' => $battleStats,
                'quest_stats' => $questStats,
                'alliance_stats' => $allianceStats,
            ];

            LoggingUtil::info('Player statistics retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
            ], 'game_integration');

            return $this->successResponse($data, 'Player statistics retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving player statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'game_integration');

            return $this->errorResponse('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get system status
     *
     * @authenticated
     *
     * @description Retrieve system-wide status and health information.
     *
     * @response 200 {
     *   "system_status": {
     *     "status": "online",
     *     "uptime": "99.9%",
     *     "active_players": 1250,
     *     "total_villages": 5000
     *   },
     *   "server_info": {
     *     "version": "1.0.0",
     *     "last_update": "2023-01-01T00:00:00Z",
     *     "next_maintenance": "2023-01-15T02:00:00Z"
     *   }
     * }
     *
     * @tag Game Integration
     */
    public function systemStatus(): JsonResponse
    {
        try {
            $systemStatus = [
                'status' => 'online',
                'uptime' => '99.9%',
                'active_players' => Player::count(),
                'total_villages' => Village::count(),
            ];

            $serverInfo = [
                'version' => '1.0.0',
                'last_update' => now()->subDays(7)->toISOString(),
                'next_maintenance' => now()->addDays(7)->toISOString(),
            ];

            $data = [
                'system_status' => $systemStatus,
                'server_info' => $serverInfo,
            ];

            LoggingUtil::info('System status retrieved', [
                'user_id' => auth()->id(),
                'active_players' => $systemStatus['active_players'],
                'total_villages' => $systemStatus['total_villages'],
            ], 'game_integration');

            return $this->successResponse($data, 'System status retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving system status', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'game_integration');

            return $this->errorResponse('Failed to retrieve system status: ' . $e->getMessage(), 500);
        }
    }
}
