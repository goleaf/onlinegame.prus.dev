<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
