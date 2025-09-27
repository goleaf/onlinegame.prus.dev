<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasReference;

class Message extends Model
{
    use HasFactory, HasReference;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'alliance_id',
        'subject',
        'body',
        'message_type',
        'is_read',
        'is_deleted_by_sender',
        'is_deleted_by_recipient',
        'parent_message_id',
        'priority',
        'expires_at',
        'reference_number',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_deleted_by_sender' => 'boolean',
        'is_deleted_by_recipient' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // Message types
    const TYPE_PRIVATE = 'private';
    const TYPE_ALLIANCE = 'alliance';
    const TYPE_SYSTEM = 'system';
    const TYPE_BATTLE_REPORT = 'battle_report';
    const TYPE_TRADE_OFFER = 'trade_offer';
    const TYPE_DIPLOMACY = 'diplomacy';

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'recipient_id');
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_message_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeForPlayer($query, $playerId)
    {
        return $query->where(function ($q) use ($playerId) {
            $q->where('sender_id', $playerId)
              ->orWhere('recipient_id', $playerId);
        });
    }

    public function scopeInbox($query, $playerId)
    {
        return $query->where('recipient_id', $playerId)
                    ->where('is_deleted_by_recipient', false);
    }

    public function scopeSent($query, $playerId)
    {
        return $query->where('sender_id', $playerId)
                    ->where('is_deleted_by_sender', false);
    }

    public function scopeAllianceMessages($query, $allianceId)
    {
        return $query->where('alliance_id', $allianceId)
                    ->where('message_type', self::TYPE_ALLIANCE);
    }

    public function scopeSystemMessages($query)
    {
        return $query->where('message_type', self::TYPE_SYSTEM);
    }

    public function scopeBattleReports($query)
    {
        return $query->where('message_type', self::TYPE_BATTLE_REPORT);
    }

    public function scopeTradeOffers($query)
    {
        return $query->where('message_type', self::TYPE_TRADE_OFFER);
    }

    public function scopeDiplomacyMessages($query)
    {
        return $query->where('message_type', self::TYPE_DIPLOMACY);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    public function isPrivate(): bool
    {
        return $this->message_type === self::TYPE_PRIVATE;
    }

    public function isAlliance(): bool
    {
        return $this->message_type === self::TYPE_ALLIANCE;
    }

    public function isSystem(): bool
    {
        return $this->message_type === self::TYPE_SYSTEM;
    }

    public function isBattleReport(): bool
    {
        return $this->message_type === self::TYPE_BATTLE_REPORT;
    }

    public function isTradeOffer(): bool
    {
        return $this->message_type === self::TYPE_TRADE_OFFER;
    }

    public function isDiplomacy(): bool
    {
        return $this->message_type === self::TYPE_DIPLOMACY;
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function isUrgent(): bool
    {
        return $this->priority === self::PRIORITY_URGENT;
    }

    public function canBeReadBy(int $playerId): bool
    {
        return $this->recipient_id === $playerId && !$this->is_deleted_by_recipient;
    }

    public function canBeDeletedBy(int $playerId): bool
    {
        return ($this->sender_id === $playerId && !$this->is_deleted_by_sender) ||
               ($this->recipient_id === $playerId && !$this->is_deleted_by_recipient);
    }

    public function getOtherPlayer(int $currentPlayerId): ?Player
    {
        if ($this->sender_id === $currentPlayerId) {
            return $this->recipient;
        }

        if ($this->recipient_id === $currentPlayerId) {
            return $this->sender;
        }

        return null;
    }

    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'red',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_LOW => 'gray',
            default => 'gray',
        };
    }

    public function getTypeIcon(): string
    {
        return match ($this->message_type) {
            self::TYPE_PRIVATE => 'envelope',
            self::TYPE_ALLIANCE => 'users',
            self::TYPE_SYSTEM => 'cog',
            self::TYPE_BATTLE_REPORT => 'sword',
            self::TYPE_TRADE_OFFER => 'exchange-alt',
            self::TYPE_DIPLOMACY => 'handshake',
            default => 'envelope',
        };
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getExcerpt(int $length = 100): string
    {
        return \Str::limit(strip_tags($this->body), $length);
    }
}