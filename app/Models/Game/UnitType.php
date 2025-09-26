<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'tribe',
        'description',
        'attack',
        'defense_infantry',
        'defense_cavalry',
        'speed',
        'carry_capacity',
        'costs',
        'requirements',
        'is_special',
        'is_active',
    ];

    protected $casts = [
        'costs' => 'array',
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
