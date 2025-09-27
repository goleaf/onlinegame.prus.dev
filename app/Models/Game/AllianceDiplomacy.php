<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AllianceDiplomacy extends Model
{
    protected $table = 'alliance_diplomacy';

    protected $fillable = [
        'alliance_id',
        'target_alliance_id',
        'status',
        'proposed_by',
        'response_status',
        'message',
        'proposed_at',
        'responded_at',
        'expires_at',
        'terms',
    ];

    protected $casts = [
        'proposed_at' => 'datetime',
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
        'terms' => 'array',
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function targetAlliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'target_alliance_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('response_status', 'accepted');
    }

    public function scopePending($query)
    {
        return $query->where('response_status', 'pending');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByAlliance($query, $allianceId)
    {
        return $query->where(function ($q) use ($allianceId) {
            $q
                ->where('alliance_id', $allianceId)
                ->orWhere('target_alliance_id', $allianceId);
        });
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->response_status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->response_status === 'accepted';
    }

    public function isDeclined(): bool
    {
        return $this->response_status === 'declined';
    }

    public function getOtherAlliance($currentAllianceId): Alliance
    {
        return $this->alliance_id === $currentAllianceId
            ? $this->targetAlliance
            : $this->alliance;
    }

    public function canRespond($allianceId): bool
    {
        return $this->isPending() &&
            !$this->isExpired() &&
            $this->target_alliance_id === $allianceId;
    }

    public function canCancel($allianceId): bool
    {
        return $this->isPending() &&
            $this->alliance_id === $allianceId;
    }
}
