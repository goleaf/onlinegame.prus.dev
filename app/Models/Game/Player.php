<?php

namespace App\Models\Game;

use App\Models\User;
use App\ValueObjects\PlayerStats;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Notable\Traits\HasNotables;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Player extends Model implements Auditable
{
    use HasFactory;
    use HasNotables;
    use AuditableTrait;

    protected $fillable = [
        'user_id',
        'world_id',
        'name',
        'tribe',
        'alliance_id',
        'population',
        'villages_count',
        'is_active',
        'is_online',
        'last_login',
        'last_active_at',
        'points',
        'total_attack_points',
        'total_defense_points',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'last_active_at' => 'datetime',
        'is_active' => 'boolean',
        'is_online' => 'boolean',
    ];

    /**
     * Get the player stats as a value object
     */
    protected function stats(): Attribute
    {
        return Attribute::make(
            get: fn () => new PlayerStats(
                points: $this->points,
                population: $this->population,
                villagesCount: $this->villages_count,
                totalAttackPoints: $this->total_attack_points,
                totalDefensePoints: $this->total_defense_points,
                isActive: $this->is_active,
                isOnline: $this->is_online
            ),
            set: fn (PlayerStats $stats) => [
                'points' => $stats->points,
                'population' => $stats->population,
                'villages_count' => $stats->villagesCount,
                'total_attack_points' => $stats->totalAttackPoints,
                'total_defense_points' => $stats->totalDefensePoints,
                'is_active' => $stats->isActive,
                'is_online' => $stats->isOnline,
            ]
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function world()
    {
        return $this->belongsTo(World::class);
    }

    public function alliance()
    {
        return $this->belongsTo(Alliance::class);
    }

    public function allianceMembership()
    {
        return $this->hasOne(AllianceMember::class);
    }

    public function statistics()
    {
        return $this->hasOne(PlayerStatistic::class);
    }

    public function quests()
    {
        return $this
            ->belongsToMany(Quest::class, 'player_quests')
            ->withPivot(['status', 'progress', 'progress_data', 'started_at', 'completed_at', 'expires_at'])
            ->withTimestamps();
    }

    public function achievements()
    {
        return $this
            ->belongsToMany(Achievement::class, 'player_achievements')
            ->withPivot(['unlocked_at', 'progress_data'])
            ->withTimestamps();
    }

    public function technologies()
    {
        return $this
            ->belongsToMany(Technology::class, 'player_technologies')
            ->withPivot(['level', 'research_progress', 'unlocked_at'])
            ->withTimestamps();
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            players.*,
            (SELECT COUNT(*) FROM villages WHERE player_id = players.id) as village_count,
            (SELECT SUM(population) FROM villages WHERE player_id = players.id) as total_population,
            (SELECT COUNT(*) FROM reports WHERE attacker_id = players.id OR defender_id = players.id) as total_battles,
            (SELECT SUM(CASE WHEN attacker_id = players.id AND status = "victory" THEN 1 ELSE 0 END) + 
                    SUM(CASE WHEN defender_id = players.id AND status = "victory" THEN 1 ELSE 0 END) 
             FROM reports) as total_victories
        ');
    }

    public function scopeByWorld($query, $worldId)
    {
        return $query->where('world_id', $worldId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeInAlliance($query, $allianceId = null)
    {
        return $query->when($allianceId, function ($q) use ($allianceId) {
            return $q->where('alliance_id', $allianceId);
        }, function ($q) {
            return $q->whereNotNull('alliance_id');
        });
    }

    public function scopeByTribe($query, $tribe)
    {
        return $query->where('tribe', $tribe);
    }

    public function scopeTopPlayers($query, $limit = 10)
    {
        return $query->orderBy('points', 'desc')->limit($limit);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where('name', 'like', '%' . $searchTerm . '%');
        });
    }

    public function tasks()
    {
        return $this->hasMany(GameTask::class);
    }

    public function events()
    {
        return $this->hasMany(GameEvent::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function playerQuests(): HasMany
    {
        return $this->hasMany(PlayerQuest::class);
    }

    public function notes()
    {
        return $this->hasMany(PlayerNote::class);
    }

    public function targetNotes()
    {
        return $this->hasMany(PlayerNote::class, 'target_player_id');
    }

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function battlesAsAttacker()
    {
        return $this->hasMany(Battle::class, 'attacker_id');
    }

    public function battlesAsDefender()
    {
        return $this->hasMany(Battle::class, 'defender_id');
    }
}
