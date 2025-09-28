<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MohamedSaid\Referenceable\Traits\HasReference;

class Wonder extends Model
{
    use HasFactory;
    use HasReference;

    protected $fillable = [
        'name',
        'description',
        'location_x',
        'location_y',
        'continent',
        'max_level',
        'current_level',
        'construction_cost_multiplier',
        'construction_time_multiplier',
        'bonus_effects',
        'reference_number',
        'is_active',
        'world_id',
    ];

    protected $casts = [
        'location_x' => 'integer',
        'location_y' => 'integer',
        'continent' => 'integer',
        'max_level' => 'integer',
        'current_level' => 'integer',
        'construction_cost_multiplier' => 'float',
        'construction_time_multiplier' => 'float',
        'bonus_effects' => 'array',
        'is_active' => 'boolean',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';

    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'WND-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'WND';

    /**
     * Get the world this wonder belongs to
     */
    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    /**
     * Get the wonder constructions
     */
    public function constructions(): HasMany
    {
        return $this->hasMany(WonderConstruction::class);
    }

    /**
     * Get the current construction in progress
     */
    public function currentConstruction(): HasMany
    {
        return $this->hasMany(WonderConstruction::class)->inProgress();
    }

    /**
     * Get completed constructions
     */
    public function completedConstructions(): HasMany
    {
        return $this->hasMany(WonderConstruction::class)->completed();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByContinent($query, int $continent)
    {
        return $query->where('continent', $continent);
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('current_level', $level);
    }

    public function scopeMaxLevel($query)
    {
        return $query->whereColumn('current_level', 'max_level');
    }

    public function scopeInProgress($query)
    {
        return $query->whereHas('constructions', function ($q): void {
            $q->inProgress();
        });
    }

    // Methods
    public function isMaxLevel(): bool
    {
        return $this->current_level >= $this->max_level;
    }

    public function canUpgrade(): bool
    {
        return ! $this->isMaxLevel() && ! $this->hasConstructionInProgress();
    }

    public function hasConstructionInProgress(): bool
    {
        return $this->constructions()->inProgress()->exists();
    }

    public function getCurrentConstruction(): ?WonderConstruction
    {
        return $this->constructions()->inProgress()->first();
    }

    public function getConstructionCost(int $level): array
    {
        $baseCost = $this->getBaseCost($level);
        $multiplier = $this->construction_cost_multiplier;

        return [
            'wood' => (int) ($baseCost['wood'] * $multiplier),
            'clay' => (int) ($baseCost['clay'] * $multiplier),
            'iron' => (int) ($baseCost['iron'] * $multiplier),
            'crop' => (int) ($baseCost['crop'] * $multiplier),
        ];
    }

    public function getConstructionTime(int $level): int
    {
        $baseTime = $this->getBaseTime($level);

        return (int) ($baseTime * $this->construction_time_multiplier);
    }

    public function getBonusEffects(int $level): array
    {
        if (empty($this->bonus_effects)) {
            return [];
        }

        $effects = [];
        foreach ($this->bonus_effects as $effect => $values) {
            if (isset($values[$level - 1])) {
                $effects[$effect] = $values[$level - 1];
            }
        }

        return $effects;
    }

    public function getTotalResourcesContributed(): array
    {
        $contributions = $this->constructions()
            ->whereNotNull('resources_contributed')
            ->get()
            ->pluck('resources_contributed')
            ->toArray();

        $total = ['wood' => 0, 'clay' => 0, 'iron' => 0, 'crop' => 0];

        foreach ($contributions as $contribution) {
            foreach ($total as $resource => $amount) {
                $total[$resource] += $contribution[$resource] ?? 0;
            }
        }

        return $total;
    }

    public function getConstructionProgress(): float
    {
        if (! $this->hasConstructionInProgress()) {
            return 0;
        }

        $construction = $this->getCurrentConstruction();

        return $construction->getProgressPercentage();
    }

    protected function getBaseCost(int $level): array
    {
        // Base cost calculation - this would typically come from configuration
        $baseCost = [
            'wood' => 1000 * pow(1.5, $level - 1),
            'clay' => 1000 * pow(1.5, $level - 1),
            'iron' => 1000 * pow(1.5, $level - 1),
            'crop' => 1000 * pow(1.5, $level - 1),
        ];

        return array_map('intval', $baseCost);
    }

    protected function getBaseTime(int $level): int
    {
        // Base construction time in seconds
        return 3600 * pow(1.3, $level - 1); // 1 hour base, increases by 30% per level
    }

    public function getLocationString(): string
    {
        return "({$this->location_x}|{$this->location_y})";
    }

    public function getFormattedName(): string
    {
        return "{$this->name} {$this->getLocationString()}";
    }

    public function getStatusAttribute(): string
    {
        if ($this->isMaxLevel()) {
            return 'completed';
        }

        if ($this->hasConstructionInProgress()) {
            return 'under_construction';
        }

        return 'available';
    }
}
