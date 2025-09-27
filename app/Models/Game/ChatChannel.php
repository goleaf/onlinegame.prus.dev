<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SmartCache\Facades\SmartCache;

class ChatChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'channel_type',
        'alliance_id',
        'is_public',
        'is_active',
        'max_members',
        'created_by',
        'settings',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    // Channel types
    const TYPE_GLOBAL = 'global';
    const TYPE_ALLIANCE = 'alliance';
    const TYPE_PRIVATE = 'private';
    const TYPE_TRADE = 'trade';
    const TYPE_DIPLOMACY = 'diplomacy';
    const TYPE_CUSTOM = 'custom';

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id');
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('channel_type', $type);
    }

    public function scopeForAlliance($query, $allianceId)
    {
        return $query->where('alliance_id', $allianceId);
    }

    // Methods
    public function canJoin($playerId): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->is_public) {
            return true;
        }

        // Check alliance membership for alliance channels
        if ($this->channel_type === self::TYPE_ALLIANCE && $this->alliance_id) {
            $player = Player::find($playerId);
            return $player && $player->alliance_id === $this->alliance_id;
        }

        return false;
    }

    public function getMemberCount(): int
    {
        return SmartCache::remember("chat_channel_members:{$this->id}", 300, function () {
            // This would need to be implemented based on your membership tracking
            return 0;
        });
    }

    public function getLastMessage(): ?ChatMessage
    {
        return $this->messages()
            ->notDeleted()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function getUnreadCount($playerId): int
    {
        // This would need to be implemented based on your read tracking
        return 0;
    }

    public static function getGlobalChannel(): self
    {
        return SmartCache::remember('global_chat_channel', 3600, function () {
            return self::firstOrCreate(
                ['channel_type' => self::TYPE_GLOBAL],
                [
                    'name' => 'Global Chat',
                    'description' => 'Global chat channel for all players',
                    'is_public' => true,
                    'is_active' => true,
                    'max_members' => null,
                ]
            );
        });
    }

    public static function getAllianceChannel($allianceId): ?self
    {
        return SmartCache::remember("alliance_chat_channel:{$allianceId}", 3600, function () use ($allianceId) {
            return self::where('channel_type', self::TYPE_ALLIANCE)
                ->where('alliance_id', $allianceId)
                ->first();
        });
    }

    public static function getPrivateChannel($playerId1, $playerId2): self
    {
        $channelId = min($playerId1, $playerId2) . '_' . max($playerId1, $playerId2);
        
        return SmartCache::remember("private_chat_channel:{$channelId}", 3600, function () use ($playerId1, $playerId2, $channelId) {
            return self::firstOrCreate(
                ['name' => $channelId, 'channel_type' => self::TYPE_PRIVATE],
                [
                    'description' => "Private chat between players {$playerId1} and {$playerId2}",
                    'is_public' => false,
                    'is_active' => true,
                    'max_members' => 2,
                ]
            );
        });
    }

    public static function getAvailableChannels($playerId): array
    {
        return SmartCache::remember("available_chat_channels:{$playerId}", 300, function () use ($playerId) {
            $player = Player::find($playerId);
            
            $channels = collect();
            
            // Global channel
            $channels->push(self::getGlobalChannel());
            
            // Alliance channel
            if ($player && $player->alliance_id) {
                $allianceChannel = self::getAllianceChannel($player->alliance_id);
                if ($allianceChannel) {
                    $channels->push($allianceChannel);
                }
            }
            
            // Public custom channels
            $publicChannels = self::active()
                ->public()
                ->byType(self::TYPE_CUSTOM)
                ->get();
            
            $channels = $channels->merge($publicChannels);
            
            return $channels->filter(function ($channel) use ($playerId) {
                return $channel->canJoin($playerId);
            })->values()->toArray();
        });
    }

    public static function getChannelStats(): array
    {
        return SmartCache::remember('chat_channel_stats', 600, function () {
            return [
                'total_channels' => self::active()->count(),
                'global_channels' => self::byType(self::TYPE_GLOBAL)->active()->count(),
                'alliance_channels' => self::byType(self::TYPE_ALLIANCE)->active()->count(),
                'private_channels' => self::byType(self::TYPE_PRIVATE)->active()->count(),
                'custom_channels' => self::byType(self::TYPE_CUSTOM)->active()->count(),
                'public_channels' => self::public()->active()->count(),
            ];
        });
    }
}
