<?php

namespace App\Http\Controllers\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use App\Services\GameSecurityService;
use App\Traits\ValidationHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;

class SecureGameController extends CrudController
{
    use ApiResponseTrait;
    use ValidationHelperTrait;

    protected $securityService;

    public function __construct(GameSecurityService $securityService)
    {
        $this->securityService = $securityService;
        parent::__construct(new User());
    }

    public function dashboard()
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return redirect()->route('login');
            }

            $player = Player::where('user_id', $user->id)->first();
            if (! $player) {
                return view('game.no-player', compact('user'));
            }

            return view('game.dashboard');
        } catch (\Exception $e) {
            return view('game.error', ['error' => $e->getMessage()]);
        }
    }

    public function upgradeBuilding(Request $request)
    {
        $validated = $this->validateRequestData($request, [
            'village_id' => 'required|integer|exists:villages,id',
            'building_id' => 'required|integer|exists:buildings,id',
            'target_level' => 'required|integer|min:1|max:20',
        ]);

        // Check security
        if (! $this->securityService->validateGameAction($request, 'building_upgrade', $request->all())) {
            return $this->errorResponse('Unauthorized action', 403);
        }

        // Check for suspicious activity
        if ($this->securityService->checkSuspiciousActivity($request, 'building_upgrade')) {
            return $this->errorResponse('Suspicious activity detected', 429);
        }

        try {
            $village = $request->get('village');
            $building = $village->buildings()->find($request->building_id);

            if (! $building) {
                return $this->errorResponse('Building not found', 404);
            }

            // Additional security checks
            if ($building->upgrade_started_at) {
                return $this->errorResponse('Building is already upgrading', 400);
            }

            // Process building upgrade
            $this->processBuildingUpgrade($building, $request->target_level);

            return $this->successResponse(null, 'Building upgrade started');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upgrade building', 500);
        }
    }

    public function trainTroops(Request $request)
    {
        $validated = $this->validateRequestData($request, [
            'village_id' => 'required|integer|exists:villages,id',
            'unit_type_id' => 'required|integer|exists:unit_types,id',
            'quantity' => 'required|integer|min:1|max:1000',
        ]);

        // Check security
        if (! $this->securityService->validateGameAction($request, 'troop_training', $request->all())) {
            return $this->errorResponse('Unauthorized action', 403);
        }

        // Check for suspicious activity
        if ($this->securityService->checkSuspiciousActivity($request, 'troop_training')) {
            return $this->errorResponse('Suspicious activity detected', 429);
        }

        try {
            $village = $request->get('village');

            // Process troop training
            $this->processTroopTraining($village, $request->unit_type_id, $request->quantity);

            return $this->successResponse(null, 'Troop training started');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to train troops', 500);
        }
    }

    public function spendResources(Request $request)
    {
        $validated = $this->validateRequestData($request, [
            'village_id' => 'required|integer|exists:villages,id',
            'costs' => 'required|array',
            'costs.wood' => 'integer|min:0',
            'costs.clay' => 'integer|min:0',
            'costs.iron' => 'integer|min:0',
            'costs.crop' => 'integer|min:0',
        ]);

        // Check security
        if (! $this->securityService->validateGameAction($request, 'resource_spend', $request->all())) {
            return $this->errorResponse('Unauthorized action', 403);
        }

        // Check for suspicious activity
        if ($this->securityService->checkSuspiciousActivity($request, 'resource_spend')) {
            return $this->errorResponse('Suspicious activity detected', 429);
        }

        try {
            $village = $request->get('village');

            // Process resource spending
            $this->processResourceSpending($village, $request->costs);

            return $this->successResponse(null, 'Resources spent successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to spend resources', 500);
        }
    }

    public function getVillageData(Request $request, $villageId)
    {
        // Check security
        if (! $this->securityService->validateGameAction($request, 'village_management', ['village_id' => $villageId])) {
            return $this->errorResponse('Unauthorized access', 403);
        }

        try {
            $village = Village::with(['buildings', 'resources', 'troops'])
                ->findOrFail($villageId);

            return $this->successResponse([
                'village' => $village,
                'buildings' => $village->buildings,
                'resources' => $village->resources,
                'troops' => $village->troops,
            ], 'Village data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load village data', 500);
        }
    }

    private function processBuildingUpgrade($building, $targetLevel)
    {
        // Implementation for building upgrade
        $building->update([
            'level' => $targetLevel,
            'upgrade_started_at' => now(),
        ]);
    }

    private function processTroopTraining($village, $unitTypeId, $quantity)
    {
        // Implementation for troop training
        $village->troops()->create([
            'unit_type_id' => $unitTypeId,
            'quantity' => $quantity,
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
