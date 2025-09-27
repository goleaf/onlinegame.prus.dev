<?php

namespace App\Models\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\Alliance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'type',
        'title',
        'message',
        'data',
        'priority',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the player that owns the notification
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the village associated with the notification
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    /**
     * Get the alliance associated with the notification
     */
    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for notifications by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for notifications by priority
     */
    public function scopeWithPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Get formatted priority
     */
    public function getFormattedPriorityAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => '🔴 Urgent',
            'high' => '🟠 High',
            'normal' => '🟡 Normal',
            'low' => '🟢 Low',
            default => '🟡 Normal'
        };
    }

    /**
     * Get formatted type
     */
    public function getFormattedTypeAttribute(): string
    {
        return match ($this->type) {
            'battle' => '⚔️ Battle',
            'movement' => '🚶 Movement',
            'building' => '🏗️ Building',
            'alliance' => '🤝 Alliance',
            'resource' => '💰 Resource',
            'system' => '⚙️ System',
            'achievement' => '🏆 Achievement',
            'quest' => '📋 Quest',
            default => '📢 ' . ucfirst($this->type)
        };
    }

    /**
     * Get time since creation
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if notification is recent (within last hour)
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->created_at->isAfter(now()->subHour());
    }

    /**
     * Check if notification is urgent
     */
    public function getIsUrgentAttribute(): bool
    {
        return in_array($this->priority, ['high', 'urgent']);
    }

    /**
     * Get notification summary for display
     */
    public function getSummaryAttribute(): string
    {
        $summary = $this->formatted_type . ' - ' . $this->title;
        
        if ($this->is_recent) {
            $summary .= ' (New)';
        }
        
        return $summary;
    }

    /**
     * Get notification data with defaults
     */
    public function getDataAttribute($value): array
    {
        $data = json_decode($value, true) ?? [];
        
        return array_merge([
            'village_name' => null,
            'alliance_name' => null,
            'player_name' => null,
            'amount' => null,
            'level' => null,
        ], $data);
    }
}