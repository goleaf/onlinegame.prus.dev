<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $table = 'player_tasks';

    protected $fillable = [
        'world_id',
        'player_id',
        'title',
        'description',
        'type',
        'status',
        'progress',
        'target',
        'rewards',
        'deadline',
        'started_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'rewards' => 'array',
        'deadline' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
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

    public function scopeExpired($query)
    {
        return $query->where('deadline', '<', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('deadline')
              ->orWhere('deadline', '>', now());
        });
    }
}
