<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceProductionLog extends Model
{
    protected $fillable = [
        'village_id',
        'type',
        'amount_produced',
        'amount_consumed',
        'final_amount',
        'produced_at',
    ];

    protected $casts = [
        'produced_at' => 'datetime',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
