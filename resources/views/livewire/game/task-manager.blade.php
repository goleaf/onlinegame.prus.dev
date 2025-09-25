<div>
    <div class="task-manager">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-tasks"></i> Task Manager
            </h4>
            <button 
                wire:click="toggleAddTask" 
                class="btn btn-primary"
            >
                <i class="fas fa-plus"></i> Add Task
            </button>
        </div>

        <!-- Task Statistics -->
        <div class="task-stats mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-info">
                            <h6>Total Tasks</h6>
                            <span class="stat-number">{{ $taskStats['total'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h6>Completed</h6>
                            <span class="stat-number text-success">{{ $taskStats['completed'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h6>In Progress</h6>
                            <span class="stat-number text-warning">{{ $taskStats['in_progress'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-info">
                            <h6>Completion Rate</h6>
                            <span class="stat-number text-info">{{ $taskStats['completion_rate'] }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="task-filters mb-4">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Filter by Status:</label>
                    <select 
                        wire:model.live="filterStatus" 
                        class="form-select"
                    >
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Filter by Category:</label>
                    <select 
                        wire:model.live="filterCategory" 
                        class="form-select"
                    >
                        <option value="all">All Categories</option>
                        <option value="building">Building</option>
                        <option value="exploration">Exploration</option>
                        <option value="research">Research</option>
                        <option value="general">General</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Add Task Modal -->
        @if($showAddTask)
            <div class="add-task-modal mb-4">
                <div class="card bg-dark">
                    <div class="card-header">
                        <h5><i class="fas fa-plus"></i> Add New Task</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Task Title:</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                wire:model.live="newTask"
                                placeholder="Enter task title..."
                            >
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Priority:</label>
                                <select 
                                    wire:model.live="taskPriority" 
                                    class="form-select"
                                >
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category:</label>
                                <select 
                                    wire:model.live="taskCategory" 
                                    class="form-select"
                                >
                                    <option value="general">General</option>
                                    <option value="building">Building</option>
                                    <option value="exploration">Exploration</option>
                                    <option value="research">Research</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button 
                                wire:click="addTask" 
                                class="btn btn-success"
                                {{ empty($newTask) ? 'disabled' : '' }}
                            >
                                <i class="fas fa-plus"></i> Add Task
                            </button>
                            <button 
                                wire:click="toggleAddTask" 
                                class="btn btn-secondary ms-2"
                            >
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Task List -->
        <div class="task-list">
            @forelse($filteredTasks as $task)
                <div class="task-item {{ $task['status'] }}">
                    <div class="task-header">
                        <div class="task-title">
                            <h6>{{ $task['title'] }}</h6>
                            <span class="task-category badge bg-{{ $task['category'] === 'building' ? 'primary' : ($task['category'] === 'exploration' ? 'info' : ($task['category'] === 'research' ? 'warning' : 'secondary')) }}">
                                {{ ucfirst($task['category']) }}
                            </span>
                        </div>
                        <div class="task-actions">
                            <div class="btn-group">
                                @if($task['status'] === 'pending')
                                    <button 
                                        wire:click="updateTaskStatus({{ $task['id'] }}, 'in_progress')" 
                                        class="btn btn-sm btn-warning"
                                    >
                                        <i class="fas fa-play"></i>
                                    </button>
                                @elseif($task['status'] === 'in_progress')
                                    <button 
                                        wire:click="updateTaskStatus({{ $task['id'] }}, 'completed')" 
                                        class="btn btn-sm btn-success"
                                    >
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endif
                                <button 
                                    wire:click="deleteTask({{ $task['id'] }})" 
                                    class="btn btn-sm btn-danger"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="task-body">
                        <p class="task-description">{{ $task['description'] }}</p>
                        
                        <div class="task-progress">
                            <div class="progress">
                                <div 
                                    class="progress-bar bg-{{ $task['status'] === 'completed' ? 'success' : ($task['status'] === 'in_progress' ? 'warning' : 'secondary') }}" 
                                    style="width: {{ $task['progress'] }}%"
                                ></div>
                            </div>
                            <span class="progress-text">{{ $task['progress'] }}%</span>
                        </div>
                        
                        <div class="task-meta">
                            <div class="task-priority">
                                <i class="fas fa-flag text-{{ $task['priority'] === 'high' ? 'danger' : ($task['priority'] === 'medium' ? 'warning' : 'success') }}"></i>
                                {{ ucfirst($task['priority']) }} Priority
                            </div>
                            <div class="task-due">
                                <i class="fas fa-clock"></i>
                                Due: {{ $task['due_at']->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="no-tasks">
                    <i class="fas fa-inbox fa-3x text-muted"></i>
                    <h5>No tasks found</h5>
                    <p>Create your first task to get started!</p>
                </div>
            @endforelse
        </div>
    </div>

    <style>
        .task-manager {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .task-stats .stat-card {
            background: #34495e;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
        }

        .task-stats .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            font-size: 2rem;
            color: #3498db;
        }

        .stat-info h6 {
            margin-bottom: 5px;
            color: #bdc3c7;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #fff;
        }

        .task-filters {
            background: #34495e;
            border-radius: 8px;
            padding: 15px;
        }

        .add-task-modal {
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .task-item {
            background: #34495e;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
        }

        .task-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .task-item.completed {
            border-left-color: #27ae60;
            opacity: 0.8;
        }

        .task-item.in_progress {
            border-left-color: #f39c12;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .task-title h6 {
            margin-bottom: 5px;
            color: #fff;
        }

        .task-category {
            font-size: 0.8rem;
        }

        .task-actions .btn-group {
            gap: 5px;
        }

        .task-description {
            color: #bdc3c7;
            margin-bottom: 15px;
        }

        .task-progress {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .progress {
            flex: 1;
            height: 8px;
            background: #2c3e50;
            border-radius: 4px;
        }

        .progress-text {
            color: #3498db;
            font-weight: bold;
            min-width: 40px;
        }

        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #bdc3c7;
        }

        .no-tasks {
            text-align: center;
            padding: 40px;
            color: #bdc3c7;
        }

        .no-tasks i {
            margin-bottom: 15px;
        }
    </style>
</div>