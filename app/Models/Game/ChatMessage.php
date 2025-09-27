<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SmartCache\Facades\SmartCache;
use MohamedSaid\Referenceable\Traits\HasReference;

class ChatMessage extends Model
{
    use HasFactory, HasReference;

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

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';
    protected $referenceTemplate = [
        'format' => 'CHT-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];
    protected $referencePrefix = 'CHT';

    // Channel types
    const CHANNEL_GLOBAL = 'global';
    const CHANNEL_ALLIANCE = 'alliance';
    const CHANNEL_PRIVATE = 'private';
    const CHANNEL_TRADE = 'trade';
    const CHANNEL_DIPLOMACY = 'diplomacy';

    // Message types
    const TYPE_TEXT = 'text';
    const TYPE_SYSTEM = 'system';
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_EMOTE = 'emote';
    const TYPE_COMMAND = 'command';

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'sender_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }

    // Scopes
    public function scopeForChannel($query, $channelId, $channelType = null)
    {
        $query = $query->where('channel_id', $channelId);
        
        if ($channelType) {
            $query->where('channel_type', $channelType);
        }
        
        return $query;
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            chat_messages.*,
            (SELECT COUNT(*) FROM chat_messages cm2 WHERE cm2.sender_id = chat_messages.sender_id) as sender_total_messages,
            (SELECT COUNT(*) FROM chat_messages cm3 WHERE cm3.channel_id = chat_messages.channel_id AND cm3.channel_type = chat_messages.channel_type) as channel_total_messages,
            (SELECT COUNT(*) FROM chat_messages cm4 WHERE cm4.sender_id = chat_messages.sender_id AND cm4.is_deleted = 0) as sender_active_messages,
            (SELECT AVG(LENGTH(message)) FROM chat_messages cm5 WHERE cm5.sender_id = chat_messages.sender_id) as sender_avg_message_length
        ');
    }

    public function scopeBySender($query, $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    public function scopeByChannelType($query, $channelType = null)
    {
        return $query->when($channelType, function ($q) use ($channelType) {
            return $q->where('channel_type', $channelType);
        });
    }

    public function scopeByMessageType($query, $messageType = null)
    {
        return $query->when($messageType, function ($q) use ($messageType) {
            return $q->where('message_type', $messageType);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeDeleted($query)
    {
        return $query->where('is_deleted', true);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->where('created_at', '>=', now()->startOfWeek());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where('message', 'like', '%' . $searchTerm . '%');
        });
    }

    public function scopeWithSenderInfo($query)
    {
        return $query->with([
            'sender:id,name,alliance_id'
        ]);
    }

    // Methods
    public function softDelete(): void
    {
        $this->update([
            'is_deleted' => true,
            'deleted_at' => now(),
        ]);
    }

    public function isDeleted(): bool
    {
        return $this->is_deleted;
    }

    public function canBeDeletedBy($playerId): bool
    {
        return $this->sender_id === $playerId;
    }

    public function getFormattedMessage(): string
    {
        if ($this->is_deleted) {
            return '[Message deleted]';
        }

        return $this->message;
    }

    public static function getChannelMessages($channelId, $channelType, $limit = 50, $offset = 0): array
    {
        return SmartCache::remember("chat_messages:{$channelType}:{$channelId}:{$limit}:{$offset}", 60, function () use ($channelId, $channelType, $limit, $offset) {
            $messages = self::with(['sender'])
                ->forChannel($channelId, $channelType)
                ->notDeleted()
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->reverse()
                ->values();

            $total = self::forChannel($channelId, $channelType)
                ->notDeleted()
                ->count();

            return [
                'messages' => $messages,
                'total' => $total,
            ];
        });
    }

    public static function getGlobalMessages($limit = 50, $offset = 0): array
    {
        return self::getChannelMessages(0, self::CHANNEL_GLOBAL, $limit, $offset);
    }

    public static function getAllianceMessages($allianceId, $limit = 50, $offset = 0): array
    {
        return self::getChannelMessages($allianceId, self::CHANNEL_ALLIANCE, $limit, $offset);
    }

    public static function getPrivateMessages($playerId, $otherPlayerId, $limit = 50, $offset = 0): array
    {
        $channelId = min($playerId, $otherPlayerId) . '_' . max($playerId, $otherPlayerId);
        return self::getChannelMessages($channelId, self::CHANNEL_PRIVATE, $limit, $offset);
    }

    public static function cleanupOldMessages($days = 30): int
    {
        $cutoffDate = now()->subDays($days);
        
        return self::where('created_at', '<', $cutoffDate)
            ->where('channel_type', self::CHANNEL_GLOBAL)
            ->delete();
    }

    public static function getMessageStats(): array
    {
        return SmartCache::remember('chat_message_stats', 300, function () {
            return [
                'total_messages' => self::notDeleted()->count(),
                'global_messages' => self::where('channel_type', self::CHANNEL_GLOBAL)->notDeleted()->count(),
                'alliance_messages' => self::where('channel_type', self::CHANNEL_ALLIANCE)->notDeleted()->count(),
                'private_messages' => self::where('channel_type', self::CHANNEL_PRIVATE)->notDeleted()->count(),
                'messages_today' => self::whereDate('created_at', today())->notDeleted()->count(),
                'active_channels' => self::select('channel_id', 'channel_type')
                    ->distinct()
                    ->count(),
            ];
        });
    }
}
