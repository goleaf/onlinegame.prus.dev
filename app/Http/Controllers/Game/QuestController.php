<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Quest;
use App\Models\Game\PlayerQuest;
use App\Models\Game\Achievement;
use App\Models\Game\PlayerAchievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @group Quest & Achievement Management
 *
 * API endpoints for managing quests, player quest progress, and achievements.
 * Quests provide objectives for players to complete, while achievements track milestones.
 *
 * @authenticated
 *
 * @tag Quest System
 * @tag Achievement System
 * @tag Player Progression
 */
class QuestController extends Controller
{
    /**
     * Get all available quests
     *
     * @authenticated
     *
     * @description Retrieve a paginated list of all available quests.
     *
     * @queryParam page int The page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam type string Filter by quest type. Example: "daily"
     * @queryParam difficulty string Filter by difficulty (easy, medium, hard, epic). Example: "medium"
     * @queryParam category string Filter by category. Example: "building"
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Build Your First Village",
     *       "description": "Construct your first village building",
     *       "type": "tutorial",
     *       "difficulty": "easy",
     *       "category": "building",
     *       "requirements": {
     *         "build_building": {
     *           "type": "barracks",
     *           "count": 1
     *         }
     *       },
     *       "rewards": {
     *         "experience": 100,
     *         "resources": {
     *           "wood": 500,
     *           "clay": 500
     *         }
     *       },
     *       "is_active": true,
     *       "created_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @tag Quest System
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Quest::where('is_active', true);

            // Apply filters
            if ($request->has('type')) {
                $query->where('type', $request->input('type'));
            }

            if ($request->has('difficulty')) {
                $query->where('difficulty', $request->input('difficulty'));
            }

            if ($request->has('category')) {
                $query->where('category', $request->input('category'));
            }

            $quests = $query->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return response()->json($quests);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quests: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific quest
     *
     * @authenticated
     *
     * @description Retrieve detailed information about a specific quest.
     *
     * @urlParam id int required The ID of the quest. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "title": "Build Your First Village",
     *   "description": "Construct your first village building",
     *   "type": "tutorial",
     *   "difficulty": "easy",
     *   "category": "building",
     *   "requirements": {
     *     "build_building": {
     *       "type": "barracks",
     *       "count": 1
     *     }
     *   },
     *   "rewards": {
     *     "experience": 100,
     *     "resources": {
     *       "wood": 500,
     *       "clay": 500
     *     }
     *   },
     *   "progress": {
     *     "current": 0,
     *     "required": 1,
     *     "percentage": 0
     *   },
     *   "status": "available",
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     *   }
     *
     * @response 404 {
     *   "message": "Quest not found"
     * }
     *
     * @tag Quest System
     */
    public function show(int $id): JsonResponse
    {
        try {
            $quest = Quest::findOrFail($id);
            $playerId = Auth::user()->player->id;

            // Get player's progress for this quest
            $playerQuest = PlayerQuest::where('player_id', $playerId)
                ->where('quest_id', $id)
                ->first();

            $questData = $quest->toArray();

            if ($playerQuest) {
                $questData['progress'] = [
                    'current' => $playerQuest->progress,
                    'required' => $quest->requirements['count'] ?? 1,
                    'percentage' => $quest->requirements['count'] > 0 
                        ? round(($playerQuest->progress / $quest->requirements['count']) * 100, 2)
                        : 0
                ];
                $questData['status'] = $playerQuest->status;
            } else {
                $questData['progress'] = [
                    'current' => 0,
                    'required' => $quest->requirements['count'] ?? 1,
                    'percentage' => 0
                ];
                $questData['status'] = 'available';
            }

            return response()->json($questData);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Quest not found'
            ], 404);
        }
    }

    /**
     * Get player's quests
     *
     * @authenticated
     *
     * @description Retrieve all quests for the authenticated player.
     *
     * @queryParam status string Filter by status (available, in_progress, completed, failed). Example: "in_progress"
     * @queryParam type string Filter by quest type. Example: "daily"
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "quest_id": 1,
     *       "status": "in_progress",
     *       "progress": 50,
     *       "started_at": "2023-01-01T00:00:00.000000Z",
     *       "completed_at": null,
     *       "quest": {
     *         "id": 1,
     *         "title": "Build Your First Village",
     *         "description": "Construct your first village building",
     *         "type": "tutorial",
     *         "difficulty": "easy",
     *         "requirements": {
     *           "build_building": {
     *             "type": "barracks",
     *             "count": 1
     *           }
     *         },
     *         "rewards": {
     *           "experience": 100,
     *           "resources": {
     *             "wood": 500,
     *             "clay": 500
     *           }
     *         }
     *       }
     *     }
     *   ]
     * }
     *
     * @tag Quest System
     */
    public function myQuests(Request $request): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;

            $query = PlayerQuest::with(['quest'])
                ->where('player_id', $playerId);

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('type')) {
                $query->whereHas('quest', function ($q) use ($request) {
                    $q->where('type', $request->input('type'));
                });
            }

            $playerQuests = $query->orderBy('started_at', 'desc')
                ->get();

            return response()->json(['data' => $playerQuests]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve player quests: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a quest
     *
     * @authenticated
     *
     * @description Start a new quest for the authenticated player.
     *
     * @urlParam id int required The ID of the quest to start. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Quest started successfully",
     *   "player_quest": {
     *     "id": 1,
     *     "player_id": 1,
     *     "quest_id": 1,
     *     "status": "in_progress",
     *     "progress": 0,
     *     "started_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Quest is not available or already started"
     * }
     *
     * @tag Quest System
     */
    public function start(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $quest = Quest::findOrFail($id);

            if (!$quest->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quest is not available'
                ], 400);
            }

            // Check if player already has this quest
            $existingQuest = PlayerQuest::where('player_id', $playerId)
                ->where('quest_id', $id)
                ->first();

            if ($existingQuest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quest is already started or completed'
                ], 400);
            }

            $playerQuest = PlayerQuest::create([
                'player_id' => $playerId,
                'quest_id' => $id,
                'status' => 'in_progress',
                'progress' => 0,
                'started_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quest started successfully',
                'player_quest' => $playerQuest
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start quest: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a quest
     *
     * @authenticated
     *
     * @description Mark a quest as completed and grant rewards.
     *
     * @urlParam id int required The ID of the quest to complete. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Quest completed successfully",
     *   "rewards": {
     *     "experience": 100,
     *     "resources": {
     *       "wood": 500,
     *       "clay": 500
     *     }
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Quest is not ready to be completed"
     * }
     *
     * @tag Quest System
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $quest = Quest::findOrFail($id);

            $playerQuest = PlayerQuest::where('player_id', $playerId)
                ->where('quest_id', $id)
                ->first();

            if (!$playerQuest || $playerQuest->status !== 'in_progress') {
                return response()->json([
                    'success' => false,
                    'message' => 'Quest is not in progress'
                ], 400);
            }

            // Check if quest requirements are met
            $requiredProgress = $quest->requirements['count'] ?? 1;
            if ($playerQuest->progress < $requiredProgress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quest requirements not met'
                ], 400);
            }

            DB::beginTransaction();

            // Update quest status
            $playerQuest->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Grant rewards
            $rewards = $quest->rewards ?? [];
            
            // Add experience
            if (isset($rewards['experience'])) {
                $player = Auth::user()->player;
                $player->increment('experience', $rewards['experience']);
            }

            // Add resources (would need to implement resource addition logic)
            if (isset($rewards['resources'])) {
                // Implementation would depend on your resource system
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quest completed successfully',
                'rewards' => $rewards
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete quest: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get player achievements
     *
     * @authenticated
     *
     * @description Retrieve all achievements for the authenticated player.
     *
     * @queryParam category string Filter by achievement category. Example: "battle"
     * @queryParam rarity string Filter by rarity (common, uncommon, rare, epic, legendary). Example: "rare"
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "player_id": 1,
     *       "achievement_id": 1,
     *       "unlocked_at": "2023-01-01T00:00:00.000000Z",
     *       "achievement": {
     *         "id": 1,
     *         "name": "First Victory",
     *         "description": "Win your first battle",
     *         "category": "battle",
     *         "rarity": "common",
     *         "icon": "sword",
     *         "points": 10
     *       }
     *     }
     *   ]
     * }
     *
     * @tag Achievement System
     */
    public function achievements(Request $request): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;

            $query = PlayerAchievement::with(['achievement'])
                ->where('player_id', $playerId);

            if ($request->has('category')) {
                $query->whereHas('achievement', function ($q) use ($request) {
                    $q->where('category', $request->input('category'));
                });
            }

            if ($request->has('rarity')) {
                $query->whereHas('achievement', function ($q) use ($request) {
                    $q->where('rarity', $request->input('rarity'));
                });
            }

            $achievements = $query->orderBy('unlocked_at', 'desc')
                ->get();

            return response()->json(['data' => $achievements]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve achievements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get achievement leaderboard
     *
     * @authenticated
     *
     * @description Get top players by achievement points.
     *
     * @queryParam limit int Number of players to return. Example: 10
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "player_id": 1,
     *       "player_name": "Achiever",
     *       "total_achievements": 25,
     *       "total_points": 500,
     *       "rare_achievements": 5,
     *       "epic_achievements": 2,
     *       "legendary_achievements": 1
     *     }
     *   ]
     * }
     *
     * @tag Achievement System
     */
    public function achievementLeaderboard(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);

            $leaderboard = DB::table('player_achievements')
                ->join('players', 'players.id', '=', 'player_achievements.player_id')
                ->join('achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
                ->select([
                    'players.id as player_id',
                    'players.name as player_name',
                    DB::raw('COUNT(*) as total_achievements'),
                    DB::raw('SUM(achievements.points) as total_points'),
                    DB::raw('SUM(CASE WHEN achievements.rarity = "rare" THEN 1 ELSE 0 END) as rare_achievements'),
                    DB::raw('SUM(CASE WHEN achievements.rarity = "epic" THEN 1 ELSE 0 END) as epic_achievements'),
                    DB::raw('SUM(CASE WHEN achievements.rarity = "legendary" THEN 1 ELSE 0 END) as legendary_achievements')
                ])
                ->groupBy('players.id', 'players.name')
                ->orderBy('total_points', 'desc')
                ->limit($limit)
                ->get();

            return response()->json(['data' => $leaderboard]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve achievement leaderboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quest statistics
     *
     * @authenticated
     *
     * @description Get comprehensive quest and achievement statistics for the authenticated player.
     *
     * @response 200 {
     *   "quests": {
     *     "total_started": 50,
     *     "total_completed": 35,
     *     "completion_rate": 70.0,
     *     "active_quests": 5,
     *     "total_experience_gained": 2500
     *   },
     *   "achievements": {
     *     "total_unlocked": 25,
     *     "total_points": 500,
     *     "by_rarity": {
     *       "common": 15,
     *       "uncommon": 7,
     *       "rare": 2,
     *       "epic": 1,
     *       "legendary": 0
     *     }
     *   }
     * }
     *
     * @tag Quest System
     */
    public function statistics(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;

            // Quest statistics
            $totalStarted = PlayerQuest::where('player_id', $playerId)->count();
            $totalCompleted = PlayerQuest::where('player_id', $playerId)
                ->where('status', 'completed')
                ->count();
            $activeQuests = PlayerQuest::where('player_id', $playerId)
                ->where('status', 'in_progress')
                ->count();

            $completionRate = $totalStarted > 0 ? ($totalCompleted / $totalStarted) * 100 : 0;

            // Achievement statistics
            $totalAchievements = PlayerAchievement::where('player_id', $playerId)->count();
            $totalPoints = PlayerAchievement::where('player_id', $playerId)
                ->join('achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
                ->sum('achievements.points');

            $achievementsByRarity = PlayerAchievement::where('player_id', $playerId)
                ->join('achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
                ->select('achievements.rarity', DB::raw('COUNT(*) as count'))
                ->groupBy('achievements.rarity')
                ->pluck('count', 'rarity')
                ->toArray();

            return response()->json([
                'quests' => [
                    'total_started' => $totalStarted,
                    'total_completed' => $totalCompleted,
                    'completion_rate' => round($completionRate, 2),
                    'active_quests' => $activeQuests,
                    'total_experience_gained' => 0 // Would need to calculate from quest rewards
                ],
                'achievements' => [
                    'total_unlocked' => $totalAchievements,
                    'total_points' => $totalPoints,
                    'by_rarity' => $achievementsByRarity
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
