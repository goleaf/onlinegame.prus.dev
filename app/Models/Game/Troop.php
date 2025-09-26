<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Troop extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'unit_type_id',
        'count',
        'in_village',
        'in_attack',
        'in_defense',
        'in_support',
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
