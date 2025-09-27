<?php

namespace App\Services\Game;

use App\Models\Game\Battle;
use App\Models\Game\Village;
use App\Models\Game\Player;
use App\Models\Game\UnitType;
use App\Models\Game\Troop;
use App\Models\Game\Resource;
use App\Services\PerformanceMonitoringService;
use Illuminate\Support\Facades\DB;
use SmartCache\Facades\SmartCache;

class CombatService
{
    public function __construct(
        private ResourceService $resourceService,
        private MovementService $movementService
    ) {}

    /**
     * Execute a battle between attacker and defender
     */
    public function executeBattle(Village $attackerVillage, Village $defenderVillage, array $attackerTroops, array $defenderTroops = null): array
    {
        return PerformanceMonitoringService::monitorQueries(function () use ($attackerVillage, $defenderVillage, $attackerTroops, $defenderTroops) {
            // Get defender troops if not provided
            if ($defenderTroops === null) {
                $defenderTroops = $this->getDefenderTroops($defenderVillage);
            }

        // Calculate battle strength
        $attackerStrength = $this->calculateBattleStrength($attackerTroops, 'attack');
        $defenderStrength = $this->calculateBattleStrength($defenderTroops, 'defense');

        // Apply village bonuses
        $attackerStrength = $this->applyVillageBonuses($attackerVillage, $attackerStrength, 'attack');
        $defenderStrength = $this->applyVillageBonuses($defenderVillage, $defenderStrength, 'defense');

        // Calculate battle result
        $battleResult = $this->calculateBattleResult($attackerStrength, $defenderStrength);

        // Calculate casualties
        $attackerCasualties = $this->calculateCasualties($attackerTroops, $battleResult['attacker_loss_percentage']);
        $defenderCasualties = $this->calculateCasualties($defenderTroops, $battleResult['defender_loss_percentage']);

        // Calculate loot
        $loot = $this->calculateLoot($defenderVillage, $battleResult['success']);

        // Create battle record
        $battle = $this->createBattleRecord($attackerVillage, $defenderVillage, $attackerTroops, $defenderTroops, $attackerCasualties, $defenderCasualties, $loot, $battleResult);

        // Apply battle consequences
        $this->applyBattleConsequences($attackerVillage, $defenderVillage, $attackerCasualties, $defenderCasualties, $loot, $battleResult);

        return [
            'success' => true,
            'battle' => $battle,
            'result' => $battleResult,
            'attacker_casualties' => $attackerCasualties,
            'defender_casualties' => $defenderCasualties,
            'loot' => $loot,
        ];
    }

    /**
     * Calculate battle strength for troops
     */
    public function calculateBattleStrength(array $troops, string $type = 'attack'): int
    {
        $totalStrength = 0;

        foreach ($troops as $unitTypeId => $quantity) {
            if ($quantity <= 0) continue;

            $unitType = UnitType::find($unitTypeId);
            if (!$unitType) continue;

            $strength = $type === 'attack' ? $unitType->attack : $unitType->defense_infantry;
            $totalStrength += $strength * $quantity;
        }

        return $totalStrength;
    }

    /**
     * Apply village bonuses to battle strength
     */
    private function applyVillageBonuses(Village $village, int $strength, string $type): int
    {
        $bonus = 0;

        // Wall bonus for defense
        if ($type === 'defense') {
            $wall = $village->buildings()->where('building_type', 'wall')->first();
            if ($wall) {
                $bonus += $wall->level * 5; // 5% per wall level
            }
        }

        // Hero bonus
        $hero = $village->player->hero;
        if ($hero && $hero->is_active) {
            $bonus += $hero->getBattleBonus($type);
        }

        // Alliance bonus
        if ($village->player->alliance) {
            $bonus += $village->player->alliance->getBattleBonus($type);
        }

        return (int) ($strength * (1 + $bonus / 100));
    }

    /**
     * Calculate battle result
     */
    private function calculateBattleResult(int $attackerStrength, int $defenderStrength): array
    {
        $totalStrength = $attackerStrength + $defenderStrength;
        
        if ($totalStrength === 0) {
            return [
                'success' => false,
                'attacker_loss_percentage' => 0,
                'defender_loss_percentage' => 0,
                'victory_type' => 'draw',
            ];
        }

        $attackerWinChance = $attackerStrength / $totalStrength;
        $success = $attackerWinChance > 0.5;

        // Calculate loss percentages based on strength difference
        $strengthDifference = abs($attackerStrength - $defenderStrength);
        $maxLossPercentage = 80; // Maximum 80% casualties

        if ($success) {
            $attackerLossPercentage = min($maxLossPercentage, $strengthDifference / $attackerStrength * 20);
            $defenderLossPercentage = min($maxLossPercentage, $strengthDifference / $defenderStrength * 30);
        } else {
            $attackerLossPercentage = min($maxLossPercentage, $strengthDifference / $attackerStrength * 30);
            $defenderLossPercentage = min($maxLossPercentage, $strengthDifference / $defenderStrength * 20);
        }

        return [
            'success' => $success,
            'attacker_loss_percentage' => $attackerLossPercentage,
            'defender_loss_percentage' => $defenderLossPercentage,
            'victory_type' => $success ? 'attacker_victory' : 'defender_victory',
            'attacker_strength' => $attackerStrength,
            'defender_strength' => $defenderStrength,
        ];
    }

    /**
     * Calculate casualties for troops
     */
    private function calculateCasualties(array $troops, float $lossPercentage): array
    {
        $casualties = [];

        foreach ($troops as $unitTypeId => $quantity) {
            if ($quantity <= 0) continue;

            $casualties[$unitTypeId] = (int) ($quantity * $lossPercentage / 100);
        }

        return $casualties;
    }

    /**
     * Calculate loot from battle
     */
    private function calculateLoot(Village $defenderVillage, bool $success): array
    {
        if (!$success) {
            return [];
        }

        $resources = $this->resourceService->getVillageResources($defenderVillage);
        $loot = [];

        foreach ($resources as $resource => $amount) {
            // Loot 25% of available resources
            $loot[$resource] = (int) ($amount * 0.25);
        }

        return $loot;
    }

    /**
     * Create battle record
     */
    private function createBattleRecord(Village $attackerVillage, Village $defenderVillage, array $attackerTroops, array $defenderTroops, array $attackerCasualties, array $defenderCasualties, array $loot, array $battleResult): Battle
    {
        return Battle::create([
            'attacker_id' => $attackerVillage->player_id,
            'defender_id' => $defenderVillage->player_id,
            'village_id' => $defenderVillage->id,
            'attacker_troops' => $attackerTroops,
            'defender_troops' => $defenderTroops,
            'attacker_losses' => $attackerCasualties,
            'defender_losses' => $defenderCasualties,
            'loot' => $loot,
            'result' => $battleResult['victory_type'],
            'occurred_at' => now(),
        ]);
    }

    /**
     * Apply battle consequences
     */
    private function applyBattleConsequences(Village $attackerVillage, Village $defenderVillage, array $attackerCasualties, array $defenderCasualties, array $loot, array $battleResult): void
    {
        DB::transaction(function () use ($attackerVillage, $defenderVillage, $attackerCasualties, $defenderCasualties, $loot, $battleResult) {
            // Apply casualties
            $this->applyCasualties($attackerVillage, $attackerCasualties);
            $this->applyCasualties($defenderVillage, $defenderCasualties);

            // Apply loot
            if ($battleResult['success'] && !empty($loot)) {
                $this->resourceService->deductResources($defenderVillage, $loot);
                $this->resourceService->addResources($attackerVillage, $loot);
            }

            // Update village population
            $this->updateVillagePopulation($attackerVillage);
            $this->updateVillagePopulation($defenderVillage);
        });
    }

    /**
     * Apply casualties to village troops
     */
    private function applyCasualties(Village $village, array $casualties): void
    {
        foreach ($casualties as $unitTypeId => $casualtyCount) {
            if ($casualtyCount <= 0) continue;

            $troop = $village->troops()->where('unit_type_id', $unitTypeId)->first();
            if ($troop) {
                $newQuantity = max(0, $troop->quantity - $casualtyCount);
                $troop->update(['quantity' => $newQuantity]);
            }
        }
    }

    /**
     * Update village population based on troops
     */
    private function updateVillagePopulation(Village $village): void
    {
        $totalTroops = $village->troops()->sum('quantity');
        $population = $village->buildings()->sum('level') * 10 + $totalTroops;
        
        $village->update(['population' => $population]);
    }

    /**
     * Get defender troops from village
     */
    private function getDefenderTroops(Village $village): array
    {
        $troops = $village->troops()->with('unitType')->get();
        $defenderTroops = [];

        foreach ($troops as $troop) {
            if ($troop->quantity > 0) {
                $defenderTroops[$troop->unit_type_id] = $troop->quantity;
            }
        }

        return $defenderTroops;
    }

    /**
     * Get battle statistics for a player
     */
    public function getBattleStatistics(Player $player): array
    {
        $cacheKey = "battle_stats:{$player->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($player) {
            $attackerBattles = Battle::where('attacker_id', $player->id)->get();
            $defenderBattles = Battle::where('defender_id', $player->id)->get();

            $totalBattles = $attackerBattles->count() + $defenderBattles->count();
            $victories = $attackerBattles->where('result', 'attacker_victory')->count() + 
                        $defenderBattles->where('result', 'defender_victory')->count();
            $defeats = $totalBattles - $victories;

            return [
                'total_battles' => $totalBattles,
                'victories' => $victories,
                'defeats' => $defeats,
                'victory_rate' => $totalBattles > 0 ? ($victories / $totalBattles) * 100 : 0,
                'total_attacks' => $attackerBattles->count(),
                'total_defenses' => $defenderBattles->count(),
                'attack_victories' => $attackerBattles->where('result', 'attacker_victory')->count(),
                'defense_victories' => $defenderBattles->where('result', 'defender_victory')->count(),
                'total_loot' => $attackerBattles->sum('loot'),
                'total_casualties' => $attackerBattles->sum('attacker_losses') + $defenderBattles->sum('defender_losses'),
            ];
        });
    }

    /**
     * Get recent battles for a player
     */
    public function getRecentBattles(Player $player, int $limit = 20): array
    {
        return Battle::where('attacker_id', $player->id)
            ->orWhere('defender_id', $player->id)
            ->with(['attacker', 'defender', 'village'])
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($battle) use ($player) {
                return [
                    'id' => $battle->id,
                    'reference_number' => $battle->reference_number,
                    'is_attacker' => $battle->attacker_id === $player->id,
                    'opponent' => $battle->attacker_id === $player->id ? $battle->defender->name : $battle->attacker->name,
                    'village' => $battle->village->name,
                    'result' => $battle->result,
                    'occurred_at' => $battle->occurred_at,
                    'loot' => $battle->loot,
                ];
            })
            ->toArray();
    }

    /**
     * Clear battle cache
     */
    public function clearBattleCache(Player $player): void
    {
        SmartCache::forget("battle_stats:{$player->id}");
    }
}
