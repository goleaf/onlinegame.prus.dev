<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Troop extends Model
{
    protected $fillable = [
        'village_id',
        'unit_type_id',
        'count',
        'is_training',
        'training_started_at',
        'training_completed_at',
    ];

    protected $casts = [
        'is_training' => 'boolean',
        'training_started_at' => 'datetime',
        'training_completed_at' => 'datetime',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }
}
