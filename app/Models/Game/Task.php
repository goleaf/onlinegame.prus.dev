<?php

namespace App\Models\Game;

use App\Traits\Commentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use WendellAdriel\Lift\Lift;

class Task extends Model
{
    use HasFactory, HasReference, Commentable, Lift;

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
        'reference_number',
    ];

    protected $casts = [
        'rewards' => 'array',
        'deadline' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'TSK-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'TSK';

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
            $q
                ->whereNull('deadline')
                ->orWhere('deadline', '>', now());
        });
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            player_tasks.*,
            (SELECT COUNT(*) FROM player_tasks pt2 WHERE pt2.player_id = player_tasks.player_id) as total_tasks,
            (SELECT COUNT(*) FROM player_tasks pt3 WHERE pt3.player_id = player_tasks.player_id AND pt3.status = "active") as active_tasks,
            (SELECT COUNT(*) FROM player_tasks pt4 WHERE pt4.player_id = player_tasks.player_id AND pt4.status = "completed") as completed_tasks,
            (SELECT AVG(progress) FROM player_tasks pt5 WHERE pt5.player_id = player_tasks.player_id AND pt5.status = "active") as avg_progress
        ');
    }

    public function scopeByWorld($query, $worldId)
    {
        return $query->where('world_id', $worldId);
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeByTypeFilter($query, $type = null)
    {
        return $query->when($type, function ($q) use ($type) {
            return $q->where('type', $type);
        });
    }

    public function scopeByStatusFilter($query, $status = null)
    {
        return $query->when($status, function ($q) use ($status) {
            return $q->where('status', $status);
        });
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeDueSoon($query, $hours = 24)
    {
        return $query
            ->where('deadline', '<=', now()->addHours($hours))
            ->where('deadline', '>', now())
            ->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query
            ->where('deadline', '<', now())
            ->where('status', 'active');
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where(function ($subQ) use ($searchTerm) {
                $subQ
                    ->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('type', 'like', '%' . $searchTerm . '%');
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'player:id,name',
            'world:id,name',
        ]);
    }
}
