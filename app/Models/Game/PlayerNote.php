<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;

class PlayerNote extends Model
{
    use HasReference;

    protected $fillable = [
        'player_id',
        'target_player_id',
        'title',
        'content',
        'category',
        'is_private',
        'tags',
        'reference_number',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'tags' => 'array',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';
    protected $referenceTemplate = [
        'format' => 'PN-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];
    protected $referencePrefix = 'PN';

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function targetPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'target_player_id');
    }
}
