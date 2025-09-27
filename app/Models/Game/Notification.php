<?php

namespace App\Models\Game;

use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use WendellAdriel\Lift\Lift;

class Notification extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'player_id',
        'title',
        'message',
        'type',
        'priority',
        'status',
        'data',
        'icon',
        'action_url',
        'read_at',
        'expires_at',
        'is_persistent',
        'is_auto_dismiss',
        'auto_dismiss_seconds',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_persistent' => 'boolean',
        'is_auto_dismiss' => 'boolean',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';
    protected $referenceTemplate = [
        'format' => 'NOT-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];
    protected $referencePrefix = 'NOT';

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopePersistent($query)
    {
        return $query->where('is_persistent', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('status', '!=', 'dismissed')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    // Helper methods
    public function isUnread(): bool
    {
        return $this->status === 'unread';
    }

    public function isRead(): bool
    {
        return $this->status === 'read';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at <= now();
    }

    public function markAsRead(): bool
    {
        if ($this->status === 'read') {
            return false;
        }

        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);

        return true;
    }

    public function dismiss(): bool
    {
        if ($this->status === 'dismissed') {
            return false;
        }

        $this->update(['status' => 'dismissed']);
        return true;
    }

    public function getTypeDisplayNameAttribute(): string
    {
        return match($this->type) {
            'info' => 'Information',
            'warning' => 'Warning',
            'success' => 'Success',
            'error' => 'Error',
            'achievement' => 'Achievement',
            'battle' => 'Battle Report',
            'trade' => 'Trade Update',
            'diplomacy' => 'Diplomatic Message',
            'artifact' => 'Artifact Discovery',
            default => ucfirst($this->type)
        };
    }

    public function getPriorityDisplayNameAttribute(): string
    {
        return ucfirst($this->priority);
    }

    public function getStatusDisplayNameAttribute(): string
    {
        return match($this->status) {
            'unread' => 'Unread',
            'read' => 'Read',
            'dismissed' => 'Dismissed',
            default => ucfirst($this->status)
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'info' => '#3B82F6',        // Blue
            'warning' => '#F59E0B',     // Orange
            'success' => '#10B981',     // Green
            'error' => '#EF4444',       // Red
            'achievement' => '#8B5CF6', // Purple
            'battle' => '#DC2626',      // Dark Red
            'trade' => '#059669',       // Emerald
            'diplomacy' => '#7C3AED',   // Violet
            'artifact' => '#F59E0B',    // Amber
            default => '#6B7280'        // Gray
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => '#9CA3AF',      // Gray
            'normal' => '#3B82F6',   // Blue
            'high' => '#F59E0B',     // Orange
            'urgent' => '#EF4444',   // Red
            default => '#6B7280'
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'info' => 'info-circle',
            'warning' => 'exclamation-triangle',
            'success' => 'check-circle',
            'error' => 'times-circle',
            'achievement' => 'trophy',
            'battle' => 'sword',
            'trade' => 'exchange-alt',
            'diplomacy' => 'handshake',
            'artifact' => 'gem',
            default => 'bell'
        };
    }

    public function getRemainingTimeAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, $this->expires_at->diffInMinutes(now()));
    }

    public function getTimeRemainingFormattedAttribute(): ?string
    {
        $minutes = $this->remaining_time;
        
        if ($minutes === null) {
            return 'Never expires';
        }

        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours} hours";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0 ? "{$days}d {$remainingHours}h" : "{$days} days";
    }

    /**
     * Create notification with integration
     */
    public static function createWithIntegration(array $data): self
    {
        $notification = self::create($data);
        
        // Send real-time notification
        GameNotificationService::sendNotification(
            [$notification->player_id],
            $notification->type,
            [
                'notification_id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'priority' => $notification->priority,
                'data' => $notification->data,
            ],
            $notification->priority
        );

        return $notification;
    }

    /**
     * Mark as read with integration
     */
    public function markAsReadWithIntegration(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);

        // Send read confirmation notification
        GameNotificationService::markNotificationAsRead(
            $this->player_id,
            $this->id
        );
    }

    /**
     * Send system-wide notification with integration
     */
    public static function sendSystemNotificationWithIntegration(string $title, string $message, string $priority = 'normal'): void
    {
        GameIntegrationService::sendSystemAnnouncement($title, $message, $priority);
    }
}