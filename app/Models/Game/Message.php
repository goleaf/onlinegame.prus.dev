<?php

namespace App\Models\Game;

use App\Traits\Commentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use SmartCache\Facades\SmartCache;

class Message extends Model
{
    use HasFactory, HasReference, Commentable;

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

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'MSG-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'MSG';

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

    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_message_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForPlayer($query, $playerId)
    {
        return $query->where(function ($q) use ($playerId) {
            $q
                ->where('recipient_id', $playerId)
                ->orWhere('sender_id', $playerId);
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeNotDeleted($query, $playerId)
    {
        return $query->where(function ($q) use ($playerId) {
            $q->where(function ($subQ) use ($playerId) {
                $subQ
                    ->where('sender_id', $playerId)
                    ->where('is_deleted_by_sender', false);
            })->orWhere(function ($subQ) use ($playerId) {
                $subQ
                    ->where('recipient_id', $playerId)
                    ->where('is_deleted_by_recipient', false);
            });
        });
    }

    public function scopeAllianceMessages($query, $allianceId)
    {
        return $query
            ->where('alliance_id', $allianceId)
            ->where('message_type', self::TYPE_ALLIANCE);
    }

    // Methods
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);

        // Clear cache for this player's unread count
        SmartCache::forget("unread_messages_count:{$this->recipient_id}");
    }

    public function markAsDeleted($playerId): void
    {
        if ($this->sender_id === $playerId) {
            $this->update(['is_deleted_by_sender' => true]);
        } elseif ($this->recipient_id === $playerId) {
            $this->update(['is_deleted_by_recipient' => true]);
        }
    }

    public function isDeleted($playerId): bool
    {
        if ($this->sender_id === $playerId) {
            return $this->is_deleted_by_sender;
        } elseif ($this->recipient_id === $playerId) {
            return $this->is_deleted_by_recipient;
        }

        return false;
    }

    public function canBeDeleted($playerId): bool
    {
        return $this->sender_id === $playerId || $this->recipient_id === $playerId;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getUnreadCountForPlayer($playerId): int
    {
        return SmartCache::remember("unread_messages_count:{$playerId}", 300, function () use ($playerId) {
            return self::where('recipient_id', $playerId)
                ->where('is_read', false)
                ->where('is_deleted_by_recipient', false)
                ->count();
        });
    }

    public function getInboxForPlayer($playerId, $limit = 50)
    {
        return self::with(['sender', 'recipient'])
            ->where('recipient_id', $playerId)
            ->where('is_deleted_by_recipient', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getSentForPlayer($playerId, $limit = 50)
    {
        return self::with(['sender', 'recipient'])
            ->where('sender_id', $playerId)
            ->where('is_deleted_by_sender', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getConversation($playerId, $otherPlayerId)
    {
        return self::with(['sender', 'recipient'])
            ->where(function ($q) use ($playerId, $otherPlayerId) {
                $q
                    ->where('sender_id', $playerId)
                    ->where('recipient_id', $otherPlayerId);
            })
            ->orWhere(function ($q) use ($playerId, $otherPlayerId) {
                $q
                    ->where('sender_id', $otherPlayerId)
                    ->where('recipient_id', $playerId);
            })
            ->where('message_type', self::TYPE_PRIVATE)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public static function createSystemMessage($recipientId, $subject, $body, $priority = self::PRIORITY_NORMAL)
    {
        return self::create([
            'sender_id' => null,  // System message
            'recipient_id' => $recipientId,
            'subject' => $subject,
            'body' => $body,
            'message_type' => self::TYPE_SYSTEM,
            'priority' => $priority,
            'is_read' => false,
        ]);
    }

    public static function createAllianceMessage($allianceId, $senderId, $subject, $body, $priority = self::PRIORITY_NORMAL)
    {
        return self::create([
            'sender_id' => $senderId,
            'alliance_id' => $allianceId,
            'subject' => $subject,
            'body' => $body,
            'message_type' => self::TYPE_ALLIANCE,
            'priority' => $priority,
            'is_read' => false,
        ]);
    }

    public static function createBattleReportMessage($recipientId, $battleId, $subject, $body)
    {
        return self::create([
            'sender_id' => null,  // System generated
            'recipient_id' => $recipientId,
            'subject' => $subject,
            'body' => $body,
            'message_type' => self::TYPE_BATTLE_REPORT,
            'priority' => self::PRIORITY_HIGH,
            'is_read' => false,
        ]);
    }

    public static function cleanupExpiredMessages(): int
    {
        return self::where('expires_at', '<', now())
            ->delete();
    }

    public static function getMessageStatsForPlayer($playerId): array
    {
        return SmartCache::remember("message_stats:{$playerId}", 600, function () use ($playerId) {
            return [
                'total_messages' => self::forPlayer($playerId)->count(),
                'unread_messages' => self::where('recipient_id', $playerId)
                    ->where('is_read', false)
                    ->where('is_deleted_by_recipient', false)
                    ->count(),
                'sent_messages' => self::where('sender_id', $playerId)
                    ->where('is_deleted_by_sender', false)
                    ->count(),
                'received_messages' => self::where('recipient_id', $playerId)
                    ->where('is_deleted_by_recipient', false)
                    ->count(),
                'alliance_messages' => self::where('recipient_id', $playerId)
                    ->where('message_type', self::TYPE_ALLIANCE)
                    ->where('is_deleted_by_recipient', false)
                    ->count(),
                'system_messages' => self::where('recipient_id', $playerId)
                    ->where('message_type', self::TYPE_SYSTEM)
                    ->where('is_deleted_by_recipient', false)
                    ->count(),
            ];
        });
    }
}
