<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Artifact;
use App\Models\Game\ArtifactEffect;
use App\Models\Game\Player;
use App\Models\Game\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\FilteringUtil;

/**
 * @group Artifact Management
 *
 * API endpoints for managing game artifacts, their effects, and discovery system.
 * Artifacts are powerful items that can provide various bonuses to players, villages, or the entire server.
 *
 * @authenticated
 *
 * @tag Artifact System
 * @tag Game Items
 * @tag Server Effects
 */
class ArtifactController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all artifacts
     *
     * @authenticated
     *
     * @description Retrieve a paginated list of all artifacts in the system.
     *
     * @queryParam page int The page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam type string Filter by artifact type. Example: weapon
     * @queryParam rarity string Filter by rarity level. Example: legendary
     * @queryParam status string Filter by artifact status. Example: active
     * @queryParam server_wide boolean Filter server-wide artifacts. Example: true
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Sword of Power",
     *       "description": "A legendary weapon that grants immense power",
     *       "type": "weapon",
     *       "rarity": "legendary",
     *       "status": "active",
     *       "power_level": 85,
     *       "durability": 100,
     *       "max_durability": 100,
     *       "is_server_wide": false,
     *       "is_unique": true,
     *       "owner_id": 1,
     *       "village_id": 5,
     *       "discovered_at": "2023-01-01T00:00:00.000000Z",
     *       "activated_at": "2023-01-01T00:00:00.000000Z",
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
     * @tag Artifact System
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Artifact::with(['owner', 'village', 'artifactEffects']);

            // Apply filters
            if ($request->has('type')) {
                $query->byType($request->input('type'));
            }

            if ($request->has('rarity')) {
                $query->byRarity($request->input('rarity'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('server_wide')) {
                $query->serverWide();
            }

            $artifacts = $query->paginate($request->input('per_page', 15));

            LoggingUtil::info('Artifacts retrieved', [
                'user_id' => auth()->id(),
                'filters' => $request->only(['type', 'rarity', 'status', 'server_wide']),
                'count' => $artifacts->count(),
            ], 'artifact_system');

            return response()->json($artifacts);
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving artifacts', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'artifact_system');

            return $this->errorResponse('Failed to retrieve artifacts.', 500);
        }
    }

    /**
     * Get specific artifact
     *
     * @authenticated
     *
     * @description Retrieve detailed information about a specific artifact including its effects.
     *
     * @urlParam id int required The ID of the artifact. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "name": "Sword of Power",
     *   "description": "A legendary weapon that grants immense power",
     *   "type": "weapon",
     *   "rarity": "legendary",
     *   "status": "active",
     *   "power_level": 85,
     *   "durability": 100,
     *   "max_durability": 100,
     *   "effects": [
     *     {
     *       "type": "combat_bonus",
     *       "magnitude": 25.5,
     *       "target_type": "player",
     *       "effect_data": {
     *         "attack_bonus": 25,
     *         "defense_bonus": 15
     *       }
     *     }
     *   ],
     *   "requirements": [
     *     {
     *       "type": "player_level",
     *       "value": 50
     *     }
     *   ],
     *   "owner": {
     *     "id": 1,
     *     "name": "PlayerOne"
     *   },
     *   "village": {
     *     "id": 5,
     *     "name": "Capital City"
     *   },
     *   "artifact_effects": [
     *     {
     *       "id": 1,
     *       "effect_type": "combat_bonus",
     *       "target_type": "player",
     *       "magnitude": 25.5,
     *       "is_active": true
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   "message": "Artifact not found"
     * }
     *
     * @tag Artifact System
     */
    public function show(int $id): JsonResponse
    {
        try {
            $artifact = Artifact::with(['owner', 'village', 'artifactEffects'])
                ->findOrFail($id);

            LoggingUtil::info('Artifact details retrieved', [
                'user_id' => auth()->id(),
                'artifact_id' => $id,
                'artifact_name' => $artifact->name,
            ], 'artifact_system');

            return response()->json($artifact);
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving artifact details', [
                'error' => $e->getMessage(),
                'artifact_id' => $id,
                'user_id' => auth()->id(),
            ], 'artifact_system');

            return $this->errorResponse('Artifact not found.', 404);
        }
    }

    /**
     * Create new artifact
     *
     * @authenticated
     *
     * @description Create a new artifact with specified properties and effects.
     *
     * @bodyParam name string required The name of the artifact. Example: "Staff of Wisdom"
     * @bodyParam description string The description of the artifact. Example: "A mystical staff that enhances magical abilities"
     * @bodyParam type string required The type of artifact (weapon, armor, tool, mystical, relic, crystal). Example: "mystical"
     * @bodyParam rarity string required The rarity level (common, uncommon, rare, epic, legendary, mythic). Example: "rare"
     * @bodyParam power_level int The power level (1-100). Example: 65
     * @bodyParam effects array Array of effects. Example: [{"type": "resource_bonus", "magnitude": 15.0}]
     * @bodyParam requirements array Array of requirements. Example: [{"type": "player_level", "value": 30}]
     * @bodyParam is_server_wide boolean Whether the artifact affects the entire server. Example: false
     * @bodyParam is_unique boolean Whether only one of this artifact can exist. Example: true
     *
     * @response 201 {
     *   "success": true,
     *   "artifact": {
     *     "id": 2,
     *     "name": "Staff of Wisdom",
     *     "description": "A mystical staff that enhances magical abilities",
     *     "type": "mystical",
     *     "rarity": "rare",
     *     "status": "inactive",
     *     "power_level": 65,
     *     "durability": 100,
     *     "max_durability": 100,
     *     "is_server_wide": false,
     *     "is_unique": true,
     *     "created_at": "2023-01-01T00:00:00.000000Z",
     *     "updated_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "name": ["The name field is required."],
     *     "type": ["The selected type is invalid."]
     *   }
     * }
     *
     * @tag Artifact System
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:weapon,armor,tool,mystical,relic,crystal',
                'rarity' => 'required|in:common,uncommon,rare,epic,legendary,mythic',
                'power_level' => 'nullable|integer|min:1|max:100',
                'effects' => 'nullable|array',
                'requirements' => 'nullable|array',
                'is_server_wide' => 'boolean',
                'is_unique' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $artifact = Artifact::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'type' => $request->input('type'),
                'rarity' => $request->input('rarity'),
                'status' => 'inactive',
                'power_level' => $request->input('power_level', 50),
                'durability' => 100,
                'max_durability' => 100,
                'effects' => $request->input('effects', []),
                'requirements' => $request->input('requirements', []),
                'is_server_wide' => $request->boolean('is_server_wide', false),
                'is_unique' => $request->boolean('is_unique', false),
                'discovered_at' => now(),
            ]);

            LoggingUtil::info('Artifact created', [
                'user_id' => auth()->id(),
                'artifact_id' => $artifact->id,
                'artifact_name' => $artifact->name,
                'artifact_type' => $artifact->type,
                'artifact_rarity' => $artifact->rarity,
            ], 'artifact_system');

            return response()->json([
                'success' => true,
                'artifact' => $artifact
            ], 201);
        } catch (\Exception $e) {
            LoggingUtil::error('Error creating artifact', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
            ], 'artifact_system');

            return $this->errorResponse('Failed to create artifact.', 500);
        }
    }

    /**
     * Activate artifact
     *
     * @authenticated
     *
     * @description Activate an artifact, applying its effects to the game world.
     *
     * @urlParam id int required The ID of the artifact to activate. Example: 1
     * @bodyParam owner_id int The ID of the player who will own the artifact. Example: 1
     * @bodyParam village_id int The ID of the village where the artifact will be placed. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Artifact activated successfully",
     *   "artifact": {
     *     "id": 1,
     *     "status": "active",
     *     "activated_at": "2023-01-01T00:00:00.000000Z",
     *     "owner_id": 1,
     *     "village_id": 5
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Artifact cannot be activated"
     * }
     *
     * @response 404 {
     *   "message": "Artifact not found"
     * }
     *
     * @tag Artifact System
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        try {
            $artifact = Artifact::findOrFail($id);

            if (!$artifact->canActivate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Artifact cannot be activated. Check requirements and status.'
                ], 400);
            }

            // Update owner and village if provided
            if ($request->has('owner_id')) {
                $artifact->owner_id = $request->input('owner_id');
            }

            if ($request->has('village_id')) {
                $artifact->village_id = $request->input('village_id');
            }

            $activated = $artifact->activate();

            if ($activated) {
                LoggingUtil::info('Artifact activated', [
                    'user_id' => auth()->id(),
                    'artifact_id' => $artifact->id,
                    'artifact_name' => $artifact->name,
                    'owner_id' => $artifact->owner_id,
                    'village_id' => $artifact->village_id,
                ], 'artifact_system');

                return response()->json([
                    'success' => true,
                    'message' => 'Artifact activated successfully',
                    'artifact' => $artifact->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to activate artifact'
                ], 400);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error activating artifact', [
                'error' => $e->getMessage(),
                'artifact_id' => $id,
                'user_id' => auth()->id(),
            ], 'artifact_system');

            return $this->errorResponse('Failed to activate artifact.', 500);
        }
    }

    /**
     * Deactivate artifact
     *
     * @authenticated
     *
     * @description Deactivate an artifact, removing its effects from the game world.
     *
     * @urlParam id int required The ID of the artifact to deactivate. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Artifact deactivated successfully",
     *   "artifact": {
     *     "id": 1,
     *     "status": "inactive",
     *       "activated_at": null
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Artifact is not active"
     * }
     *
     * @response 404 {
     *   "message": "Artifact not found"
     * }
     *
     * @tag Artifact System
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $artifact = Artifact::findOrFail($id);

            $deactivated = $artifact->deactivate();

            if ($deactivated) {
                LoggingUtil::info('Artifact deactivated', [
                    'user_id' => auth()->id(),
                    'artifact_id' => $artifact->id,
                    'artifact_name' => $artifact->name,
                ], 'artifact_system');

                return response()->json([
                    'success' => true,
                    'message' => 'Artifact deactivated successfully',
                    'artifact' => $artifact->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Artifact is not active'
                ], 400);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error deactivating artifact', [
                'error' => $e->getMessage(),
                'artifact_id' => $id,
                'user_id' => auth()->id(),
            ], 'artifact_system');

            return $this->errorResponse('Failed to deactivate artifact.', 500);
        }
    }

    /**
     * Get server-wide artifacts
     *
     * @authenticated
     *
     * @description Retrieve all artifacts that affect the entire server.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 3,
     *       "name": "Crown of Kings",
     *       "type": "relic",
     *       "rarity": "mythic",
     *       "status": "active",
     *       "power_level": 95,
     *       "is_server_wide": true,
     *       "effects": [
     *         {
     *           "type": "server_bonus",
     *           "magnitude": 10.0,
     *           "target_type": "server",
     *           "effect_data": {
     *             "resource_production_bonus": 10,
     *             "experience_bonus": 5
     *           }
     *         }
     *       ]
     *     }
     *   ]
     * }
     *
     * @tag Artifact System
     */
    public function serverWide(): JsonResponse
    {
        try {
            $artifacts = Artifact::serverWide()
                ->active()
                ->with(['artifactEffects'])
                ->get();

            LoggingUtil::info('Server-wide artifacts retrieved', [
                'user_id' => auth()->id(),
                'count' => $artifacts->count(),
            ], 'artifact_system');

            return response()->json(['data' => $artifacts]);
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving server-wide artifacts', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'artifact_system');

            return $this->errorResponse('Failed to retrieve server-wide artifacts.', 500);
        }
    }

    /**
     * Generate random artifact
     *
     * @authenticated
     *
     * @description Generate a random artifact with random properties and effects.
     *
     * @bodyParam type string The specific type to generate. Example: "weapon"
     * @bodyParam rarity string The specific rarity to generate. Example: "rare"
     * @bodyParam power_level_min int Minimum power level. Example: 20
     * @bodyParam power_level_max int Maximum power level. Example: 80
     *
     * @response 201 {
     *   "success": true,
     *   "artifact": {
     *     "id": 4,
     *     "name": "Blade of Thunder",
     *     "description": "A weapon crackling with electric energy",
     *     "type": "weapon",
     *     "rarity": "rare",
     *     "status": "inactive",
     *     "power_level": 65,
     *     "durability": 100,
     *     "max_durability": 100,
     *     "effects": [
     *       {
     *         "type": "combat_bonus",
     *         "magnitude": 20.0,
     *         "target_type": "player"
     *       }
     *     ],
     *     "created_at": "2023-01-01T00:00:00.000000Z",
     *     "updated_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @tag Artifact System
     */
    public function generateRandom(Request $request): JsonResponse
    {
        try {
            $options = [];

            if ($request->has('type')) {
                $options['type'] = $request->input('type');
            }

            if ($request->has('rarity')) {
                $options['rarity'] = $request->input('rarity');
            }

            $artifact = Artifact::generateRandomArtifact($options);

            LoggingUtil::info('Random artifact generated', [
                'user_id' => auth()->id(),
                'artifact_id' => $artifact->id,
                'artifact_name' => $artifact->name,
                'artifact_type' => $artifact->type,
                'artifact_rarity' => $artifact->rarity,
                'options' => $options,
            ], 'artifact_system');

            return response()->json([
                'success' => true,
                'artifact' => $artifact
            ], 201);
        } catch (\Exception $e) {
            LoggingUtil::error('Error generating random artifact', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'options' => $request->all(),
            ], 'artifact_system');

            return $this->errorResponse('Failed to generate random artifact.', 500);
        }
    }

    /**
     * Get artifact effects
     *
     * @authenticated
     *
     * @description Retrieve all effects for a specific artifact.
     *
     * @urlParam id int required The ID of the artifact. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "artifact_id": 1,
     *       "effect_type": "combat_bonus",
     *       "target_type": "player",
     *       "target_id": null,
     *       "effect_data": {
     *         "attack_bonus": 25,
     *         "defense_bonus": 15
     *       },
     *       "magnitude": 25.5,
     *       "duration_type": "permanent",
     *       "duration_hours": null,
     *       "starts_at": null,
     *       "expires_at": null,
     *       "is_active": true,
     *       "created_at": "2023-01-01T00:00:00.000000Z",
     *       "updated_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @tag Artifact System
     */
    public function effects(int $id): JsonResponse
    {
        try {
            $artifact = Artifact::findOrFail($id);
            $effects = $artifact->artifactEffects()->get();

            LoggingUtil::info('Artifact effects retrieved', [
                'user_id' => auth()->id(),
                'artifact_id' => $id,
                'effects_count' => $effects->count(),
            ], 'artifact_system');

            return response()->json(['data' => $effects]);
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving artifact effects', [
                'error' => $e->getMessage(),
                'artifact_id' => $id,
                'user_id' => auth()->id(),
            ], 'artifact_system');

            return $this->errorResponse('Failed to retrieve artifact effects.', 500);
        }
    }

    /**
     * Update artifact
     *
     * @authenticated
     *
     * @description Update an existing artifact's properties.
     *
     * @urlParam id int required The ID of the artifact to update. Example: 1
     * @bodyParam name string The new name of the artifact. Example: "Enhanced Sword of Power"
     * @bodyParam description string The new description. Example: "An even more powerful version"
     * @bodyParam power_level int The new power level (1-100). Example: 90
     * @bodyParam durability int The new durability (0-100). Example: 85
     * @bodyParam effects array Updated effects array.
     * @bodyParam requirements array Updated requirements array.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Artifact updated successfully",
     *   "artifact": {
     *     "id": 1,
     *       "name": "Enhanced Sword of Power",
     *       "power_level": 90,
     *       "durability": 85,
     *       "updated_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "message": "Artifact not found"
     * }
     *
     * @tag Artifact System
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $artifact = Artifact::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'power_level' => 'sometimes|integer|min:1|max:100',
                'durability' => 'sometimes|integer|min:0|max:100',
                'effects' => 'nullable|array',
                'requirements' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $artifact->update($request->only([
                'name', 'description', 'power_level', 'durability', 'effects', 'requirements'
            ]));

            LoggingUtil::info('Artifact updated', [
                'user_id' => auth()->id(),
                'artifact_id' => $artifact->id,
                'artifact_name' => $artifact->name,
                'updated_fields' => array_keys($request->only([
                    'name', 'description', 'power_level', 'durability', 'effects', 'requirements'
                ])),
            ], 'artifact_system');

            return response()->json([
                'success' => true,
                'message' => 'Artifact updated successfully',
                'artifact' => $artifact->fresh()
            ]);
        } catch (\Exception $e) {
            LoggingUtil::error('Error updating artifact', [
                'error' => $e->getMessage(),
                'artifact_id' => $id,
                'user_id' => auth()->id(),
            ], 'artifact_system');

            return $this->errorResponse('Failed to update artifact.', 500);
        }
    }

    /**
     * Delete artifact
     *
     * @authenticated
     *
     * @description Delete an artifact from the system. Only inactive artifacts can be deleted.
     *
     * @urlParam id int required The ID of the artifact to delete. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Artifact deleted successfully"
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Cannot delete active artifact"
     * }
     *
     * @response 404 {
     *   "message": "Artifact not found"
     * }
     *
     * @tag Artifact System
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $artifact = Artifact::findOrFail($id);

            if ($artifact->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete active artifact. Deactivate it first.'
                ], 400);
            }

            $artifactName = $artifact->name;
            $artifact->delete();

            LoggingUtil::info('Artifact deleted', [
                'user_id' => auth()->id(),
                'artifact_id' => $id,
                'artifact_name' => $artifactName,
            ], 'artifact_system');

            return response()->json([
                'success' => true,
                'message' => 'Artifact deleted successfully'
            ]);
        } catch (\Exception $e) {
            LoggingUtil::error('Error deleting artifact', [
                'error' => $e->getMessage(),
                'artifact_id' => $id,
                'user_id' => auth()->id(),
            ], 'artifact_system');

            return $this->errorResponse('Failed to delete artifact.', 500);
        }
    }
}
