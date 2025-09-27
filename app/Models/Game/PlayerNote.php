<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;

class PlayerNote extends Model
{
    protected $fillable = [
        'player_id',
        'target_player_id',
        'title',
        'content',
        'category',
        'is_private',
        'tags',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'tags' => 'array',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function targetPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'target_player_id');
    }
}
