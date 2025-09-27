<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MohamedSaid\Referenceable\Traits\HasReference;

class Notification extends Model
{
    use HasFactory, HasTaxonomy, HasReference;

    protected $fillable = [
        'player_id',
        'type',
        'title',
        'message',
        'data',
        'priority',
        'read_at',
        'sent_at',
        'reference_number',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'NTF-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'NTF';

    /**
     * Get the player that owns the notification
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the user associated with the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id', 'id')
            ->through('player');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeForPlayer($query, int $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Methods
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): bool
    {
        if ($this->isRead()) {
            return false;
        }

        $this->update(['read_at' => now()]);
        return true;
    }

    public function markAsUnread(): bool
    {
        if ($this->isUnread()) {
            return false;
        }

        $this->update(['read_at' => null]);
        return true;
    }

    public function getPriorityLevel(): int
    {
        return match($this->priority) {
            'low' => 1,
            'normal' => 2,
            'high' => 3,
            'urgent' => 4,
            default => 2,
        };
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedReadAt(): ?string
    {
        return $this->read_at?->diffForHumans();
    }

    public function getTimeToRead(): ?int
    {
        if (!$this->read_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->read_at);
    }

    public function getNotificationIcon(): string
    {
        return match($this->type) {
            'battle' => '⚔️',
            'alliance' => '🤝',
            'village' => '🏘️',
            'quest' => '📜',
            'achievement' => '🏆',
            'system' => '⚙️',
            'maintenance' => '🔧',
            'trade' => '💰',
            'wonder' => '🏛️',
            'event' => '🎉',
            default => '📢',
        };
    }

    public function getNotificationColor(): string
    {
        return match($this->priority) {
            'low' => 'text-gray-500',
            'normal' => 'text-blue-600',
            'high' => 'text-orange-600',
            'urgent' => 'text-red-600',
            default => 'text-blue-600',
        };
    }

    public function getNotificationBadge(): string
    {
        return match($this->priority) {
            'low' => 'bg-gray-100 text-gray-800',
            'normal' => 'bg-blue-100 text-blue-800',
            'high' => 'bg-orange-100 text-orange-800',
            'urgent' => 'bg-red-100 text-red-800',
            default => 'bg-blue-100 text-blue-800',
        };
    }

    public function getDataAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getShortMessage(int $length = 100): string
    {
        return strlen($this->message) > $length 
            ? substr($this->message, 0, $length) . '...'
            : $this->message;
    }

    public function getFormattedData(): array
    {
        $data = $this->data;
        
        // Format specific data types
        if (isset($data['attack_time'])) {
            $data['attack_time'] = \Carbon\Carbon::parse($data['attack_time'])->format('Y-m-d H:i:s');
        }
        
        if (isset($data['war_duration'])) {
            $data['war_duration'] = $data['war_duration'] . ' hours';
        }
        
        return $data;
    }

    // Static methods
    public static function getUnreadCountForPlayer(int $playerId): int
    {
        return static::where('player_id', $playerId)
            ->whereNull('read_at')
            ->count();
    }

    public static function getRecentNotificationsForPlayer(int $playerId, int $limit = 10)
    {
        return static::where('player_id', $playerId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getNotificationStats(): array
    {
        return [
            'total' => static::count(),
            'unread' => static::whereNull('read_at')->count(),
            'read' => static::whereNotNull('read_at')->count(),
            'by_type' => static::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_priority' => static::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
        ];
    }
}