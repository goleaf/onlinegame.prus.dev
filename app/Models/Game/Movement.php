<?php

namespace App\Models\Game;

// use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;
use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
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
use sbamtr\LaravelQueryEnrich\QE;
use function sbamtr\LaravelQueryEnrich\c;

class Movement extends Model implements Auditable
{
    use HasFactory, HasReference, AuditableTrait, Lift;

    // Laravel Lift typed properties
    public int $id;
    public int $player_id;
    public int $from_village_id;
    public int $to_village_id;
    public string $type;
    public ?array $troops;
    public ?array $resources;
    public \Carbon\Carbon $started_at;
    public \Carbon\Carbon $arrives_at;
    public ?\Carbon\Carbon $returned_at;
    public string $status;
    public ?array $metadata;
    public ?string $reference_number;
    public \Carbon\CarbonImmutable $created_at;
    public \Carbon\CarbonImmutable $updated_at;

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

    /**
     * Get the troops as a value object
     */
    protected function troops(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? new TroopCounts(
                legionnaires: $value['legionnaires'] ?? 0,
                praetorians: $value['praetorians'] ?? 0,
                imperians: $value['imperians'] ?? 0,
                equitesLegati: $value['equites_legati'] ?? 0,
                equitesImperatoris: $value['equites_imperatoris'] ?? 0,
                equitesCaesaris: $value['equites_caesaris'] ?? 0,
                batteringRams: $value['battering_rams'] ?? 0,
                fireCatapults: $value['fire_catapults'] ?? 0,
                senators: $value['senators'] ?? 0,
                settlers: $value['settlers'] ?? 0
            ) : null,
            set: fn (TroopCounts $troops = null) => $troops ? [
                'legionnaires' => $troops->legionnaires,
                'praetorians' => $troops->praetorians,
                'imperians' => $troops->imperians,
                'equites_legati' => $troops->equitesLegati,
                'equites_imperatoris' => $troops->equitesImperatoris,
                'equites_caesaris' => $troops->equitesCaesaris,
                'battering_rams' => $troops->batteringRams,
                'fire_catapults' => $troops->fireCatapults,
                'senators' => $troops->senators,
                'settlers' => $troops->settlers,
            ] : null
        );
    }

    /**
     * Get the resources as a value object
     */
    protected function resources(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? new ResourceAmounts(
                wood: $value['wood'] ?? 0,
                clay: $value['clay'] ?? 0,
                iron: $value['iron'] ?? 0,
                crop: $value['crop'] ?? 0
            ) : null,
            set: fn (ResourceAmounts $resources = null) => $resources ? [
                'wood' => $resources->wood,
                'clay' => $resources->clay,
                'iron' => $resources->iron,
                'crop' => $resources->crop,
            ] : null
        );
    }

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

    // Enhanced query scopes using Query Enrich
    public function scopeWithStats($query)
    {
        return $query->select([
            'movements.*',
            QE::select(QE::count(c('id')))
                ->from('movements', 'm2')
                ->where(function($q) {
                    $q->whereColumn('m2.from_village_id', c('movements.from_village_id'))
                      ->orWhereColumn('m2.to_village_id', c('movements.from_village_id'));
                })
                ->as('total_movements_from_village'),
            QE::select(QE::count(c('id')))
                ->from('movements', 'm3')
                ->where(function($q) {
                    $q->whereColumn('m3.from_village_id', c('movements.to_village_id'))
                      ->orWhereColumn('m3.to_village_id', c('movements.to_village_id'));
                })
                ->as('total_movements_to_village'),
            QE::select(QE::avg(c('travel_time')))
                ->from('movements', 'm4')
                ->whereColumn('m4.from_village_id', c('movements.from_village_id'))
                ->as('avg_travel_time_from'),
            QE::select(QE::avg(c('travel_time')))
                ->from('movements', 'm5')
                ->whereColumn('m5.to_village_id', c('movements.to_village_id'))
                ->as('avg_travel_time_to')
        ]);
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
            Filter::field('reference_number', ['$eq', '$like']),
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
