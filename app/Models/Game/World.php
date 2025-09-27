<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Notable\Traits\HasNotables;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use WendellAdriel\Lift\Lift;

class World extends Model implements Auditable
{
    use HasFactory, HasTaxonomy;
    use HasNotables;
    use HasReference;
    use Lift;
    use AuditableTrait;

    // Laravel Lift typed properties
    public int $id;
    public string $name;
    public ?string $description;
    public bool $is_active;
    public ?string $reference_number;
    public \Carbon\Carbon $created_at;
    public \Carbon\Carbon $updated_at;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'reference_number',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
