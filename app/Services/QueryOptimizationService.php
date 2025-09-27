<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use sbamtr\LaravelQueryEnrich\QE;
use function sbamtr\LaravelQueryEnrich\c;

class QueryOptimizationService
{
    /**
     * Apply conditional filters using when() method
     */
    public static function applyConditionalFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $condition => $callback) {
            if (is_callable($callback)) {
                $query->when($condition, $callback);
            } else {
                $query->when($condition, function ($q) use ($callback) {
                    return $q->where($callback);
                });
            }
        }

        return $query;
    }

    /**
     * Create optimized stats query with selectRaw
     */
    public static function createStatsQuery(Model $model, array $stats): Builder
    {
        $selectRaw = [];
        foreach ($stats as $stat) {
            $selectRaw[] = $stat;
        }

        return $model->selectRaw(implode(', ', $selectRaw));
    }

    /**
     * Clone query for reuse
     */
    public static function cloneQuery(Builder $query): Builder
    {
        return clone $query;
    }

    /**
     * Optimize whereHas with subquery selectRaw
     */
    public static function optimizeWhereHas(Builder $query, string $relation, string $column, $value): Builder
    {
        return $query->whereIn($column, function ($subQuery) use ($relation, $value) {
            $subQuery
                ->select('id')
                ->from($relation)
                ->where('name', 'like', '%' . $value . '%');
        });
    }

    /**
     * Create aggregated stats query
     */
    public static function createAggregatedStats(Model $model, array $aggregations): Builder
    {
        $selectRaw = [];
        foreach ($aggregations as $alias => $aggregation) {
            $selectRaw[] = $aggregation . ' as ' . $alias;
        }

        return $model->selectRaw(implode(', ', $selectRaw));
    }

    /**
     * Apply search with multiple fields
     */
    public static function applySearch(Builder $query, string $searchTerm, array $fields): Builder
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm, $fields) {
            return $q->where(function ($subQ) use ($searchTerm, $fields) {
                foreach ($fields as $field) {
                    $subQ->orWhere($field, 'like', '%' . $searchTerm . '%');
                }
            });
        });
    }

    /**
     * Create optimized pagination query
     */
    public static function createPaginatedQuery(Builder $query, int $perPage = 15, array $with = []): Builder
    {
        return $query
            ->with($with)
            ->selectRaw('*, (SELECT COUNT(*) FROM related_table WHERE foreign_key = main_table.id) as related_count');
    }

    /**
     * Optimize N+1 queries with eager loading and selectRaw.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $relations
     * @param  array  $selectRaw
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function optimizeNPlusOne(Builder $query, array $relations = [], array $selectRaw = []): Builder
    {
        if (!empty($relations)) {
            $query->with($relations);
        }

        if (!empty($selectRaw)) {
            $query->selectRaw(implode(', ', $selectRaw));
        }

        return $query;
    }

    /**
     * Apply conditional ordering to a query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sortBy
     * @param  string  $sortOrder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    /**
     * Create conditional ordering
     */
    public static function applyConditionalOrdering(Builder $query, string $sortBy, string $sortOrder = 'asc'): Builder
    {
        return $query->when($sortBy, function ($q) use ($sortBy, $sortOrder) {
            return $q->orderBy($sortBy, $sortOrder);
        });
    }

    /**
     * Apply date range filtering
     */
    public static function applyDateRange(Builder $query, string $column, $startDate = null, $endDate = null): Builder
    {
        return $query->when($startDate, function ($q) use ($column, $startDate) {
            return $q->where($column, '>=', $startDate);
        })->when($endDate, function ($q) use ($column, $endDate) {
            return $q->where($column, '<=', $endDate);
        });
    }

    /**
     * Enhanced stats query using Query Enrich
     */
    public static function createEnhancedStatsQuery(Model $model, array $stats): Builder
    {
        $selectColumns = [];
        foreach ($stats as $alias => $stat) {
            if (is_string($stat)) {
                // Handle raw SQL expressions
                $selectColumns[] = DB::raw($stat . ' as ' . $alias);
            } else {
                // Handle Query Enrich expressions
                $selectColumns[] = $stat->as($alias);
            }
        }

        return $model->select($selectColumns);
    }

    /**
     * Get players active in the last N days using Query Enrich
     */
    public static function getActivePlayers($days = 7, $worldId = null): Builder
    {
        $query = DB::table('players');
        
        if ($worldId) {
            $query->where('world_id', $worldId);
        }
        
        return $query->where(c('last_activity'), '>=', QE::subDate(QE::now(), $days, QE::Unit::DAY));
    }

    /**
     * Get buildings completing construction soon using Query Enrich
     */
    public static function getUpcomingCompletions($hours = 24, $villageId = null): Builder
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
     * Enhanced resource capacity check using Query Enrich
     */
    public static function getResourcesReachingCapacity($hours = 24, $villageId = null): Builder
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
        ->where(function($q) use ($hours) {
            $q->where(QE::add(c('wood'), QE::multiply(c('wood_production_rate'), $hours)), '>=', c('villages.wood_capacity'))
              ->orWhere(QE::add(c('clay'), QE::multiply(c('clay_production_rate'), $hours)), '>=', c('villages.clay_capacity'))
              ->orWhere(QE::add(c('iron'), QE::multiply(c('iron_production_rate'), $hours)), '>=', c('villages.iron_capacity'))
              ->orWhere(QE::add(c('crop'), QE::multiply(c('crop_production_rate'), $hours)), '>=', c('villages.crop_capacity'));
        });
    }

    /**
     * Enhanced player ranking query using Query Enrich
     */
    public static function getPlayerRankings($worldId = null, $limit = 100): Builder
    {
        $query = DB::table('players');
        
        if ($worldId) {
            $query->where('world_id', $worldId);
        }
        
        return $query->select([
            'players.*',
            QE::count(c('villages.id'))->as('village_count'),
            QE::sum(c('villages.population'))->as('total_population'),
            QE::sum(c('villages.points'))->as('total_points'),
            QE::count(
                QE::case()
                    ->when(QE::eq(c('villages.is_capital'), true), c('villages.id'))
                    ->else(null)
            )->as('capital_count')
        ])
        ->leftJoin('villages', 'villages.player_id', '=', 'players.id')
        ->groupBy('players.id')
        ->orderByDesc('total_points')
        ->limit($limit);
    }

    /**
     * Enhanced battle statistics using Query Enrich
     */
    public static function getBattleStatistics($playerId = null, $days = 30): Builder
    {
        $query = DB::table('reports');
        
        if ($playerId) {
            $query->where(function($q) use ($playerId) {
                $q->where('attacker_id', $playerId)
                  ->orWhere('defender_id', $playerId);
            });
        }
        
        $query->where(c('created_at'), '>=', QE::subDate(QE::now(), $days, QE::Unit::DAY));
        
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
            QE::count(
                QE::case()
                    ->when(QE::eq(c('status'), 'draw'), c('id'))
                    ->else(null)
            )->as('draws'),
            QE::sum(c('attacker_losses'))->as('total_attacker_losses'),
            QE::sum(c('defender_losses'))->as('total_defender_losses'),
            QE::avg(c('attacker_losses'))->as('avg_attacker_losses'),
            QE::avg(c('defender_losses'))->as('avg_defender_losses')
        ]);
    }
}
