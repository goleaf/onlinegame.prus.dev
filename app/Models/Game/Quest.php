<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'requirements',
        'rewards',
        'is_repeatable',
        'is_active',
        'min_level',
        'max_level',
        'duration_hours',
    ];

    protected $casts = [
        'requirements' => 'array',
        'rewards' => 'array',
        'is_repeatable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function players(): BelongsToMany
    {
        return $this
            ->belongsToMany(Player::class, 'player_quests')
            ->withPivot(['status', 'progress', 'progress_data', 'started_at', 'completed_at', 'expires_at'])
            ->withTimestamps();
    }
}
