<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Notification extends Model implements Auditable
{
    use HasFactory, HasReference, AuditableTrait;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'data',
        'is_read',
        'read_at',
        'reference_number',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'NOT-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'NOT';

    // Notification types
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    const TYPE_SYSTEM = 'system';
    const TYPE_BATTLE = 'battle';
    const TYPE_MOVEMENT = 'movement';
    const TYPE_RESOURCE = 'resource';
    const TYPE_ALLIANCE = 'alliance';
    const TYPE_QUEST = 'quest';
    const TYPE_ACHIEVEMENT = 'achievement';

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
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
        return $query->where('type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            self::TYPE_SUCCESS => 'green',
            self::TYPE_WARNING => 'yellow',
            self::TYPE_ERROR => 'red',
            self::TYPE_SYSTEM => 'blue',
            self::TYPE_BATTLE => 'purple',
            self::TYPE_MOVEMENT => 'indigo',
            self::TYPE_RESOURCE => 'orange',
            self::TYPE_ALLIANCE => 'pink',
            self::TYPE_QUEST => 'teal',
            self::TYPE_ACHIEVEMENT => 'gold',
            default => 'gray',
        };
    }

    public function getTypeIcon(): string
    {
        return match ($this->type) {
            self::TYPE_SUCCESS => 'check-circle',
            self::TYPE_WARNING => 'exclamation-triangle',
            self::TYPE_ERROR => 'x-circle',
            self::TYPE_SYSTEM => 'cog',
            self::TYPE_BATTLE => 'sword',
            self::TYPE_MOVEMENT => 'arrow-right',
            self::TYPE_RESOURCE => 'cube',
            self::TYPE_ALLIANCE => 'users',
            self::TYPE_QUEST => 'book-open',
            self::TYPE_ACHIEVEMENT => 'trophy',
            default => 'bell',
        };
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedReadAt(): ?string
    {
        return $this->read_at ? $this->read_at->diffForHumans() : null;
    }

    public function isRecent(): bool
    {
        return $this->created_at->isAfter(now()->subHours(24));
    }

    public function getPriority(): string
    {
        return match ($this->type) {
            self::TYPE_ERROR, self::TYPE_BATTLE => 'high',
            self::TYPE_WARNING, self::TYPE_SYSTEM => 'medium',
            default => 'low',
        };
    }

    public function getDataValue(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function setDataValue(string $key, $value): void
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->update(['data' => $data]);
    }

    public function getShortMessage(int $length = 100): string
    {
        return strlen($this->message) > $length 
            ? substr($this->message, 0, $length) . '...'
            : $this->message;
    }

    public function getNotificationUrl(): ?string
    {
        $data = $this->data ?? [];
        
        return match ($this->type) {
            self::TYPE_BATTLE => $data['battle_id'] ? "/game/battle/{$data['battle_id']}" : null,
            self::TYPE_MOVEMENT => $data['movement_id'] ? "/game/movement/{$data['movement_id']}" : null,
            self::TYPE_RESOURCE => $data['village_id'] ? "/game/village/{$data['village_id']}" : null,
            self::TYPE_ALLIANCE => $data['alliance_id'] ? "/game/alliance/{$data['alliance_id']}" : null,
            self::TYPE_QUEST => $data['quest_id'] ? "/game/quest/{$data['quest_id']}" : null,
            self::TYPE_ACHIEVEMENT => $data['achievement_id'] ? "/game/achievement/{$data['achievement_id']}" : null,
            default => null,
        };
    }
}