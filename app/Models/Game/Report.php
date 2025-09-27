<?php

namespace App\Models\Game;

use App\Traits\Commentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use IndexZer0\EloquentFiltering\Contracts\IsFilterable;
use IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList;
use IndexZer0\EloquentFiltering\Filter\Filterable\Filter;
use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;
use IndexZer0\EloquentFiltering\Filter\Types\Types;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use WendellAdriel\Lift\Lift;

class Report extends Model implements Auditable
{
    use HasFactory, HasReference, AuditableTrait, Commentable, Lift;

    // Laravel Lift typed properties
    public int $id;
    public int $world_id;
    public ?int $attacker_id;
    public ?int $defender_id;
    public ?int $from_village_id;
    public ?int $to_village_id;
    public string $title;
    public ?string $content;
    public string $type;
    public string $status;
    public ?array $battle_data;
    public ?array $attachments;
    public bool $is_read;
    public bool $is_important;
    public ?\Carbon\Carbon $read_at;
    public ?string $reference_number;
    public \Carbon\Carbon $created_at;
    public \Carbon\Carbon $updated_at;

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
            Filter::field('title', ['$eq', '$like']),
            Filter::field('content', ['$eq', '$like']),
            Filter::field('type', ['$eq']),
            Filter::field('status', ['$eq']),
            Filter::field('is_read', ['$eq']),
            Filter::field('is_important', ['$eq']),
            Filter::field('world_id', ['$eq']),
            Filter::field('attacker_id', ['$eq']),
            Filter::field('defender_id', ['$eq']),
            Filter::field('from_village_id', ['$eq']),
            Filter::field('to_village_id', ['$eq']),
            Filter::field('read_at', ['$eq', '$gt', '$lt']),
            Filter::field('reference_number', ['$eq', '$like']),
            Filter::relation('world', ['$has']),
            Filter::relation('attacker', ['$has']),
            Filter::relation('defender', ['$has']),
            Filter::relation('fromVillage', ['$has']),
            Filter::relation('toVillage', ['$has'])
        );
    }
}
