<?php

namespace App\Services;

use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\ValueObjects\BattleResult;
use App\ValueObjects\Coordinates;
use App\ValueObjects\PlayerStats;
use App\ValueObjects\ResourceAmounts;
use App\ValueObjects\TroopCounts;
use App\ValueObjects\VillageResources;

class ValueObjectService
{
    /**
     * Create VillageResources from a village's resource records
     */
    public function createVillageResources(Village $village): VillageResources
    {
        $resources = $village->resources;

        $amounts = new ResourceAmounts();
        $production = new ResourceAmounts();
        $capacity = new ResourceAmounts();
        $levels = [];

        foreach ($resources as $resource) {
            $type = $resource->type;
            $levels[$type] = $resource->level;

            switch ($type) {
                case 'wood':
                    $amounts = new ResourceAmounts(wood: $resource->amount, clay: $amounts->clay, iron: $amounts->iron, crop: $amounts->crop);
                    $production = new ResourceAmounts(wood: $resource->production_rate, clay: $production->clay, iron: $production->iron, crop: $production->crop);
                    $capacity = new ResourceAmounts(wood: $resource->storage_capacity, clay: $capacity->clay, iron: $capacity->iron, crop: $capacity->crop);

                    break;
                case 'clay':
                    $amounts = new ResourceAmounts(wood: $amounts->wood, clay: $resource->amount, iron: $amounts->iron, crop: $amounts->crop);
                    $production = new ResourceAmounts(wood: $production->wood, clay: $resource->production_rate, iron: $production->iron, crop: $production->crop);
                    $capacity = new ResourceAmounts(wood: $capacity->wood, clay: $resource->storage_capacity, iron: $capacity->iron, crop: $capacity->crop);

                    break;
                case 'iron':
                    $amounts = new ResourceAmounts(wood: $amounts->wood, clay: $amounts->clay, iron: $resource->amount, crop: $amounts->crop);
                    $production = new ResourceAmounts(wood: $production->wood, clay: $production->clay, iron: $resource->production_rate, crop: $production->crop);
                    $capacity = new ResourceAmounts(wood: $capacity->wood, clay: $capacity->clay, iron: $resource->storage_capacity, crop: $capacity->crop);

                    break;
                case 'crop':
                    $amounts = new ResourceAmounts(wood: $amounts->wood, clay: $amounts->clay, iron: $amounts->iron, crop: $resource->amount);
                    $production = new ResourceAmounts(wood: $production->wood, clay: $production->clay, iron: $production->iron, crop: $resource->production_rate);
                    $capacity = new ResourceAmounts(wood: $capacity->wood, clay: $capacity->clay, iron: $capacity->iron, crop: $resource->storage_capacity);

                    break;
            }
        }

        return new VillageResources($amounts, $production, $capacity, $levels);
    }

    /**
     * Create Coordinates from village data
     */
    public function createCoordinatesFromVillage(Village $village): Coordinates
    {
        return new Coordinates(
            x: $village->x_coordinate,
            y: $village->y_coordinate,
            latitude: $village->latitude,
            longitude: $village->longitude,
            elevation: $village->elevation,
            geohash: $village->geohash
        );
    }

    /**
     * Create PlayerStats from player data
     */
    public function createPlayerStatsFromPlayer($player): PlayerStats
    {
        return new PlayerStats(
            points: $player->points,
            population: $player->population,
            villagesCount: $player->villages_count,
            totalAttackPoints: $player->total_attack_points,
            totalDefensePoints: $player->total_defense_points,
            isActive: $player->is_active,
            isOnline: $player->is_online
        );
    }

    /**
     * Create TroopCounts from troops data
     */
    public function createTroopCountsFromTroops(array $troops): TroopCounts
    {
        $counts = new TroopCounts();

        foreach ($troops as $troop) {
            $type = $troop['type'] ?? $troop->type ?? '';
            $quantity = $troop['quantity'] ?? $troop->quantity ?? 0;

            switch ($type) {
                case 'spearman':
                case 'spearmen':
                    $counts = new TroopCounts(spearmen: $quantity, swordsmen: $counts->swordsmen, archers: $counts->archers, cavalry: $counts->cavalry, mountedArchers: $counts->mountedArchers, catapults: $counts->catapults, rams: $counts->rams, spies: $counts->spies, settlers: $counts->settlers);

                    break;
                case 'swordsman':
                case 'swordsmen':
                    $counts = new TroopCounts(spearmen: $counts->spearmen, swordsmen: $quantity, archers: $counts->archers, cavalry: $counts->cavalry, mountedArchers: $counts->mountedArchers, catapults: $counts->catapults, rams: $counts->rams, spies: $counts->spies, settlers: $counts->settlers);

                    break;
                case 'archer':
                case 'archers':
                    $counts = new TroopCounts(spearmen: $counts->spearmen, swordsmen: $counts->swordsmen, archers: $quantity, cavalry: $counts->cavalry, mountedArchers: $counts->mountedArchers, catapults: $counts->catapults, rams: $counts->rams, spies: $counts->spies, settlers: $counts->settlers);

                    break;
                case 'cavalry':
                    $counts = new TroopCounts(spearmen: $counts->spearmen, swordsmen: $counts->swordsmen, archers: $counts->archers, cavalry: $quantity, mountedArchers: $counts->mountedArchers, catapults: $counts->catapults, rams: $counts->rams, spies: $counts->spies, settlers: $counts->settlers);

                    break;
                case 'mounted_archer':
                case 'mounted_archers':
                    $counts = new TroopCounts(spearmen: $counts->spearmen, swordsmen: $counts->swordsmen, archers: $counts->archers, cavalry: $counts->cavalry, mountedArchers: $quantity, catapults: $counts->catapults, rams: $counts->rams, spies: $counts->spies, settlers: $counts->settlers);

                    break;
                case 'catapult':
                case 'catapults':
                    $counts = new TroopCounts(spearmen: $counts->spearmen, swordsmen: $counts->swordsmen, archers: $counts->archers, cavalry: $counts->cavalry, mountedArchers: $counts->mountedArchers, catapults: $quantity, rams: $counts->rams, spies: $counts->spies, settlers: $counts->settlers);

                    break;
                case 'ram':
                case 'rams':
                    $counts = new TroopCounts(spearmen: $counts->spearmen, swordsmen: $counts->swordsmen, archers: $counts->archers, cavalry: $counts->cavalry, mountedArchers: $counts->mountedArchers, catapults: $counts->catapults, rams: $quantity, spies: $counts->spies, settlers: $counts->settlers);

                    break;
                case 'spy':
                case 'spies':
                    $counts = new TroopCounts(spearmen: $counts->spearmen, swordsmen: $counts->swordsmen, archers: $counts->archers, cavalry: $counts->cavalry, mountedArchers: $counts->mountedArchers, catapults: $counts->catapults, rams: $counts->rams, spies: $quantity, settlers: $counts->settlers);

                    break;
                case 'settler':
                case 'settlers':
                    $counts = new TroopCounts(spearmen: $counts->spearmen, swordsmen: $counts->swordsmen, archers: $counts->archers, cavalry: $counts->cavalry, mountedArchers: $counts->mountedArchers, catapults: $counts->catapults, rams: $counts->rams, spies: $counts->spies, settlers: $quantity);

                    break;
            }
        }

        return $counts;
    }

    /**
     * Create BattleResult from battle data
     */
    public function createBattleResultFromBattle(array $battleData): BattleResult
    {
        return new BattleResult(
            status: $battleData['status'] ?? 'draw',
            attackerLosses: $battleData['attacker_losses'] ?? 0,
            defenderLosses: $battleData['defender_losses'] ?? 0,
            loot: isset($battleData['loot']) ? ResourceAmounts::fromArray($battleData['loot']) : new ResourceAmounts(),
            attackerPoints: $battleData['attacker_points'] ?? 0,
            defenderPoints: $battleData['defender_points'] ?? 0,
            battleType: $battleData['battle_type'] ?? null,
            duration: $battleData['duration'] ?? null,
            attackerTroops: $battleData['attacker_troops'] ?? [],
            defenderTroops: $battleData['defender_troops'] ?? []
        );
    }

    /**
     * Update village resources in database
     */
    public function updateVillageResourcesInDatabase(Village $village, VillageResources $resources): void
    {
        $resourceTypes = ['wood', 'clay', 'iron', 'crop'];

        foreach ($resourceTypes as $type) {
            $resource = $village->resources()->where('type', $type)->first();

            if ($resource) {
                $resource->update([
                    'amount' => $resources->amounts->{$type},
                    'production_rate' => $resources->production->{$type},
                    'storage_capacity' => $resources->capacity->{$type},
                    'level' => $resources->getLevel($type),
                    'last_updated' => now(),
                ]);
            }
        }
    }
}
