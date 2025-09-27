<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class TournamentParticipant extends Model implements Auditable
{
    use HasFactory, HasReference, AuditableTrait;

    protected $fillable = [
        'tournament_id',
        'player_id',
        'status',
        'score',
        'wins',
        'losses',
        'draws',
        'stats',
        'rewards',
        'registered_at',
        'eliminated_at',
        'final_rank',
        'reference_number',
    ];

    protected $casts = [
        'stats' => 'array',
        'rewards' => 'array',
        'registered_at' => 'datetime',
        'eliminated_at' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'TP-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'TP';

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEliminated($query)
    {
        return $query->where('status', 'eliminated');
    }

    public function scopeWinner($query)
    {
        return $query->where('status', 'winner');
    }

    public function scopeByTournament($query, $tournamentId)
    {
        return $query->where('tournament_id', $tournamentId);
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeRanked($query)
    {
        return $query->whereNotNull('final_rank')->orderBy('final_rank');
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isEliminated(): bool
    {
        return $this->status === 'eliminated';
    }

    public function isWinner(): bool
    {
        return $this->status === 'winner';
    }

    public function eliminate(): bool
    {
        if ($this->status === 'eliminated') {
            return false;
        }

        $this->update([
            'status' => 'eliminated',
            'eliminated_at' => now(),
        ]);

        return true;
    }

    public function declareWinner(): bool
    {
        if ($this->status === 'winner') {
            return false;
        }

        $this->update([
            'status' => 'winner',
            'final_rank' => 1,
        ]);

        return true;
    }

    public function addWin(): void
    {
        $this->increment('wins');
        $this->increment('score', 3); // 3 points for win
    }

    public function addLoss(): void
    {
        $this->increment('losses');
    }

    public function addDraw(): void
    {
        $this->increment('draws');
        $this->increment('score', 1); // 1 point for draw
    }

    public function updateStats(array $stats): void
    {
        $currentStats = $this->stats ?? [];
        $this->update(['stats' => array_merge($currentStats, $stats)]);
    }

    public function getWinRateAttribute(): float
    {
        $totalGames = $this->wins + $this->losses + $this->draws;
        
        if ($totalGames === 0) {
            return 0.0;
        }

        return ($this->wins / $totalGames) * 100;
    }

    public function getTotalGamesAttribute(): int
    {
        return $this->wins + $this->losses + $this->draws;
    }

    public function getStatusDisplayNameAttribute(): string
    {
        return match($this->status) {
            'registered' => 'Registered',
            'active' => 'Active',
            'eliminated' => 'Eliminated',
            'winner' => 'Winner',
            'disqualified' => 'Disqualified',
            default => ucfirst($this->status)
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'registered' => '#3B82F6',   // Blue
            'active' => '#10B981',       // Green
            'eliminated' => '#6B7280',   // Gray
            'winner' => '#F59E0B',       // Orange
            'disqualified' => '#EF4444', // Red
            default => '#6B7280'
        };
    }
}