<?php

namespace App\Services;

use App\Models\Game\Alliance;
use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\Game\Task;
use App\Models\Game\Technology;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

use function sbamtr\LaravelQueryEnrich\c;

use sbamtr\LaravelQueryEnrich\QE;

/**
 * Query Enrich Game Service
 *
 * Provides game-specific query methods using Laravel Query Enrich
 * for enhanced readability and maintainability of complex database queries.
 */
class QueryEnrichGameService
{
    /**
     * Get player statistics with comprehensive data
     */
    public static function getPlayerStatistics($playerId, $worldId = null): Builder
    {
        $query = Player::where('id', $playerId);

        if ($worldId) {
            $query->where('world_id', $worldId);
        }

        return $query->select([
            'players.*',
            QE::count(c('villages.id'))->as('village_count'),
            QE::sum(c('villages.population'))->as('total_population'),
            QE::avg(c('villages.population'))->as('avg_village_population'),
            QE::max(c('villages.population'))->as('largest_village'),
            QE::count(QE::case()
                ->when(QE::eq(c('villages.is_capital'), true), c('villages.id'))
                ->else(null))
                ->as('capital_count'),
            QE::count(QE::case()
                ->when(QE::eq(c('villages.is_active'), true), c('villages.id'))
                ->else(null))
                ->as('active_villages'),
        ])
            ->leftJoin('villages', 'villages.player_id', '=', 'players.id')
            ->groupBy('players.id');
    }

    /**
     * Get alliance performance metrics
     */
    public static function getAlliancePerformance($allianceId): Builder
    {
        return Alliance::where('id', $allianceId)
            ->select([
                'alliances.*',
                QE::count(c('alliance_members.id'))->as('member_count'),
                QE::sum(QE::select(QE::sum(c('points')))
                    ->from('players')
                    ->join('alliance_members', 'alliance_members.player_id', '=', 'players.id')
                    ->whereColumn('alliance_members.alliance_id', c('alliances.id'))
                    ->as('total_points')),
                QE::avg(QE::select(QE::count(c('id')))
                    ->from('villages')
                    ->join('players', 'players.id', '=', 'villages.player_id')
                    ->join('alliance_members', 'alliance_members.player_id', '=', 'players.id')
                    ->whereColumn('alliance_members.alliance_id', c('alliances.id'))
                    ->as('avg_villages_per_member')),
                QE::count(QE::case()
                    ->when(QE::eq(c('players.is_online'), true), c('players.id'))
                    ->else(null))
                    ->as('online_members'),
            ])
            ->leftJoin('alliance_members', 'alliance_members.alliance_id', '=', 'alliances.id')
            ->leftJoin('players', 'players.id', '=', 'alliance_members.player_id')
            ->groupBy('alliances.id');
    }

    /**
     * Get village efficiency analysis
     */
    public static function getVillageEfficiency($villageId): Builder
    {
        return Village::where('id', $villageId)
            ->select([
                'villages.*',
                QE::select(QE::sum(QE::add(
                    QE::add(c('wood_production'), c('clay_production')),
                    QE::add(c('iron_production'), c('crop_production'))
                )))
                    ->from('resources')
                    ->whereColumn('village_id', c('villages.id'))
                    ->as('total_production'),
                QE::select(QE::count(c('id')))
                    ->from('buildings')
                    ->whereColumn('village_id', c('villages.id'))
                    ->where('is_active', '=', true)
                    ->as('active_buildings'),
                QE::select(QE::avg(c('level')))
                    ->from('buildings')
                    ->whereColumn('village_id', c('villages.id'))
                    ->where('is_active', '=', true)
                    ->as('avg_building_level'),
                QE::select(QE::sum(c('quantity')))
                    ->from('troops')
                    ->whereColumn('village_id', c('villages.id'))
                    ->as('total_troops'),
            ]);
    }

    /**
     * Get technology research statistics
     */
    public static function getTechnologyResearchStats($worldId = null): Builder
    {
        $query = Technology::query();

        if ($worldId) {
            $query->where('world_id', $worldId);
        }

        return $query->select([
            'technologies.*',
            QE::count(c('player_technologies.id'))->as('research_count'),
            QE::avg(c('player_technologies.level'))->as('avg_level'),
            QE::max(c('player_technologies.level'))->as('max_level'),
            QE::count(QE::case()
                ->when(QE::eq(c('player_technologies.status'), 'completed'), c('player_technologies.id'))
                ->else(null))
                ->as('completed_count'),
            QE::count(QE::case()
                ->when(QE::eq(c('player_technologies.status'), 'researching'), c('player_technologies.id'))
                ->else(null))
                ->as('researching_count'),
        ])
            ->leftJoin('player_technologies', 'player_technologies.technology_id', '=', 'technologies.id')
            ->groupBy('technologies.id')
            ->orderByDesc('research_count');
    }

    /**
     * Get task completion statistics
     */
    public static function getTaskCompletionStats($playerId = null, $worldId = null): Builder
    {
        $query = Task::query();

        if ($playerId) {
            $query->where('player_id', $playerId);
        }

        if ($worldId) {
            $query->where('world_id', $worldId);
        }

        return $query->select([
            'player_tasks.*',
            QE::count(QE::case()
                ->when(QE::eq(c('status'), 'active'), c('id'))
                ->else(null))
                ->as('active_tasks'),
            QE::count(QE::case()
                ->when(QE::eq(c('status'), 'completed'), c('id'))
                ->else(null))
                ->as('completed_tasks'),
            QE::avg(QE::case()
                ->when(QE::eq(c('status'), 'active'), c('progress'))
                ->else(null))
                ->as('avg_progress'),
            QE::count(QE::case()
                ->when(QE::and(
                    QE::notNull(c('deadline')),
                    QE::lt(c('deadline'), QE::now())
                ), c('id'))
                ->else(null))
                ->as('overdue_tasks'),
        ])
            ->groupBy('player_tasks.id');
    }

    /**
     * Get movement analytics
     */
    public static function getMovementAnalytics($playerId = null, $days = 7): Builder
    {
        $query = Movement::where(c('created_at'), '>=', QE::subDate(QE::now(), $days, QE::Unit::DAY));

        if ($playerId) {
            $query->where('player_id', $playerId);
        }

        return $query->select([
            'movements.*',
            QE::select(c('name'))
                ->from('villages')
                ->whereColumn('id', c('movements.from_village_id'))
                ->as('from_village_name'),
            QE::select(c('name'))
                ->from('villages')
                ->whereColumn('id', c('movements.to_village_id'))
                ->as('to_village_name'),
            QE::select(c('name'))
                ->from('players')
                ->whereColumn('id', c('movements.player_id'))
                ->as('player_name'),
            QE::count(QE::case()
                ->when(QE::eq(c('type'), 'attack'), c('id'))
                ->else(null))
                ->as('attack_count'),
            QE::count(QE::case()
                ->when(QE::eq(c('type'), 'support'), c('id'))
                ->else(null))
                ->as('support_count'),
        ])
            ->orderByDesc('created_at');
    }

    /**
     * Get world leaderboard with enhanced statistics
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
                    ->as('capital_count'),
                QE::select(QE::count(c('id')))
                    ->from('reports')
                    ->where(function ($q): void {
                        $q->whereColumn('attacker_id', c('players.id'))
                            ->orWhereColumn('defender_id', c('players.id'));
                    })
                    ->as('total_battles'),
            ])
            ->leftJoin('villages', 'villages.player_id', '=', 'players.id')
            ->groupBy('players.id')
            ->orderByDesc('total_points')
            ->limit($limit);
    }

    /**
     * Get resource production analysis
     */
    public static function getResourceProductionAnalysis($worldId = null): Builder
    {
        $query = DB::table('villages')
            ->join('resources', 'resources.village_id', '=', 'villages.id');

        if ($worldId) {
            $query->where('villages.world_id', $worldId);
        }

        return $query->select([
            'villages.id as village_id',
            'villages.name as village_name',
            'villages.player_id',
            QE::sum(c('resources.wood_production'))->as('wood_production'),
            QE::sum(c('resources.clay_production'))->as('clay_production'),
            QE::sum(c('resources.iron_production'))->as('iron_production'),
            QE::sum(c('resources.crop_production'))->as('crop_production'),
            QE::add(
                QE::add(c('resources.wood_production'), c('resources.clay_production')),
                QE::add(c('resources.iron_production'), c('resources.crop_production'))
            )
                ->as('total_production'),
        ])
            ->groupBy('villages.id', 'villages.name', 'villages.player_id')
            ->orderByDesc('total_production');
    }

    /**
     * Get battle statistics for player
     */
    public static function getPlayerBattleStats($playerId, $days = 30): Builder
    {
        return Report::where(c('created_at'), '>=', QE::subDate(QE::now(), $days, QE::Unit::DAY))
            ->where(function ($q) use ($playerId): void {
                $q->where('attacker_id', $playerId)
                    ->orWhere('defender_id', $playerId);
            })
            ->select([
                QE::count(c('id'))->as('total_battles'),
                QE::count(QE::case()
                    ->when(QE::and(
                        QE::eq(c('attacker_id'), $playerId),
                        QE::eq(c('status'), 'victory')
                    ), c('id'))
                    ->else(null))
                    ->as('attack_victories'),
                QE::count(QE::case()
                    ->when(QE::and(
                        QE::eq(c('defender_id'), $playerId),
                        QE::eq(c('status'), 'victory')
                    ), c('id'))
                    ->else(null))
                    ->as('defense_victories'),
                QE::sum(QE::add(c('attacker_losses'), c('defender_losses')))
                    ->as('total_losses'),
                QE::avg(QE::add(c('attacker_losses'), c('defender_losses')))
                    ->as('avg_losses_per_battle'),
            ])
            ->groupBy('id')
            ->first();
    }
}
