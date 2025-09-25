<div class="task-manager bg-gray-900 text-white min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-900 to-blue-900 p-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Task Center</h1>
                    <p class="text-green-200 mt-2">Manage your tasks, quests, and achievements</p>
                </div>
                <div class="flex items-center space-x-4">
                    @if($lastUpdate)
                        <span class="text-sm text-green-200">Last updated: {{ $lastUpdate->format('H:i:s') }}</span>
                    @endif
                    <button wire:click="refreshTasks" 
                            class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-6">
            <nav class="flex space-x-8">
                @foreach($taskCategories as $key => $label)
                    <button wire:click="setViewMode('{{ $key }}')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                                   {{ $viewMode === $key ? 'border-green-500 text-green-400' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300' }}">
                        <i class="fas fa-{{ $this->getTaskIcon($key) }} mr-2"></i>{{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>
    </div>

    <!-- Filters and Controls -->
    <div class="bg-gray-800 p-4 border-b border-gray-700">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Type Filter -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-300">Type:</label>
                    @if($viewMode === 'tasks')
                        <select wire:model.live="taskType" 
                                class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-1 text-white">
                            @foreach($taskTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @elseif($viewMode === 'quests')
                        <select wire:model.live="questType" 
                                class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-1 text-white">
                            @foreach($questTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @elseif($viewMode === 'achievements')
                        <select wire:model.live="achievementType" 
                                class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-1 text-white">
                            @foreach($achievementTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <!-- Search -->
                <div class="flex items-center space-x-2">
                    <input type="text" 
                           wire:model.live.debounce.300ms="searchQuery"
                           placeholder="Search tasks..."
                           class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-1 text-white placeholder-gray-400">
                    <button wire:click="searchTasks" 
                            class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded-lg transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <!-- Real-time Controls -->
                <div class="flex items-center space-x-4">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" 
                               wire:model.live="realTimeUpdates"
                               class="rounded border-gray-600 bg-gray-700 text-green-600">
                        <span class="text-sm text-gray-300">Real-time</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" 
                               wire:model.live="autoRefresh"
                               class="rounded border-gray-600 bg-gray-700 text-green-600">
                        <span class="text-sm text-gray-300">Auto-refresh</span>
                    </label>
                </div>

                <!-- Clear Filters -->
                <button wire:click="clearFilters" 
                        class="bg-gray-600 hover:bg-gray-700 px-3 py-1 rounded-lg transition-colors">
                    <i class="fas fa-times mr-1"></i>Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    @if($isLoading)
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-500"></div>
            <span class="ml-3 text-gray-400">Loading tasks...</span>
        </div>
    @endif

    <!-- Tasks Content -->
    <div class="max-w-7xl mx-auto p-6">
        @if($viewMode === 'tasks')
            <!-- Tasks View -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                @forelse($tasks as $task)
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-{{ $this->getTaskIcon($task->type ?? 'task') }} text-2xl text-green-400 mr-3"></i>
                                <div>
                                    <h3 class="text-lg font-bold text-white">{{ $task->title }}</h3>
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        {{ $this->getTaskColor($task->status) === 'blue' ? 'bg-blue-600 text-blue-100' : 
                                           ($this->getTaskColor($task->status) === 'green' ? 'bg-green-600 text-green-100' : 'bg-gray-600 text-gray-100') }}">
                                        {{ ucfirst($task->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p class="text-gray-300 text-sm mb-4">{{ $task->description }}</p>

                        @if($task->progress && $task->target)
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-400 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $task->progress }}/{{ $task->target }}</span>
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" 
                                         style="width: {{ $this->getProgressPercentage($task->progress, $task->target) }}%"></div>
                                </div>
                            </div>
                        @endif

                        @if($task->deadline)
                            <div class="mb-4">
                                <p class="text-sm text-gray-400">
                                    <i class="fas fa-clock mr-1"></i>
                                    Deadline: {{ $task->deadline->format('M j, Y H:i') }}
                                </p>
                            </div>
                        @endif

                        @if($task->rewards)
                            <div class="mb-4">
                                <p class="text-sm text-yellow-400">
                                    <i class="fas fa-gift mr-1"></i>
                                    Rewards: {{ is_array($task->rewards) ? json_encode($task->rewards) : $task->rewards }}
                                </p>
                            </div>
                        @endif

                        <div class="flex space-x-2">
                            @if($task->status === 'available')
                                <button wire:click="startTask({{ $task->id }})" 
                                        class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-sm transition-colors">
                                    <i class="fas fa-play mr-1"></i>Start
                                </button>
                            @elseif($task->status === 'active')
                                <button wire:click="completeTask({{ $task->id }})" 
                                        class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-sm transition-colors">
                                    <i class="fas fa-check mr-1"></i>Complete
                                </button>
                                <button wire:click="abandonTask({{ $task->id }})" 
                                        class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm transition-colors">
                                    <i class="fas fa-times mr-1"></i>Abandon
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-tasks text-6xl text-gray-600 mb-4"></i>
                        <p class="text-gray-400 text-lg">No tasks found</p>
                    </div>
                @endforelse
            </div>

        @elseif($viewMode === 'quests')
            <!-- Quests View -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                @forelse($quests as $quest)
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-star text-2xl text-yellow-400 mr-3"></i>
                                <div>
                                    <h3 class="text-lg font-bold text-white">{{ $quest->title }}</h3>
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        {{ $this->getTaskColor($quest->status) === 'blue' ? 'bg-blue-600 text-blue-100' : 
                                           ($this->getTaskColor($quest->status) === 'green' ? 'bg-green-600 text-green-100' : 'bg-gray-600 text-gray-100') }}">
                                        {{ ucfirst($quest->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p class="text-gray-300 text-sm mb-4">{{ $quest->description }}</p>

                        @if($quest->progress && $quest->target)
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-400 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $quest->progress }}/{{ $quest->target }}</span>
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-2">
                                    <div class="bg-yellow-500 h-2 rounded-full" 
                                         style="width: {{ $this->getProgressPercentage($quest->progress, $quest->target) }}%"></div>
                                </div>
                            </div>
                        @endif

                        @if($quest->rewards)
                            <div class="mb-4">
                                <p class="text-sm text-yellow-400">
                                    <i class="fas fa-gift mr-1"></i>
                                    Rewards: {{ is_array($quest->rewards) ? json_encode($quest->rewards) : $quest->rewards }}
                                </p>
                            </div>
                        @endif

                        <div class="flex space-x-2">
                            @if($quest->status === 'available')
                                <button wire:click="startQuest({{ $quest->id }})" 
                                        class="bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded text-sm transition-colors">
                                    <i class="fas fa-play mr-1"></i>Start
                                </button>
                            @elseif($quest->status === 'active')
                                <button wire:click="completeQuest({{ $quest->id }})" 
                                        class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-sm transition-colors">
                                    <i class="fas fa-check mr-1"></i>Complete
                                </button>
                                <button wire:click="abandonQuest({{ $quest->id }})" 
                                        class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm transition-colors">
                                    <i class="fas fa-times mr-1"></i>Abandon
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-star text-6xl text-gray-600 mb-4"></i>
                        <p class="text-gray-400 text-lg">No quests found</p>
                    </div>
                @endforelse
            </div>

        @elseif($viewMode === 'achievements')
            <!-- Achievements View -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                @forelse($achievements as $achievement)
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-trophy text-2xl text-yellow-400 mr-3"></i>
                                <div>
                                    <h3 class="text-lg font-bold text-white">{{ $achievement->title }}</h3>
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        {{ $this->getTaskColor($achievement->status) === 'yellow' ? 'bg-yellow-600 text-yellow-100' : 'bg-gray-600 text-gray-100' }}">
                                        {{ ucfirst($achievement->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p class="text-gray-300 text-sm mb-4">{{ $achievement->description }}</p>

                        @if($achievement->progress && $achievement->target)
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-400 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $achievement->progress }}/{{ $achievement->target }}</span>
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-2">
                                    <div class="bg-yellow-500 h-2 rounded-full" 
                                         style="width: {{ $this->getProgressPercentage($achievement->progress, $achievement->target) }}%"></div>
                                </div>
                            </div>
                        @endif

                        @if($achievement->rewards)
                            <div class="mb-4">
                                <p class="text-sm text-yellow-400">
                                    <i class="fas fa-gift mr-1"></i>
                                    Rewards: {{ is_array($achievement->rewards) ? json_encode($achievement->rewards) : $achievement->rewards }}
                                </p>
                            </div>
                        @endif

                        <div class="flex space-x-2">
                            @if($achievement->status === 'available')
                                <button wire:click="claimAchievement({{ $achievement->id }})" 
                                        class="bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded text-sm transition-colors">
                                    <i class="fas fa-trophy mr-1"></i>Claim
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-trophy text-6xl text-gray-600 mb-4"></i>
                        <p class="text-gray-400 text-lg">No achievements found</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    <!-- Notifications -->
    @if(count($notifications) > 0)
        <div class="fixed top-4 right-4 space-y-2 z-50">
            @foreach($notifications as $notification)
                <div class="bg-gray-800 border border-gray-600 rounded-lg p-4 shadow-lg max-w-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-2 h-2 rounded-full mr-3
                                {{ $notification['type'] === 'success' ? 'bg-green-500' : 
                                   ($notification['type'] === 'error' ? 'bg-red-500' : 'bg-blue-500') }}">
                            </div>
                            <span class="text-white text-sm">{{ $notification['message'] }}</span>
                        </div>
                        <button wire:click="clearNotifications" class="text-gray-400 hover:text-white ml-2">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Auto-refresh Script -->
    @if($autoRefresh && $realTimeUpdates)
        <script>
            setInterval(function() {
                @this.call('refreshTasks');
            }, {{ $refreshInterval * 1000 }});
        </script>
    @endif
</div>