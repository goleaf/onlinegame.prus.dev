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

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            achievements.*,
            (SELECT COUNT(*) FROM player_achievements pa WHERE pa.achievement_id = achievements.id AND pa.unlocked_at IS NOT NULL) as unlocked_count,
            (SELECT COUNT(*) FROM player_achievements pa2 WHERE pa2.achievement_id = achievements.id) as total_players,
            (SELECT AVG(EXTRACT(EPOCH FROM (pa3.unlocked_at - pa3.created_at))/3600) FROM player_achievements pa3 WHERE pa3.achievement_id = achievements.id AND pa3.unlocked_at IS NOT NULL) as avg_unlock_time_hours
        ');
    }

    public function scopeByWorld($query, $worldId)
    {
        return $query->where('world_id', $worldId);
    }

    public function scopeByCategoryFilter($query, $category = null)
    {
        return $query->when($category, function ($q) use ($category) {
            return $q->where('category', $category);
        });
    }

    public function scopeByDifficulty($query, $difficulty = null)
    {
        return $query->when($difficulty, function ($q) use ($difficulty) {
            return $q->where('difficulty', $difficulty);
        });
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query
            ->orderByRaw('(SELECT COUNT(*) FROM player_achievements pa WHERE pa.achievement_id = achievements.id AND pa.unlocked_at IS NOT NULL) DESC')
            ->limit($limit);
    }

    public function scopeRare($query, $limit = 10)
    {
        return $query
            ->orderByRaw('(SELECT COUNT(*) FROM player_achievements pa WHERE pa.achievement_id = achievements.id AND pa.unlocked_at IS NOT NULL) ASC')
            ->limit($limit);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where(function ($subQ) use ($searchTerm): void {
                $subQ
                    ->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%')
                    ->orWhere('category', 'like', '%'.$searchTerm.'%');
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'players:id,name',
        ]);
    }
}
