<?php

namespace App\Models\Game;

// // use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;
// // use IndexZer0\EloquentFiltering\Contracts\IsFilterable;
// use IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList;
// use IndexZer0\EloquentFiltering\Filter\Filterable\Filter;
// use IndexZer0\EloquentFiltering\Filter\FilterType;
use App\Traits\Commentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MohamedSaid\Notable\Traits\HasNotables;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

use function sbamtr\LaravelQueryEnrich\c;

use sbamtr\LaravelQueryEnrich\QE;

class Alliance extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;
    use Commentable;
    use HasNotables;
    use HasReference;
    // use Lift;

    protected $fillable = [
        'name',
        'tag',
        'description',
        'world_id',
        'leader_id',
        'points',
        'villages_count',
        'members_count',
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

    public function diplomacy(): HasMany
    {
        return $this->hasMany(AllianceDiplomacy::class);
    }

    public function targetDiplomacy(): HasMany
    {
        return $this->hasMany(AllianceDiplomacy::class, 'target_alliance_id');
    }

    public function allDiplomacy()
    {
        return AllianceDiplomacy::where('alliance_id', $this->id)
            ->orWhere('target_alliance_id', $this->id);
    }

    public function wars(): HasMany
    {
        return $this->hasMany(AllianceWar::class, 'attacker_alliance_id');
    }

    public function defendingWars(): HasMany
    {
        return $this->hasMany(AllianceWar::class, 'defender_alliance_id');
    }

    public function allWars()
    {
        return AllianceWar::where('attacker_alliance_id', $this->id)
            ->orWhere('defender_alliance_id', $this->id);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AllianceMessage::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AllianceLog::class);
    }

    // Enhanced query scopes using Query Enrich
    public function scopeWithStats($query)
    {
        return $query->select([
            'alliances.*',
            QE::select(QE::count(c('id')))
                ->from('players', 'p')
                ->whereColumn('p.alliance_id', c('alliances.id'))
                ->as('member_count'),
            QE::select(QE::sum(c('points')))
                ->from('players', 'p2')
                ->whereColumn('p2.alliance_id', c('alliances.id'))
                ->as('total_points'),
            QE::select(QE::avg(c('points')))
                ->from('players', 'p3')
                ->whereColumn('p3.alliance_id', c('alliances.id'))
                ->as('avg_points'),
            QE::select(QE::max(c('points')))
                ->from('players', 'p4')
                ->whereColumn('p4.alliance_id', c('alliances.id'))
                ->as('max_points'),
            QE::select(QE::count(c('id')))
                ->from('villages', 'v')
                ->whereIn('v.player_id', function ($subQuery): void {
                    $subQuery
                        ->select('id')
                        ->from('players', 'p5')
                        ->whereColumn('p5.alliance_id', c('alliances.id'));
                })
                ->as('total_villages'),
            QE::select(QE::sum(c('population')))
                ->from('villages', 'v2')
                ->whereIn('v2.player_id', function ($subQuery): void {
                    $subQuery
                        ->select('id')
                        ->from('players', 'p6')
                        ->whereColumn('p6.alliance_id', c('alliances.id'));
                })
                ->as('total_population'),
        ]);
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
            return $q->where(function ($subQ) use ($searchTerm): void {
                $subQ
                    ->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('tag', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'founder:id,name,points',
            'leader:id,name,points',
            'players:id,name,alliance_id,points,created_at',
        ]);
    }

    /**
     * Define allowed filters for the Alliance model
     */
    public function allowedFilters(): AllowedFilterList
    {
        return Filter::only(
            Filter::field('name', ['$eq', '$like']),
            Filter::field('tag', ['$eq', '$like']),
            Filter::field('description', ['$eq', '$like']),
            Filter::field('points', ['$eq', '$gt', '$lt']),
            Filter::field('villages_count', ['$eq', '$gt', '$lt']),
            Filter::field('members_count', ['$eq', '$gt', '$lt']),
            Filter::field('is_active', ['$eq']),
            Filter::field('world_id', ['$eq']),
            Filter::field('leader_id', ['$eq']),
            Filter::field('reference_number', ['$eq', '$like']),
            Filter::relation('world', ['$has']),
            Filter::relation('leader', ['$has']),
            Filter::relation('members', ['$has']),
            Filter::relation('players', ['$has'])
        );
    }
}
