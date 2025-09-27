<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MohamedSaid\Referenceable\Traits\HasReference;

class AllianceMessage extends Model
{
    use HasFactory, HasReference;

    protected $fillable = [
        'alliance_id',
        'sender_id',
        'subject',
        'body',
        'message_type',
        'priority',
        'is_pinned',
        'is_announcement',
        'expires_at',
        'reference_number',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_announcement' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // Message types
    const TYPE_GENERAL = 'general';
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_WAR = 'war';
    const TYPE_DIPLOMACY = 'diplomacy';
    const TYPE_TRADE = 'trade';
    const TYPE_STRATEGY = 'strategy';
    const TYPE_SOCIAL = 'social';

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'sender_id');
    }

    // Scopes
    public function scopeByAlliance($query, $allianceId)
    {
        return $query->where('alliance_id', $allianceId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeAnnouncements($query)
    {
        return $query->where('is_announcement', true);
    }

    public function scopeGeneral($query)
    {
        return $query->where('message_type', self::TYPE_GENERAL);
    }

    public function scopeWar($query)
    {
        return $query->where('message_type', self::TYPE_WAR);
    }

    public function scopeDiplomacy($query)
    {
        return $query->where('message_type', self::TYPE_DIPLOMACY);
    }

    public function scopeTrade($query)
    {
        return $query->where('message_type', self::TYPE_TRADE);
    }

    public function scopeStrategy($query)
    {
        return $query->where('message_type', self::TYPE_STRATEGY);
    }

    public function scopeSocial($query)
    {
        return $query->where('message_type', self::TYPE_SOCIAL);
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

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    public function isAnnouncement(): bool
    {
        return $this->is_announcement;
    }

    public function isGeneral(): bool
    {
        return $this->message_type === self::TYPE_GENERAL;
    }

    public function isWar(): bool
    {
        return $this->message_type === self::TYPE_WAR;
    }

    public function isDiplomacy(): bool
    {
        return $this->message_type === self::TYPE_DIPLOMACY;
    }

    public function isTrade(): bool
    {
        return $this->message_type === self::TYPE_TRADE;
    }

    public function isStrategy(): bool
    {
        return $this->message_type === self::TYPE_STRATEGY;
    }

    public function isSocial(): bool
    {
        return $this->message_type === self::TYPE_SOCIAL;
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function isUrgent(): bool
    {
        return $this->priority === self::PRIORITY_URGENT;
    }

    public function canBeEditedBy(int $playerId): bool
    {
        return $this->sender_id === $playerId;
    }

    public function canBeDeletedBy(int $playerId): bool
    {
        return $this->sender_id === $playerId;
    }

    public function canBePinnedBy(int $playerId): bool
    {
        // Only alliance leaders and officers can pin messages
        $allianceMember = $this->alliance->members()
            ->where('player_id', $playerId)
            ->first();

        return $allianceMember && in_array($allianceMember->role, ['leader', 'officer']);
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
            self::TYPE_GENERAL => 'comment',
            self::TYPE_ANNOUNCEMENT => 'bullhorn',
            self::TYPE_WAR => 'sword',
            self::TYPE_DIPLOMACY => 'handshake',
            self::TYPE_TRADE => 'exchange-alt',
            self::TYPE_STRATEGY => 'chess',
            self::TYPE_SOCIAL => 'users',
            default => 'comment',
        };
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getExcerpt(int $length = 150): string
    {
        return \Str::limit(strip_tags($this->body), $length);
    }

    public function getFormattedBody(): string
    {
        // Convert line breaks to HTML
        return nl2br(e($this->body));
    }

    public static function createMessage(int $allianceId, int $senderId, string $subject, string $body, string $type = self::TYPE_GENERAL, string $priority = self::PRIORITY_NORMAL): self
    {
        return self::create([
            'alliance_id' => $allianceId,
            'sender_id' => $senderId,
            'subject' => $subject,
            'body' => $body,
            'message_type' => $type,
            'priority' => $priority,
            'reference_number' => self::generateReferenceNumber(),
        ]);
    }

    private static function generateReferenceNumber(): string
    {
        do {
            $reference = 'AMSG-' . strtoupper(\Str::random(8));
        } while (self::where('reference_number', $reference)->exists());

        return $reference;
    }
}