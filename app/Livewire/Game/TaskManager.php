<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskManager extends Component
{
    public $village;
    public $tasks = [];
    public $newTask = '';
    public $taskPriority = 'medium';
    public $taskCategory = 'general';
    public $showAddTask = false;
    public $filterStatus = 'all';
    public $filterCategory = 'all';

    public function mount($villageId = null)
    {
        if ($villageId) {
            $this->village = Village::findOrFail($villageId);
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->village = $player?->villages()->first();
        }

        $this->loadTasks();
    }

    public function loadTasks()
    {
        // Sample tasks - in a real game, these would come from the database
        $this->tasks = [
            [
                'id' => 1,
                'title' => 'Upgrade Woodcutter to Level 2',
                'description' => 'Increase wood production by upgrading the woodcutter',
                'category' => 'building',
                'priority' => 'high',
                'status' => 'pending',
                'created_at' => now()->subMinutes(30),
                'due_at' => now()->addHours(2),
                'progress' => 0
            ],
            [
                'id' => 2,
                'title' => 'Build Warehouse',
                'description' => 'Increase storage capacity for resources',
                'category' => 'building',
                'priority' => 'medium',
                'status' => 'in_progress',
                'created_at' => now()->subHours(1),
                'due_at' => now()->addHours(4),
                'progress' => 60
            ],
            [
                'id' => 3,
                'title' => 'Explore Map',
                'description' => 'Scout the surrounding area for resources',
                'category' => 'exploration',
                'priority' => 'low',
                'status' => 'completed',
                'created_at' => now()->subHours(2),
                'due_at' => now()->subHours(1),
                'progress' => 100
            ],
            [
                'id' => 4,
                'title' => 'Research New Technology',
                'description' => 'Unlock new building types',
                'category' => 'research',
                'priority' => 'medium',
                'status' => 'pending',
                'created_at' => now()->subMinutes(15),
                'due_at' => now()->addDays(1),
                'progress' => 0
            ]
        ];
    }

    public function addTask()
    {
        if (empty($this->newTask))
            return;

        $task = [
            'id' => count($this->tasks) + 1,
            'title' => $this->newTask,
            'description' => 'Custom task created by player',
            'category' => $this->taskCategory,
            'priority' => $this->taskPriority,
            'status' => 'pending',
            'created_at' => now(),
            'due_at' => now()->addHours(24),
            'progress' => 0
        ];

        $this->tasks[] = $task;
        $this->newTask = '';
        $this->showAddTask = false;

        $this->dispatch('task-added', ['task' => $task]);
    }

    public function updateTaskStatus($taskId, $status)
    {
        foreach ($this->tasks as &$task) {
            if ($task['id'] == $taskId) {
                $task['status'] = $status;
                if ($status === 'completed') {
                    $task['progress'] = 100;
                }
                break;
            }
        }

        $this->dispatch('task-updated', ['task_id' => $taskId, 'status' => $status]);
    }

    public function updateTaskProgress($taskId, $progress)
    {
        foreach ($this->tasks as &$task) {
            if ($task['id'] == $taskId) {
                $task['progress'] = max(0, min(100, $progress));
                if ($task['progress'] >= 100) {
                    $task['status'] = 'completed';
                } elseif ($task['progress'] > 0) {
                    $task['status'] = 'in_progress';
                }
                break;
            }
        }

        $this->dispatch('task-progress-updated', ['task_id' => $taskId, 'progress' => $progress]);
    }

    public function deleteTask($taskId)
    {
        $this->tasks = array_filter($this->tasks, function ($task) use ($taskId) {
            return $task['id'] != $taskId;
        });

        $this->dispatch('task-deleted', ['task_id' => $taskId]);
    }

    public function toggleAddTask()
    {
        $this->showAddTask = !$this->showAddTask;
    }

    public function setFilterStatus($status)
    {
        $this->filterStatus = $status;
    }

    public function setFilterCategory($category)
    {
        $this->filterCategory = $category;
    }

    public function getFilteredTasks()
    {
        $filtered = $this->tasks;

        if ($this->filterStatus !== 'all') {
            $filtered = array_filter($filtered, function ($task) {
                return $task['status'] === $this->filterStatus;
            });
        }

        if ($this->filterCategory !== 'all') {
            $filtered = array_filter($filtered, function ($task) {
                return $task['category'] === $this->filterCategory;
            });
        }

        return $filtered;
    }

    public function getTaskStats()
    {
        $total = count($this->tasks);
        $completed = count(array_filter($this->tasks, function ($task) {
            return $task['status'] === 'completed';
        }));
        $inProgress = count(array_filter($this->tasks, function ($task) {
            return $task['status'] === 'in_progress';
        }));
        $pending = count(array_filter($this->tasks, function ($task) {
            return $task['status'] === 'pending';
        }));

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'pending' => $pending,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0
        ];
    }

    public function render()
    {
        return view('livewire.game.task-manager', [
            'filteredTasks' => $this->getFilteredTasks(),
            'taskStats' => $this->getTaskStats()
        ]);
    }
}
