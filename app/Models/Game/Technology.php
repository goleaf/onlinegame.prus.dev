<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Technology extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'max_level',
        'base_costs',
        'cost_multiplier',
        'research_time_base',
        'research_time_multiplier',
        'requirements',
        'effects',
        'is_active',
    ];

    protected $casts = [
        'base_costs' => 'array',
        'cost_multiplier' => 'array',
        'requirements' => 'array',
        'effects' => 'array',
        'is_active' => 'boolean',
    ];

    public function players(): BelongsToMany
    {
        return $this
            ->belongsToMany(Player::class, 'player_technologies')
            ->withPivot(['level', 'researched_at'])
            ->withTimestamps();
    }
}
