<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alliance extends Model
{
    protected $fillable = [
        'name',
        'tag',
        'description',
        'world_id',
        'founder_id',
        'leader_id',
        'member_count',
        'points',
        'rank',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'founder_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(AllianceMember::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
