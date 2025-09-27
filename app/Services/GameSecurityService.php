<?php

namespace App\Services;

use App\Models\Game\Player;
use App\Models\Game\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class GameSecurityService
{
    public function validateGameAction(Request $request, string $action, array $data = [])
    {
        // Check rate limiting
        if (!$this->checkRateLimit($request, $action)) {
            return false;
        }

        // Check player ownership
        if (!$this->checkPlayerOwnership($request, $data)) {
            return false;
        }

        // Check village ownership
        if (isset($data['village_id']) && !$this->checkVillageOwnership($request, $data['village_id'])) {
            return false;
        }

        // Check action-specific security
        if (!$this->checkActionSecurity($request, $action, $data)) {
            return false;
        }

        return true;
    }

    private function checkRateLimit(Request $request, string $action)
    {
        $key = 'game-action:' . $request->ip() . ':' . $action;
        $maxAttempts = $this->getRateLimitForAction($action);
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'action' => $action,
                'user_id' => $request->user()?->id,
            ]);

            return false;
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return true;
    }

    private function getRateLimitForAction(string $action)
    {
        $limits = [
            'building_upgrade' => 10,  // 10 building upgrades per minute
            'troop_training' => 20,  // 20 troop training actions per minute
            'resource_spend' => 30,  // 30 resource spending actions per minute
            'village_management' => 15,  // 15 village management actions per minute
            'map_interaction' => 50,  // 50 map interactions per minute
            'default' => 60,  // 60 general actions per minute
        ];

        return $limits[$action] ?? $limits['default'];
    }

    private function checkPlayerOwnership(Request $request, array $data)
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        $player = Player::where('user_id', $user->id)->first();
        if (!$player || !$player->is_active) {
            return false;
        }

        // Add player to request for easy access
        $request->merge(['player' => $player]);

        return true;
    }

    private function checkVillageOwnership(Request $request, int $villageId)
    {
        $player = $request->get('player');
        if (!$player) {
            return false;
        }

        $village = Village::where('id', $villageId)
            ->where('player_id', $player->id)
            ->first();

        if (!$village) {
            Log::warning('Unauthorized village access attempt', [
                'user_id' => $request->user()?->id,
                'village_id' => $villageId,
                'ip' => $request->ip(),
            ]);

            return false;
        }

        $request->merge(['village' => $village]);

        return true;
    }

    private function checkActionSecurity(Request $request, string $action, array $data)
    {
        switch ($action) {
            case 'building_upgrade':
                return $this->validateBuildingUpgrade($request, $data);
            case 'troop_training':
                return $this->validateTroopTraining($request, $data);
            case 'resource_spend':
                return $this->validateResourceSpending($request, $data);
            case 'village_management':
                return $this->validateVillageManagement($request, $data);
            default:
                return true;
        }
    }

    private function validateBuildingUpgrade(Request $request, array $data)
    {
        $village = $request->get('village');
        if (!$village) {
            return false;
        }

        // Check if building exists and belongs to village
        $building = $village
            ->buildings()
            ->where('id', $data['building_id'] ?? 0)
            ->first();

        if (!$building) {
            return false;
        }

        // Check if building is already upgrading
        if ($building->upgrade_started_at) {
            return false;
        }

        return true;
    }

    private function validateTroopTraining(Request $request, array $data)
    {
        $village = $request->get('village');
        if (!$village) {
            return false;
        }

        // Check if unit type is valid for player's tribe
        $unitType = $village->player->tribe;
        if (!in_array($data['unit_type_id'] ?? 0, $this->getValidUnitTypes($unitType))) {
            return false;
        }

        // Check quantity limits
        $quantity = $data['quantity'] ?? 0;
        if ($quantity <= 0 || $quantity > 1000) {
            return false;
        }

        return true;
    }

    private function validateResourceSpending(Request $request, array $data)
    {
        $village = $request->get('village');
        if (!$village) {
            return false;
        }

        // Check if player has enough resources
        $costs = $data['costs'] ?? [];
        foreach ($costs as $resource => $amount) {
            $resourceModel = $village->resources()->where('type', $resource)->first();
            if (!$resourceModel || $resourceModel->amount < $amount) {
                return false;
            }
        }

        return true;
    }

    private function validateVillageManagement(Request $request, array $data)
    {
        $village = $request->get('village');
        if (!$village) {
            return false;
        }

        // Check if village is active
        if (!$village->is_active) {
            return false;
        }

        return true;
    }

    private function getValidUnitTypes(string $tribe)
    {
        $unitTypes = [
            'roman' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'teuton' => [11, 12, 13, 14, 15, 16, 17, 18, 19, 20],
            'gaul' => [21, 22, 23, 24, 25, 26, 27, 28, 29, 30],
            'natars' => [31, 32, 33, 34, 35, 36, 37, 38, 39, 40],
        ];

        return $unitTypes[$tribe] ?? [];
    }

    public function logSecurityEvent(string $event, array $data = [])
    {
        Log::info('Game security event', [
            'event' => $event,
            'data' => $data,
            'timestamp' => now(),
        ]);
    }

    public function checkSuspiciousActivity(Request $request, string $action)
    {
        $suspiciousPatterns = [
            'rapid_building_upgrades' => $this->checkRapidBuildingUpgrades($request),
            'excessive_resource_spending' => $this->checkExcessiveResourceSpending($request),
            'unusual_troop_training' => $this->checkUnusualTroopTraining($request),
        ];

        foreach ($suspiciousPatterns as $pattern => $isSuspicious) {
            if ($isSuspicious) {
                $this->logSecurityEvent('suspicious_activity', [
                    'pattern' => $pattern,
                    'action' => $action,
                    'ip' => $request->ip(),
                    'user_id' => $request->user()?->id,
                ]);

                return true;
            }
        }

        return false;
    }

    private function checkRapidBuildingUpgrades(Request $request)
    {
        $key = 'building_upgrades:' . $request->ip();
        $attempts = RateLimiter::attempts($key);

        return $attempts > 20;  // More than 20 building upgrades in the last minute
    }

    private function checkExcessiveResourceSpending(Request $request)
    {
        $key = 'resource_spending:' . $request->ip();
        $attempts = RateLimiter::attempts($key);

        return $attempts > 50;  // More than 50 resource spending actions in the last minute
    }

    private function checkUnusualTroopTraining(Request $request)
    {
        $key = 'troop_training:' . $request->ip();
        $attempts = RateLimiter::attempts($key);

        return $attempts > 100;  // More than 100 troop training actions in the last minute
    }
}
