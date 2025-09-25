<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class UnitType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'attack_power',
        'defense_power',
        'speed',
        'carry_capacity',
        'training_costs',
        'training_time',
        'requirements',
        'is_special',
        'is_active',
    ];

    protected $casts = [
        'training_costs' => 'array',
        'requirements' => 'array',
        'is_special' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function troops(): HasMany
    {
        return $this->hasMany(Troop::class);
    }

    public function trainingQueues(): HasMany
    {
        return $this->hasMany(TrainingQueue::class);
    }
}
