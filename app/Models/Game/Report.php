<?php

namespace App\Models\Game;

use EloquentFiltering\Filterable;
use EloquentFiltering\Contracts\IsFilterable;
use EloquentFiltering\AllowedFilterList;
use EloquentFiltering\Filter;
use EloquentFiltering\FilterType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Report extends Model implements Auditable
{
    use HasFactory, HasReference, AuditableTrait, Filterable;

    protected $fillable = [
        'world_id',
        'attacker_id',
        'defender_id',
        'from_village_id',
        'to_village_id',
        'title',
        'content',
        'type',
        'status',
        'battle_data',
        'attachments',
        'is_read',
        'is_important',
        'read_at',
        'reference_number',
    ];

    protected $casts = [
        'battle_data' => 'array',
        'attachments' => 'array',
        'is_read' => 'boolean',
        'is_important' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'RPT-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'RPT';

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function attacker(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'attacker_id');
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'defender_id');
    }

    public function fromVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'from_village_id');
    }

    public function toVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'to_village_id');
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function markAsImportant()
    {
        $this->update(['is_important' => true]);
    }

    public function markAsUnimportant()
    {
        $this->update(['is_important' => false]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForPlayer($query, $playerId)
    {
        return $query->where(function ($q) use ($playerId) {
            $q
                ->where('attacker_id', $playerId)
                ->orWhere('defender_id', $playerId);
        });
    }

    /**
     * Define allowed filters for the Report model
     */
    public function allowedFilters(): AllowedFilterList
    {
        return Filter::only(
            Filter::field('title', [FilterType::EQUAL, FilterType::CONTAINS]),
            Filter::field('content', [FilterType::EQUAL, FilterType::CONTAINS]),
            Filter::field('type', [FilterType::EQUAL]),
            Filter::field('status', [FilterType::EQUAL]),
            Filter::field('is_read', [FilterType::EQUAL]),
            Filter::field('is_important', [FilterType::EQUAL]),
            Filter::field('world_id', [FilterType::EQUAL]),
            Filter::field('attacker_id', [FilterType::EQUAL]),
            Filter::field('defender_id', [FilterType::EQUAL]),
            Filter::field('from_village_id', [FilterType::EQUAL]),
            Filter::field('to_village_id', [FilterType::EQUAL]),
            Filter::field('read_at', [FilterType::EQUAL, FilterType::GREATER_THAN, FilterType::LESS_THAN]),
            Filter::field('reference_number', [FilterType::EQUAL, FilterType::CONTAINS]),
            Filter::relation('world', [FilterType::HAS])->includeRelationFields(),
            Filter::relation('attacker', [FilterType::HAS])->includeRelationFields(),
            Filter::relation('defender', [FilterType::HAS])->includeRelationFields(),
            Filter::relation('fromVillage', [FilterType::HAS])->includeRelationFields(),
            Filter::relation('toVillage', [FilterType::HAS])->includeRelationFields()
        );
    }
}
