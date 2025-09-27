<div class="max-w-4xl mx-auto p-6">
    <!-- Task Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $task->title }}</h1>
                @if($task->reference_number)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $task->reference_number }}
                    </span>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    {{ $task->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                       ($task->status === 'active' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200') }}">
                    {{ ucfirst($task->status) }}
                </span>
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                    {{ ucfirst($task->type) }}
                </span>
            </div>
        </div>

        @if($task->description)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Description</h3>
                <p class="text-gray-700 dark:text-gray-300">{{ $task->description }}</p>
            </div>
        @endif

        @if($task->progress !== null && $task->target)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Progress</h3>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ min(100, ($task->progress / $task->target) * 100) }}%"></div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ number_format($task->progress) }} / {{ number_format($task->target) }} 
                    ({{ number_format(min(100, ($task->progress / $task->target) * 100), 1) }}%)
                </p>
            </div>
        @endif

        @if($task->rewards)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Rewards</h3>
                <ul class="list-disc list-inside text-gray-700 dark:text-gray-300">
                    @foreach($task->rewards as $reward)
                        <li>{{ $reward }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($task->deadline)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Deadline</h3>
                <p class="text-gray-700 dark:text-gray-300">
                    {{ $task->deadline->format('M j, Y \a\t g:i A') }}
                    @if($task->deadline->isFuture())
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            ({{ $task->deadline->diffForHumans() }})
                        </span>
                    @else
                        <span class="text-sm text-red-500 dark:text-red-400">
                            (Expired {{ $task->deadline->diffForHumans() }})
                        </span>
                    @endif
                </p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($task->started_at)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Started</h4>
                    <p class="text-gray-700 dark:text-gray-300">{{ $task->started_at->format('M j, Y \a\t g:i A') }}</p>
                </div>
            @endif
            @if($task->completed_at)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Completed</h4>
                    <p class="text-gray-700 dark:text-gray-300">{{ $task->completed_at->format('M j, Y \a\t g:i A') }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Comments Section -->
    <livewire:game.task-comments :task="$task" />
</div>
