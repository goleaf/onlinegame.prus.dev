<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerStatistic extends Model
{
    protected $fillable = [
        'player_id',
        'world_id',
        'points',
        'rank',
        'attack_points',
        'defense_points',
        'barbarian_points',
        'villages_count',
        'population',
        'attacks_won',
        'attacks_lost',
        'defenses_won',
        'defenses_lost',
        'barbarians_attacked',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
