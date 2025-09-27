<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GameIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class GameIntegrationController extends Controller
{
    protected $gameIntegrationService;

    public function __construct(GameIntegrationService $gameIntegrationService)
    {
        $this->gameIntegrationService = $gameIntegrationService;
    }

    /**
     * Initialize real-time features for a user
     */
    public function initializeRealTime(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->gameIntegrationService->initializeUserRealTime($request->user_id);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Deinitialize real-time features for a user
     */
    public function deinitializeRealTime(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->gameIntegrationService->deinitializeUserRealTime($request->user_id);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Create village with real-time integration
     */
    public function createVillage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'player_id' => 'required|integer|exists:players,id',
            'name' => 'required|string|max:255',
            'x' => 'required|integer|min:0|max:999',
            'y' => 'required|integer|min:0|max:999',
            'world_id' => 'required|integer|exists:worlds,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $villageData = $request->only(['player_id', 'name', 'x', 'y', 'world_id']);
        $result = $this->gameIntegrationService->createVillageWithIntegration($villageData);

        return response()->json($result, $result['success'] ? 201 : 500);
    }

    /**
     * Upgrade building with real-time integration
     */
    public function upgradeBuilding(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'village_id' => 'required|integer|exists:villages,id',
            'building_type_id' => 'required|integer|exists:building_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->gameIntegrationService->upgradeBuildingWithIntegration(
            $request->village_id,
            $request->building_type_id
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Join alliance with real-time integration
     */
    public function joinAlliance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'player_id' => 'required|integer|exists:players,id',
            'alliance_id' => 'required|integer|exists:alliances,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->gameIntegrationService->joinAllianceWithIntegration(
            $request->player_id,
            $request->alliance_id
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get comprehensive game statistics
     */
    public function getGameStatistics(): JsonResponse
    {
        $result = $this->gameIntegrationService->getGameStatistics();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Send system announcement (admin only)
     */
    public function sendSystemAnnouncement(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->gameIntegrationService->sendSystemAnnouncement(
            $request->title,
            $request->message,
            $request->priority ?? 'normal'
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Perform system maintenance
     */
    public function performMaintenance(): JsonResponse
    {
        $result = $this->gameIntegrationService->performMaintenance();

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
