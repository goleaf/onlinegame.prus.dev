<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use sbamtr\LaravelQueryEnrich\QE;
use SmartCache\Facades\SmartCache;
use WendellAdriel\Lift\Lift;

use function sbamtr\LaravelQueryEnrich\c;

class Building extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;
    use Lift;

    // Laravel Lift typed properties
    public int $id;
    public int $village_id;
    public int $building_type_id;
    public string $name;
    public int $level;
    public ?int $x;
    public ?int $y;
    public bool $is_active;
    public ?\Carbon\Carbon $upgrade_started_at;
    public ?\Carbon\Carbon $upgrade_completed_at;
    public ?array $metadata;
    public \Carbon\Carbon $created_at;
    public \Carbon\Carbon $updated_at;

    protected $fillable = [
        'village_id',
        'building_type_id',
        'name',
        'level',
        'x',
        'y',
        'is_active',
        'upgrade_started_at',
        'upgrade_completed_at',
        'metadata',
    ];

    protected $casts = [
        'upgrade_started_at' => 'datetime',
        'upgrade_completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }

    // Enhanced query scopes using Query Enrich
    public function scopeWithStats($query)
    {
        return $query->select([
            'buildings.*',
            QE::select(QE::count(c('id')))
                ->from('buildings', 'b2')
                ->whereColumn('b2.village_id', c('buildings.village_id'))
                ->where('b2.is_active', '=', 1)
                ->as('total_buildings'),
            QE::select(QE::avg(c('level')))
                ->from('buildings', 'b3')
                ->whereColumn('b3.village_id', c('buildings.village_id'))
                ->where('b3.is_active', '=', 1)
                ->as('avg_level'),
            QE::select(QE::max(c('level')))
                ->from('buildings', 'b4')
                ->whereColumn('b4.village_id', c('buildings.village_id'))
                ->where('b4.is_active', '=', 1)
                ->as('max_level'),
            QE::select(QE::count(c('id')))
                ->from('buildings', 'b5')
                ->whereColumn('b5.village_id', c('buildings.village_id'))
                ->whereColumn('b5.building_type_id', c('buildings.building_type_id'))
                ->as('same_type_count')
        ]);
    }

    public function scopeByVillage($query, $villageId)
    {
        return $query->where('village_id', $villageId);
    }

    public function scopeByType($query, $typeId = null)
    {
        return $query->when($typeId, function ($q) use ($typeId) {
            return $q->where('building_type_id', $typeId);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, $minLevel = null, $maxLevel = null)
    {
        return $query->when($minLevel, function ($q) use ($minLevel) {
            return $q->where('level', '>=', $minLevel);
        })->when($maxLevel, function ($q) use ($maxLevel) {
            return $q->where('level', '<=', $maxLevel);
        });
    }

    public function scopeUpgradeable($query)
    {
        return $query
            ->where('is_active', true)
            ->where('upgrade_started_at', null);
    }

    public function scopeInProgress($query)
    {
        return $query
            ->whereNotNull('upgrade_started_at')
            ->whereNull('upgrade_completed_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('upgrade_completed_at');
    }

    /**
     * Get buildings with SmartCache optimization
     */
    public static function getCachedBuildings($villageId, $filters = [])
    {
        $cacheKey = "village_{$villageId}_buildings_" . md5(serialize($filters));

        return SmartCache::remember($cacheKey, now()->addMinutes(5), function () use ($villageId, $filters) {
            $query = static::byVillage($villageId)->withStats();

            if (isset($filters['type'])) {
                $query->byType($filters['type']);
            }

            if (isset($filters['active'])) {
                $query->active();
            }

            if (isset($filters['upgradeable'])) {
                $query->upgradeable();
            }

            return $query->get();
        });
    }

    public function scopeTopLevel($query, $limit = 10)
    {
        return $query->orderBy('level', 'desc')->limit($limit);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->whereHas('buildingType', function ($typeQ) use ($searchTerm) {
                $typeQ
                    ->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        });
    }

    public function scopeWithBuildingTypeInfo($query)
    {
        return $query->with([
            'buildingType:id,name,description,costs,production_bonus',
            'village:id,name,player_id'
        ]);
    }
}
