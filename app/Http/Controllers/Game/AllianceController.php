<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Alliance;
use App\Models\Game\AllianceMember;
use App\Models\Game\AllianceWar;
use App\Models\Game\AllianceDiplomacy;
use App\Models\Game\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @group Alliance Management
 *
 * API endpoints for managing alliances, memberships, diplomacy, and wars.
 * Alliances are player groups that can cooperate, engage in diplomacy, and wage war.
 *
 * @authenticated
 *
 * @tag Alliance System
 * @tag Diplomacy
 * @tag War System
 */
class AllianceController extends Controller
{
    /**
     * Get all alliances
     *
     * @authenticated
     *
     * @description Retrieve a paginated list of all alliances in the system.
     *
     * @queryParam page int The page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam search string Search alliances by name or tag. Example: "Knights"
     * @queryParam sort_by string Sort field (name, members_count, points, created_at). Example: "members_count"
     * @queryParam sort_order string Sort order (asc, desc). Example: "desc"
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Knights of the Round Table",
     *       "tag": "[KRT]",
     *       "description": "Honor and chivalry above all",
     *       "leader_id": 5,
     *       "members_count": 25,
     *       "points": 150000,
     *       "rank": 1,
     *       "created_at": "2023-01-01T00:00:00.000000Z",
     *       "updated_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 50,
     *     "last_page": 4
     *   }
     * }
     *
     * @tag Alliance System
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Alliance::with(['leader']);

            // Search functionality
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('tag', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'points');
            $sortOrder = $request->input('sort_order', 'desc');
            
            if (in_array($sortBy, ['name', 'tag', 'points', 'members_count', 'created_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $alliances = $query->paginate($request->input('per_page', 15));

            return response()->json($alliances);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alliances: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific alliance
     *
     * @authenticated
     *
     * @description Retrieve detailed information about a specific alliance.
     *
     * @urlParam id int required The ID of the alliance. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "name": "Knights of the Round Table",
     *   "tag": "[KRT]",
     *   "description": "Honor and chivalry above all",
     *   "leader_id": 5,
     *   "members_count": 25,
     *   "points": 150000,
     *   "rank": 1,
     *   "leader": {
     *     "id": 5,
     *     "name": "King Arthur"
     *   },
     *   "members": [
     *     {
     *       "id": 1,
     *       "player_id": 5,
     *       "role": "leader",
     *       "joined_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ],
     *   "created_at": "2023-01-01T00:00:00.000000Z",
     *   "updated_at": "2023-01-01T00:00:00.000000Z"
     * }
     *
     * @response 404 {
     *   "message": "Alliance not found"
     * }
     *
     * @tag Alliance System
     */
    public function show(int $id): JsonResponse
    {
        try {
            $alliance = Alliance::with(['leader', 'members.player'])
                ->findOrFail($id);

            return response()->json($alliance);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Alliance not found'
            ], 404);
        }
    }

    /**
     * Create new alliance
     *
     * @authenticated
     *
     * @description Create a new alliance. The creator becomes the leader.
     *
     * @bodyParam name string required The name of the alliance. Example: "Knights of the Round Table"
     * @bodyParam tag string required The alliance tag (short identifier). Example: "[KRT]"
     * @bodyParam description string The description of the alliance. Example: "Honor and chivalry above all"
     *
     * @response 201 {
     *   "success": true,
     *   "alliance": {
     *     "id": 2,
     *     "name": "Knights of the Round Table",
     *     "tag": "[KRT]",
     *     "description": "Honor and chivalry above all",
     *     "leader_id": 5,
     *     "members_count": 1,
     *     "points": 0,
     *     "created_at": "2023-01-01T00:00:00.000000Z",
     *     "updated_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "name": ["The name field is required."],
     *     "tag": ["The tag field is required."]
     *   }
     * }
     *
     * @tag Alliance System
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:alliances,name',
                'tag' => 'required|string|max:10|unique:alliances,tag',
                'description' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $player = Auth::user()->player;

            // Check if player is already in an alliance
            if ($player->alliance_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player is already in an alliance'
                ], 400);
            }

            DB::beginTransaction();

            $alliance = Alliance::create([
                'name' => $request->input('name'),
                'tag' => $request->input('tag'),
                'description' => $request->input('description'),
                'leader_id' => $player->id,
                'members_count' => 1,
                'points' => $player->points,
            ]);

            // Add creator as leader
            AllianceMember::create([
                'alliance_id' => $alliance->id,
                'player_id' => $player->id,
                'role' => 'leader',
                'joined_at' => now(),
            ]);

            // Update player's alliance
            $player->update(['alliance_id' => $alliance->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'alliance' => $alliance->fresh()
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create alliance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Join alliance
     *
     * @authenticated
     *
     * @description Request to join an alliance.
     *
     * @urlParam id int required The ID of the alliance to join. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Successfully joined the alliance"
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Player is already in an alliance"
     * }
     *
     * @tag Alliance System
     */
    public function join(int $id): JsonResponse
    {
        try {
            $player = Auth::user()->player;

            if ($player->alliance_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player is already in an alliance'
                ], 400);
            }

            $alliance = Alliance::findOrFail($id);

            // Check if alliance has space
            if ($alliance->members_count >= 50) { // Assuming max 50 members
                return response()->json([
                    'success' => false,
                    'message' => 'Alliance is full'
                ], 400);
            }

            DB::beginTransaction();

            // Add member
            AllianceMember::create([
                'alliance_id' => $alliance->id,
                'player_id' => $player->id,
                'role' => 'member',
                'joined_at' => now(),
            ]);

            // Update player and alliance
            $player->update(['alliance_id' => $alliance->id]);
            $alliance->increment('members_count');
            $alliance->increment('points', $player->points);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the alliance'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to join alliance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Leave alliance
     *
     * @authenticated
     *
     * @description Leave the current alliance.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Successfully left the alliance"
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Player is not in an alliance"
     * }
     *
     * @tag Alliance System
     */
    public function leave(): JsonResponse
    {
        try {
            $player = Auth::user()->player;

            if (!$player->alliance_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player is not in an alliance'
                ], 400);
            }

            $alliance = Alliance::findOrFail($player->alliance_id);

            // Check if player is the leader
            if ($alliance->leader_id === $player->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leader cannot leave alliance. Transfer leadership first or disband the alliance.'
                ], 400);
            }

            DB::beginTransaction();

            // Remove member
            AllianceMember::where('alliance_id', $alliance->id)
                ->where('player_id', $player->id)
                ->delete();

            // Update player and alliance
            $player->update(['alliance_id' => null]);
            $alliance->decrement('members_count');
            $alliance->decrement('points', $player->points);

            // Disband alliance if no members left
            if ($alliance->members_count <= 0) {
                $alliance->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully left the alliance'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to leave alliance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get alliance members
     *
     * @authenticated
     *
     * @description Get all members of a specific alliance.
     *
     * @urlParam id int required The ID of the alliance. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "alliance_id": 1,
     *       "player_id": 5,
     *       "role": "leader",
     *       "joined_at": "2023-01-01T00:00:00.000000Z",
     *       "player": {
     *         "id": 5,
     *         "name": "King Arthur",
     *         "points": 15000,
     *         "villages_count": 5
     *       }
     *     }
     *   ]
     * }
     *
     * @tag Alliance System
     */
    public function members(int $id): JsonResponse
    {
        try {
            $alliance = Alliance::findOrFail($id);
            $members = AllianceMember::with('player')
                ->where('alliance_id', $id)
                ->orderBy('role')
                ->orderBy('joined_at')
                ->get();

            return response()->json(['data' => $members]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alliance members: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get alliance wars
     *
     * @authenticated
     *
     * @description Get all wars involving a specific alliance.
     *
     * @urlParam id int required The ID of the alliance. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "attacker_alliance_id": 1,
     *       "defender_alliance_id": 2,
     *       "status": "active",
     *       "started_at": "2023-01-01T00:00:00.000000Z",
     *       "attacker_alliance": {
     *         "id": 1,
     *         "name": "Knights of the Round Table"
     *       },
     *       "defender_alliance": {
     *         "id": 2,
     *         "name": "Dark Legion"
     *       }
     *     }
     *   ]
     * }
     *
     * @tag Alliance System
     */
    public function wars(int $id): JsonResponse
    {
        try {
            $wars = AllianceWar::with(['attackerAlliance', 'defenderAlliance'])
                ->where(function ($query) use ($id) {
                    $query->where('attacker_alliance_id', $id)
                          ->orWhere('defender_alliance_id', $id);
                })
                ->orderBy('started_at', 'desc')
                ->get();

            return response()->json(['data' => $wars]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alliance wars: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get alliance diplomacy
     *
     * @authenticated
     *
     * @description Get diplomatic relationships for a specific alliance.
     *
     * @urlParam id int required The ID of the alliance. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "alliance_id": 1,
     *       "target_alliance_id": 3,
     *       "relationship_type": "alliance",
     *       "status": "active",
     *       "created_at": "2023-01-01T00:00:00.000000Z",
     *       "target_alliance": {
     *         "id": 3,
     *         "name": "Peace Keepers"
     *       }
     *     }
     *   ]
     * }
     *
     * @tag Alliance System
     */
    public function diplomacy(int $id): JsonResponse
    {
        try {
            $diplomacy = AllianceDiplomacy::with('targetAlliance')
                ->where('alliance_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['data' => $diplomacy]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alliance diplomacy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update alliance
     *
     * @authenticated
     *
     * @description Update alliance information. Only leaders can update.
     *
     * @urlParam id int required The ID of the alliance. Example: 1
     * @bodyParam name string The new name of the alliance. Example: "Knights of the Round Table"
     * @bodyParam description string The new description. Example: "Honor and chivalry above all"
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Alliance updated successfully",
     *   "alliance": {
     *     "id": 1,
     *     "name": "Knights of the Round Table",
     *     "description": "Honor and chivalry above all",
     *     "updated_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "message": "Only alliance leader can update alliance"
     * }
     *
     * @tag Alliance System
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $player = Auth::user()->player;
            $alliance = Alliance::findOrFail($id);

            // Check if player is the leader
            if ($alliance->leader_id !== $player->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only alliance leader can update alliance'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:alliances,name,' . $id,
                'description' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $alliance->update($request->only(['name', 'description']));

            return response()->json([
                'success' => true,
                'message' => 'Alliance updated successfully',
                'alliance' => $alliance->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update alliance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disband alliance
     *
     * @authenticated
     *
     * @description Disband the alliance. Only leaders can disband.
     *
     * @urlParam id int required The ID of the alliance to disband. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Alliance disbanded successfully"
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "message": "Only alliance leader can disband alliance"
     * }
     *
     * @tag Alliance System
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $player = Auth::user()->player;
            $alliance = Alliance::findOrFail($id);

            // Check if player is the leader
            if ($alliance->leader_id !== $player->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only alliance leader can disband alliance'
                ], 403);
            }

            DB::beginTransaction();

            // Remove all members
            AllianceMember::where('alliance_id', $id)->delete();

            // Update all players to remove alliance
            Player::where('alliance_id', $id)->update(['alliance_id' => null]);

            // Delete alliance
            $alliance->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Alliance disbanded successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to disband alliance: ' . $e->getMessage()
            ], 500);
        }
    }
}