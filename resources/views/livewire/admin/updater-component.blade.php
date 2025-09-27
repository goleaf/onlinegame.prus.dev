<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Application Updater</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Manage application updates and system maintenance</p>
            </div>
            <div class="flex space-x-3">
                <x-flux::button wire:click="checkForUpdates" variant="outline" icon="arrow-path">
                    Check for Updates
                </x-flux::button>
                @if($updateAvailable && !$isUpdating)
                    <x-flux::button wire:click="performUpdate" variant="primary" icon="arrow-down-tray">
                        Update Now
                    </x-flux::button>
                @endif
            </div>
        </div>
    </div>

    <!-- Version Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Version Information</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Current Version:</span>
                    <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $currentVersion }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Latest Version:</span>
                    <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $latestVersion }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Status:</span>
                    @if($updateAvailable)
                        <x-flux::badge variant="warning">Update Available</x-flux::badge>
                    @else
                        <x-flux::badge variant="success">Up to Date</x-flux::badge>
                    @endif
                </div>
                @if($behindCommits > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Commits Behind:</span>
                        <span class="font-mono text-sm bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-2 py-1 rounded">{{ $behindCommits }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- System Information -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Information</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">PHP Version:</span>
                    <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $systemInfo['php_version'] ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Laravel Version:</span>
                    <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $systemInfo['laravel_version'] ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Git Branch:</span>
                    <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $systemInfo['git_branch'] ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Memory Usage:</span>
                    <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ isset($systemInfo['memory_usage']) ? number_format($systemInfo['memory_usage'] / 1024 / 1024, 2) . ' MB' : 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Progress -->
    @if($isUpdating || !empty($updateSteps))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                @if($isUpdating)
                    Update in Progress...
                @else
                    Update Log
                @endif
            </h3>
            
            @if($isUpdating)
                <div class="flex items-center space-x-3 mb-4">
                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                    <span class="text-gray-600 dark:text-gray-400">Please wait while the update is being processed...</span>
                </div>
            @endif

            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach($updateSteps as $step)
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex-shrink-0">
                            <x-flux::icon.check-circle class="w-5 h-5 text-green-500" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-white">{{ $step['step'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $step['timestamp'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Error Display -->
    @if($error)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex">
                <x-flux::icon.exclamation-triangle class="w-5 h-5 text-red-400" />
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error</h3>
                    <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                        {{ $error }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Maintenance Tools -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Maintenance Tools</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-flux::button wire:click="clearCache" 
                            wire:confirm="Are you sure you want to clear all caches? This action cannot be undone."
                            variant="outline" icon="trash" class="w-full">
                Clear Caches
            </x-flux::button>
            <x-flux::button wire:click="optimizeApplication" variant="outline" icon="bolt" class="w-full">
                Optimize Application
            </x-flux::button>
        </div>
    </div>

    <!-- Update History -->
    @if(!empty($updateHistory))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Update History</h3>
            <div class="space-y-3">
                @foreach(array_reverse($updateHistory) as $update)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center space-x-3">
                            @if($update['success'])
                                <x-flux::icon.check-circle class="w-5 h-5 text-green-500" />
                            @else
                                <x-flux::icon.x-circle class="w-5 h-5 text-red-500" />
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $update['from_version'] }} → {{ $update['to_version'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($update['timestamp'])->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            @if($update['success'])
                                <x-flux::badge variant="success">Success</x-flux::badge>
                            @else
                                <x-flux::badge variant="danger">Failed</x-flux::badge>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
