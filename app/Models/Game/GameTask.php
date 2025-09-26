<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameTask extends Model
{
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
    ];

    protected $casts = [
        'task_data' => 'array',
        'result_data' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
