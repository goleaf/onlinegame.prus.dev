<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestTemplate extends Model
{
    use HasFactory;

    protected $table = 'quests';

    protected $fillable = [
        'name',
        'key',
        'description',
        'instructions',
        'category',
        'difficulty',
        'requirements',
        'rewards',
        'experience_reward',
        'gold_reward',
        'resource_rewards',
        'is_repeatable',
        'is_active',
    ];

    protected $casts = [
        'requirements' => 'array',
        'rewards' => 'array',
        'resource_rewards' => 'array',
        'is_repeatable' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }
}
