<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Notable\Traits\HasNotables;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use SmartCache\Facades\SmartCache;

class Resource extends Model implements Auditable
{
    use AuditableTrait;
    use HasFactory;
    use HasNotables;
    // use Lift;

    // Laravel Lift typed properties
    public int $id;

    public int $village_id;

    public string $type;

    public int $amount;

    public float $production_rate;

    public int $storage_capacity;

    public int $level;

    public ?\Carbon\Carbon $last_updated;

    public \Carbon\CarbonImmutable $created_at;

    public \Carbon\CarbonImmutable $updated_at;

    protected $fillable = [
        'village_id',
        'type',
        'amount',
        'production_rate',
        'storage_capacity',
        'level',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            resources.*,
            (SELECT COUNT(*) FROM resources r2 WHERE r2.village_id = resources.village_id) as total_resources_in_village,
            (SELECT SUM(amount) FROM resources r3 WHERE r3.village_id = resources.village_id) as total_amount_in_village,
            (SELECT SUM(production_rate) FROM resources r4 WHERE r4.village_id = resources.village_id) as total_production_in_village,
            (SELECT SUM(storage_capacity) FROM resources r5 WHERE r5.village_id = resources.village_id) as total_capacity_in_village,
            (SELECT AVG(production_rate) FROM resources r6 WHERE r6.village_id = resources.village_id) as avg_production_rate,
            (SELECT MAX(production_rate) FROM resources r7 WHERE r7.village_id = resources.village_id) as max_production_rate
        ');
    }

    public function scopeByVillage($query, $villageId)
    {
        return $query->where('village_id', $villageId);
    }

    public function scopeByType($query, $type = null)
    {
        return $query->when($type, function ($q) use ($type) {
            return $q->where('type', $type);
        });
    }

    public function scopeByAmount($query, $minAmount = null, $maxAmount = null)
    {
        return $query->when($minAmount, function ($q) use ($minAmount) {
            return $q->where('amount', '>=', $minAmount);
        })->when($maxAmount, function ($q) use ($maxAmount) {
            return $q->where('amount', '<=', $maxAmount);
        });
    }

    public function scopeByProductionRate($query, $minRate = null, $maxRate = null)
    {
        return $query->when($minRate, function ($q) use ($minRate) {
            return $q->where('production_rate', '>=', $minRate);
        })->when($maxRate, function ($q) use ($maxRate) {
            return $q->where('production_rate', '<=', $maxRate);
        });
    }

    public function scopeByCapacity($query, $minCapacity = null, $maxCapacity = null)
    {
        return $query->when($minCapacity, function ($q) use ($minCapacity) {
            return $q->where('storage_capacity', '>=', $minCapacity);
        })->when($maxCapacity, function ($q) use ($maxCapacity) {
            return $q->where('storage_capacity', '<=', $maxCapacity);
        });
    }

    /**
     * Get resources with SmartCache optimization
     */
    public static function getCachedResources($villageId, $filters = [])
    {
        $cacheKey = "village_{$villageId}_resources_".md5(serialize($filters));

        return SmartCache::remember($cacheKey, now()->addMinutes(2), function () use ($villageId, $filters) {
            $query = static::byVillage($villageId)->withStats();

            if (isset($filters['type'])) {
                $query->byType($filters['type']);
            }

            if (isset($filters['min_amount'])) {
                $query->byAmount($filters['min_amount']);
            }

            if (isset($filters['min_production'])) {
                $query->byProductionRate($filters['min_production']);
            }

            return $query->get();
        });
    }

    public function scopeByLevel($query, $minLevel = null, $maxLevel = null)
    {
        return $query->when($minLevel, function ($q) use ($minLevel) {
            return $q->where('level', '>=', $minLevel);
        })->when($maxLevel, function ($q) use ($maxLevel) {
            return $q->where('level', '<=', $maxLevel);
        });
    }

    public function scopeTopProduction($query, $limit = 10)
    {
        return $query->orderBy('production_rate', 'desc')->limit($limit);
    }

    public function scopeTopAmount($query, $limit = 10)
    {
        return $query->orderBy('amount', 'desc')->limit($limit);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('last_updated', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where('type', 'like', '%'.$searchTerm.'%');
        });
    }

    public function scopeWithVillageInfo($query)
    {
        return $query->with([
            'village:id,name,player_id,population',
        ]);
    }
}
