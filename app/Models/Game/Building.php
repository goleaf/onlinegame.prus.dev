<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Building extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;

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

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            buildings.*,
            (SELECT COUNT(*) FROM buildings b2 WHERE b2.village_id = buildings.village_id AND b2.is_active = 1) as total_buildings,
            (SELECT AVG(level) FROM buildings b3 WHERE b3.village_id = buildings.village_id AND b3.is_active = 1) as avg_level,
            (SELECT MAX(level) FROM buildings b4 WHERE b4.village_id = buildings.village_id AND b4.is_active = 1) as max_level,
            (SELECT COUNT(*) FROM buildings b5 WHERE b5.village_id = buildings.village_id AND b5.building_type_id = buildings.building_type_id) as same_type_count
        ');
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
