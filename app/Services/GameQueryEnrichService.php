<?php

namespace App\Services;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\Report;
use App\Models\Game\Building;
use App\Models\Game\Troop;
use App\Models\Game\Quest;
use App\Models\Game\Alliance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use sbamtr\LaravelQueryEnrich\QE;
use function sbamtr\LaravelQueryEnrich\c;

/**
 * Game Query Enrich Service
 * 
 * This service provides game-specific query methods using Laravel Query Enrich
 * for enhanced readability and maintainability of complex database queries.
 */
class GameQueryEnrichService
{
    /**
     * Get comprehensive player dashboard data
     */
    public static function getPlayerDashboardData($playerId, $worldId = null): array
    {
        $player = Player::where('id', $playerId)
            ->when($worldId, function($q) use ($worldId) {
                return $q->where('world_id', $worldId);
            })
            ->withStats()
            ->with(['alliance:id,name', 'villages'])
            ->first();

        if (!$player) {
            return [];
        }

        // Get village statistics
        $villageStats = Village::where('player_id', $playerId)
            ->select([
                QE::count(c('id'))->as('total_villages'),
                QE::sum(c('population'))->as('total_population'),
                QE::avg(c('population'))->as('avg_population'),
                QE::sum(c('points'))->as('total_points'),
                QE::max(c('population'))->as('largest_village'),
                QE::min(c('population'))->as('smallest_village')
            ])
            ->first();

        // Get resource totals
        $resourceStats = DB::table('villages')
            ->join('resources', 'resources.village_id', '=', 'villages.id')
            ->where('villages.player_id', $playerId)
            ->select([
                QE::sum(c('resources.wood'))->as('total_wood'),
                QE::sum(c('resources.clay'))->as('total_clay'),
                QE::sum(c('resources.iron'))->as('total_iron'),
                QE::sum(c('resources.crop'))->as('total_crop'),
                QE::sum(c('resources.wood_production'))->as('wood_production'),
                QE::sum(c('resources.clay_production'))->as('clay_production'),
                QE::sum(c('resources.iron_production'))->as('iron_production'),
                QE::sum(c('resources.crop_production'))->as('crop_production')
            ])
            ->first();

        // Get battle statistics
        $battleStats = Report::where('world_id', $worldId ?? $player->world_id)
            ->where(function($q) use ($playerId) {
                $q->where('attacker_id', $playerId)
                  ->orWhere('defender_id', $playerId);
            })
            ->select([
                QE::count(c('id'))->as('total_battles'),
                QE::count(QE::case()
                    ->when(QE::eq(c('status'), 'victory'), c('id'))
                    ->else(null))
                    ->as('victories'),
                QE::count(QE::case()
                    ->when(QE::eq(c('status'), 'defeat'), c('id'))
                    ->else(null))
                    ->as('defeats'),
                QE::sum(QE::case()
                    ->when(QE::eq(c('attacker_id'), $playerId), c('attacker_losses'))
                    ->else(c('defender_losses')))
                    ->as('total_losses'),
                QE::sum(QE::case()
                    ->when(QE::eq(c('defender_id'), $playerId), c('defender_losses'))
                    ->else(c('attacker_losses')))
                    ->as('total_inflicted_losses')
            ])
            ->first();

        return [
            'player' => $player,
            'village_stats' => $villageStats,
            'resource_stats' => $resourceStats,
            'battle_stats' => $battleStats
        ];
    }

    /**
     * Get world leaderboard data
     */
    public static function getWorldLeaderboard($worldId, $limit = 100): Builder
    {
        return Player::where('world_id', $worldId)
            ->select([
                'players.*',
                QE::count(c('villages.id'))->as('village_count'),
                QE::sum(c('villages.population'))->as('total_population'),
                QE::sum(c('villages.points'))->as('total_points'),
                QE::max(c('villages.population'))->as('largest_village'),
                QE::count(QE::case()
                    ->when(QE::eq(c('villages.is_capital'), true), c('villages.id'))
                    ->else(null))
                    ->as('capital_count')
            ])
            ->leftJoin('villages', 'villages.player_id', '=', 'players.id')
            ->groupBy('players.id')
            ->orderByDesc('total_points')
            ->limit($limit);
    }

    /**
     * Get alliance statistics
     */
    public static function getAllianceStats($allianceId): Builder
    {
        return Alliance::where('id', $allianceId)
            ->select([
                'alliances.*',
                QE::count(c('alliance_members.id'))->as('member_count'),
                QE::sum(c('players.total_population'))->as('total_population'),
                QE::avg(c('players.total_population'))->as('avg_member_population'),
                QE::sum(c('players.total_points'))->as('total_points'),
                QE::count(QE::case()
                    ->when(QE::eq(c('players.is_online'), true), c('players.id'))
                    ->else(null))
                    ->as('online_members'),
                QE::count(QE::case()
                    ->when(QE::eq(c('players.is_active'), true), c('players.id'))
                    ->else(null))
                    ->as('active_members')
            ])
            ->leftJoin('alliance_members', 'alliance_members.alliance_id', '=', 'alliances.id')
            ->leftJoin('players', 'players.id', '=', 'alliance_members.player_id')
            ->groupBy('alliances.id');
    }

    /**
     * Get village production analysis
     */
    public static function getVillageProductionAnalysis($playerId = null, $villageId = null): Builder
    {
        $query = Village::query();

        if ($playerId) {
            $query->where('player_id', $playerId);
        }

        if ($villageId) {
            $query->where('id', $villageId);
        }

        return $query->select([
            'villages.*',
            QE::sum(c('resources.wood_production'))->as('wood_production'),
            QE::sum(c('resources.clay_production'))->as('clay_production'),
            QE::sum(c('resources.iron_production'))->as('iron_production'),
            QE::sum(c('resources.crop_production'))->as('crop_production'),
            QE::sum(QE::add(QE::add(c('resources.wood_production'), c('resources.clay_production')), 
                          QE::add(c('resources.iron_production'), c('resources.crop_production'))))
                ->as('total_production'),
            QE::avg(QE::add(QE::add(c('resources.wood_production'), c('resources.clay_production')), 
                          QE::add(c('resources.iron_production'), c('resources.crop_production'))))
                ->as('avg_production_per_village')
        ])
        ->leftJoin('resources', 'resources.village_id', '=', 'villages.id')
        ->groupBy('villages.id');
    }

    /**
     * Get building statistics for player
     */
    public static function getBuildingStatistics($playerId): Builder
    {
        return Building::join('villages', 'villages.id', '=', 'buildings.village_id')
            ->where('villages.player_id', $playerId)
            ->select([
                'buildings.type',
                QE::count(c('buildings.id'))->as('total_buildings'),
                QE::avg(c('buildings.level'))->as('avg_level'),
                QE::max(c('buildings.level'))->as('max_level'),
                QE::min(c('buildings.level'))->as('min_level'),
                QE::sum(QE::case()
                    ->when(QE::eq(c('buildings.level'), 20), 1)
                    ->else(0))
                    ->as('maxed_buildings'),
                QE::count(QE::case()
                    ->when(QE::notNull(c('buildings.construction_completed_at')), c('buildings.id'))
                    ->else(null))
                    ->as('under_construction')
            ])
            ->groupBy('buildings.type')
            ->orderByDesc('total_buildings');
    }

    /**
     * Get troop statistics for player
     */
    public static function getTroopStatistics($playerId): Builder
    {
        return Troop::join('villages', 'villages.id', '=', 'troops.village_id')
            ->join('unit_types', 'unit_types.id', '=', 'troops.unit_type_id')
            ->where('villages.player_id', $playerId)
            ->select([
                'unit_types.name as unit_name',
                'unit_types.attack',
                'unit_types.defense',
                QE::sum(c('troops.quantity'))->as('total_quantity'),
                QE::avg(c('troops.quantity'))->as('avg_quantity_per_village'),
                QE::max(c('troops.quantity'))->as('max_quantity_in_village'),
                QE::count(QE::case()
                    ->when(QE::gt(c('troops.quantity'), 0), c('troops.id'))
                    ->else(null))
                    ->as('villages_with_units')
            ])
            ->groupBy('unit_types.id', 'unit_types.name', 'unit_types.attack', 'unit_types.defense')
            ->orderByDesc('total_quantity');
    }

    /**
     * Get quest completion statistics
     */
    public static function getQuestStatistics($playerId = null, $worldId = null): Builder
    {
        $query = Quest::query();

        if ($worldId) {
            $query->where('world_id', $worldId);
        }

        return $query->select([
            'quests.*',
            QE::count(c('player_quests.id'))->as('total_attempts'),
            QE::count(QE::case()
                ->when(QE::eq(c('player_quests.status'), 'completed'), c('player_quests.id'))
                ->else(null))
                ->as('completed_attempts'),
            QE::avg(QE::case()
                ->when(QE::eq(c('player_quests.status'), 'completed'), c('player_quests.progress'))
                ->else(null))
                ->as('avg_completion_progress'),
            QE::count(QE::case()
                ->when(QE::eq(c('player_quests.player_id'), $playerId), c('player_quests.id'))
                ->else(null))
                ->as('player_attempts'),
            QE::select(c('status'))
                ->from('player_quests')
                ->whereColumn('quest_id', c('quests.id'))
                ->where('player_id', $playerId)
                ->limit(1)
                ->as('player_status')
        ])
        ->leftJoin('player_quests', 'player_quests.quest_id', '=', 'quests.id')
        ->groupBy('quests.id')
        ->when($playerId, function($q) {
            return $q->havingRaw('player_attempts > 0');
        });
    }

    /**
     * Get market activity statistics
     */
    public static function getMarketStatistics($worldId = null, $days = 30): Builder
    {
        $query = DB::table('market_offers');

        if ($worldId) {
            $query->join('villages', 'villages.id', '=', 'market_offers.village_id')
                  ->where('villages.world_id', $worldId);
        }

        return $query->select([
            QE::count(c('id'))->as('total_offers'),
            QE::sum(QE::case()
                ->when(QE::eq(c('resource_type'), 'wood'), c('amount'))
                ->else(0))
                ->as('wood_offers'),
            QE::sum(QE::case()
                ->when(QE::eq(c('resource_type'), 'clay'), c('amount'))
                ->else(0))
                ->as('clay_offers'),
            QE::sum(QE::case()
                ->when(QE::eq(c('resource_type'), 'iron'), c('amount'))
                ->else(0))
                ->as('iron_offers'),
            QE::sum(QE::case()
                ->when(QE::eq(c('resource_type'), 'crop'), c('amount'))
                ->else(0))
                ->as('crop_offers'),
            QE::avg(c('price_per_unit'))->as('avg_price_per_unit'),
            QE::max(c('price_per_unit'))->as('max_price_per_unit'),
            QE::min(c('price_per_unit'))->as('min_price_per_unit')
        ])
        ->where(c('created_at'), '>=', QE::subDate(QE::now(), $days, QE::Unit::DAY));
    }

    /**
     * Get players by activity level
     */
    public static function getPlayersByActivity($worldId, $days = 7): Builder
    {
        return Player::where('world_id', $worldId)
            ->select([
                'players.*',
                QE::count(c('villages.id'))->as('village_count'),
                QE::sum(c('villages.population'))->as('total_population'),
                QE::select(QE::count(c('id')))
                    ->from('reports')
                    ->where(function($q) {
                        $q->whereColumn('attacker_id', c('players.id'))
                          ->orWhereColumn('defender_id', c('players.id'));
                    })
                    ->where(c('created_at'), '>=', QE::subDate(QE::now(), $days, QE::Unit::DAY))
                    ->as('recent_activity')
            ])
            ->leftJoin('villages', 'villages.player_id', '=', 'players.id')
            ->groupBy('players.id')
            ->orderByDesc('recent_activity')
            ->orderByDesc('total_population');
    }

    /**
     * Get resource capacity warnings
     */
    public static function getResourceCapacityWarnings($playerId, $hours = 24): Builder
    {
        return DB::table('resources')
            ->join('villages', 'villages.id', '=', 'resources.village_id')
            ->where('villages.player_id', $playerId)
            ->select([
                'resources.*',
                'villages.name as village_name',
                'villages.wood_capacity',
                'villages.clay_capacity',
                'villages.iron_capacity',
                'villages.crop_capacity',
                QE::add(c('wood'), QE::multiply(c('wood_production'), $hours))->as('projected_wood'),
                QE::add(c('clay'), QE::multiply(c('clay_production'), $hours))->as('projected_clay'),
                QE::add(c('iron'), QE::multiply(c('iron_production'), $hours))->as('projected_iron'),
                QE::add(c('crop'), QE::multiply(c('crop_production'), $hours))->as('projected_crop')
            ])
            ->where(function($q) use ($hours) {
                $q->where(QE::add(c('wood'), QE::multiply(c('wood_production'), $hours)), '>=', c('villages.wood_capacity'))
                  ->orWhere(QE::add(c('clay'), QE::multiply(c('clay_production'), $hours)), '>=', c('villages.clay_capacity'))
                  ->orWhere(QE::add(c('iron'), QE::multiply(c('iron_production'), $hours)), '>=', c('villages.iron_capacity'))
                  ->orWhere(QE::add(c('crop'), QE::multiply(c('crop_production'), $hours)), '>=', c('villages.crop_capacity'));
            });
    }
}

