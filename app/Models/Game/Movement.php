<?php

namespace App\Models\Game;

// use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;
use IndexZer0\EloquentFiltering\Contracts\IsFilterable;
use EloquentFiltering\AllowedFilterList;
use EloquentFiltering\Filter;
use EloquentFiltering\FilterType;
use App\Services\GeographicService;
use App\ValueObjects\TroopCounts;
use App\ValueObjects\ResourceAmounts;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Movement extends Model implements Auditable
{
    use HasFactory, HasReference, AuditableTrait;

    protected $fillable = [
        'player_id',
        'from_village_id',
        'to_village_id',
        'type',
        'troops',
        'resources',
        'started_at',
        'arrives_at',
        'returned_at',
        'status',
        'metadata',
        'reference_number',
    ];

    protected $casts = [
        'troops' => 'array',
        'resources' => 'array',
        'started_at' => 'datetime',
        'arrives_at' => 'datetime',
        'returned_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'MOV-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'MOV';

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function fromVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'from_village_id');
    }

    public function toVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'to_village_id');
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            movements.*,
            (SELECT COUNT(*) FROM movements m2 WHERE m2.from_village_id = movements.from_village_id OR m2.to_village_id = movements.from_village_id) as total_movements_from_village,
            (SELECT COUNT(*) FROM movements m3 WHERE m3.from_village_id = movements.to_village_id OR m3.to_village_id = movements.to_village_id) as total_movements_to_village,
            (SELECT AVG(travel_time) FROM movements m4 WHERE m4.from_village_id = movements.from_village_id) as avg_travel_time_from,
            (SELECT AVG(travel_time) FROM movements m5 WHERE m5.to_village_id = movements.to_village_id) as avg_travel_time_to
        ');
    }

    public function scopeByVillage($query, $villageId)
    {
        return $query->where(function ($q) use ($villageId) {
            $q
                ->where('from_village_id', $villageId)
                ->orWhere('to_village_id', $villageId);
        });
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeByType($query, $type = null)
    {
        return $query->when($type, function ($q) use ($type) {
            return $q->where('type', $type);
        });
    }

    public function scopeByStatus($query, $status = null)
    {
        return $query->when($status, function ($q) use ($status) {
            return $q->where('status', $status);
        });
    }

    public function scopeTravelling($query)
    {
        return $query->where('status', 'travelling');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where(function ($subQ) use ($searchTerm) {
                $subQ->whereIn('to_village_id', function ($villageQ) use ($searchTerm) {
                    $villageQ
                        ->select('id')
                        ->from('villages')
                        ->where('name', 'like', '%' . $searchTerm . '%');
                })->orWhereIn('from_village_id', function ($villageQ) use ($searchTerm) {
                    $villageQ
                        ->select('id')
                        ->from('villages')
                        ->where('name', 'like', '%' . $searchTerm . '%');
                });
            });
        });
    }

    public function scopeWithVillageInfo($query)
    {
        return $query->with([
            'fromVillage:id,name,x_coordinate,y_coordinate',
            'toVillage:id,name,x_coordinate,y_coordinate',
            'player:id,name',
        ]);
    }

    /**
     * Define allowed filters for the Movement model
     */
    public function allowedFilters(): AllowedFilterList
    {
        return Filter::only(
            Filter::field('type', ['$eq']),
            Filter::field('status', ['$eq']),
            Filter::field('player_id', ['$eq']),
            Filter::field('from_village_id', ['$eq']),
            Filter::field('to_village_id', ['$eq']),
            Filter::field('started_at', ['$eq', '$gt', '$lt']),
            Filter::field('arrives_at', ['$eq', '$gt', '$lt']),
            Filter::field('returned_at', ['$eq', '$gt', '$lt']),
            Filter::field('reference_number', ['$eq', '$contains']),
            Filter::relation('player', ['$has']),
            Filter::relation('fromVillage', ['$has']),
            Filter::relation('toVillage', ['$has'])
        );
    }

    /**
     * Calculate distance between from and to villages
     */
    public function getDistanceAttribute(): ?float
    {
        if (!$this->fromVillage || !$this->toVillage) {
            return null;
        }

        $geographicService = app(GeographicService::class);
        return $geographicService->calculateDistance(
            $this->fromVillage->latitude,
            $this->fromVillage->longitude,
            $this->toVillage->latitude,
            $this->toVillage->longitude
        );
    }

    /**
     * Calculate travel time based on distance and speed
     */
    public function getTravelTimeAttribute(): ?int
    {
        $distance = $this->distance;
        if (!$distance) {
            return null;
        }

        $geographicService = app(GeographicService::class);
        return $geographicService->calculateTravelTimeFromDistance($distance);
    }

    /**
     * Get bearing from source to destination
     */
    public function getBearingAttribute(): ?float
    {
        if (!$this->fromVillage || !$this->toVillage) {
            return null;
        }

        $geographicService = app(GeographicService::class);
        return $geographicService->calculateBearing(
            $this->fromVillage->latitude,
            $this->fromVillage->longitude,
            $this->toVillage->latitude,
            $this->toVillage->longitude
        );
    }

    /**
     * Scope for movements within a certain distance
     */
    public function scopeWithinDistance($query, $latitude, $longitude, $maxDistance)
    {
        return $query->whereHas('fromVillage', function ($q) use ($latitude, $longitude, $maxDistance) {
            $q->whereRaw("ST_Distance_Sphere(
                POINT(longitude, latitude), 
                POINT(?, ?)
            ) <= ?", [$longitude, $latitude, $maxDistance * 1000]);
        });
    }
}
