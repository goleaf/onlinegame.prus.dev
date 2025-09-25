<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    use HasFactory;
    protected $fillable = [
        'player_id',
        'from_village_id',
        'to_village_id',
        'type',
        'troops',
        'resources',
        'started_at',
        'arrives_at',
        'returned_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'troops' => 'array',
        'resources' => 'array',
        'started_at' => 'datetime',
        'arrives_at' => 'datetime',
        'returned_at' => 'datetime',
        'metadata' => 'array',
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
