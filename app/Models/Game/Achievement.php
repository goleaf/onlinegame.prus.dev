<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'requirements',
        'rewards',
        'icon',
        'is_hidden',
        'is_active',
    ];

    protected $casts = [
        'requirements' => 'array',
        'rewards' => 'array',
        'is_hidden' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function players(): BelongsToMany
    {
        return $this
            ->belongsToMany(Player::class, 'player_achievements')
            ->withPivot(['unlocked_at', 'progress_data'])
            ->withTimestamps();
    }
}
