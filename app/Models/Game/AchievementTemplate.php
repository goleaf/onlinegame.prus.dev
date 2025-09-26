<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AchievementTemplate extends Model
{
    use HasFactory;

    protected $table = 'achievements';

    protected $fillable = [
        'name',
        'key',
        'description',
        'category',
        'points',
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
