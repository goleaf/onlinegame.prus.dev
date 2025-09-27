<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PlayerAchievement extends Model
{
    use HasFactory;

    protected $table = 'player_achievements';

    protected $fillable = [
        'player_id',
        'achievement_id',
        'unlocked_at',
        'progress_data',
    ];

    protected $casts = [
        'progress_data' => 'array',
        'unlocked_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    // Scopes
    public function scopeUnlocked($query)
    {
        return $query->where('status', 'unlocked');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query
            ->selectRaw('
            player_achievements.*,
            (SELECT COUNT(*) FROM player_achievements pa2 WHERE pa2.player_id = player_achievements.player_id AND pa2.unlocked_at IS NOT NULL) as total_unlocked,
            (SELECT COUNT(*) FROM player_achievements pa3 WHERE pa3.player_id = player_achievements.player_id) as total_available,
            (SELECT SUM(achievements.points) FROM achievements WHERE achievements.id = player_achievements.achievement_id AND player_achievements.unlocked_at IS NOT NULL) as total_points
        ')
            ->join('achievements', 'player_achievements.achievement_id', '=', 'achievements.id');
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeUnlockedFilter($query)
    {
        return $query->whereNotNull('unlocked_at');
    }

    public function scopeAvailableFilter($query)
    {
        return $query->whereNull('unlocked_at');
    }

    public function scopeByCategory($query, $category = null)
    {
        return $query->when($category, function ($q) use ($category) {
            return $q->whereHas('achievement', function ($achievementQ) use ($category) {
                $achievementQ->where('category', $category);
            });
        });
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('unlocked_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->whereHas('achievement', function ($achievementQ) use ($searchTerm) {
                $achievementQ
                    ->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'player:id,name',
            'achievement:id,name,description,category,points',
        ]);
    }
}
