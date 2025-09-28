<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MohamedSaid\Referenceable\Traits\HasReference;

class TrainingQueue extends Model
{
    use HasFactory;
    use HasReference;

    protected $fillable = [
        'village_id',
        'unit_type_id',
        'count',
        'started_at',
        'completed_at',
        'costs',
        'status',
        'reference_number',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'costs' => 'array',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';

    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'TRN-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'TRN';

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }
}
