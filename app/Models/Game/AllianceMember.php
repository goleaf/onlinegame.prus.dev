<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use SmartCache\Facades\SmartCache;

class AllianceMember extends Model
{
    use HasReference;

    protected $fillable = [
        'alliance_id',
        'player_id',
        'rank',
        'joined_at',
        'reference_number',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'AM-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'AM';

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            alliance_members.*,
            (SELECT COUNT(*) FROM alliance_members am2 WHERE am2.alliance_id = alliance_members.alliance_id AND am2.is_active = 1) as total_active_members,
            (SELECT COUNT(*) FROM alliance_members am3 WHERE am3.alliance_id = alliance_members.alliance_id) as total_members,
            (SELECT AVG(EXTRACT(EPOCH FROM (am4.joined_at - am4.created_at))/3600) FROM alliance_members am4 WHERE am4.alliance_id = alliance_members.alliance_id AND am4.joined_at IS NOT NULL) as avg_join_time_hours
        ');
    }

    public function scopeByAlliance($query, $allianceId)
    {
        return $query->where('alliance_id', $allianceId);
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRank($query, $rank = null)
    {
        return $query->when($rank, function ($q) use ($rank) {
            return $q->where('rank', $rank);
        });
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('joined_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->whereHas('player', function ($playerQ) use ($searchTerm) {
                $playerQ->where('name', 'like', '%' . $searchTerm . '%');
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'player:id,name,points,created_at',
            'alliance:id,name,tag'
        ]);
    }
}
