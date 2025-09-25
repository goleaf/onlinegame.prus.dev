<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    protected $fillable = [
        'player_id',
        'from_village_id',
        'to_village_id',
        'movement_type',
        'troops',
        'started_at',
        'arrives_at',
        'return_at',
        'status',
        'movement_data',
    ];

    protected $casts = [
        'troops' => 'array',
        'started_at' => 'datetime',
        'arrives_at' => 'datetime',
        'return_at' => 'datetime',
        'movement_data' => 'array',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function fromVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'from_village_id');
    }

    public function toVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'to_village_id');
    }
}
