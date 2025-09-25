<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class GameEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'village_id',
        'event_type',
        'event_data',
        'occurred_at',
        'is_read',
    ];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
