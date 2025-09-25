<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'building_type_id',
        'name',
        'level',
        'x',
        'y',
        'is_active',
        'upgrade_started_at',
        'upgrade_completed_at',
        'metadata',
    ];

    protected $casts = [
        'upgrade_started_at' => 'datetime',
        'upgrade_completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }
}
