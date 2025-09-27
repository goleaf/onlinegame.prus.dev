<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceLog extends Model
{
    protected $fillable = [
        'alliance_id',
        'player_id',
        'action',
        'description',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    // Scopes
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeMemberActions($query)
    {
        return $query->whereIn('action', [
            'member_joined', 'member_left', 'member_kicked', 
            'member_promoted', 'member_demoted'
        ]);
    }

    public function scopeDiplomacyActions($query)
    {
        return $query->whereIn('action', [
            'diplomacy_proposed', 'diplomacy_accepted', 
            'diplomacy_declined', 'diplomacy_cancelled'
        ]);
    }

    public function scopeWarActions($query)
    {
        return $query->whereIn('action', ['war_declared', 'war_ended']);
    }

    // Static helper methods
    public static function logMemberJoined($allianceId, $playerId, $playerName)
    {
        return static::create([
            'alliance_id' => $allianceId,
            'player_id' => $playerId,
            'action' => 'member_joined',
            'description' => "{$playerName} joined the alliance",
            'data' => ['player_name' => $playerName],
        ]);
    }

    public static function logMemberLeft($allianceId, $playerId, $playerName)
    {
        return static::create([
            'alliance_id' => $allianceId,
            'player_id' => $playerId,
            'action' => 'member_left',
            'description' => "{$playerName} left the alliance",
            'data' => ['player_name' => $playerName],
        ]);
    }

    public static function logMemberKicked($allianceId, $kickedPlayerId, $kickedPlayerName, $kickerPlayerId, $kickerPlayerName)
    {
        return static::create([
            'alliance_id' => $allianceId,
            'player_id' => $kickerPlayerId,
            'action' => 'member_kicked',
            'description' => "{$kickerPlayerName} kicked {$kickedPlayerName} from the alliance",
            'data' => [
                'kicked_player_id' => $kickedPlayerId,
                'kicked_player_name' => $kickedPlayerName,
                'kicker_player_name' => $kickerPlayerName,
            ],
        ]);
    }

    public static function logMemberPromoted($allianceId, $promotedPlayerId, $promotedPlayerName, $promoterPlayerId, $promoterPlayerName, $newRank)
    {
        return static::create([
            'alliance_id' => $allianceId,
            'player_id' => $promoterPlayerId,
            'action' => 'member_promoted',
            'description' => "{$promoterPlayerName} promoted {$promotedPlayerName} to {$newRank}",
            'data' => [
                'promoted_player_id' => $promotedPlayerId,
                'promoted_player_name' => $promotedPlayerName,
                'promoter_player_name' => $promoterPlayerName,
                'new_rank' => $newRank,
            ],
        ]);
    }

    public static function logDiplomacyProposed($allianceId, $playerId, $playerName, $targetAllianceName, $status)
    {
        return static::create([
            'alliance_id' => $allianceId,
            'player_id' => $playerId,
            'action' => 'diplomacy_proposed',
            'description' => "{$playerName} proposed {$status} with {$targetAllianceName}",
            'data' => [
                'player_name' => $playerName,
                'target_alliance_name' => $targetAllianceName,
                'diplomacy_status' => $status,
            ],
        ]);
    }

    public static function logWarDeclared($allianceId, $playerId, $playerName, $targetAllianceName)
    {
        return static::create([
            'alliance_id' => $allianceId,
            'player_id' => $playerId,
            'action' => 'war_declared',
            'description' => "{$playerName} declared war on {$targetAllianceName}",
            'data' => [
                'player_name' => $playerName,
                'target_alliance_name' => $targetAllianceName,
            ],
        ]);
    }
}