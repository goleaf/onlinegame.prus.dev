<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;

class PlayerStatistic extends Model
{
    use HasReference;

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
        'reference_number',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';
    protected $referenceTemplate = [
        'format' => 'PS-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];
    protected $referencePrefix = 'PS';

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
