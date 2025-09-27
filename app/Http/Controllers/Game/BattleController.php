<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Battle;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Traits\GameValidationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\LoggingUtil;

/**
 * @group Battle Management
 *
 * API endpoints for managing battles, combat reports, and war statistics.
 * Battles represent combat encounters between players and their outcomes.
 *
 * @authenticated
 *
 * @tag Battle System
 * @tag Combat
 * @tag War System
 */
class BattleController extends CrudController
{
    use ApiResponseTrait;
    /**
     * Get all battles
     *
     * @authenticated
     *
     * @description Retrieve a paginated list of all battles in the system.
     *
     * @queryParam page int The page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam attacker_id int Filter by attacker ID. Example: 1
     * @queryParam defender_id int Filter by defender ID. Example: 2
     * @queryParam result string Filter by battle result (victory, defeat, draw). Example: "victory"
     * @queryParam war_id int Filter by war ID. Example: 1
     * @queryParam date_from string Filter battles from date. Example: "2023-01-01"
     * @queryParam date_to string Filter battles to date. Example: "2023-12-31"
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "attacker_id": 1,
     *       "defender_id": 2,
     *       "village_id": 5,
     *       "attacker_troops": {
     *         "legionnaires": 100,
     *         "praetorians": 50
     *       },
     *       "defender_troops": {
     *         "legionnaires": 80,
     *         "praetorians": 40
     *       },
     *       "attacker_losses": {
     *         "legionnaires": 20,
     *         "praetorians": 10
     *       },
     *       "defender_losses": {
     *         "legionnaires": 60,
     *         "praetorians": 30
     *       },
     *       "loot": {
     *         "wood": 1000,
     *         "clay": 800,
     *         "iron": 600,
     *         "crop": 400
     *       },
     *       "result": "victory",
     *       "occurred_at": "2023-01-01T12:00:00.000000Z",
     *       "created_at": "2023-01-01T12:00:00.000000Z",
     *       "updated_at": "2023-01-01T12:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 100,
     *     "last_page": 7
     *   }
     * }
     *
     * @tag Battle System
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Battle::with(['attacker', 'defender', 'village']);

            // Apply filters
            if ($request->has('attacker_id')) {
                $query->where('attacker_id', $request->input('attacker_id'));
            }

            if ($request->has('defender_id')) {
                $query->where('defender_id', $request->input('defender_id'));
            }

            if ($request->has('result')) {
                $query->where('result', $request->input('result'));
            }

            if ($request->has('war_id')) {
                $query->where('war_id', $request->input('war_id'));
            }

            if ($request->has('date_from')) {
                $query->where('occurred_at', '>=', $request->input('date_from'));
            }

            if ($request->has('date_to')) {
                $query->where('occurred_at', '<=', $request->input('date_to'));
            }

            $battles = $query->orderBy('occurred_at', 'desc')
                ->paginate($request->input('per_page', 15));

            LoggingUtil::info('Battles retrieved', [
                'user_id' => auth()->id(),
                'filters' => $request->only(['attacker_id', 'defender_id', 'result', 'war_id', 'date_from', 'date_to']),
                'count' => $battles->count(),
            ], 'battle_system');

            return $this->paginatedResponse($battles, 'Battles retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving battles', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'battle_system');

            return $this->errorResponse('Failed to retrieve battles: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get specific battle
     *
     * @authenticated
     *
     * @description Retrieve detailed information about a specific battle.
     *
     * @urlParam id int required The ID of the battle. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "attacker_id": 1,
     *   "defender_id": 2,
     *   "village_id": 5,
     *   "attacker_troops": {
     *     "legionnaires": 100,
     *     "praetorians": 50
     *   },
     *   "defender_troops": {
     *     "legionnaires": 80,
     *     "praetorians": 40
     *   },
     *   "attacker_losses": {
     *     "legionnaires": 20,
     *     "praetorians": 10
     *   },
     *   "defender_losses": {
     *     "legionnaires": 60,
     *     "praetorians": 30
     *   },
     *   "loot": {
     *     "wood": 1000,
     *     "clay": 800,
     *     "iron": 600,
     *     "crop": 400
     *   },
     *   "result": "victory",
     *   "occurred_at": "2023-01-01T12:00:00.000000Z",
     *   "attacker": {
     *     "id": 1,
     *     "name": "PlayerOne"
     *   },
     *   "defender": {
     *     "id": 2,
     *     "name": "PlayerTwo"
     *   },
     *   "village": {
     *     "id": 5,
     *     "name": "Battle Village"
     *   },
     *   "created_at": "2023-01-01T12:00:00.000000Z",
     *   "updated_at": "2023-01-01T12:00:00.000000Z"
     * }
     *
     * @response 404 {
     *   "message": "Battle not found"
     * }
     *
     * @tag Battle System
     */
    public function show(int $id): JsonResponse
    {
        try {
            $battle = Battle::with(['attacker', 'defender', 'village'])
                ->findOrFail($id);

            LoggingUtil::info('Battle details retrieved', [
                'user_id' => auth()->id(),
                'battle_id' => $id,
            ], 'battle_system');

            return $this->successResponse($battle, 'Battle details retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving battle details', [
                'error' => $e->getMessage(),
                'battle_id' => $id,
                'user_id' => auth()->id(),
            ], 'battle_system');

            return $this->errorResponse('Battle not found', 404);
        }
    }

    /**
     * Get player's battles
     *
     * @authenticated
     *
     * @description Retrieve battles where the authenticated player was either attacker or defender.
     *
     * @queryParam page int The page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam role string Filter by role (attacker, defender). Example: "attacker"
     * @queryParam result string Filter by battle result (victory, defeat, draw). Example: "victory"
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "attacker_id": 1,
     *       "defender_id": 2,
     *       "result": "victory",
     *       "occurred_at": "2023-01-01T12:00:00.000000Z",
     *       "attacker": {
     *         "id": 1,
     *         "name": "PlayerOne"
     *       },
     *       "defender": {
     *         "id": 2,
     *         "name": "PlayerTwo"
     *       }
     *     }
     *   ]
     * }
     *
     * @tag Battle System
     */
    public function myBattles(Request $request): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            
            $query = Battle::with(['attacker', 'defender', 'village'])
                ->where(function ($q) use ($playerId) {
                    $q->where('attacker_id', $playerId)
                      ->orWhere('defender_id', $playerId);
                });

            // Apply filters
            if ($request->has('role')) {
                $role = $request->input('role');
                if ($role === 'attacker') {
                    $query->where('attacker_id', $playerId);
                } elseif ($role === 'defender') {
                    $query->where('defender_id', $playerId);
                }
            }

            if ($request->has('result')) {
                $query->where('result', $request->input('result'));
            }

            $battles = $query->orderBy('occurred_at', 'desc')
                ->paginate($request->input('per_page', 15));

            LoggingUtil::info('Player battles retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'filters' => $request->only(['role', 'result']),
                'count' => $battles->count(),
            ], 'battle_system');

            return $this->paginatedResponse($battles, 'Player battles retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving player battles', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'battle_system');

            return $this->errorResponse('Failed to retrieve player battles: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get battle statistics
     *
     * @authenticated
     *
     * @description Get comprehensive battle statistics for the authenticated player.
     *
     * @response 200 {
     *   "total_battles": 50,
     *   "victories": 35,
     *   "defeats": 10,
     *   "draws": 5,
     *   "win_rate": 70.0,
     *   "total_troops_killed": 2500,
     *   "total_troops_lost": 800,
     *   "total_loot_gained": {
     *     "wood": 50000,
     *     "clay": 40000,
     *     "iron": 30000,
     *     "crop": 20000
     *   },
     *   "recent_battles": [
     *     {
     *       "id": 1,
     *       "result": "victory",
     *       "occurred_at": "2023-01-01T12:00:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @tag Battle System
     */
    public function statistics(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;

            $totalBattles = Battle::where('attacker_id', $playerId)
                ->orWhere('defender_id', $playerId)
                ->count();

            $victories = Battle::where(function ($q) use ($playerId) {
                $q->where('attacker_id', $playerId)->where('result', 'victory')
                  ->orWhere('defender_id', $playerId)->where('result', 'defeat');
            })->count();

            $defeats = Battle::where(function ($q) use ($playerId) {
                $q->where('attacker_id', $playerId)->where('result', 'defeat')
                  ->orWhere('defender_id', $playerId)->where('result', 'victory');
            })->count();

            $draws = Battle::where(function ($q) use ($playerId) {
                $q->where('attacker_id', $playerId)
                  ->orWhere('defender_id', $playerId);
            })->where('result', 'draw')->count();

            $winRate = $totalBattles > 0 ? ($victories / $totalBattles) * 100 : 0;

            // Calculate total loot gained
            $totalLoot = Battle::where('attacker_id', $playerId)
                ->where('result', 'victory')
                ->get()
                ->reduce(function ($carry, $battle) {
                    $loot = $battle->loot ?? [];
                    return [
                        'wood' => ($carry['wood'] ?? 0) + ($loot['wood'] ?? 0),
                        'clay' => ($carry['clay'] ?? 0) + ($loot['clay'] ?? 0),
                        'iron' => ($carry['iron'] ?? 0) + ($loot['iron'] ?? 0),
                        'crop' => ($carry['crop'] ?? 0) + ($loot['crop'] ?? 0),
                    ];
                }, []);

            $recentBattles = Battle::where('attacker_id', $playerId)
                ->orWhere('defender_id', $playerId)
                ->orderBy('occurred_at', 'desc')
                ->limit(5)
                ->get(['id', 'result', 'occurred_at']);

            $stats = [
                'total_battles' => $totalBattles,
                'victories' => $victories,
                'defeats' => $defeats,
                'draws' => $draws,
                'win_rate' => round($winRate, 2),
                'total_loot_gained' => $totalLoot,
                'recent_battles' => $recentBattles
            ];

            LoggingUtil::info('Battle statistics retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'total_battles' => $totalBattles,
                'win_rate' => round($winRate, 2),
            ], 'battle_system');

            return $this->successResponse($stats, 'Battle statistics retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving battle statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'battle_system');

            return $this->errorResponse('Failed to retrieve battle statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get war battles
     *
     * @authenticated
     *
     * @description Retrieve all battles for a specific war.
     *
     * @urlParam warId int required The ID of the war. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "attacker_id": 1,
     *       "defender_id": 2,
     *       "result": "victory",
     *       "occurred_at": "2023-01-01T12:00:00.000000Z",
     *       "attacker": {
     *         "id": 1,
     *         "name": "PlayerOne"
     *       },
     *       "defender": {
     *         "id": 2,
     *         "name": "PlayerTwo"
     *       }
     *     }
     *   ]
     * }
     *
     * @tag Battle System
     */
    public function warBattles(int $warId): JsonResponse
    {
        try {
            $battles = Battle::with(['attacker', 'defender', 'village'])
                ->where('war_id', $warId)
                ->orderBy('occurred_at', 'desc')
                ->get();

            LoggingUtil::info('War battles retrieved', [
                'user_id' => auth()->id(),
                'war_id' => $warId,
                'count' => $battles->count(),
            ], 'battle_system');

            return $this->successResponse($battles, 'War battles retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving war battles', [
                'error' => $e->getMessage(),
                'war_id' => $warId,
                'user_id' => auth()->id(),
            ], 'battle_system');

            return $this->errorResponse('Failed to retrieve war battles: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create battle report
     *
     * @authenticated
     *
     * @description Create a new battle report (typically done by the game system).
     *
     * @bodyParam attacker_id int required The ID of the attacking player. Example: 1
     * @bodyParam defender_id int required The ID of the defending player. Example: 2
     * @bodyParam village_id int required The ID of the attacked village. Example: 5
     * @bodyParam attacker_troops object required Troops sent by attacker. Example: {"legionnaires": 100, "praetorians": 50}
     * @bodyParam defender_troops object required Troops defending. Example: {"legionnaires": 80, "praetorians": 40}
     * @bodyParam attacker_losses object Troops lost by attacker. Example: {"legionnaires": 20, "praetorians": 10}
     * @bodyParam defender_losses object Troops lost by defender. Example: {"legionnaires": 60, "praetorians": 30}
     * @bodyParam loot object Resources looted. Example: {"wood": 1000, "clay": 800}
     * @bodyParam result string required Battle result (victory, defeat, draw). Example: "victory"
     * @bodyParam war_id int The ID of the war this battle belongs to. Example: 1
     *
     * @response 201 {
     *   "success": true,
     *   "battle": {
     *     "id": 1,
     *     "attacker_id": 1,
     *     "defender_id": 2,
     *     "village_id": 5,
     *     "result": "victory",
     *     "occurred_at": "2023-01-01T12:00:00.000000Z",
     *     "created_at": "2023-01-01T12:00:00.000000Z",
     *     "updated_at": "2023-01-01T12:00:00.000000Z"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "attacker_id": ["The attacker id field is required."],
     *     "defender_id": ["The defender id field is required."]
     *   }
     * }
     *
     * @tag Battle System
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'attacker_id' => 'required|exists:players,id',
                'defender_id' => 'required|exists:players,id',
                'village_id' => 'required|exists:villages,id',
                'attacker_troops' => 'required|array',
                'defender_troops' => 'required|array',
                'attacker_losses' => 'nullable|array',
                'defender_losses' => 'nullable|array',
                'loot' => 'nullable|array',
                'result' => 'required|in:victory,defeat,draw',
                'war_id' => 'nullable|exists:alliance_wars,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $battle = Battle::create([
                'attacker_id' => $request->input('attacker_id'),
                'defender_id' => $request->input('defender_id'),
                'village_id' => $request->input('village_id'),
                'attacker_troops' => $request->input('attacker_troops'),
                'defender_troops' => $request->input('defender_troops'),
                'attacker_losses' => $request->input('attacker_losses', []),
                'defender_losses' => $request->input('defender_losses', []),
                'loot' => $request->input('loot', []),
                'result' => $request->input('result'),
                'war_id' => $request->input('war_id'),
                'occurred_at' => now(),
            ]);

            LoggingUtil::info('Battle report created', [
                'user_id' => auth()->id(),
                'battle_id' => $battle->id,
                'attacker_id' => $battle->attacker_id,
                'defender_id' => $battle->defender_id,
                'result' => $battle->result,
            ], 'battle_system');

            return $this->successResponse($battle, 'Battle report created successfully.', 201);

        } catch (\Exception $e) {
            LoggingUtil::error('Error creating battle report', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
            ], 'battle_system');

            return $this->errorResponse('Failed to create battle report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get battle leaderboard
     *
     * @authenticated
     *
     * @description Get top players by battle performance.
     *
     * @queryParam limit int Number of players to return. Example: 10
     * @queryParam metric string Sort metric (victories, win_rate, total_battles). Example: "victories"
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "player_id": 1,
     *       "player_name": "TopFighter",
     *       "total_battles": 100,
     *       "victories": 85,
     *       "defeats": 10,
     *       "draws": 5,
     *       "win_rate": 85.0,
     *       "total_loot": 500000
     *     }
     *   ]
     * }
     *
     * @tag Battle System
     */
    public function leaderboard(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $metric = $request->input('metric', 'victories');

            $query = DB::table('battles')
                ->select([
                    'attacker_id as player_id',
                    'result'
                ])
                ->union(
                    DB::table('battles')
                        ->select([
                            'defender_id as player_id',
                            'result'
                        ])
                );

            $subQuery = DB::table(DB::raw("({$query->toSql()}) as all_battles"))
                ->join('players', 'players.id', '=', 'all_battles.player_id')
                ->select([
                    'players.id as player_id',
                    'players.name as player_name',
                    DB::raw('COUNT(*) as total_battles'),
                    DB::raw('SUM(CASE WHEN (all_battles.player_id = battles.attacker_id AND battles.result = "victory") OR (all_battles.player_id = battles.defender_id AND battles.result = "defeat") THEN 1 ELSE 0 END) as victories'),
                    DB::raw('SUM(CASE WHEN (all_battles.player_id = battles.attacker_id AND battles.result = "defeat") OR (all_battles.player_id = battles.defender_id AND battles.result = "victory") THEN 1 ELSE 0 END) as defeats'),
                    DB::raw('SUM(CASE WHEN battles.result = "draw" THEN 1 ELSE 0 END) as draws')
                ])
                ->groupBy('players.id', 'players.name');

            $orderBy = match($metric) {
                'win_rate' => DB::raw('(victories / total_battles) DESC'),
                'total_battles' => 'total_battles DESC',
                default => 'victories DESC'
            };

            $leaderboard = $subQuery->orderByRaw($orderBy)
                ->limit($limit)
                ->get();

            // Calculate win rates
            $leaderboard = $leaderboard->map(function ($player) {
                $player->win_rate = $player->total_battles > 0 
                    ? round(($player->victories / $player->total_battles) * 100, 2)
                    : 0;
                return $player;
            });

            return response()->json(['data' => $leaderboard]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve battle leaderboard: ' . $e->getMessage()
            ], 500);
        }
    }
}

                    DB::table('battles')
                        ->select([
                            'defender_id as player_id',
                            'result'
                        ])
                );

            $subQuery = DB::table(DB::raw("({$query->toSql()}) as all_battles"))
                ->join('players', 'players.id', '=', 'all_battles.player_id')
                ->select([
                    'players.id as player_id',
                    'players.name as player_name',
                    DB::raw('COUNT(*) as total_battles'),
                    DB::raw('SUM(CASE WHEN (all_battles.player_id = battles.attacker_id AND battles.result = "victory") OR (all_battles.player_id = battles.defender_id AND battles.result = "defeat") THEN 1 ELSE 0 END) as victories'),
                    DB::raw('SUM(CASE WHEN (all_battles.player_id = battles.attacker_id AND battles.result = "defeat") OR (all_battles.player_id = battles.defender_id AND battles.result = "victory") THEN 1 ELSE 0 END) as defeats'),
                    DB::raw('SUM(CASE WHEN battles.result = "draw" THEN 1 ELSE 0 END) as draws')
                ])
                ->groupBy('players.id', 'players.name');

            $orderBy = match($metric) {
                'win_rate' => DB::raw('(victories / total_battles) DESC'),
                'total_battles' => 'total_battles DESC',
                default => 'victories DESC'
            };

            $leaderboard = $subQuery->orderByRaw($orderBy)
                ->limit($limit)
                ->get();

            // Calculate win rates
            $leaderboard = $leaderboard->map(function ($player) {
                $player->win_rate = $player->total_battles > 0 
                    ? round(($player->victories / $player->total_battles) * 100, 2)
                    : 0;
                return $player;
            });

            return response()->json(['data' => $leaderboard]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve battle leaderboard: ' . $e->getMessage()
            ], 500);
        }
    }
}
