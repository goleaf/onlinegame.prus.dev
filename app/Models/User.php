<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Commenter;
use IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList;
use IndexZer0\EloquentFiltering\Filter\Filterable\Filter;
use IndexZer0\EloquentFiltering\Filter\Types\Types;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use IndexZer0\EloquentFiltering\Contracts\IsFilterable;
use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;
use LaraUtilX\Traits\LarautilxAuditable;
use MohamedSaid\Notable\Traits\HasNotables;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use WendellAdriel\Lift\Lift;

class User extends Authenticatable implements Auditable, IsFilterable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasNotables;
    use AuditableTrait;
    use Lift;
    use HasReference;
    use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;

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
        'phone',
        'phone_country',
        'reference_number',
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
            'phone' => \Propaganistas\LaravelPhone\Casts\RawPhoneNumberCast::class . ':phone_country',
            'reference_number' => 'string',
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
        $startTime = microtime(true);

        $player = $this->player;
        if (!$player) {
            ds('User has no player', [
                'user_id' => $this->id,
                'user_name' => $this->name,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ])->label('User Game Stats - No Player');

            return null;
        }

        $stats = [
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

        ds('User game statistics retrieved', [
            'user_id' => $this->id,
            'user_name' => $this->name,
            'player_id' => $player->id,
            'village_count' => $stats['village_count'],
            'total_population' => $stats['total_population'],
            'points' => $stats['points'],
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
        ])->label('User Game Stats');

        return $stats;
    }

    /**
     * Check if user has active game session
     */
    public function hasActiveGameSession(): bool
    {
        $startTime = microtime(true);

        $hasActiveSession = $this->player && $this->player->is_active;

        ds('User active game session check', [
            'user_id' => $this->id,
            'user_name' => $this->name,
            'has_player' => (bool) $this->player,
            'player_active' => $this->player ? $this->player->is_active : false,
            'has_active_session' => $hasActiveSession,
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
        ])->label('User Active Session Check');

        return $hasActiveSession;
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
        $startTime = microtime(true);

        if (!$this->player) {
            ds('User is not online - no player', [
                'user_id' => $this->id,
                'user_name' => $this->name,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ])->label('User Online Check - No Player');

            return false;
        }

        $isOnline = $this->player->is_online &&
            $this->player->last_active_at &&
            $this->player->last_active_at->diffInMinutes(now()) <= 15;

        ds('User online status check', [
            'user_id' => $this->id,
            'user_name' => $this->name,
            'player_id' => $this->player->id,
            'player_online' => $this->player->is_online,
            'last_active_at' => $this->player->last_active_at,
            'minutes_since_active' => $this->player->last_active_at ? $this->player->last_active_at->diffInMinutes(now()) : null,
            'is_online' => $isOnline,
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
        ])->label('User Online Check');

        return $isOnline;
    }

    /**
     * Get user's villages
     */
    public function getVillages()
    {
        $startTime = microtime(true);

        $villages = $this->player ? $this->player->villages : collect();

        ds('User villages retrieved', [
            'user_id' => $this->id,
            'user_name' => $this->name,
            'has_player' => (bool) $this->player,
            'village_count' => $villages->count(),
            'villages' => $villages->pluck('name')->toArray(),
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
        ])->label('User Villages');

        return $villages;
    }

    /**
     * Get user's capital village
     */
    public function getCapitalVillage()
    {
        $startTime = microtime(true);

        $capitalVillage = $this->player ? $this->player->villages->where('is_capital', true)->first() : null;

        ds('User capital village retrieved', [
            'user_id' => $this->id,
            'user_name' => $this->name,
            'has_player' => (bool) $this->player,
            'has_capital' => (bool) $capitalVillage,
            'capital_village_id' => $capitalVillage ? $capitalVillage->id : null,
            'capital_village_name' => $capitalVillage ? $capitalVillage->name : null,
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
        ])->label('User Capital Village');

        return $capitalVillage;
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
            Filter::field('name', ['$eq', '$like']),
            Filter::field('email', ['$eq', '$like']),
            Filter::field('phone', ['$eq', '$like']),
            Filter::field('phone_country', ['$eq']),
            Filter::field('phone_normalized', ['$eq', '$like']),
            Filter::field('phone_e164', ['$eq', '$like']),
            Filter::field('email_verified_at', ['$eq', '$gt', '$lt']),
            Filter::relation('players', ['$has']),
            Filter::relation('player', ['$has'])
        );
    }
}
