<?php

namespace App\Models\Game;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

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
        'updated_at'
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'last_active_at' => 'datetime',
        'is_active' => 'boolean',
        'is_online' => 'boolean',
    ];

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
            ->withPivot(['level', 'researched_at'])
            ->withTimestamps();
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
