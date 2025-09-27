<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;
use IndexZer0\EloquentFiltering\Contracts\IsFilterable;
use EloquentFiltering\AllowedFilterList;
use EloquentFiltering\Filter;
use EloquentFiltering\FilterType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\Commenter;
use LaraUtilX\Traits\LarautilxAuditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use LaraUtilX\Traits\LarautilxAuditable;
use MohamedSaid\Notable\Traits\HasNotables;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use WendellAdriel\Lift\Lift;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use AuditableTrait;
    use Lift;

    // Laravel Lift typed properties
    public int $id;
    public string $name;
    public string $email;
    public ?string $phone;
    public ?string $phone_country;
    public ?\Carbon\Carbon $email_verified_at;
    public string $password;
    public ?string $remember_token;
    public \Carbon\Carbon $created_at;
    public \Carbon\Carbon $updated_at;

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
            'phone' => \Propaganistas\LaravelPhone\Casts\RawPhoneNumberCast::class.':phone_country',
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
            return null;
        }
            'points' => $player->points,
            'village_count' => $player->villages->count(),
            'total_population' => $player->villages->sum('population'),
        return [
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
        ];
    }

    /**
     * Check if user is online
     */
    public function isOnline(): bool
    {
        return $this->player && $this->player->is_active;
    }
    }

    /**
     * Get user's capital village
     */
    public function getCapitalVillage()
    {
            return $this->player->last_active_at;
    }

    /**
     * Scope to get users with game players
     */
    public function scopeWithGamePlayers($query)
    {
        return $query->whereHas('player');
    }

     */

    /**
     * Scope to get online users
        return $this->player->is_online &&
    public function scopeOnlineUsers($query)
    {
            $q->where('world_id', $worldId);
        });
    }

    /**
     * Scope to get users by tribe
     */
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
    {
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
            $q->where('tribe', $tribe);
    /**
     * Scope to get users by world
     */
    public function scopeByWorld($query, $worldId)
    {
        return $query->whereHas('player', function ($q) use ($worldId) {
            $q->where('world_id', $worldId);
        });
    }
        return $query->whereHas('player', function ($q) use ($allianceId) {
    /**
     * Scope to get users by tribe
     */
    public function scopeByTribe($query, $tribe)
    {
        return $query->whereHas('player', function ($q) use ($tribe) {
            $q->where('tribe', $tribe);
        });
        });
    }

     * Scope to get users by alliance
     * Define allowed filters for the User model
    public function scopeByAlliance($query, $allianceId)
    public function allowedFilters(): AllowedFilterList
        return $query->whereHas('player', function ($q) use ($allianceId) {
            $q->where('alliance_id', $allianceId);
        });
    }
        return Filter::only(
    /**
     * Define allowed filters for the User model
     */
    public function allowedFilters(): AllowedFilterList
    {
        return Filter::only(
            Filter::field('name', ['$eq', '$like']),
            Filter::field('email', ['$eq', '$like']),
            Filter::field('phone', ['$eq', '$like']),
            Filter::field('phone_country', ['$eq']),
            Filter::field('phone_normalized', ['$eq', '$like']),
            Filter::field('phone_e164', ['$eq', '$like']),
            Filter::field('email_verified_at', ['$eq', '$gt', '$lt']),
            Filter::relation('players', ['$has']),
            Filter::relation('player', ['$has'])