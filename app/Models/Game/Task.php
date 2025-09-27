<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use App\Traits\Commentable;
use App\Traits\GameValidationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use sbamtr\LaravelQueryEnrich\QE;
use function sbamtr\LaravelQueryEnrich\c;

class Task extends Model
{
    use HasFactory, HasTaxonomy, HasReference, Commentable, Lift, GameValidationTrait;

    // Laravel Lift typed properties
    public int $id;
    public int $world_id;
    public int $player_id;
    public string $title;
    public ?string $description;
    public string $type;
    public string $status;
    public int $progress;
    public ?int $target;
    public ?array $rewards;
    public ?\Carbon\Carbon $deadline;
    public ?\Carbon\Carbon $started_at;
    public ?\Carbon\Carbon $completed_at;
    public ?string $reference_number;
    public \Carbon\CarbonImmutable $created_at;
    public \Carbon\CarbonImmutable $updated_at;

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

    // Enhanced query scopes using Query Enrich
    public function scopeWithStats($query)
    {
        return $query->select([
            'player_tasks.*',
            QE::select(QE::count(c('id')))
                ->from('player_tasks', 'pt2')
                ->whereColumn('pt2.player_id', c('player_tasks.player_id'))
                ->as('total_tasks'),
            QE::select(QE::count(c('id')))
                ->from('player_tasks', 'pt3')
                ->whereColumn('pt3.player_id', c('player_tasks.player_id'))
                ->where('pt3.status', '=', 'active')
                ->as('active_tasks'),
            QE::select(QE::count(c('id')))
                ->from('player_tasks', 'pt4')
                ->whereColumn('pt4.player_id', c('player_tasks.player_id'))
                ->where('pt4.status', '=', 'completed')
                ->as('completed_tasks'),
            QE::select(QE::avg(c('progress')))
                ->from('player_tasks', 'pt5')
                ->whereColumn('pt5.player_id', c('player_tasks.player_id'))
                ->where('pt5.status', '=', 'active')
                ->as('avg_progress')
        ]);
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

    /**
     * Validate task data using GameValidationTrait
     */
    public function validateTaskData(array $data)
    {
        return parent::validateTaskData($data);
    }

    /**
     * Create task with real-time integration
     */
    public static function createWithIntegration(array $data): self
    {
        $task = self::create($data);
        
        // Send notification
        GameNotificationService::sendQuestNotification(
            $task->player_id,
            'created',
            [
                'task_id' => $task->id,
                'title' => $task->title,
                'type' => $task->type,
            ]
        );

        return $task;
    }

    /**
     * Complete task with real-time integration
     */
    public function completeWithIntegration(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Send completion notification
        GameNotificationService::sendQuestNotification(
            $this->player_id,
            'completed',
            [
                'task_id' => $this->id,
                'title' => $this->title,
                'rewards' => $this->rewards,
            ]
        );
    }

    /**
     * Update task progress with real-time integration
     */
    public function updateProgressWithIntegration(int $progress): void
    {
        $this->update(['progress' => $progress]);

        // Send progress notification if significant milestone
        if ($progress >= $this->target * 0.5) {
            GameNotificationService::sendQuestNotification(
                $this->player_id,
                'progress',
                [
                    'task_id' => $this->id,
                    'title' => $this->title,
                    'progress' => $progress,
                    'target' => $this->target,
                ]
            );
        }
    }
}
