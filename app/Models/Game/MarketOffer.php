<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use IndexZer0\EloquentFiltering\Contracts\IsFilterable;
use IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList;
use IndexZer0\EloquentFiltering\Filter\Filterable\Filter;
use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class MarketOffer extends Model implements Auditable, IsFilterable
{
    use AuditableTrait;
    use Filterable;
    use HasFactory;
    use HasReference;

    protected $fillable = [
        'village_id',
        'player_id',
        'offering',
        'requesting',
        'ratio',
        'fee',
        'status',
        'expires_at',
        'completed_at',
        'cancelled_at',
        'buyer_village_id',
        'quantity_traded',
        'reference_number',
    ];

    protected $casts = [
        'offering' => 'array',
        'requesting' => 'array',
        'ratio' => 'decimal:2',
        'fee' => 'integer',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'quantity_traded' => 'integer',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';

    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'MKT-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'MKT';

    // Status constants
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function buyerVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'buyer_village_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeByVillage($query, $villageId)
    {
        return $query->where('village_id', $villageId);
    }

    public function scopeOfferingResource($query, $resource)
    {
        return $query->whereJsonContains('offering->'.$resource, '>', 0);
    }

    public function scopeRequestingResource($query, $resource)
    {
        return $query->whereJsonContains('requesting->'.$resource, '>', 0);
    }

    public function scopeWithRatio($query, $minRatio = null, $maxRatio = null)
    {
        if ($minRatio !== null) {
            $query->where('ratio', '>=', $minRatio);
        }
        if ($maxRatio !== null) {
            $query->where('ratio', '<=', $maxRatio);
        }

        return $query;
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_COMPLETED => 'blue',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }

    public function getStatusIcon(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'check-circle',
            self::STATUS_COMPLETED => 'check-double',
            self::STATUS_CANCELLED => 'times-circle',
            self::STATUS_EXPIRED => 'clock',
            default => 'question-circle',
        };
    }

    public function getFormattedOffering(): string
    {
        $formatted = [];
        foreach ($this->offering as $resource => $amount) {
            $formatted[] = number_format($amount).' '.ucfirst($resource);
        }

        return implode(', ', $formatted);
    }

    public function getFormattedRequesting(): string
    {
        $formatted = [];
        foreach ($this->requesting as $resource => $amount) {
            $formatted[] = number_format($amount).' '.ucfirst($resource);
        }

        return implode(', ', $formatted);
    }

    public function getTotalOfferingValue(): int
    {
        $baseValues = [
            'wood' => 1,
            'clay' => 1,
            'iron' => 1,
            'crop' => 1,
        ];

        $total = 0;
        foreach ($this->offering as $resource => $amount) {
            $total += $amount * ($baseValues[$resource] ?? 1);
        }

        return $total;
    }

    public function getTotalRequestingValue(): int
    {
        $baseValues = [
            'wood' => 1,
            'clay' => 1,
            'iron' => 1,
            'crop' => 1,
        ];

        $total = 0;
        foreach ($this->requesting as $resource => $amount) {
            $total += $amount * ($baseValues[$resource] ?? 1);
        }

        return $total;
    }

    public function getEffectiveRatio(): float
    {
        $offeringValue = $this->getTotalOfferingValue();
        $requestingValue = $this->getTotalRequestingValue();

        if ($requestingValue == 0) {
            return 0;
        }

        return $offeringValue / $requestingValue;
    }

    public function getTimeRemaining(): ?string
    {
        if (! $this->expires_at) {
            return null;
        }

        if ($this->expires_at->isPast()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedCompletedAt(): ?string
    {
        return $this->completed_at ? $this->completed_at->diffForHumans() : null;
    }

    public function getFormattedCancelledAt(): ?string
    {
        return $this->cancelled_at ? $this->cancelled_at->diffForHumans() : null;
    }

    /**
     * Define allowed filters for the MarketOffer model
     */
    public function allowedFilters(): AllowedFilterList
    {
        return Filter::only(
            Filter::field('status', ['$eq']),
            Filter::field('type', ['$eq']),
            Filter::field('resource_type', ['$eq', '$like']),
            Filter::field('ratio', ['$eq', '$gt', '$lt']),
            Filter::field('fee', ['$eq', '$gt', '$lt']),
            Filter::field('quantity_traded', ['$eq', '$gt', '$lt']),
            Filter::field('village_id', ['$eq']),
            Filter::field('player_id', ['$eq']),
            Filter::field('buyer_village_id', ['$eq']),
            Filter::field('expires_at', ['$eq', '$gt', '$lt']),
            Filter::field('completed_at', ['$eq', '$gt', '$lt']),
            Filter::field('cancelled_at', ['$eq', '$gt', '$lt']),
            Filter::field('reference_number', ['$eq', '$like']),
            Filter::relation('village', ['$has']),
            Filter::relation('player', ['$has']),
            Filter::relation('buyerVillage', ['$has'])
        );
    }
}
