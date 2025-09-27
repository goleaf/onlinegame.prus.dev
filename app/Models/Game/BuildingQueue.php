<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class BuildingQueue extends Model
{
    protected $fillable = [
        'village_id',
        'building_id',
        'building_type_id',
        'level',
        'started_at',
        'completed_at',
        'status',
        'costs',
        'reference_number',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'costs' => 'array',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function buildingType(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class);
    }
}
