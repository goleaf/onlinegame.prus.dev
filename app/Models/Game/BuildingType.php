<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingType extends Model
{
    use HasFactory;
    use HasTaxonomy;

    protected $fillable = [
        'name',
        'key',
        'description',
        'category',
        'max_level',
        'base_costs',
        'cost_multiplier',
        'build_time_base',
        'build_time_multiplier',
        'requirements',
        'effects',
        'costs',
        'production',
        'population',
        'is_special',
        'is_active',
    ];

    protected $casts = [
        'base_costs' => 'array',
        'cost_multiplier' => 'array',
        'requirements' => 'array',
        'effects' => 'array',
        'costs' => 'array',
        'production' => 'array',
        'population' => 'array',
        'is_special' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }
}
