<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MohamedSaid\Referenceable\Traits\HasReference;

class GameTask extends Model
{
    use HasReference;

    protected $fillable = [
        'player_id',
        'village_id',
        'task_type',
        'task_data',
        'status',
        'priority',
        'scheduled_at',
        'started_at',
        'completed_at',
        'result_data',
        'reference_number',
    ];

    protected $casts = [
        'task_data' => 'array',
        'result_data' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';

    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'GT-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'GT';

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
