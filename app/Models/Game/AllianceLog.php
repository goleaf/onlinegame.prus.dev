<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;

class AllianceLog extends Model
{
    use HasFactory, HasReference;

    protected $fillable = [
        'alliance_id',
        'player_id',
        'action',
        'description',
        'data',
        'ip_address',
        'user_agent',
        'reference_number',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // Action types
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_MEMBER_JOINED = 'member_joined';
    const ACTION_MEMBER_LEFT = 'member_left';
    const ACTION_MEMBER_KICKED = 'member_kicked';
    const ACTION_MEMBER_PROMOTED = 'member_promoted';
    const ACTION_MEMBER_DEMOTED = 'member_demoted';
    const ACTION_DIPLOMACY_PROPOSED = 'diplomacy_proposed';
    const ACTION_DIPLOMACY_ACCEPTED = 'diplomacy_accepted';
    const ACTION_DIPLOMACY_DECLINED = 'diplomacy_declined';
    const ACTION_DIPLOMACY_CANCELLED = 'diplomacy_cancelled';
    const ACTION_WAR_DECLARED = 'war_declared';
    const ACTION_WAR_ENDED = 'war_ended';
    const ACTION_MESSAGE_SENT = 'message_sent';
    const ACTION_SETTINGS_CHANGED = 'settings_changed';

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    // Scopes
    public function scopeByAlliance($query, $allianceId)
    {
        return $query->where('alliance_id', $allianceId);
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeMemberActions($query)
    {
        return $query->whereIn('action', [
            self::ACTION_MEMBER_JOINED,
            self::ACTION_MEMBER_LEFT,
            self::ACTION_MEMBER_KICKED,
            self::ACTION_MEMBER_PROMOTED,
            self::ACTION_MEMBER_DEMOTED,
        ]);
    }

    public function scopeDiplomacyActions($query)
    {
        return $query->whereIn('action', [
            self::ACTION_DIPLOMACY_PROPOSED,
            self::ACTION_DIPLOMACY_ACCEPTED,
            self::ACTION_DIPLOMACY_DECLINED,
            self::ACTION_DIPLOMACY_CANCELLED,
        ]);
    }

    public function scopeWarActions($query)
    {
        return $query->whereIn('action', [
            self::ACTION_WAR_DECLARED,
            self::ACTION_WAR_ENDED,
        ]);
    }

    // Helper methods
    public function isMemberAction(): bool
    {
        return in_array($this->action, [
            self::ACTION_MEMBER_JOINED,
            self::ACTION_MEMBER_LEFT,
            self::ACTION_MEMBER_KICKED,
            self::ACTION_MEMBER_PROMOTED,
            self::ACTION_MEMBER_DEMOTED,
        ]);
    }

    public function isDiplomacyAction(): bool
    {
        return in_array($this->action, [
            self::ACTION_DIPLOMACY_PROPOSED,
            self::ACTION_DIPLOMACY_ACCEPTED,
            self::ACTION_DIPLOMACY_DECLINED,
            self::ACTION_DIPLOMACY_CANCELLED,
        ]);
    }

    public function isWarAction(): bool
    {
        return in_array($this->action, [
            self::ACTION_WAR_DECLARED,
            self::ACTION_WAR_ENDED,
        ]);
    }

    public function getActionColor(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED, self::ACTION_MEMBER_JOINED, self::ACTION_DIPLOMACY_ACCEPTED => 'green',
            self::ACTION_UPDATED, self::ACTION_MEMBER_PROMOTED, self::ACTION_SETTINGS_CHANGED => 'blue',
            self::ACTION_DELETED, self::ACTION_MEMBER_LEFT, self::ACTION_MEMBER_KICKED, self::ACTION_DIPLOMACY_DECLINED => 'red',
            self::ACTION_WAR_DECLARED => 'red',
            self::ACTION_WAR_ENDED => 'green',
            self::ACTION_DIPLOMACY_PROPOSED, self::ACTION_DIPLOMACY_CANCELLED => 'yellow',
            self::ACTION_MESSAGE_SENT => 'purple',
            default => 'gray',
        };
    }

    public function getActionIcon(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'plus',
            self::ACTION_UPDATED => 'edit',
            self::ACTION_DELETED => 'trash',
            self::ACTION_MEMBER_JOINED => 'user-plus',
            self::ACTION_MEMBER_LEFT => 'user-minus',
            self::ACTION_MEMBER_KICKED => 'user-times',
            self::ACTION_MEMBER_PROMOTED => 'arrow-up',
            self::ACTION_MEMBER_DEMOTED => 'arrow-down',
            self::ACTION_DIPLOMACY_PROPOSED => 'handshake',
            self::ACTION_DIPLOMACY_ACCEPTED => 'check',
            self::ACTION_DIPLOMACY_DECLINED => 'times',
            self::ACTION_DIPLOMACY_CANCELLED => 'ban',
            self::ACTION_WAR_DECLARED => 'sword',
            self::ACTION_WAR_ENDED => 'peace',
            self::ACTION_MESSAGE_SENT => 'envelope',
            self::ACTION_SETTINGS_CHANGED => 'cog',
            default => 'info',
        };
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedData(): array
    {
        if (!$this->data) {
            return [];
        }

        return $this->data;
    }

    public static function logAction(int $allianceId, int $playerId, string $action, string $description, array $data = []): self
    {
        return self::create([
            'alliance_id' => $allianceId,
            'player_id' => $playerId,
            'action' => $action,
            'description' => $description,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reference_number' => self::generateReferenceNumber(),
        ]);
    }

    private static function generateReferenceNumber(): string
    {
        do {
            $reference = 'LOG-' . strtoupper(\Str::random(8));
        } while (self::where('reference_number', $reference)->exists());

        return $reference;
    }
}
