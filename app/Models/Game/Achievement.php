<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use WendellAdriel\Lift\Lift;

class Achievement extends Model
{
    use HasFactory, HasTaxonomy, HasReference;

    protected $table = 'achievements';

    protected $fillable = [
        'name',
        'key',
        'description',
        'category',
        'points',
        'requirements',
        'rewards',
        'icon',
        'is_hidden',
        'is_active',
        'reference_number',
    ];

    protected $casts = [
        'requirements' => 'array',
        'rewards' => 'array',
        'is_hidden' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'ACH-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'ACH';

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    // Scopes
    public function scopeUnlocked($query)
    {
        return $query->where('status', 'unlocked');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
