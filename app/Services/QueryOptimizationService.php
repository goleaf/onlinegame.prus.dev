<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
            $subQuery->select('id')
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
        return $query->with($with)
            ->selectRaw('*, (SELECT COUNT(*) FROM related_table WHERE foreign_key = main_table.id) as related_count');
    }

    /**
     * Optimize N+1 queries with selectRaw subqueries
     */
    public static function optimizeNPlusOne(Builder $query, array $subqueries): Builder
    {
        $selectRaw = [];
        foreach ($subqueries as $alias => $subquery) {
            $selectRaw[] = '(' . $subquery . ') as ' . $alias;
        }

        return $query->selectRaw(implode(', ', $selectRaw));
    }

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
}
