<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use sbamtr\LaravelQueryEnrich\QE;
use function sbamtr\LaravelQueryEnrich\c;

/**
 * Query Enrich Service for Laravel Game
 * 
 * This service provides enhanced query building capabilities using Laravel Query Enrich
 * to replace raw SQL queries with more readable and maintainable syntax.
 */
class QueryEnrichService
{
    /**
     * Create enhanced player statistics query
     */
    public static function getPlayerStatsQuery($worldId = null): Builder
    {
        $query = DB::table('players');
        
        if ($worldId) {
            $query->where('world_id', $worldId);
        }
        
        return $query->select([
            'players.*',
            QE::count(c('villages.id'))->as('village_count'),
            QE::sum(c('villages.population'))->as('total_population'),
            QE::count(c('reports.id'))->as('total_battles'),
            QE::sum(
                QE::case()
                    ->when(QE::and(
                        QE::eq(c('reports.attacker_id'), c('players.id')),
                        QE::eq(c('reports.status'), 'victory')
                    ), 1)
                    ->else(0)
            )->as('total_victories')
        ])
        ->leftJoin('villages', 'villages.player_id', '=', 'players.id')
        ->leftJoin('reports', function($join) {
            $join->on('reports.attacker_id', '=', 'players.id')
                 ->orOn('reports.defender_id', '=', 'players.id');
        })
        ->groupBy('players.id');
    }

    /**
     * Create enhanced quest statistics query
     */
    public static function getQuestStatsQuery($playerId = null): Builder
    {
        $query = DB::table('quests');
        
        $selectColumns = [
            'quests.*',
            QE::count(c('player_quests.id'))->as('total_players'),
            QE::count(
                QE::case()
                    ->when(QE::eq(c('player_quests.status'), 'completed'), c('player_quests.id'))
                    ->else(null)
            )->as('completed_count'),
            QE::avg(c('player_quests.progress'))->as('avg_progress')
        ];
        
        if ($playerId) {
            $selectColumns[] = QE::select('status')
                ->from('player_quests')
                ->whereColumn('quest_id', c('quests.id'))
                ->where('player_id', $playerId)
                ->limit(1)
                ->as('player_status');
                
            $selectColumns[] = QE::select('progress')
                ->from('player_quests')
                ->whereColumn('quest_id', c('quests.id'))
                ->where('player_id', $playerId)
                ->limit(1)
                ->as('player_progress');
        }
        
        return $query->select($selectColumns)
            ->leftJoin('player_quests', 'player_quests.quest_id', '=', 'quests.id')
            ->groupBy('quests.id');
    }

    /**
     * Create enhanced village statistics query
     */
    public static function getVillageStatsQuery($playerId = null): Builder
    {
        $query = DB::table('villages');
        
        if ($playerId) {
            $query->where('player_id', $playerId);
        }
        
        return $query->select([
            'villages.*',
            QE::sum(c('resources.wood'))->as('total_wood'),
            QE::sum(c('resources.clay'))->as('total_clay'),
            QE::sum(c('resources.iron'))->as('total_iron'),
            QE::sum(c('resources.crop'))->as('total_crop'),
            QE::count(c('buildings.id'))->as('total_buildings'),
            QE::avg(c('buildings.level'))->as('avg_building_level')
        ])
        ->leftJoin('resources', 'resources.village_id', '=', 'villages.id')
        ->leftJoin('buildings', 'buildings.village_id', '=', 'villages.id')
        ->groupBy('villages.id');
    }

    /**
     * Create enhanced battle statistics query
     */
    public static function getBattleStatsQuery($playerId = null): Builder
    {
        $query = DB::table('reports');
        
        if ($playerId) {
            $query->where(function($q) use ($playerId) {
                $q->where('attacker_id', $playerId)
                  ->orWhere('defender_id', $playerId);
            });
        }
        
        return $query->select([
            QE::count(c('id'))->as('total_battles'),
            QE::count(
                QE::case()
                    ->when(QE::eq(c('status'), 'victory'), c('id'))
                    ->else(null)
            )->as('victories'),
            QE::count(
                QE::case()
                    ->when(QE::eq(c('status'), 'defeat'), c('id'))
                    ->else(null)
            )->as('defeats'),
            QE::sum(c('attacker_losses'))->as('total_attacker_losses'),
            QE::sum(c('defender_losses'))->as('total_defender_losses'),
            QE::avg(c('attacker_losses'))->as('avg_attacker_losses'),
            QE::avg(c('defender_losses'))->as('avg_defender_losses')
        ]);
    }

    /**
     * Create enhanced resource production query
     */
    public static function getResourceProductionQuery($villageId = null): Builder
    {
        $query = DB::table('villages');
        
        if ($villageId) {
            $query->where('id', $villageId);
        }
        
        return $query->select([
            'villages.*',
            QE::sum(
                QE::case()
                    ->when(QE::eq(c('buildings.type'), 'woodcutter'), c('buildings.production_rate'))
                    ->else(0)
            )->as('wood_production'),
            QE::sum(
                QE::case()
                    ->when(QE::eq(c('buildings.type'), 'clay_pit'), c('buildings.production_rate'))
                    ->else(0)
            )->as('clay_production'),
            QE::sum(
                QE::case()
                    ->when(QE::eq(c('buildings.type'), 'iron_mine'), c('buildings.production_rate'))
                    ->else(0)
            )->as('iron_production'),
            QE::sum(
                QE::case()
                    ->when(QE::eq(c('buildings.type'), 'cropland'), c('buildings.production_rate'))
                    ->else(0)
            )->as('crop_production')
        ])
        ->leftJoin('buildings', 'buildings.village_id', '=', 'villages.id')
        ->groupBy('villages.id');
    }

    /**
     * Create enhanced alliance statistics query
     */
    public static function getAllianceStatsQuery($allianceId = null): Builder
    {
        $query = DB::table('alliances');
        
        if ($allianceId) {
            $query->where('id', $allianceId);
        }
        
        return $query->select([
            'alliances.*',
            QE::count(c('alliance_members.id'))->as('member_count'),
            QE::sum(c('players.total_population'))->as('total_population'),
            QE::avg(c('players.total_population'))->as('avg_member_population'),
            QE::count(
                QE::case()
                    ->when(QE::eq(c('players.is_online'), true), c('players.id'))
                    ->else(null)
            )->as('online_members')
        ])
        ->leftJoin('alliance_members', 'alliance_members.alliance_id', '=', 'alliances.id')
        ->leftJoin('players', 'players.id', '=', 'alliance_members.player_id')
        ->groupBy('alliances.id');
    }

    /**
     * Create enhanced market statistics query
     */
    public static function getMarketStatsQuery($villageId = null): Builder
    {
        $query = DB::table('market_offers');
        
        if ($villageId) {
            $query->where('village_id', $villageId);
        }
        
        return $query->select([
            QE::count(c('id'))->as('total_offers'),
            QE::sum(
                QE::case()
                    ->when(QE::eq(c('resource_type'), 'wood'), c('amount'))
                    ->else(0)
            )->as('total_wood_offers'),
            QE::sum(
                QE::case()
                    ->when(QE::eq(c('resource_type'), 'clay'), c('amount'))
                    ->else(0)
            )->as('total_clay_offers'),
            QE::sum(
                QE::case()
                    ->when(QE::eq(c('resource_type'), 'iron'), c('amount'))
                    ->else(0)
            )->as('total_iron_offers'),
            QE::sum(
                QE::case()
                    ->when(QE::eq(c('resource_type'), 'crop'), c('amount'))
                    ->else(0)
            )->as('total_crop_offers'),
            QE::avg(c('price_per_unit'))->as('avg_price_per_unit')
        ]);
    }

    /**
     * Get players active in the last N days
     */
    public static function getActivePlayersQuery($days = 7, $worldId = null): Builder
    {
        $query = DB::table('players');
        
        if ($worldId) {
            $query->where('world_id', $worldId);
        }
        
        return $query->where(c('last_activity'), '>=', QE::subDate(QE::now(), $days, QE::Unit::DAY));
    }

    /**
     * Get buildings that will finish construction in the next N hours
     */
    public static function getUpcomingCompletionsQuery($hours = 24, $villageId = null): Builder
    {
        $query = DB::table('buildings');
        
        if ($villageId) {
            $query->where('village_id', $villageId);
        }
        
        return $query->where(c('construction_completed_at'), '<=', QE::addDate(QE::now(), $hours, QE::Unit::HOUR))
            ->where(c('construction_completed_at'), '>', QE::now())
            ->whereNotNull('construction_completed_at');
    }

    /**
     * Get resources that will be full in the next N hours
     */
    public static function getResourcesReachingCapacityQuery($hours = 24, $villageId = null): Builder
    {
        $query = DB::table('resources');
        
        if ($villageId) {
            $query->where('village_id', $villageId);
        }
        
        return $query->select([
            'resources.*',
            'villages.wood_capacity',
            'villages.clay_capacity', 
            'villages.iron_capacity',
            'villages.crop_capacity',
            QE::add(c('wood'), QE::multiply(c('wood_production_rate'), $hours))->as('projected_wood'),
            QE::add(c('clay'), QE::multiply(c('clay_production_rate'), $hours))->as('projected_clay'),
            QE::add(c('iron'), QE::multiply(c('iron_production_rate'), $hours))->as('projected_iron'),
            QE::add(c('crop'), QE::multiply(c('crop_production_rate'), $hours))->as('projected_crop')
        ])
        ->leftJoin('villages', 'villages.id', '=', 'resources.village_id')
        ->where(function($q) {
            $q->whereRaw('(wood + (wood_production_rate * ?)) >= wood_capacity', [$hours])
              ->orWhereRaw('(clay + (clay_production_rate * ?)) >= clay_capacity', [$hours])
              ->orWhereRaw('(iron + (iron_production_rate * ?)) >= iron_capacity', [$hours])
              ->orWhereRaw('(crop + (crop_production_rate * ?)) >= crop_capacity', [$hours]);
        });
    }
}
