<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Notable\Traits\HasNotables;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Alliance extends Model implements Auditable
{
    use HasNotables, HasReference;
    use AuditableTrait;

    protected $fillable = [
        'name',
        'tag',
        'description',
        'world_id',
        'founder_id',
        'leader_id',
        'member_count',
        'points',
        'rank',
        'is_active',
        'created_at',
        'updated_at',
        'reference_number',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';
    protected $referenceTemplate = [
        'format' => 'ALL-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];
    protected $referencePrefix = 'ALL';

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function founder(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'founder_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(AllianceMember::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            alliances.*,
            (SELECT COUNT(*) FROM players p WHERE p.alliance_id = alliances.id) as member_count,
            (SELECT SUM(points) FROM players p2 WHERE p2.alliance_id = alliances.id) as total_points,
            (SELECT AVG(points) FROM players p3 WHERE p3.alliance_id = alliances.id) as avg_points,
            (SELECT MAX(points) FROM players p4 WHERE p4.alliance_id = alliances.id) as max_points,
            (SELECT COUNT(*) FROM villages v WHERE v.player_id IN (SELECT id FROM players p5 WHERE p5.alliance_id = alliances.id)) as total_villages,
            (SELECT SUM(population) FROM villages v2 WHERE v2.player_id IN (SELECT id FROM players p6 WHERE p6.alliance_id = alliances.id)) as total_population
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

    public function scopeByPoints($query, $minPoints = null, $maxPoints = null)
    {
        return $query->when($minPoints, function ($q) use ($minPoints) {
            return $q->where('points', '>=', $minPoints);
        })->when($maxPoints, function ($q) use ($maxPoints) {
            return $q->where('points', '<=', $maxPoints);
        });
    }

    public function scopeByMemberCount($query, $minMembers = null, $maxMembers = null)
    {
        return $query->when($minMembers, function ($q) use ($minMembers) {
            return $q->where('member_count', '>=', $minMembers);
        })->when($maxMembers, function ($q) use ($maxMembers) {
            return $q->where('member_count', '<=', $maxMembers);
        });
    }

    public function scopeTopAlliances($query, $limit = 10)
    {
        return $query->orderBy('points', 'desc')->limit($limit);
    }

    public function scopeByRank($query, $rank = null)
    {
        return $query->when($rank, function ($q) use ($rank) {
            return $q->where('rank', $rank);
        });
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where(function ($subQ) use ($searchTerm) {
                $subQ
                    ->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('tag', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'founder:id,name,points',
            'leader:id,name,points',
            'players:id,name,alliance_id,points,created_at'
        ]);
    }
}
