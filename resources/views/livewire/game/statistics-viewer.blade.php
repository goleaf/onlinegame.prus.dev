<div class="statistics-viewer bg-gray-900 text-white min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-900 to-purple-900 p-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Statistics Center</h1>
                    <p class="text-blue-200 mt-2">Comprehensive player statistics and rankings</p>
                </div>
                <div class="flex items-center space-x-4">
                    @if ($lastUpdate)
                        <span class="text-sm text-blue-200">Last updated: {{ $lastUpdate->format('H:i:s') }}</span>
                    @endif
                    <button wire:click="refreshStatistics"
                            class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition-colors">
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
                @foreach ($statCategories as $key => $label)
                    <button wire:click="setViewMode('{{ $key }}')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                                   {{ $viewMode === $key ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300' }}">
                        <i class="fas fa-{{ $this->getStatIcon($key) }} mr-2"></i>{{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>
    </div>

    <!-- Filters and Controls -->
    <div class="bg-gray-800 p-4 border-b border-gray-700">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Time Range Filter -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-300">Time Range:</label>
                    <select wire:model.live="timeRange"
                            class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-1 text-white">
                        @foreach ($timeRanges as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Statistics Type Filter -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-300">Type:</label>
                    <select wire:model.live="statType"
                            class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-1 text-white">
                        @foreach ($statTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Search -->
                <div class="flex items-center space-x-2">
                    <input type="text"
                           wire:model.live.debounce.300ms="searchQuery"
                           placeholder="Search players..."
                           class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-1 text-white placeholder-gray-400">
                    <button wire:click="searchStatistics"
                            class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded-lg transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <!-- Real-time Controls -->
                <div class="flex items-center space-x-4">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox"
                               wire:model.live="realTimeUpdates"
                               class="rounded border-gray-600 bg-gray-700 text-blue-600">
                        <span class="text-sm text-gray-300">Real-time</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox"
                               wire:model.live="autoRefresh"
                               class="rounded border-gray-600 bg-gray-700 text-blue-600">
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
    @if ($isLoading)
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <span class="ml-3 text-gray-400">Loading statistics...</span>
        </div>
    @endif

    <!-- Statistics Content -->
    <div class="max-w-7xl mx-auto p-6">
        @if ($viewMode === 'overview')
            <!-- Overview Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Player Rank -->
                <div class="bg-gradient-to-br from-yellow-600 to-orange-600 p-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-trophy text-3xl text-yellow-200"></i>
                        <div class="ml-4">
                            <p class="text-yellow-200 text-sm">Rank</p>
                            <p class="text-2xl font-bold text-white">{{ $playerStats['rank'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Points -->
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 p-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-star text-3xl text-blue-200"></i>
                        <div class="ml-4">
                            <p class="text-blue-200 text-sm">Points</p>
                            <p class="text-2xl font-bold text-white">
                                {{ $this->formatNumber($playerStats['points'] ?? 0) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Villages -->
                <div class="bg-gradient-to-br from-green-600 to-green-800 p-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-home text-3xl text-green-200"></i>
                        <div class="ml-4">
                            <p class="text-green-200 text-sm">Villages</p>
                            <p class="text-2xl font-bold text-white">{{ $playerStats['villages'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Population -->
                <div class="bg-gradient-to-br from-purple-600 to-purple-800 p-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-users text-3xl text-purple-200"></i>
                        <div class="ml-4">
                            <p class="text-purple-200 text-sm">Population</p>
                            <p class="text-2xl font-bold text-white">
                                {{ $this->formatNumber($playerStats['population'] ?? 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Battle Statistics -->
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-sword mr-2"></i>Battle Statistics
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-green-400 text-2xl font-bold">{{ $battleStats['attacks_won'] ?? 0 }}</p>
                        <p class="text-gray-400 text-sm">Attacks Won</p>
                    </div>
                    <div class="text-center">
                        <p class="text-red-400 text-2xl font-bold">{{ $battleStats['attacks_lost'] ?? 0 }}</p>
                        <p class="text-gray-400 text-sm">Attacks Lost</p>
                    </div>
                    <div class="text-center">
                        <p class="text-green-400 text-2xl font-bold">{{ $battleStats['defenses_won'] ?? 0 }}</p>
                        <p class="text-gray-400 text-sm">Defenses Won</p>
                    </div>
                    <div class="text-center">
                        <p class="text-red-400 text-2xl font-bold">{{ $battleStats['defenses_lost'] ?? 0 }}</p>
                        <p class="text-gray-400 text-sm">Defenses Lost</p>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-blue-400 text-lg font-bold">Win Rate: {{ $battleStats['win_rate'] ?? 0 }}%</p>
                </div>
            </div>

            <!-- Resource Statistics -->
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-coins mr-2"></i>Resource Statistics
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-yellow-400 text-xl font-bold">
                            {{ $this->formatNumber($resourceStats['total_wood'] ?? 0) }}</p>
                        <p class="text-gray-400 text-sm">Wood</p>
                    </div>
                    <div class="text-center">
                        <p class="text-orange-400 text-xl font-bold">
                            {{ $this->formatNumber($resourceStats['total_clay'] ?? 0) }}</p>
                        <p class="text-gray-400 text-sm">Clay</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-400 text-xl font-bold">
                            {{ $this->formatNumber($resourceStats['total_iron'] ?? 0) }}</p>
                        <p class="text-gray-400 text-sm">Iron</p>
                    </div>
                    <div class="text-center">
                        <p class="text-green-400 text-xl font-bold">
                            {{ $this->formatNumber($resourceStats['total_crop'] ?? 0) }}</p>
                        <p class="text-gray-400 text-sm">Crop</p>
                    </div>
                </div>
            </div>
        @elseif($viewMode === 'rankings')
            <!-- Rankings View -->
            <div class="bg-gray-800 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-xl font-bold text-white">Player Rankings</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Rank</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Player</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Points</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Villages</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Alliance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @forelse($rankingStats as $index => $player)
                                <tr class="hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                        #{{ $index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ $player->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ $this->formatNumber($player->points ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ $player->villages->count() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ $player->alliance?->name ?? 'No Alliance' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-400">No players found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($viewMode === 'battles')
            <!-- Battle Statistics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-xl font-bold text-white mb-4">Recent Battles</h3>
                    <div class="space-y-3">
                        @forelse($battleStats['recent_battles'] ?? [] as $battle)
                            <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                                <div>
                                    <p class="text-white font-medium">{{ $battle['title'] ?? 'Battle' }}</p>
                                    <p class="text-gray-400 text-sm">{{ $battle['date'] ?? 'Unknown' }}</p>
                                </div>
                                <span
                                      class="px-2 py-1 rounded text-xs font-medium
                                    {{ $battle['status'] === 'victory' ? 'bg-green-600 text-green-100' : 'bg-red-600 text-red-100' }}">
                                    {{ ucfirst($battle['status'] ?? 'Unknown') }}
                                </span>
                            </div>
                        @empty
                            <p class="text-gray-400 text-center py-4">No recent battles</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-xl font-bold text-white mb-4">Battle Summary</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-300">Total Battles:</span>
                            <span class="text-white font-bold">{{ $battleStats['total_battles'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-300">Win Rate:</span>
                            <span class="text-green-400 font-bold">{{ $battleStats['win_rate'] ?? 0 }}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-300">Attacks Won:</span>
                            <span class="text-green-400 font-bold">{{ $battleStats['attacks_won'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-300">Defenses Won:</span>
                            <span class="text-green-400 font-bold">{{ $battleStats['defenses_won'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($viewMode === 'resources')
            <!-- Resource Statistics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-xl font-bold text-white mb-4">Current Resources</h3>
                    <div class="space-y-4">
                        @foreach ($resourceStats['current_resources'] ?? [] as $resource => $amount)
                            <div class="flex justify-between items-center">
                                <span class="text-gray-300 capitalize">{{ $resource }}:</span>
                                <span class="text-white font-bold">{{ $this->formatNumber($amount) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-xl font-bold text-white mb-4">Production Rates</h3>
                    <div class="space-y-4">
                        @foreach ($resourceStats['production_rates'] ?? [] as $resource => $rate)
                            <div class="flex justify-between items-center">
                                <span class="text-gray-300 capitalize">{{ $resource }}:</span>
                                <span class="text-green-400 font-bold">{{ $this->formatNumber($rate) }}/hour</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @elseif($viewMode === 'buildings')
            <!-- Building Statistics -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-xl font-bold text-white mb-4">Building Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-blue-400">{{ $buildingStats['total_buildings'] ?? 0 }}</p>
                        <p class="text-gray-400">Total Buildings</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-green-400">{{ $buildingStats['upgrade_progress'] ?? 0 }}</p>
                        <p class="text-gray-400">Upgrading</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-purple-400">
                            {{ $buildingStats['building_efficiency'] ?? 0 }}%</p>
                        <p class="text-gray-400">Efficiency</p>
                    </div>
                </div>
            </div>
        @elseif($viewMode === 'troops')
            <!-- Troop Statistics -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-xl font-bold text-white mb-4">Troop Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-orange-400">
                            {{ $this->formatNumber($troopStats['total_troops'] ?? 0) }}</p>
                        <p class="text-gray-400">Total Troops</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-red-400">
                            {{ $this->formatNumber($troopStats['army_strength'] ?? 0) }}</p>
                        <p class="text-gray-400">Army Strength</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-yellow-400">{{ $troopStats['troop_efficiency'] ?? 0 }}%</p>
                        <p class="text-gray-400">Efficiency</p>
                    </div>
                </div>
            </div>
        @elseif($viewMode === 'achievements')
            <!-- Achievement Statistics -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-xl font-bold text-white mb-4">Achievement Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-yellow-400">
                            {{ $achievementStats['unlocked_achievements'] ?? 0 }}</p>
                        <p class="text-gray-400">Unlocked</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-blue-400">
                            {{ $achievementStats['available_achievements'] ?? 0 }}</p>
                        <p class="text-gray-400">Available</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-purple-400">
                            {{ $achievementStats['achievement_points'] ?? 0 }}</p>
                        <p class="text-gray-400">Points</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Notifications -->
    @if (count($notifications) > 0)
        <div class="fixed top-4 right-4 space-y-2 z-50">
            @foreach ($notifications as $notification)
                <div class="bg-gray-800 border border-gray-600 rounded-lg p-4 shadow-lg max-w-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div
                                 class="w-2 h-2 rounded-full mr-3
                                {{ $notification['type'] === 'success'
                                    ? 'bg-green-500'
                                    : ($notification['type'] === 'error'
                                        ? 'bg-red-500'
                                        : 'bg-blue-500') }}">
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
    @if ($autoRefresh && $realTimeUpdates)
        <script>
            setInterval(function() {
                @this.call('refreshStatistics');
            }, {{ $refreshInterval * 1000 }});
        </script>
    @endif
</div>
