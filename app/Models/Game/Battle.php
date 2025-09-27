<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Battle extends Model
{
    protected $fillable = [
        'attacker_id',
        'defender_id',
        'village_id',
        'battle_type',
        'result',
        'attacker_losses',
        'defender_losses',
        'resources_looted',
        'battle_data',
        'occurred_at',
    ];

    protected $casts = [
        'attacker_losses' => 'array',
        'defender_losses' => 'array',
        'resources_looted' => 'array',
        'battle_data' => 'array',
        'occurred_at' => 'datetime',
    ];

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
