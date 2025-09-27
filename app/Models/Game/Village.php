<?php

namespace App\Models\Game;

use App\Services\GeographicService;
use App\Traits\Commentable;
use App\ValueObjects\Coordinates;
use App\ValueObjects\VillageResources;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use IndexZer0\EloquentFiltering\Contracts\IsFilterable;
use IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList;
use IndexZer0\EloquentFiltering\Filter\Filterable\Filter;
use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;
use IndexZer0\EloquentFiltering\Filter\Types\Types;
use MohamedSaid\Notable\Traits\HasNotables;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use WendellAdriel\Lift\Lift;

class Village extends Model implements Auditable
{
    use HasFactory, Commentable;
    use HasNotables;
    use AuditableTrait;
    use Lift;

    // Laravel Lift typed properties
    public int $id;
    public int $player_id;
    public int $world_id;
    public string $name;
    public ?int $x_coordinate;
    public ?int $y_coordinate;
    public ?float $latitude;
    public ?float $longitude;
    public ?string $geohash;
    public ?float $elevation;
    public ?array $geographic_metadata;
    public int $population;
    public bool $is_capital;
    public bool $is_active;
    public \Carbon\Carbon $created_at;
    public \Carbon\Carbon $updated_at;

    protected $fillable = [
        'player_id',
        'world_id',
        'name',
        'x_coordinate',
        'y_coordinate',
        'latitude',
        'longitude',
        'geohash',
        'elevation',
        'geographic_metadata',
        'population',
        'is_capital',
        'is_active',
        'reference_number',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_capital' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'elevation' => 'decimal:2',
        'geographic_metadata' => 'array',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'VIL-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'VIL';

    /**
     * Get the coordinates as a value object
     */
    protected function coordinates(): Attribute
    {
        return Attribute::make(
            get: fn() => new Coordinates(
                x: $this->x_coordinate,
                y: $this->y_coordinate,
                latitude: $this->latitude,
                longitude: $this->longitude,
                elevation: $this->elevation,
                geohash: $this->geohash
            ),
            set: fn(Coordinates $coordinates) => [
                'x_coordinate' => $coordinates->x,
                'y_coordinate' => $coordinates->y,
                'latitude' => $coordinates->latitude,
                'longitude' => $coordinates->longitude,
                'elevation' => $coordinates->elevation,
                'geohash' => $coordinates->geohash,
            ]
        );
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function resources(): HasMany

    /**
     * Get the village resources as a value object
     */
    protected function villageResources(): Attribute
    {
        return Attribute::make(
            get: fn() => {
                $resource = $this->resources()->first();
                if (!$resource) {
                    return new \App\ValueObjects\VillageResources(0, 0, 0, 0);
                }
                return new \App\ValueObjects\VillageResources(
                    wood: $resource->wood,
                    clay: $resource->clay,
                    iron: $resource->iron,
                    crop: $resource->crop
                );
            }
        );
    }
    {
        return $this->hasMany(Resource::class);
    }

    public function resourceProductionLogs(): HasMany
    {
        return $this->hasMany(ResourceProductionLog::class);
    }

    public function trainingQueues(): HasMany
    {
        return $this->hasMany(TrainingQueue::class);
    }

    public function buildingQueues(): HasMany
    {
        return $this->hasMany(BuildingQueue::class);
    }

    public function troops(): HasMany
    {
        return $this->hasMany(Troop::class);
    }

    public function movementsFrom(): HasMany
    {
        return $this->hasMany(Movement::class, 'from_village_id');
    }

    public function movementsTo(): HasMany
    {
        return $this->hasMany(Movement::class, 'to_village_id');
    }

    public function battles(): HasMany
    {
        return $this->hasMany(Battle::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function getCoordinatesAttribute()
    {
        return "({$this->x_coordinate}|{$this->y_coordinate})";
    }

    /**
     * Get real-world coordinates for this village
     *
     * @return array
     */
    public function getRealWorldCoordinates(): array
    {
        // Use stored coordinates if available, otherwise calculate from game coordinates
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => (float) $this->latitude,
                'lon' => (float) $this->longitude
            ];
        }

        $geoService = app(GeographicService::class);
        return $geoService->gameToRealWorld($this->x_coordinate, $this->y_coordinate);
    }

    /**
     * Update geographic data for this village
     *
     * @return void
     */
    public function updateGeographicData(): void
    {
        $geoService = app(GeographicService::class);
        $coords = $this->getRealWorldCoordinates();

        $this->update([
            'latitude' => $coords['lat'],
            'longitude' => $coords['lon'],
            'geohash' => $geoService->generateGeohash($coords['lat'], $coords['lon']),
        ]);
    }

    /**
     * Calculate distance to another village
     *
     * @param Village $village
     * @return float
     */
    public function distanceTo(Village $village): float
    {
        $geoService = app(GeographicService::class);
        return $geoService->calculateGameDistance(
            $this->x_coordinate,
            $this->y_coordinate,
            $village->x_coordinate,
            $village->y_coordinate
        );
    }

    /**
     * Calculate real-world distance to another village
     *
     * @param Village $village
     * @return float
     */
    public function realWorldDistanceTo(Village $village): float
    {
        $geoService = app(GeographicService::class);
        $coords1 = $this->getRealWorldCoordinates();
        $coords2 = $village->getRealWorldCoordinates();

        return $geoService->calculateDistance(
            $coords1['lat'],
            $coords1['lon'],
            $coords2['lat'],
            $coords2['lon']
        );
    }

    /**
     * Get geohash for this village
     *
     * @param int $precision
     * @return string
     */
    public function getGeohash(int $precision = 8): string
    {
        $geoService = app(GeographicService::class);
        $coords = $this->getRealWorldCoordinates();

        return $geoService->generateGeohash($coords['lat'], $coords['lon'], $precision);
    }

    /**
     * Get bearing to another village
     *
     * @param Village $village
     * @return float
     */
    public function bearingTo(Village $village): float
    {
        $geoService = app(GeographicService::class);
        $coords1 = $this->getRealWorldCoordinates();
        $coords2 = $village->getRealWorldCoordinates();

        return $geoService->getBearing(
            $coords1['lat'],
            $coords1['lon'],
            $coords2['lat'],
            $coords2['lon']
        );
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            villages.*,
            (SELECT COUNT(*) FROM buildings WHERE village_id = villages.id) as building_count,
            (SELECT COUNT(*) FROM troops WHERE village_id = villages.id AND quantity > 0) as troop_count,
            (SELECT SUM(wood + clay + iron + crop) FROM resources WHERE village_id = villages.id) as total_resources,
            (SELECT SUM(wood_production + clay_production + iron_production + crop_production) FROM resources WHERE village_id = villages.id) as total_production,
            (SELECT COUNT(*) FROM movements WHERE from_village_id = villages.id OR to_village_id = villages.id) as total_movements,
            (SELECT COUNT(*) FROM battles WHERE village_id = villages.id) as total_battles
        ');
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeByWorld($query, $worldId)
    {
        return $query->where('world_id', $worldId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCapital($query)
    {
        return $query->where('is_capital', true);
    }

    public function scopeByCoordinates($query, $x, $y, $radius = 0)
    {
        return $query->when($radius > 0, function ($q) use ($x, $y, $radius) {
            return $q->whereRaw('SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2)) <= ?', [$x, $y, $radius]);
        }, function ($q) use ($x, $y) {
            return $q->where('x_coordinate', $x)->where('y_coordinate', $y);
        });
    }

    public function scopeByPopulation($query, $minPopulation = null, $maxPopulation = null)
    {
        return $query->when($minPopulation, function ($q) use ($minPopulation) {
            return $q->where('population', '>=', $minPopulation);
        })->when($maxPopulation, function ($q) use ($maxPopulation) {
            return $q->where('population', '<=', $maxPopulation);
        });
    }

    public function scopeTopVillages($query, $limit = 10)
    {
        return $query->orderBy('population', 'desc')->limit($limit);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where(function ($subQ) use ($searchTerm) {
                $subQ
                    ->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhereIn('player_id', function ($playerQ) use ($searchTerm) {
                        $playerQ
                            ->select('id')
                            ->from('players')
                            ->where('name', 'like', '%' . $searchTerm . '%');
                    });
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'player:id,name,points,alliance_id',
            'world:id,name'
        ]);
    }

    /**
     * Scope to find villages within a radius of given coordinates
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $centerX
     * @param int $centerY
     * @param float $radius
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithinRadius($query, int $centerX, int $centerY, float $radius)
    {
        return $query->whereRaw('SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2)) <= ?', [
            $centerX, $centerY, $radius
        ]);
    }

    /**
     * Scope to find villages within a real-world radius
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $centerLat
     * @param float $centerLon
     * @param float $radiusKm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithinRealWorldRadius($query, float $centerLat, float $centerLon, float $radiusKm)
    {
        $geoService = app(GeographicService::class);
        $centerGame = $geoService->realWorldToGame($centerLat, $centerLon);

        // Convert radius from km to game units (approximate)
        $radiusGameUnits = $radiusKm * 1000;  // Rough conversion

        return $query->withinRadius($centerGame['x'], $centerGame['y'], $radiusGameUnits);
    }

    /**
     * Scope to order villages by distance from a point
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $centerX
     * @param int $centerY
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByDistance($query, int $centerX, int $centerY)
    {
        return $query->orderByRaw('SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2))', [
            $centerX, $centerY
        ]);
    }

    /**
     * Define allowed filters for the Village model
     */
    public function allowedFilters(): AllowedFilterList
    {
        return Filter::only(
            Filter::field('name', ['$eq', '$like']),
            Filter::field('population', ['$eq', '$gt', '$lt']),
            Filter::field('x_coordinate', ['$eq', '$gt', '$lt']),
            Filter::field('y_coordinate', ['$eq', '$gt', '$lt']),
            Filter::field('latitude', ['$eq', '$gt', '$lt']),
            Filter::field('longitude', ['$eq', '$gt', '$lt']),
            Filter::field('elevation', ['$eq', '$gt', '$lt']),
            Filter::field('is_capital', ['$eq']),
            Filter::field('is_active', ['$eq']),
            Filter::field('player_id', ['$eq']),
            Filter::field('world_id', ['$eq']),
            Filter::field('geohash', ['$eq', '$like']),
            Filter::field('reference_number', ['$eq', '$like']),
            Filter::relation('player', ['$has']),
            Filter::relation('world', ['$has']),
            Filter::relation('buildings', ['$has']),
            Filter::relation('resources', ['$has']),
            Filter::relation('troops', ['$has'])
        );
    }
}
