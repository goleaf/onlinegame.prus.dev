<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Task;
use App\Traits\GameValidationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Intervention\Validation\Rules\Username;
use JonPurvis\Squeaky\Rules\Clean;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;

class TaskController extends CrudController
{
    use ApiResponseTrait, GameValidationTrait;

    protected Model $model;

    protected function validationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', new Username(), new Clean],
            'description' => ['nullable', 'string', new Clean],
            'type' => 'required|string|in:building,combat,resource,exploration,alliance',
            'status' => 'required|string|in:available,active,completed,expired',
            'progress' => 'integer|min:0|max:100',
            'target' => 'required|integer|min:1',
            'rewards' => 'nullable|json',
            'deadline' => 'nullable|date|after:now',
            'world_id' => 'required|exists:worlds,id',
            'player_id' => 'required|exists:players,id',
        ];
    }

    protected array $searchableFields = ['title', 'description', 'type'];
    protected array $relationships = ['player', 'world'];
    protected int $perPage = 20;

    public function __construct()
    {
        $this->model = new Task();
        parent::__construct($this->model);
    }

    /**
     * Get tasks with advanced filtering
     */
    public function withStats(Request $request)
    {
        $query = Task::withStats()
            ->with($this->relationships);

        // Apply filters
        if ($request->has('world_id')) {
            $query->byWorld($request->get('world_id'));
        }

        if ($request->has('player_id')) {
            $query->byPlayer($request->get('player_id'));
        }

        if ($request->has('type')) {
            $query->byType($request->get('type'));
        }

        if ($request->has('status')) {
            $query->byStatus($request->get('status'));
        }

        if ($request->has('expired')) {
            if ($request->get('expired') === 'true') {
                $query->expired();
            } else {
                $query->notExpired();
            }
        }

        if ($request->has('due_soon')) {
            $hours = $request->get('due_soon', 24);
            $query->dueSoon($hours);
        }

        // Apply search
        if ($request->has('search')) {
            $query->search($request->get('search'));
        }

        // Apply sorting
        if ($request->has('sort_by')) {
            $direction = $request->get('sort_direction', 'desc');
            $query->orderBy($request->get('sort_by'), $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $tasks = $query->paginate($request->get('per_page', $this->perPage));

        return $this->paginatedResponse($tasks, 'Tasks fetched successfully.');
    }

    /**
     * Start a task
     */
    public function start(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        if ($task->status !== 'available') {
            return $this->errorResponse('Task is not available to start.', 400);
        }

        $task->update([
            'status' => 'active',
            'started_at' => now(),
        ]);

        return $this->successResponse($task->fresh(), 'Task started successfully.');
    }

    /**
     * Complete a task
     */
    public function complete(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        if ($task->status !== 'active') {
            return $this->errorResponse('Task is not active.', 400);
        }

        $task->update([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
        ]);

        // Apply rewards if any
        if ($task->rewards) {
            $this->applyTaskRewards($task);
        }

        return $this->successResponse($task->fresh(), 'Task completed successfully.');
    }

    /**
     * Update task progress
     */
    public function updateProgress(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $task->update($validated);

        // Auto-complete if progress reaches 100%
        if ($validated['progress'] >= 100 && $task->status === 'active') {
            $task->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            if ($task->rewards) {
                $this->applyTaskRewards($task);
            }
        }

        return $this->successResponse($task->fresh(), 'Task progress updated successfully.');
    }

    /**
     * Get player task statistics
     */
    public function playerStats($playerId)
    {
        $stats = Task::where('player_id', $playerId)
            ->selectRaw('
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_tasks,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) as available_tasks,
                SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired_tasks,
                AVG(CASE WHEN status = "completed" THEN progress ELSE NULL END) as avg_progress,
                MAX(completed_at) as last_completed
            ')
            ->first();

        return $this->successResponse($stats, 'Player task statistics fetched successfully.');
    }

    /**
     * Get overdue tasks
     */
    public function overdue(Request $request)
    {
        $query = Task::with($this->relationships)
            ->overdue();

        if ($request->has('player_id')) {
            $query->byPlayer($request->get('player_id'));
        }

        if ($request->has('world_id')) {
            $query->byWorld($request->get('world_id'));
        }

        $tasks = $query->orderBy('deadline', 'asc')->get();

        return $this->successResponse($tasks, 'Overdue tasks fetched successfully.');
    }

    /**
     * Apply task rewards to player
     */
    private function applyTaskRewards(Task $task)
    {
        $rewards = is_array($task->rewards) ? $task->rewards : json_decode($task->rewards, true);

        if (!is_array($rewards)) {
            return;
        }

        $player = $task->player;

        foreach ($rewards as $type => $amount) {
            switch ($type) {
                case 'points':
                    $player->increment('points', $amount);
                    break;
                case 'resources':
                    if (is_array($amount)) {
                        foreach ($amount as $resource => $value) {
                            $village = $player->villages->first();
                            if ($village) {
                                $village->resources()->updateOrCreate(
                                    ['village_id' => $village->id],
                                    [$resource => \DB::raw("$resource + $value")]
                                );
                            }
                        }
                    }
                    break;
            }
        }
    }
}
