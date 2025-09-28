<?php

namespace App\Models\Game;

use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ChatChannel extends Model implements Auditable
{
    use AuditableTrait;
    use HasFactory;
    use HasReference;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'alliance_id',
        'created_by',
        'is_active',
        'is_public',
        'settings',
        'reference_number',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'settings' => 'array',
    ];

    // Channel types
    public const TYPE_GLOBAL = 'global';

    public const TYPE_ALLIANCE = 'alliance';

    public const TYPE_PRIVATE = 'private';

    public const TYPE_TRADE = 'trade';

    public const TYPE_DIPLOMACY = 'diplomacy';

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByAlliance($query, $allianceId)
    {
        return $query->where('alliance_id', $allianceId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeGlobal($query)
    {
        return $query->where('type', self::TYPE_GLOBAL);
    }

    public function scopeAlliance($query)
    {
        return $query->where('type', self::TYPE_ALLIANCE);
    }

    public function scopeTrade($query)
    {
        return $query->where('type', self::TYPE_TRADE);
    }

    public function scopeDiplomacy($query)
    {
        return $query->where('type', self::TYPE_DIPLOMACY);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isPublic(): bool
    {
        return $this->is_public;
    }

    public function isPrivate(): bool
    {
        return ! $this->is_public;
    }

    public function isGlobal(): bool
    {
        return $this->type === self::TYPE_GLOBAL;
    }

    public function isAlliance(): bool
    {
        return $this->type === self::TYPE_ALLIANCE;
    }

    public function isTrade(): bool
    {
        return $this->type === self::TYPE_TRADE;
    }

    public function isDiplomacy(): bool
    {
        return $this->type === self::TYPE_DIPLOMACY;
    }

    public function canBeAccessedBy(int $playerId): bool
    {
        if ($this->isGlobal()) {
            return true;
        }

        if ($this->isAlliance()) {
            $player = Player::find($playerId);

            return $player && $player->alliance_id === $this->alliance_id;
        }

        if ($this->isPrivate()) {
            // Add private channel access logic here
            return true;
        }

        return true;
    }

    public function canBeModifiedBy(int $playerId): bool
    {
        if ($this->created_by === $playerId) {
            return true;
        }

        if ($this->isAlliance()) {
            $player = Player::find($playerId);
            if ($player && $player->alliance_id === $this->alliance_id) {
                $allianceMember = $this->alliance->members()
                    ->where('player_id', $playerId)
                    ->first();

                return $allianceMember && in_array($allianceMember->role, ['leader', 'officer']);
            }
        }

        return false;
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            self::TYPE_GLOBAL => 'blue',
            self::TYPE_ALLIANCE => 'green',
            self::TYPE_PRIVATE => 'purple',
            self::TYPE_TRADE => 'yellow',
            self::TYPE_DIPLOMACY => 'red',
            default => 'gray',
        };
    }

    public function getTypeIcon(): string
    {
        return match ($this->type) {
            self::TYPE_GLOBAL => 'globe',
            self::TYPE_ALLIANCE => 'users',
            self::TYPE_PRIVATE => 'lock',
            self::TYPE_TRADE => 'exchange-alt',
            self::TYPE_DIPLOMACY => 'handshake',
            default => 'comment',
        };
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getMessageCount(): int
    {
        return $this->messages()->notDeleted()->count();
    }

    public function getRecentMessageCount(int $minutes = 60): int
    {
        return $this->messages()->notDeleted()->recent($minutes)->count();
    }

    public function getLastMessage(): ?ChatMessage
    {
        return $this->messages()->notDeleted()->latest()->first();
    }

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function makePublic(): bool
    {
        return $this->update(['is_public' => true]);
    }

    public function makePrivate(): bool
    {
        return $this->update(['is_public' => false]);
    }

    public function updateSettings(array $settings): bool
    {
        $currentSettings = $this->settings ?? [];
        $newSettings = array_merge($currentSettings, $settings);

        return $this->update(['settings' => $newSettings]);
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public static function createChannel(string $name, string $type, ?int $allianceId = null, ?int $createdBy = null, array $settings = []): self
    {
        $slug = \Str::slug($name);

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return self::create([
            'name' => $name,
            'slug' => $slug,
            'type' => $type,
            'alliance_id' => $allianceId,
            'created_by' => $createdBy,
            'settings' => $settings,
            'reference_number' => self::generateReferenceNumber(),
        ]);
    }

    private static function generateReferenceNumber(): string
    {
        do {
            $reference = 'CHAN-'.strtoupper(\Str::random(8));
        } while (self::where('reference_number', $reference)->exists());

        return $reference;
    }

    /**
     * Get chat channels with SmartCache optimization
     */
    public static function getCachedChannels($filters = [])
    {
        $cacheKey = 'chat_channels_'.md5(serialize($filters));

        return SmartCache::remember($cacheKey, now()->addMinutes(10), function () use ($filters) {
            $query = static::with(['alliance']);

            if (isset($filters['type'])) {
                $query->where('channel_type', $filters['type']);
            }

            if (isset($filters['active'])) {
                $query->where('is_active', $filters['active']);
            }

            if (isset($filters['public'])) {
                $query->where('is_public', $filters['public']);
            }

            return $query->orderBy('created_at', 'desc')->get();
        });
    }
}
