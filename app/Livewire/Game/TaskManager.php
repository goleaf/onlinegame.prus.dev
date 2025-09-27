<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\PlayerAchievement;
use App\Models\Game\PlayerQuest;
use App\Models\Game\Task;
use App\Models\Game\World;
use App\Services\GeographicService;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\FilteringUtil;
use LaraUtilX\Utilities\PaginationUtil;
use SmartCache\Facades\SmartCache;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TaskManager extends Component
{
    use WithPagination, ApiResponseTrait;

    public $world;
    public $player;
    public $isLoading = false;
    public $notifications = [];
    // Task data
    public $tasks = [];
    public $activeTasks = [];
    public $completedTasks = [];
    public $availableTasks = [];
    public $taskProgress = [];
    public $taskRewards = [];
    // Quest data
    public $quests = [];
    public $activeQuests = [];
    public $completedQuests = [];
    public $availableQuests = [];
    public $questProgress = [];
    // Achievement data
    public $achievements = [];
    public $unlockedAchievements = [];
    public $availableAchievements = [];
    public $achievementProgress = [];
    // View modes and filters
    public $viewMode = 'tasks';  // tasks, quests, achievements
    public $taskType = 'all';  // all, active, completed, available
    public $questType = 'all';  // all, active, completed, available
    public $achievementType = 'all';  // all, unlocked, available
    public $sortBy = 'created_at';
    public $sortOrder = 'desc';
    public $searchQuery = '';
    // Real-time features
    public $realTimeUpdates = true;
    public $autoRefresh = true;
    public $refreshInterval = 30;  // seconds
    public $lastUpdate = null;
    // Pagination
    public $perPage = 20;
    public $currentPage = 1;

    // Task categories
    public $taskCategories = [
        'tasks' => 'Tasks',
        'quests' => 'Quests',
        'achievements' => 'Achievements',
    ];

    public $taskTypes = [
        'all' => 'All Tasks',
        'active' => 'Active',
        'completed' => 'Completed',
        'available' => 'Available',
    ];

    public $questTypes = [
        'all' => 'All Quests',
        'active' => 'Active',
        'completed' => 'Completed',
        'available' => 'Available',
    ];

    public $achievementTypes = [
        'all' => 'All Achievements',
        'unlocked' => 'Unlocked',
        'available' => 'Available',
    ];

    protected $listeners = [
        'refreshTasks',
        'taskCompleted',
        'questCompleted',
        'achievementUnlocked',
        'taskProgressUpdated',
        'questProgressUpdated',
        'gameTickProcessed',
        'villageSelected',
    ];

    public function mount($worldId = null, $world = null)
    {
        if ($world) {
            $this->world = $world;
        } elseif ($worldId) {
            $this->world = World::findOrFail($worldId);
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->world = $player?->village?->world;
        }

        // Laradumps debugging
        ds('TaskManager mounted', [
            'world_id' => $this->world?->id,
            'world_name' => $this->world?->name,
            'user_id' => Auth::id(),
            'player_id' => $player?->id,
            'player_villages' => $player?->villages->map(function($village) {
                return [
                    'village_name' => $village->name,
                    'coordinates' => "({$village->x_coordinate}|{$village->y_coordinate})",
                    'real_world_coords' => $village->getRealWorldCoordinates()
                ];
            })->toArray() ?? []
        ])->label('TaskManager Mount');

        if ($this->world) {
            $this->loadPlayerData();
            $this->loadTasks();
            $this->initializeTaskFeatures();
        }
    }

    public function loadPlayerData()
    {
        try {
            $this->player = Player::where('user_id', Auth::id())
                ->where('world_id', $this->world->id)
                ->with(['villages', 'alliance'])
                ->first();

            if (!$this->player) {
                $this->addNotification('Player not found in this world', 'error');

                return;
            }
        } catch (\Exception $e) {
            $this->addNotification('Error loading player data: ' . $e->getMessage(), 'error');
        }
    }

    public function loadTasks()
    {
        $this->isLoading = true;

        try {
            switch ($this->viewMode) {
                case 'tasks':
                    $this->loadTaskData();

                    break;
                case 'quests':
                    $this->loadQuestData();

                    break;
                case 'achievements':
                    $this->loadAchievementData();

                    break;
            }

            $this->lastUpdate = now();
        } catch (\Exception $e) {
            $this->addNotification('Error loading tasks: ' . $e->getMessage(), 'error');
        }

        $this->isLoading = false;
    }

    private function loadTaskData()
    {
        // Laradumps debugging
        ds('Loading task data', [
            'world_id' => $this->world->id,
            'player_id' => $this->player->id,
            'task_type' => $this->taskType,
            'search_query' => $this->searchQuery,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder
        ])->label('TaskManager Load Task Data');

        // Use SmartCache for task data with automatic optimization
        $cacheKey = "world_{$this->world->id}_player_{$this->player->id}_tasks_{$this->taskType}_{$this->sortBy}_{$this->sortOrder}";

        $this->tasks = SmartCache::remember($cacheKey, now()->addMinutes(3), function () {
            // Use optimized scopes from Task model
            $query = Task::byWorld($this->world->id)
                ->byPlayer($this->player->id)
                ->withStats()
                ->withPlayerInfo()
                ->byStatusFilter($this->taskType)
                ->search($this->searchQuery)
                ->orderBy($this->sortBy, $this->sortOrder);

            return $query->get();
        });

        // Apply additional filtering using FilteringUtil for complex filters
        if (!empty($this->searchQuery)) {
            $this->tasks = FilteringUtil::filter(
                $this->tasks,
                'title',
                'contains',
                $this->searchQuery
            );
        }

        // Use SmartCache for task statistics with automatic optimization
        $statsCacheKey = "world_{$this->world->id}_player_{$this->player->id}_task_stats";
        
        $taskStats = SmartCache::remember($statsCacheKey, now()->addMinutes(5), function () {
            // Use single query with selectRaw to get all task stats at once
            return Task::where('world_id', $this->world->id)
                ->where('player_id', $this->player->id)
                ->selectRaw('
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) as available_count,
                    AVG(CASE WHEN status = "completed" THEN progress ELSE NULL END) as avg_progress,
                    MAX(updated_at) as last_updated
                ')
                ->first();
        });

        // Get tasks by status using SmartCache with optimized scopes
        $activeTasksCacheKey = "world_{$this->world->id}_player_{$this->player->id}_active_tasks";
        $this->activeTasks = SmartCache::remember($activeTasksCacheKey, now()->addMinutes(2), function () {
            return Task::byWorld($this->world->id)
                ->byPlayer($this->player->id)
                ->active()
                ->withStats()
                ->get();
        });

        $completedTasksCacheKey = "world_{$this->world->id}_player_{$this->player->id}_completed_tasks";
        $this->completedTasks = SmartCache::remember($completedTasksCacheKey, now()->addMinutes(5), function () {
            return Task::byWorld($this->world->id)
            ->byPlayer($this->player->id)
            ->completed()
            ->withStats()
            ->get();

        $this->availableTasks = Task::byWorld($this->world->id)
            ->byPlayer($this->player->id)
            ->available()
            ->withStats()
            ->get();

        // Laradumps debugging for loaded data
        ds('Task data loaded', [
            'total_tasks' => $this->tasks->count(),
            'active_tasks' => $this->activeTasks->count(),
            'completed_tasks' => $this->completedTasks->count(),
            'available_tasks' => $this->availableTasks->count(),
            'task_stats' => $taskStats
        ])->label('TaskManager Task Data Loaded');
    }

    private function loadQuestData()
    {
        // Create base query for reuse
        $baseQuery = PlayerQuest::where('player_id', $this->player->id)
            ->with('quest:id,name,description,category,difficulty');

        // Use QueryOptimizationService for conditional filters
        $filters = [
            $this->questType === 'active' => function ($q) {
                return $q->where('status', 'in_progress');
            },
            $this->questType === 'completed' => function ($q) {
                return $q->where('status', 'completed');
            },
            $this->questType === 'available' => function ($q) {
                return $q->where('status', 'available');
            },
            $this->searchQuery => function ($q) {
                return $q->where(function ($subQ) {
                    $subQ->whereHas('quest', function ($questQ) {
                        $questQ
                            ->where('name', 'like', '%' . $this->searchQuery . '%')
                            ->orWhere('description', 'like', '%' . $this->searchQuery . '%');
                    });
                });
            },
        ];

        $query = QueryOptimizationService::applyConditionalFilters($baseQuery, $filters);
        $query = QueryOptimizationService::applyConditionalOrdering($query, $this->sortBy, $this->sortOrder);

        $this->quests = $query->get();

        // Use single query with selectRaw to get all quest stats at once
        $questStats = PlayerQuest::where('player_id', $this->player->id)
            ->selectRaw('
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) as available_count,
                AVG(CASE WHEN status = "completed" THEN progress ELSE NULL END) as avg_progress,
                MAX(updated_at) as last_updated
            ')
            ->first();

        // Get quests by status using optimized queries
        $this->activeQuests = PlayerQuest::where('player_id', $this->player->id)
            ->where('status', 'in_progress')
            ->with('quest:id,name,description,category,difficulty')
            ->selectRaw('player_quests.*, (SELECT COUNT(*) FROM player_quests pq2 WHERE pq2.player_id = player_quests.player_id AND pq2.status = "in_progress") as total_active')
            ->get();

        $this->completedQuests = PlayerQuest::where('player_id', $this->player->id)
            ->where('status', 'completed')
            ->with('quest:id,name,description,category,difficulty')
            ->selectRaw('player_quests.*, (SELECT COUNT(*) FROM player_quests pq2 WHERE pq2.player_id = player_quests.player_id AND pq2.status = "completed") as total_completed')
            ->get();

        $this->availableQuests = PlayerQuest::where('player_id', $this->player->id)
            ->where('status', 'available')
            ->with('quest:id,name,description,category,difficulty')
            ->selectRaw('player_quests.*, (SELECT COUNT(*) FROM player_quests pq2 WHERE pq2.player_id = player_quests.player_id AND pq2.status = "available") as total_available')
            ->get();
    }

    private function loadAchievementData()
    {
        // Create base query for reuse
        $baseQuery = PlayerAchievement::where('player_id', $this->player->id)
            ->with('achievement:id,name,description,category,points');

        // Use QueryOptimizationService for conditional filters
        $filters = [
            $this->achievementType === 'unlocked' => function ($q) {
                return $q->whereNotNull('unlocked_at');
            },
            $this->achievementType === 'available' => function ($q) {
                return $q->whereNull('unlocked_at');
            },
            $this->searchQuery => function ($q) {
                return $q->where(function ($subQ) {
                    $subQ->whereHas('achievement', function ($achievementQ) {
                        $achievementQ
                            ->where('name', 'like', '%' . $this->searchQuery . '%')
                            ->orWhere('description', 'like', '%' . $this->searchQuery . '%');
                    });
                });
            },
        ];

        $query = QueryOptimizationService::applyConditionalFilters($baseQuery, $filters);
        $query = QueryOptimizationService::applyConditionalOrdering($query, $this->sortBy, $this->sortOrder);

        $this->achievements = $query->get();

        // Use single query with selectRaw to get all achievement stats at once
        $achievementStats = PlayerAchievement::where('player_id', $this->player->id)
            ->selectRaw('
                SUM(CASE WHEN unlocked_at IS NOT NULL THEN 1 ELSE 0 END) as unlocked_count,
                SUM(CASE WHEN unlocked_at IS NULL THEN 1 ELSE 0 END) as available_count,
                SUM(CASE WHEN unlocked_at IS NOT NULL THEN achievements.points ELSE 0 END) as total_points,
                MAX(unlocked_at) as last_unlocked
            ')
            ->join('achievements', 'player_achievements.achievement_id', '=', 'achievements.id')
            ->first();

        // Get achievements by status using optimized queries
        $this->unlockedAchievements = PlayerAchievement::where('player_id', $this->player->id)
            ->whereNotNull('unlocked_at')
            ->with('achievement:id,name,description,category,points')
            ->selectRaw('player_achievements.*, (SELECT COUNT(*) FROM player_achievements pa2 WHERE pa2.player_id = player_achievements.player_id AND pa2.unlocked_at IS NOT NULL) as total_unlocked')
            ->get();

        $this->availableAchievements = PlayerAchievement::where('player_id', $this->player->id)
            ->whereNull('unlocked_at')
            ->with('achievement:id,name,description,category,points')
            ->selectRaw('player_achievements.*, (SELECT COUNT(*) FROM player_achievements pa2 WHERE pa2.player_id = player_achievements.player_id AND pa2.unlocked_at IS NULL) as total_available')
            ->get();
    }

    // Task management methods
    public function startTask($taskId)
    {
        $task = Task::find($taskId);

        // Laradumps debugging
        ds('Starting task', [
            'task_id' => $taskId,
            'task' => $task,
            'task_status' => $task?->status,
            'player_id' => $this->player?->id
        ])->label('TaskManager Start Task');

        if ($task && $task->status === 'available') {
            $task->update([
                'status' => 'active',
                'started_at' => now(),
            ]);
            $this->loadTasks();
            $this->addNotification("Task '{$task->title}' started", 'success');
            $this->dispatch('taskStarted', ['taskId' => $taskId]);

            ds('Task started successfully', [
                'task_id' => $taskId,
                'task_title' => $task->title,
                'started_at' => $task->started_at
            ])->label('TaskManager Task Started');
        } else {
            ds('Task start failed', [
                'task_id' => $taskId,
                'reason' => 'Task not available or already active',
                'task_status' => $task?->status
            ])->label('TaskManager Task Start Failed');
            $this->addNotification('Task not available or already active', 'error');
        }
    }

    public function completeTask($taskId)
    {
        $task = Task::find($taskId);

        // Laradumps debugging
        ds('Completing task', [
            'task_id' => $taskId,
            'task' => $task,
            'task_status' => $task?->status,
            'player_id' => $this->player?->id
        ])->label('TaskManager Complete Task');

        if ($task && $task->status === 'active') {
            $task->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $this->giveTaskRewards($task);
            $this->loadTasks();
            $this->addNotification("Task '{$task->title}' completed!", 'success');
            $this->dispatch('taskCompleted', ['taskId' => $taskId]);

            ds('Task completed successfully', [
                'task_id' => $taskId,
                'task_title' => $task->title,
                'completed_at' => $task->completed_at,
                'rewards' => $task->rewards
            ])->label('TaskManager Task Completed');
        } else {
            ds('Task completion failed', [
                'task_id' => $taskId,
                'reason' => 'Task not active or already completed',
                'task_status' => $task?->status
            ])->label('TaskManager Task Completion Failed');
            $this->addNotification('Task not active or already completed', 'error');
        }
    }

    public function abandonTask($taskId)
    {
        $task = Task::find($taskId);
        if ($task && $task->status === 'active') {
            $task->update([
                'status' => 'available',
                'started_at' => null,
            ]);
            $this->loadTasks();
            $this->addNotification("Task '{$task->title}' abandoned", 'info');
            $this->dispatch('taskAbandoned', ['taskId' => $taskId]);
        } else {
            $this->addNotification('Task not active', 'error');
        }
    }

    // Quest management methods
    public function startQuest($questId)
    {
        $quest = PlayerQuest::find($questId);
        if ($quest && $quest->status === 'available') {
            $quest->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
            $this->loadTasks();
            $this->addNotification("Quest '{$quest->title}' started", 'success');
            $this->dispatch('questStarted', ['questId' => $questId]);
        } else {
            $this->addNotification('Quest not available or already active', 'error');
        }
    }

    public function completeQuest($questId)
    {
        $quest = PlayerQuest::find($questId);
        if ($quest && $quest->status === 'in_progress') {
            $quest->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $this->giveQuestRewards($quest);
            $this->loadTasks();
            $this->addNotification("Quest '{$quest->title}' completed!", 'success');
            $this->dispatch('questCompleted', ['questId' => $questId]);
        } else {
            $this->addNotification('Quest not active or already completed', 'error');
        }
    }

    public function abandonQuest($questId)
    {
        $quest = PlayerQuest::find($questId);
        if ($quest && $quest->status === 'in_progress') {
            $quest->update([
                'status' => 'available',
                'started_at' => null,
            ]);
            $this->loadTasks();
            $this->addNotification("Quest '{$quest->title}' abandoned", 'info');
            $this->dispatch('questAbandoned', ['questId' => $questId]);
        } else {
            $this->addNotification('Quest not active', 'error');
        }
    }

    // Achievement management methods
    public function claimAchievement($achievementId)
    {
        $achievement = PlayerAchievement::find($achievementId);
        if ($achievement && $achievement->unlocked_at === null) {
            $achievement->update([
                'unlocked_at' => now(),
            ]);
            $this->giveAchievementRewards($achievement);
            $this->loadTasks();
            $this->addNotification("Achievement '{$achievement->title}' unlocked!", 'success');
            $this->dispatch('achievementUnlocked', ['achievementId' => $achievementId]);
        } else {
            $this->addNotification('Achievement not available or already unlocked', 'error');
        }
    }

    // Reward methods
    private function giveTaskRewards($task)
    {
        if ($task->rewards) {
            // Handle both array and JSON string rewards
            $rewards = is_array($task->rewards) ? $task->rewards : json_decode($task->rewards, true);
            if (is_array($rewards)) {
                foreach ($rewards as $type => $amount) {
                    switch ($type) {
                        case 'points':
                            $this->player->increment('points', $amount);

                            break;
                        case 'resources':
                            if (is_array($amount)) {
                                foreach ($amount as $resource => $value) {
                                    $this->player->villages->first()->increment($resource, $value);
                                }
                            }

                            break;
                    }
                }
            }
        }
    }

    private function giveQuestRewards($quest)
    {
        // Quest rewards are handled through the quest template
        // This is a placeholder for future implementation
        $this->addNotification('Quest rewards applied', 'success');
    }

    private function giveAchievementRewards($achievement)
    {
        // Achievement rewards are handled through the achievement template
        // This is a placeholder for future implementation
        $this->addNotification('Achievement rewards applied', 'success');
    }

    // View mode methods
    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->loadTasks();
        $this->addNotification('Switched to ' . ($this->taskCategories[$mode] ?? $mode) . ' view', 'info');
    }

    public function setTaskType($type)
    {
        $this->taskType = $type;
        $this->loadTasks();
        $this->addNotification('Task type set to ' . ($this->taskTypes[$type] ?? $type), 'info');
    }

    public function setQuestType($type)
    {
        $this->questType = $type;
        $this->loadTasks();
        $this->addNotification('Quest type set to ' . ($this->questTypes[$type] ?? $type), 'info');
    }

    public function setAchievementType($type)
    {
        $this->achievementType = $type;
        $this->loadTasks();
        $this->addNotification('Achievement type set to ' . ($this->achievementTypes[$type] ?? $type), 'info');
    }

    public function sortTasks($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortOrder = 'asc';
        }

        $this->loadTasks();
        $this->addNotification("Sorted by {$sortBy} ({$this->sortOrder})", 'info');
    }

    public function searchTasks()
    {
        if (empty($this->searchQuery)) {
            $this->addNotification('Search cleared', 'info');

            return;
        }

        $this->loadTasks();
        $this->addNotification("Searching for: {$this->searchQuery}", 'info');
    }

    public function clearFilters()
    {
        $this->taskType = 'all';
        $this->questType = 'all';
        $this->achievementType = 'all';
        $this->searchQuery = '';
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';

        $this->loadTasks();
        $this->addNotification('All filters cleared', 'info');
    }

    // Real-time features
    public function toggleRealTimeUpdates()
    {
        $this->realTimeUpdates = !$this->realTimeUpdates;
        $this->addNotification(
            $this->realTimeUpdates ? 'Real-time updates enabled' : 'Real-time updates disabled',
            'info'
        );
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        $this->addNotification(
            $this->autoRefresh ? 'Auto-refresh enabled' : 'Auto-refresh disabled',
            'info'
        );
    }

    public function setRefreshInterval($interval)
    {
        $this->refreshInterval = max(5, min(300, $interval));
        $this->addNotification("Refresh interval set to {$this->refreshInterval} seconds", 'info');
    }

    public function refreshTasks()
    {
        $this->loadTasks();
        $this->addNotification('Tasks refreshed', 'success');
    }

    // Event handlers
    #[On('taskCompleted')]
    public function handleTaskCompleted($data)
    {
        $this->loadTasks();
        $this->addNotification('Task completed', 'success');
    }

    #[On('questCompleted')]
    public function handleQuestCompleted($data)
    {
        $this->loadTasks();
        $this->addNotification('Quest completed', 'success');
    }

    #[On('achievementUnlocked')]
    public function handleAchievementUnlocked($data)
    {
        $this->loadTasks();
        $this->addNotification('Achievement unlocked!', 'success');
    }

    #[On('taskProgressUpdated')]
    public function handleTaskProgressUpdated($data)
    {
        $this->loadTasks();
        $this->addNotification('Task progress updated', 'info');
    }

    #[On('questProgressUpdated')]
    public function handleQuestProgressUpdated($data)
    {
        $this->loadTasks();
        $this->addNotification('Quest progress updated', 'info');
    }

    #[On('gameTickProcessed')]
    public function handleGameTickProcessed()
    {
        if ($this->realTimeUpdates) {
            $this->loadTasks();
        }
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $this->loadTasks();
        $this->addNotification('Village selected - tasks updated', 'info');
    }

    // Utility methods
    public function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
        ];
    }

    public function clearNotifications()
    {
        $this->notifications = [];
    }

    public function getTaskIcon($type)
    {
        $icons = [
            'task' => 'check-circle',
            'quest' => 'star',
            'achievement' => 'trophy',
            'building' => 'home',
            'troop' => 'users',
            'resource' => 'coins',
            'battle' => 'sword',
        ];

        return $icons[$type] ?? 'check-circle';
    }

    public function getTaskColor($status)
    {
        $colors = [
            'active' => 'blue',
            'completed' => 'green',
            'available' => 'gray',
            'unlocked' => 'yellow',
        ];

        return $colors[$status] ?? 'gray';
    }

    public function getProgressPercentage($current, $target)
    {
        if ($target == 0) {
            return 0;
        }

        return min(100, round(($current / $target) * 100, 2));
    }

    public function formatTimeRemaining($endTime)
    {
        if (!$endTime) {
            return 'No time limit';
        }

        $remaining = now()->diffInSeconds($endTime);
        if ($remaining <= 0) {
            return 'Expired';
        }

        // Round up to handle floating point precision issues
        $remaining = ceil($remaining);

        $hours = floor($remaining / 3600);
        $minutes = floor(($remaining % 3600) / 60);
        $seconds = $remaining % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        } else {
            return "{$seconds}s";
        }
    }

    private function initializeTaskFeatures()
    {
        // Initialize any additional features
        $this->lastUpdate = now();
    }

    public function render()
    {
        // Use PaginationUtil for consistent pagination
        $paginatedTasks = PaginationUtil::paginate(
            $this->tasks->toArray(),
            $this->perPage,
            $this->currentPage,
            ['path' => request()->url()]
        );

        return view('livewire.game.task-manager', [
            'taskCategories' => $this->taskCategories,
            'taskTypes' => $this->taskTypes,
            'questTypes' => $this->questTypes,
            'achievementTypes' => $this->achievementTypes,
            'paginatedTasks' => $paginatedTasks,
        ]);
    }
}
