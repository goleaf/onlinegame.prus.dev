<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;

class WonderConstruction extends Model
{
    use HasFactory, HasReference;

    protected $fillable = [
        'wonder_id',
        'alliance_id',
        'level',
        'construction_started_at',
        'construction_completed_at',
        'resources_contributed',
        'construction_time',
        'reference_number',
    ];

    protected $casts = [
        'level' => 'integer',
        'construction_started_at' => 'datetime',
        'construction_completed_at' => 'datetime',
        'resources_contributed' => 'array',
        'construction_time' => 'integer',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'WNC-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'WNC';

    public function wonder(): BelongsTo
    {
        return $this->belongsTo(Wonder::class);
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    // Scopes
    public function scopeByWonder($query, $wonderId)
    {
        return $query->where('wonder_id', $wonderId);
    }

    public function scopeByAlliance($query, $allianceId)
    {
        return $query->where('alliance_id', $allianceId);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('construction_completed_at');
    }

    public function scopeInProgress($query)
    {
        return $query->whereNotNull('construction_started_at')
            ->whereNull('construction_completed_at');
    }

    // Methods
    public function isCompleted(): bool
    {
        return $this->construction_completed_at !== null;
    }

    public function isInProgress(): bool
    {
        return $this->construction_started_at && !$this->construction_completed_at;
    }

    public function getConstructionDuration(): ?int
    {
        if (!$this->construction_started_at) {
            return null;
        }

        $endTime = $this->construction_completed_at ?? now();
        return $endTime->diffInSeconds($this->construction_started_at);
    }

    public function getRemainingTime(): ?int
    {
        if (!$this->isInProgress()) {
            return null;
        }

        $elapsedTime = now()->diffInSeconds($this->construction_started_at);
        return max(0, $this->construction_time - $elapsedTime);
    }

    public function getProgressPercentage(): float
    {
        if (!$this->isInProgress()) {
            return 100;
        }

        $elapsedTime = now()->diffInSeconds($this->construction_started_at);
        return min(100, ($elapsedTime / $this->construction_time) * 100);
    }
}
