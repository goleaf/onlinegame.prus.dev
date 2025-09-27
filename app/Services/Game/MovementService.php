<?php

namespace App\Services\Game;

use App\Models\Game\Movement;
use App\Models\Game\Village;
use App\Models\Game\Player;
use App\Models\Game\Troop;
use App\Models\Game\UnitType;
use Illuminate\Support\Facades\DB;
use SmartCache\Facades\SmartCache;

class MovementService
{
    public function __construct(
        private MapService $mapService
    ) {}

    /**
     * Start troop movement
     */
    public function startMovement(Village $fromVillage, Village $toVillage, array $troops, string $movementType = 'attack'): array
    {
        // Validate movement
        $validation = $this->validateMovement($fromVillage, $toVillage, $troops, $movementType);
        if (!$validation['valid']) {
            return $validation;
        }

        // Calculate travel time
        $travelTime = $this->calculateTravelTime($fromVillage, $toVillage, $troops);

        // Create movement record
        $movement = Movement::create([
            'from_village_id' => $fromVillage->id,
            'to_village_id' => $toVillage->id,
            'player_id' => $fromVillage->player_id,
            'movement_type' => $movementType,
            'troops' => $troops,
            'started_at' => now(),
            'arrives_at' => now()->addSeconds($travelTime),
            'status' => 'moving',
        ]);

        // Remove troops from village
        $this->removeTroopsFromVillage($fromVillage, $troops);

        // Clear cache
        $this->clearMovementCache($fromVillage->player);

        return [
            'success' => true,
            'message' => 'Movement started successfully',
            'movement' => $movement,
            'travel_time' => $travelTime,
            'arrives_at' => $movement->arrives_at,
        ];
    }

    /**
     * Complete movement
     */
    public function completeMovement(Movement $movement): array
    {
        if ($movement->status !== 'moving') {
            return [
                'success' => false,
                'message' => 'Movement is not in progress',
            ];
        }

        if ($movement->arrives_at > now()) {
            return [
                'success' => false,
                'message' => 'Movement has not arrived yet',
            ];
        }

        DB::transaction(function () use ($movement) {
            // Update movement status
            $movement->update([
                'status' => 'arrived',
                'arrived_at' => now(),
            ]);

            // Handle movement based on type
            switch ($movement->movement_type) {
                case 'attack':
                    $this->handleAttackMovement($movement);
                    break;
                case 'support':
                    $this->handleSupportMovement($movement);
                    break;
                case 'raid':
                    $this->handleRaidMovement($movement);
                    break;
                case 'return':
                    $this->handleReturnMovement($movement);
                    break;
            }
        });

        // Clear cache
        $this->clearMovementCache($movement->player);

        return [
            'success' => true,
            'message' => 'Movement completed successfully',
            'movement_type' => $movement->movement_type,
        ];
    }

    /**
     * Cancel movement
     */
    public function cancelMovement(Movement $movement): array
    {
        if ($movement->status !== 'moving') {
            return [
                'success' => false,
                'message' => 'Movement cannot be cancelled',
            ];
        }

        // Calculate return time
        $returnTime = $this->calculateReturnTime($movement);

        DB::transaction(function () use ($movement, $returnTime) {
            // Update movement status
            $movement->update([
                'status' => 'returning',
                'arrives_at' => now()->addSeconds($returnTime),
            ]);
        });

        // Clear cache
        $this->clearMovementCache($movement->player);

        return [
            'success' => true,
            'message' => 'Movement cancelled, troops returning',
            'return_time' => $returnTime,
        ];
    }

    /**
     * Get active movements for player
     */
    public function getActiveMovements(Player $player): array
    {
        $cacheKey = "active_movements:{$player->id}";

        return SmartCache::remember($cacheKey, 300, function () use ($player) {
            return Movement::where('player_id', $player->id)
                ->whereIn('status', ['moving', 'returning'])
                ->with(['fromVillage', 'toVillage'])
                ->orderBy('arrives_at', 'asc')
                ->get()
                ->map(function ($movement) {
                    return [
                        'id' => $movement->id,
                        'reference_number' => $movement->reference_number,
                        'movement_type' => $movement->movement_type,
                        'from_village' => $movement->fromVillage->name,
                        'to_village' => $movement->toVillage->name,
                        'troops' => $movement->troops,
                        'started_at' => $movement->started_at,
                        'arrives_at' => $movement->arrives_at,
                        'status' => $movement->status,
                        'remaining_time' => max(0, $movement->arrives_at->diffInSeconds(now())),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get movement history for player
     */
    public function getMovementHistory(Player $player, int $limit = 50): array
    {
        $cacheKey = "movement_history:{$player->id}:{$limit}";

        return SmartCache::remember($cacheKey, 600, function () use ($player, $limit) {
            return Movement::where('player_id', $player->id)
                ->whereIn('status', ['arrived', 'cancelled'])
                ->with(['fromVillage', 'toVillage'])
                ->orderBy('arrived_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($movement) {
                    return [
                        'id' => $movement->id,
                        'reference_number' => $movement->reference_number,
                        'movement_type' => $movement->movement_type,
                        'from_village' => $movement->fromVillage->name,
                        'to_village' => $movement->toVillage->name,
                        'troops' => $movement->troops,
                        'started_at' => $movement->started_at,
                        'arrived_at' => $movement->arrived_at,
                        'status' => $movement->status,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get incoming movements for village
     */
    public function getIncomingMovements(Village $village): array
    {
        $cacheKey = "incoming_movements:{$village->id}";

        return SmartCache::remember($cacheKey, 300, function () use ($village) {
            return Movement::where('to_village_id', $village->id)
                ->whereIn('status', ['moving', 'returning'])
                ->with(['fromVillage', 'player'])
                ->orderBy('arrives_at', 'asc')
                ->get()
                ->map(function ($movement) {
                    return [
                        'id' => $movement->id,
                        'reference_number' => $movement->reference_number,
                        'movement_type' => $movement->movement_type,
                        'from_village' => $movement->fromVillage->name,
                        'player_name' => $movement->player->name,
                        'troops' => $movement->troops,
                        'arrives_at' => $movement->arrives_at,
                        'status' => $movement->status,
                        'remaining_time' => max(0, $movement->arrives_at->diffInSeconds(now())),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get outgoing movements for village
     */
    public function getOutgoingMovements(Village $village): array
    {
        $cacheKey = "outgoing_movements:{$village->id}";

        return SmartCache::remember($cacheKey, 300, function () use ($village) {
            return Movement::where('from_village_id', $village->id)
                ->whereIn('status', ['moving', 'returning'])
                ->with(['toVillage'])
                ->orderBy('arrives_at', 'asc')
                ->get()
                ->map(function ($movement) {
                    return [
                        'id' => $movement->id,
                        'reference_number' => $movement->reference_number,
                        'movement_type' => $movement->movement_type,
                        'to_village' => $movement->toVillage->name,
                        'troops' => $movement->troops,
                        'arrives_at' => $movement->arrives_at,
                        'status' => $movement->status,
                        'remaining_time' => max(0, $movement->arrives_at->diffInSeconds(now())),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Calculate travel time
     */
    public function calculateTravelTime(Village $fromVillage, Village $toVillage, array $troops): int
    {
        $distance = $this->mapService->calculateDistance(
            $fromVillage->x_coordinate,
            $fromVillage->y_coordinate,
            $toVillage->x_coordinate,
            $toVillage->y_coordinate
        );

        // Get slowest unit speed
        $slowestSpeed = $this->getSlowestUnitSpeed($troops);

        // Base travel time calculation
        $baseTime = $distance * 60; // 1 minute per unit distance
        return (int) ($baseTime / $slowestSpeed);
    }

    /**
     * Calculate return time
     */
    private function calculateReturnTime(Movement $movement): int
    {
        $elapsedTime = $movement->started_at->diffInSeconds(now());
        $totalTime = $movement->started_at->diffInSeconds($movement->arrives_at);
        
        // Return time is the remaining time
        return max(1, $totalTime - $elapsedTime);
    }

    /**
     * Get slowest unit speed
     */
    private function getSlowestUnitSpeed(array $troops): float
    {
        $slowestSpeed = 1.0;

        foreach ($troops as $unitTypeId => $quantity) {
            if ($quantity <= 0) continue;

            $unitType = UnitType::find($unitTypeId);
            if ($unitType && $unitType->speed < $slowestSpeed) {
                $slowestSpeed = $unitType->speed;
            }
        }

        return $slowestSpeed;
    }

    /**
     * Validate movement
     */
    private function validateMovement(Village $fromVillage, Village $toVillage, array $troops, string $movementType): array
    {
        if ($fromVillage->id === $toVillage->id) {
            return [
                'valid' => false,
                'message' => 'Cannot move to the same village',
            ];
        }

        if ($fromVillage->world_id !== $toVillage->world_id) {
            return [
                'valid' => false,
                'message' => 'Cannot move between different worlds',
            ];
        }

        if (empty($troops)) {
            return [
                'valid' => false,
                'message' => 'No troops selected for movement',
            ];
        }

        // Check if village has enough troops
        if (!$this->hasEnoughTroops($fromVillage, $troops)) {
            return [
                'valid' => false,
                'message' => 'Insufficient troops for movement',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check if village has enough troops
     */
    private function hasEnoughTroops(Village $village, array $troops): bool
    {
        $villageTroops = $village->troops()->get()->keyBy('unit_type_id');

        foreach ($troops as $unitTypeId => $quantity) {
            $availableQuantity = $villageTroops->get($unitTypeId)?->quantity ?? 0;
            if ($availableQuantity < $quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove troops from village
     */
    private function removeTroopsFromVillage(Village $village, array $troops): void
    {
        foreach ($troops as $unitTypeId => $quantity) {
            $troop = $village->troops()->where('unit_type_id', $unitTypeId)->first();
            if ($troop) {
                $troop->decrement('quantity', $quantity);
            }
        }
    }

    /**
     * Handle attack movement
     */
    private function handleAttackMovement(Movement $movement): void
    {
        // This would integrate with CombatService
        // For now, just return troops
        $this->returnTroopsToVillage($movement->fromVillage, $movement->troops);
    }

    /**
     * Handle support movement
     */
    private function handleSupportMovement(Movement $movement): void
    {
        // Add troops to target village
        $this->addTroopsToVillage($movement->toVillage, $movement->troops);
    }

    /**
     * Handle raid movement
     */
    private function handleRaidMovement(Movement $movement): void
    {
        // This would integrate with CombatService for raiding
        // For now, just return troops
        $this->returnTroopsToVillage($movement->fromVillage, $movement->troops);
    }

    /**
     * Handle return movement
     */
    private function handleReturnMovement(Movement $movement): void
    {
        // Return troops to original village
        $this->returnTroopsToVillage($movement->fromVillage, $movement->troops);
    }

    /**
     * Add troops to village
     */
    private function addTroopsToVillage(Village $village, array $troops): void
    {
        foreach ($troops as $unitTypeId => $quantity) {
            $troop = $village->troops()->where('unit_type_id', $unitTypeId)->first();
            if ($troop) {
                $troop->increment('quantity', $quantity);
            } else {
                $village->troops()->create([
                    'unit_type_id' => $unitTypeId,
                    'quantity' => $quantity,
                ]);
            }
        }
    }

    /**
     * Return troops to village
     */
    private function returnTroopsToVillage(Village $village, array $troops): void
    {
        $this->addTroopsToVillage($village, $troops);
    }

    /**
     * Clear movement cache
     */
    private function clearMovementCache(Player $player): void
    {
        SmartCache::forget("active_movements:{$player->id}");
        SmartCache::forget("movement_history:{$player->id}:50");
    }
}
