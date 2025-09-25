<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\GameSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SecureGameController extends Controller
{
    protected $securityService;

    public function __construct(GameSecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function dashboard()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            $player = Player::where('user_id', $user->id)->first();
            if (!$player) {
                return view('game.no-player', compact('user'));
            }

            return view('game.dashboard');
        } catch (\Exception $e) {
            return view('game.error', ['error' => $e->getMessage()]);
        }
    }

    public function upgradeBuilding(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'village_id' => 'required|integer|exists:villages,id',
            'building_id' => 'required|integer|exists:buildings,id',
            'target_level' => 'required|integer|min:1|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Check security
        if (!$this->securityService->validateGameAction($request, 'building_upgrade', $request->all())) {
            return response()->json(['error' => 'Unauthorized action'], 403);
        }

        // Check for suspicious activity
        if ($this->securityService->checkSuspiciousActivity($request, 'building_upgrade')) {
            return response()->json(['error' => 'Suspicious activity detected'], 429);
        }

        try {
            $village = $request->get('village');
            $building = $village->buildings()->find($request->building_id);

            if (!$building) {
                return response()->json(['error' => 'Building not found'], 404);
            }

            // Additional security checks
            if ($building->upgrade_started_at) {
                return response()->json(['error' => 'Building is already upgrading'], 400);
            }

            // Process building upgrade
            $this->processBuildingUpgrade($building, $request->target_level);

            return response()->json(['success' => true, 'message' => 'Building upgrade started']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to upgrade building'], 500);
        }
    }

    public function trainTroops(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'village_id' => 'required|integer|exists:villages,id',
            'unit_type_id' => 'required|integer|exists:unit_types,id',
            'quantity' => 'required|integer|min:1|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Check security
        if (!$this->securityService->validateGameAction($request, 'troop_training', $request->all())) {
            return response()->json(['error' => 'Unauthorized action'], 403);
        }

        // Check for suspicious activity
        if ($this->securityService->checkSuspiciousActivity($request, 'troop_training')) {
            return response()->json(['error' => 'Suspicious activity detected'], 429);
        }

        try {
            $village = $request->get('village');

            // Process troop training
            $this->processTroopTraining($village, $request->unit_type_id, $request->quantity);

            return response()->json(['success' => true, 'message' => 'Troop training started']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to train troops'], 500);
        }
    }

    public function spendResources(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'village_id' => 'required|integer|exists:villages,id',
            'costs' => 'required|array',
            'costs.wood' => 'integer|min:0',
            'costs.clay' => 'integer|min:0',
            'costs.iron' => 'integer|min:0',
            'costs.crop' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Check security
        if (!$this->securityService->validateGameAction($request, 'resource_spend', $request->all())) {
            return response()->json(['error' => 'Unauthorized action'], 403);
        }

        // Check for suspicious activity
        if ($this->securityService->checkSuspiciousActivity($request, 'resource_spend')) {
            return response()->json(['error' => 'Suspicious activity detected'], 429);
        }

        try {
            $village = $request->get('village');

            // Process resource spending
            $this->processResourceSpending($village, $request->costs);

            return response()->json(['success' => true, 'message' => 'Resources spent successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to spend resources'], 500);
        }
    }

    public function getVillageData(Request $request, $villageId)
    {
        // Check security
        if (!$this->securityService->validateGameAction($request, 'village_management', ['village_id' => $villageId])) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        try {
            $village = Village::with(['buildings', 'resources', 'troops'])
                ->findOrFail($villageId);

            return response()->json([
                'village' => $village,
                'buildings' => $village->buildings,
                'resources' => $village->resources,
                'troops' => $village->troops
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load village data'], 500);
        }
    }

    private function processBuildingUpgrade($building, $targetLevel)
    {
        // Implementation for building upgrade
        $building->update([
            'level' => $targetLevel,
            'upgrade_started_at' => now()
        ]);
    }

    private function processTroopTraining($village, $unitTypeId, $quantity)
    {
        // Implementation for troop training
        $village->troops()->create([
            'unit_type_id' => $unitTypeId,
            'quantity' => $quantity
        ]);
    }

    private function processResourceSpending($village, $costs)
    {
        // Implementation for resource spending
        foreach ($costs as $resource => $amount) {
            $resourceModel = $village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $resourceModel->decrement('amount', $amount);
            }
        }
    }
}

