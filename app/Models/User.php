<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use EloquentFiltering\Filterable;
use EloquentFiltering\Contracts\IsFilterable;
use EloquentFiltering\AllowedFilterList;
use EloquentFiltering\Filter;
use EloquentFiltering\FilterType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\Commenter;
use LaraUtilX\Traits\LarautilxAuditable;
use MohamedSaid\Notable\Traits\HasNotables;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasNotables;
    use AuditableTrait;

    /**
     * Attributes to exclude from the Audit.
     */
    protected $auditExclude = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_country',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function players()
    {
        return $this->hasMany(\App\Models\Game\Player::class);
    }

    public function player()
    {
        return $this->hasOne(\App\Models\Game\Player::class);
    }

    /**
     * Get user's game statistics
     */
    public function getGameStats()
    {
        $player = $this->player;
        if (!$player) {
            return null;
        }

        return [
            'player_id' => $player->id,
            'player_name' => $player->name,
            'world_id' => $player->world_id,
            'tribe' => $player->tribe,
            'points' => $player->points,
            'village_count' => $player->villages->count(),
            'total_population' => $player->villages->sum('population'),
            'alliance_id' => $player->alliance_id,
            'is_active' => $player->is_active,
            'is_online' => $player->is_online,
            'last_active_at' => $player->last_active_at,
        ];
    }

    /**
     * Check if user has active game session
     */
    public function hasActiveGameSession(): bool
    {
        return $this->player && $this->player->is_active;
    }

    /**
     * Get user's last activity
     */
    public function getLastActivity()
    {
        if ($this->player && $this->player->last_active_at) {
            return $this->player->last_active_at;
        }

        return $this->updated_at;
    }

    /**
     * Check if user is online
     */
    public function isOnline(): bool
    {
        if (!$this->player) {
            return false;
        }

        return $this->player->is_online &&
            $this->player->last_active_at &&
            $this->player->last_active_at->diffInMinutes(now()) <= 15;
    }

    /**
     * Get user's villages
     */
    public function getVillages()
    {
        return $this->player ? $this->player->villages : collect();
    }

    /**
     * Get user's capital village
     */
    public function getCapitalVillage()
    {
        return $this->player ? $this->player->villages->where('is_capital', true)->first() : null;
    }

    /**
     * Scope to get users with game players
     */
    public function scopeWithGamePlayers($query)
    {
        return $query->whereHas('player');
    }

    /**
     * Scope to get active game users
     */
    public function scopeActiveGameUsers($query)
    {
        return $query->whereHas('player', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Scope to get online users
     */
    public function scopeOnlineUsers($query)
    {
        return $query->whereHas('player', function ($q) {
            $q
                ->where('is_online', true)
                ->where('last_active_at', '>=', now()->subMinutes(15));
        });
    }

    /**
     * Scope to get users by world
     */
    public function scopeByWorld($query, $worldId)
    {
        return $query->whereHas('player', function ($q) use ($worldId) {
            $q->where('world_id', $worldId);
        });
    }

    /**
     * Scope to get users by tribe
     */
    public function scopeByTribe($query, $tribe)
    {
        return $query->whereHas('player', function ($q) use ($tribe) {
            $q->where('tribe', $tribe);
        });
    }

    /**
     * Scope to get users by alliance
     */
    public function scopeByAlliance($query, $allianceId)
    {
        return $query->whereHas('player', function ($q) use ($allianceId) {
            $q->where('alliance_id', $allianceId);
        });
    }

    /**
     * Define allowed filters for the User model
     */
    public function allowedFilters(): AllowedFilterList
    {
        return Filter::only(
            Filter::field('name', [FilterType::EQUAL, FilterType::CONTAINS]),
            Filter::field('email', [FilterType::EQUAL, FilterType::CONTAINS]),
            Filter::field('email_verified_at', [FilterType::EQUAL, FilterType::GREATER_THAN, FilterType::LESS_THAN]),
            Filter::relation('players', [FilterType::HAS])->includeRelationFields(),
            Filter::relation('player', [FilterType::HAS])->includeRelationFields()
        );
    }
}
