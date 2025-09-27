<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">System Management</h2>
            <div class="flex space-x-2">
                <button wire:click="loadSystemHealth" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Refresh Health
                </button>
                <button wire:click="loadSystemMetrics" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Refresh Metrics
                </button>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="setActiveTab('health')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'health' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    System Health
                </button>
                <button wire:click="setActiveTab('metrics')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'metrics' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Performance Metrics
                </button>
                <button wire:click="setActiveTab('config')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'config' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Configuration
                </button>
                <button wire:click="setActiveTab('tasks')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'tasks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Scheduled Tasks
                </button>
                <button wire:click="setActiveTab('logs')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'logs' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    System Logs
                </button>
            </nav>
        </div>
    </div>

    <!-- System Health Tab -->
    @if($activeTab === 'health')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">System Health Status</h3>
            
            @if($isLoading)
                <div class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <span class="ml-2">Loading system health...</span>
                </div>
            @elseif(!empty($systemHealth))
                <div class="mb-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-lg font-medium">Overall Status:</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $systemHealth['status'] === 'healthy' ? 'bg-green-100 text-green-800' : ($systemHealth['status'] === 'degraded' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($systemHealth['status']) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">Last checked: {{ $systemHealth['timestamp'] ?? 'Unknown' }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($systemHealth['checks'] ?? [] as $checkName => $check)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $checkName)) }}</h4>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $check['status'] === 'healthy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($check['status']) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">{{ $check['message'] ?? 'No message' }}</p>
                            
                            @if(isset($check['stats']))
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    @foreach($check['stats'] as $statName => $statValue)
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">{{ ucfirst(str_replace('_', ' ', $statName)) }}:</span>
                                            <span class="font-medium">{{ $statValue }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No system health data available.
                </div>
            @endif
        </div>
    @endif

    <!-- Performance Metrics Tab -->
    @if($activeTab === 'metrics')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Performance Metrics</h3>
            
            @if($isLoading)
                <div class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <span class="ml-2">Loading metrics...</span>
                </div>
            @elseif(!empty($systemMetrics))
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Memory Usage -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Memory Usage</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Current:</span>
                                <span class="font-medium">{{ number_format(($systemMetrics['performance']['memory_usage'] ?? 0) / 1024 / 1024, 2) }} MB</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Peak:</span>
                                <span class="font-medium">{{ number_format(($systemMetrics['performance']['memory_peak'] ?? 0) / 1024 / 1024, 2) }} MB</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Limit:</span>
                                <span class="font-medium">{{ $systemMetrics['performance']['memory_limit'] ?? 'Unknown' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Database -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Database</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Connections:</span>
                                <span class="font-medium">{{ count($systemMetrics['database']['connections'] ?? []) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Queries:</span>
                                <span class="font-medium">{{ $systemMetrics['database']['query_count'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cache -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Cache System</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Driver:</span>
                                <span class="font-medium">{{ $systemMetrics['cache']['driver'] ?? 'Unknown' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Stores:</span>
                                <span class="font-medium">{{ count($systemMetrics['cache']['stores'] ?? []) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Game Metrics -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Game Activity</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Active Sessions:</span>
                                <span class="font-medium">{{ $systemMetrics['game_metrics']['active_sessions'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Requests Today:</span>
                                <span class="font-medium">{{ $systemMetrics['game_metrics']['total_requests_today'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">New Users:</span>
                                <span class="font-medium">{{ $systemMetrics['game_metrics']['new_registrations_today'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Larautilx Integration -->
                    @if(isset($systemMetrics['larautilx']))
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Larautilx Integration</h4>
                            <div class="space-y-2 text-sm">
                                @foreach($systemMetrics['larautilx'] as $component => $status)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $component)) }}:</span>
                                        <span class="font-medium {{ $status ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 text-sm text-gray-600">
                    <p>Last updated: {{ $systemMetrics['timestamp'] ?? 'Unknown' }}</p>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No metrics data available.
                </div>
            @endif
        </div>
    @endif

    <!-- Configuration Tab -->
    @if($activeTab === 'config')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">System Configuration</h3>
            
            @if($isLoading)
                <div class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <span class="ml-2">Loading configuration...</span>
                </div>
            @elseif(!empty($systemConfig))
                <div class="space-y-6">
                    @if(isset($systemConfig['game']))
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Game Configuration</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Worlds:</span>
                                    <span class="font-medium">{{ $systemConfig['game']['worlds'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Players:</span>
                                    <span class="font-medium">{{ $systemConfig['game']['players'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Villages:</span>
                                    <span class="font-medium">{{ $systemConfig['game']['villages'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Alliances:</span>
                                    <span class="font-medium">{{ $systemConfig['game']['alliances'] ?? 0 }}</span>
                                </div>
                            </div>
                            
                            @if(isset($systemConfig['game']['features']))
                                <div class="mt-4">
                                    <h5 class="font-medium text-gray-800 mb-2">Feature Toggles</h5>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                                        @foreach($systemConfig['game']['features'] as $feature => $enabled)
                                            <div class="flex items-center space-x-2">
                                                <span class="w-2 h-2 rounded-full {{ $enabled ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                                <span class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $feature)) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(isset($systemConfig['app']))
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Application Configuration</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Environment:</span>
                                    <span class="font-medium">{{ $systemConfig['app']['env'] ?? 'Unknown' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Debug Mode:</span>
                                    <span class="font-medium">{{ $systemConfig['app']['debug'] ? 'Enabled' : 'Disabled' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">URL:</span>
                                    <span class="font-medium">{{ $systemConfig['app']['url'] ?? 'Unknown' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Timezone:</span>
                                    <span class="font-medium">{{ $systemConfig['app']['timezone'] ?? 'Unknown' }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No configuration data available.
                </div>
            @endif
        </div>
    @endif

    <!-- Scheduled Tasks Tab -->
    @if($activeTab === 'tasks')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Scheduled Tasks</h3>
            
            @if($isLoading)
                <div class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <span class="ml-2">Loading scheduled tasks...</span>
                </div>
            @elseif(!empty($scheduledTasks))
                @if(isset($scheduledTasks['summary']))
                    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $scheduledTasks['summary']['total_tasks'] ?? 0 }}</div>
                            <div class="text-sm text-blue-800">Total Tasks</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $scheduledTasks['summary']['running_tasks'] ?? 0 }}</div>
                            <div class="text-sm text-green-800">Running</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600">{{ $scheduledTasks['summary']['due_tasks'] ?? 0 }}</div>
                            <div class="text-sm text-yellow-800">Due</div>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-red-600">{{ $scheduledTasks['summary']['overdue_tasks'] ? 'Yes' : 'No' }}</div>
                            <div class="text-sm text-red-800">Overdue</div>
                        </div>
                    </div>
                @endif

                @if(isset($scheduledTasks['tasks']) && count($scheduledTasks['tasks']) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Command</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Run</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($scheduledTasks['tasks'] as $task)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $task['command'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $task['expression'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $task['next_run'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex space-x-2">
                                                @if($task['is_running'] ?? false)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Running
                                                    </span>
                                                @elseif($task['is_due'] ?? false)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Due
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Scheduled
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        No scheduled tasks found.
                    </div>
                @endif
            @else
                <div class="text-center py-8 text-gray-500">
                    No scheduled tasks data available.
                </div>
            @endif
        </div>
    @endif

    <!-- System Logs Tab -->
    @if($activeTab === 'logs')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">System Logs</h3>
                <div class="flex space-x-2">
                    <select wire:model.live="logLevel" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        @foreach($logLevels as $level)
                            <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                        @endforeach
                    </select>
                    <input type="number" wire:model.live="logLimit" min="10" max="1000" class="border border-gray-300 rounded-md px-3 py-2 text-sm w-20" placeholder="Limit">
                    <button wire:click="refreshLogs" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
                        Refresh
                    </button>
                </div>
            </div>

            @if($isLoading)
                <div class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <span class="ml-2">Loading logs...</span>
                </div>
            @elseif(!empty($systemLogs))
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm max-h-96 overflow-y-auto">
                    @if(isset($systemLogs['logs']) && count($systemLogs['logs']) > 0)
                        @foreach($systemLogs['logs'] as $log)
                            <div class="mb-1">{{ $log }}</div>
                        @endforeach
                    @else
                        <div class="text-gray-500">No logs found for the selected criteria.</div>
                    @endif
                </div>

                @if(isset($systemLogs['metadata']))
                    <div class="mt-4 text-sm text-gray-600">
                        <p>Showing {{ $systemLogs['metadata']['total_retrieved'] ?? 0 }} logs (Level: {{ $systemLogs['metadata']['level'] ?? 'all' }}, Limit: {{ $systemLogs['metadata']['limit'] ?? 100 }})</p>
                    </div>
                @endif
            @else
                <div class="text-center py-8 text-gray-500">
                    No log data available.
                </div>
            @endif
        </div>
    @endif

    <!-- Cache Management -->
    <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-semibold mb-4">Cache Management</h3>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            @foreach($cacheTypes as $cacheType)
                <label class="flex items-center">
                    <input type="checkbox" wire:model="selectedCacheTypes" value="{{ $cacheType }}" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">{{ ucfirst($cacheType) }}</span>
                </label>
            @endforeach
        </div>

        <button wire:click="clearSystemCaches" 
                wire:confirm="Are you sure you want to clear the selected caches? This action cannot be undone."
                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
            Clear Selected Caches
        </button>
    </div>
</div>

