<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Admin Dashboard</h1>
                    <p class="text-blue-100 mt-1">System overview and management</p>
                </div>
                <div class="flex space-x-2">
                    <button wire:click="refreshStats" 
                            class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- System Statistics -->
        <div class="p-6">
            <h2 class="text-xl font-semibold mb-4">System Statistics</h2>
            
            @if($isLoading)
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-2 text-gray-600">Loading statistics...</span>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Users -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-users text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Users</p>
                                <p class="text-2xl font-bold text-blue-600">{{ number_format($systemStats['total_users'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Players -->
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-gamepad text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Players</p>
                                <p class="text-2xl font-bold text-green-600">{{ number_format($systemStats['total_players'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Villages -->
                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-home text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Villages</p>
                                <p class="text-2xl font-bold text-purple-600">{{ number_format($systemStats['total_villages'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Active Sessions -->
                    <div class="bg-orange-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i class="fas fa-signal text-orange-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Active Sessions</p>
                                <p class="text-2xl font-bold text-orange-600">{{ number_format($systemStats['active_sessions'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- System Uptime -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-gray-100 rounded-lg">
                                <i class="fas fa-clock text-gray-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">System Uptime</p>
                                <p class="text-sm font-bold text-gray-800">{{ $systemStats['system_uptime'] ?? 'Unknown' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Memory Usage -->
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-memory text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Memory Usage</p>
                                <p class="text-sm font-bold text-yellow-800">{{ $systemStats['memory_usage'] ?? 'Unknown' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Disk Usage -->
                    <div class="bg-red-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <i class="fas fa-hdd text-red-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Disk Usage</p>
                                <p class="text-sm font-bold text-red-800">{{ $systemStats['disk_usage'] ?? 'Unknown' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Overall Health -->
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-heartbeat text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">System Health</p>
                                <p class="text-sm font-bold text-green-800 capitalize">{{ $systemHealth['overall'] ?? 'Unknown' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- System Health Details -->
        <div class="px-6 pb-6">
            <h2 class="text-xl font-semibold mb-4">System Health Details</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Database Health -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-database text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Database</p>
                                <p class="text-sm font-bold text-gray-800 capitalize">{{ $systemHealth['database'] ?? 'Unknown' }}</p>
                            </div>
                        </div>
                        <div class="w-3 h-3 rounded-full {{ $systemHealth['database'] === 'healthy' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                    </div>
                </div>

                <!-- Cache Health -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-bolt text-yellow-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Cache</p>
                                <p class="text-sm font-bold text-gray-800 capitalize">{{ $systemHealth['cache'] ?? 'Unknown' }}</p>
                            </div>
                        </div>
                        <div class="w-3 h-3 rounded-full {{ $systemHealth['cache'] === 'healthy' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                    </div>
                </div>

                <!-- Storage Health -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-folder text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Storage</p>
                                <p class="text-sm font-bold text-gray-800 capitalize">{{ $systemHealth['storage'] ?? 'Unknown' }}</p>
                            </div>
                        </div>
                        <div class="w-3 h-3 rounded-full {{ $systemHealth['storage'] === 'healthy' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                    </div>
                </div>

                <!-- Queue Health -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-tasks text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Queue</p>
                                <p class="text-sm font-bold text-gray-800 capitalize">{{ $systemHealth['queue'] ?? 'Unknown' }}</p>
                            </div>
                        </div>
                        <div class="w-3 h-3 rounded-full {{ $systemHealth['queue'] === 'healthy' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Updates -->
        <div class="px-6 pb-6">
            <h2 class="text-xl font-semibold mb-4">Recent Updates</h2>
            
            <div class="space-y-4">
                @foreach($recentUpdates as $update)
                    <div class="bg-white border rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start">
                                <div class="p-2 bg-blue-100 rounded-lg mr-4">
                                    <i class="fas fa-{{ $update['type'] === 'system' ? 'cog' : ($update['type'] === 'feature' ? 'star' : 'wrench') }} text-blue-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $update['title'] }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">{{ $update['description'] }}</p>
                                    <p class="text-xs text-gray-500 mt-2">{{ $update['date']->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    {{ $update['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($update['status']) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>