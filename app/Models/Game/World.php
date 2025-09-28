<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MohamedSaid\Notable\Traits\HasNotables;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class World extends Model implements Auditable
{
    use AuditableTrait;
    use HasFactory;
    use HasTaxonomy;
    use HasNotables;
    use HasReference;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'reference_number',
        'max_players',
        'map_size',
        'speed',
        'has_plus',
        'has_artifacts',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_plus' => 'boolean',
        'has_artifacts' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';

    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'WLD-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'WLD';

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function alliances(): HasMany
    {
        return $this->hasMany(Alliance::class);
    }

    public function playerStatistics(): HasMany
    {
        return $this->hasMany(PlayerStatistic::class);
    }
}
