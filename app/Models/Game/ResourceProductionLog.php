<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;

class ResourceProductionLog extends Model
{
    use HasReference;

    protected $fillable = [
        'village_id',
        'type',
        'amount_produced',
        'amount_consumed',
        'final_amount',
        'produced_at',
        'reference_number',
    ];

    protected $casts = [
        'produced_at' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'RPL-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'RPL';

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
