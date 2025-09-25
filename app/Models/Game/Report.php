<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'player_id',
        'report_type',
        'title',
        'content',
        'attacker_id',
        'defender_id',
        'village_id',
        'battle_data',
        'resources',
        'troops',
        'occurred_at',
        'is_read',
    ];

    protected $casts = [
        'battle_data' => 'array',
        'resources' => 'array',
        'troops' => 'array',
        'occurred_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function attacker(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'attacker_id');
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'defender_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
