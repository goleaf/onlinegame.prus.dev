<?php

namespace App\Models\Game;

use App\Traits\Commentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ChatMessage extends Model implements Auditable
{
    use AuditableTrait;
    use Commentable;
    use HasFactory;
    use HasReference;

    protected $fillable = [
        'sender_id',
        'channel_id',
        'channel_type',
        'message',
        'message_type',
        'is_deleted',
        'deleted_at',
        'reference_number',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Channel types
    public const CHANNEL_GLOBAL = 'global';

    public const CHANNEL_ALLIANCE = 'alliance';

    public const CHANNEL_PRIVATE = 'private';

    public const CHANNEL_TRADE = 'trade';

    public const CHANNEL_DIPLOMACY = 'diplomacy';

    // Message types
    public const TYPE_TEXT = 'text';

    public const TYPE_SYSTEM = 'system';

    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const TYPE_EMOTE = 'emote';

    public const TYPE_COMMAND = 'command';

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'sender_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChatChannel::class);
    }

    // Scopes
    public function scopeByChannel($query, $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeByChannelType($query, $channelType)
    {
        return $query->where('channel_type', $channelType);
    }

    public function scopeBySender($query, $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    public function scopeByMessageType($query, $messageType)
    {
        return $query->where('message_type', $messageType);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeDeleted($query)
    {
        return $query->where('is_deleted', true);
    }

    public function scopeGlobal($query)
    {
        return $query->where('channel_type', self::CHANNEL_GLOBAL);
    }

    public function scopeAlliance($query)
    {
        return $query->where('channel_type', self::CHANNEL_ALLIANCE);
    }

    public function scopePrivate($query)
    {
        return $query->where('channel_type', self::CHANNEL_PRIVATE);
    }

    public function scopeTrade($query)
    {
        return $query->where('channel_type', self::CHANNEL_TRADE);
    }

    public function scopeDiplomacy($query)
    {
        return $query->where('channel_type', self::CHANNEL_DIPLOMACY);
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    // Helper methods
    public function isDeleted(): bool
    {
        return $this->is_deleted;
    }

    public function isGlobal(): bool
    {
        return $this->channel_type === self::CHANNEL_GLOBAL;
    }

    public function isAlliance(): bool
    {
        return $this->channel_type === self::CHANNEL_ALLIANCE;
    }

    public function isPrivate(): bool
    {
        return $this->channel_type === self::CHANNEL_PRIVATE;
    }

    public function isTrade(): bool
    {
        return $this->channel_type === self::CHANNEL_TRADE;
    }

    public function isDiplomacy(): bool
    {
        return $this->channel_type === self::CHANNEL_DIPLOMACY;
    }

    public function isText(): bool
    {
        return $this->message_type === self::TYPE_TEXT;
    }

    public function isSystem(): bool
    {
        return $this->message_type === self::TYPE_SYSTEM;
    }

    public function isAnnouncement(): bool
    {
        return $this->message_type === self::TYPE_ANNOUNCEMENT;
    }

    public function isEmote(): bool
    {
        return $this->message_type === self::TYPE_EMOTE;
    }

    public function isCommand(): bool
    {
        return $this->message_type === self::TYPE_COMMAND;
    }

    public function canBeDeletedBy(int $playerId): bool
    {
        return $this->sender_id === $playerId;
    }

    public function getChannelTypeColor(): string
    {
        return match ($this->channel_type) {
            self::CHANNEL_GLOBAL => 'blue',
            self::CHANNEL_ALLIANCE => 'green',
            self::CHANNEL_PRIVATE => 'purple',
            self::CHANNEL_TRADE => 'yellow',
            self::CHANNEL_DIPLOMACY => 'red',
            default => 'gray',
        };
    }

    public function getMessageTypeIcon(): string
    {
        return match ($this->message_type) {
            self::TYPE_TEXT => 'comment',
            self::TYPE_SYSTEM => 'cog',
            self::TYPE_ANNOUNCEMENT => 'bullhorn',
            self::TYPE_EMOTE => 'smile',
            self::TYPE_COMMAND => 'terminal',
            default => 'comment',
        };
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedMessage(): string
    {
        if ($this->isEmote()) {
            return "*{$this->sender->name} {$this->message}*";
        }

        return $this->message;
    }

    public function softDelete(): bool
    {
        return $this->update([
            'is_deleted' => true,
            'deleted_at' => now(),
        ]);
    }

    public function restore(): bool
    {
        return $this->update([
            'is_deleted' => false,
            'deleted_at' => null,
        ]);
    }

    public static function createMessage(int $senderId, ?int $channelId, string $channelType, string $message, string $messageType = self::TYPE_TEXT): self
    {
        return self::create([
            'sender_id' => $senderId,
            'channel_id' => $channelId,
            'channel_type' => $channelType,
            'message' => $message,
            'message_type' => $messageType,
            'reference_number' => self::generateReferenceNumber(),
        ]);
    }

    private static function generateReferenceNumber(): string
    {
        do {
            $reference = 'CHAT-'.strtoupper(\Str::random(8));
        } while (self::where('reference_number', $reference)->exists());

        return $reference;
    }

    /**
     * Get chat messages with SmartCache optimization
     */
    public static function getCachedMessages($channelId = null, $filters = [])
    {
        $cacheKey = "chat_messages_{$channelId}_".md5(serialize($filters));

        return SmartCache::remember($cacheKey, now()->addMinutes(3), function () use ($channelId, $filters) {
            $query = static::with(['sender']);

            if ($channelId) {
                $query->where('channel_id', $channelId);
            }

            if (isset($filters['type'])) {
                $query->where('message_type', $filters['type']);
            }

            if (isset($filters['recent'])) {
                $query->where('created_at', '>', now()->subHours($filters['recent']));
            }

            return $query->orderBy('created_at', 'desc')->get();
        });
    }
}
