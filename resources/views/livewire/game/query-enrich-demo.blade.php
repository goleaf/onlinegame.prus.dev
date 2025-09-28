<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Query Enrich Integration Demo
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Demonstrating enhanced database queries using Laravel Query Enrich
                </p>
            </div>
            <button wire:click="refreshData" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                Refresh Data
            </button>
        </div>
    </div>

    <!-- Controls -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Query Parameters</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    World ID
                </label>
                <input type="number" wire:model.live="worldId" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                       placeholder="World ID (optional)">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Player ID
                </label>
                <input type="number" wire:model.live="playerId" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                       placeholder="Player ID (optional)">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Active Days
                </label>
                <input type="number" wire:model.live="days" min="1" max="30"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Hours Ahead
                </label>
                <input type="number" wire:model.live="hours" min="1" max="168"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
    </div>

    <!-- Active Players -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Active Players (Last {{ $days }} days)
        </h3>
        @if(!empty($activePlayers))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Player
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Last Activity
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                World
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($activePlayers as $player)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $player['name'] ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $player['last_activity'] ? \Carbon\Carbon::parse($player['last_activity'])->diffForHumans() : 'Never' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $player['world_id'] ?? 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">No active players found.</p>
        @endif
    </div>

    <!-- Player Statistics -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Player Statistics
        </h3>
        @if(!empty($playerStats))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Player
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Villages
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Population
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Battles
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Victories
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($playerStats as $player)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $player['name'] ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $player['village_count'] ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ number_format($player['total_population'] ?? 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $player['total_battles'] ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $player['total_victories'] ?? 0 }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">No player statistics available.</p>
        @endif
    </div>

    <!-- Battle Statistics -->
    @if($battleStats)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Battle Statistics
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $battleStats['total_battles'] ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Battles</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ $battleStats['victories'] ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Victories</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ $battleStats['defeats'] ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Defeats</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ $battleStats['draws'] ?? 0 }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Draws</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Upcoming Completions -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Upcoming Completions (Next {{ $hours }} hours)
        </h3>
        @if(!empty($upcomingCompletions))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Village
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Building
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Level
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Completes At
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($upcomingCompletions as $building)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $building['village']['name'] ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $building['buildingType']['name'] ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $building['level'] ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $building['construction_completed_at'] ? \Carbon\Carbon::parse($building['construction_completed_at'])->diffForHumans() : 'Unknown' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">No upcoming completions found.</p>
        @endif
    </div>

    <!-- Resources Reaching Capacity -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Resources Reaching Capacity (Next {{ $hours }} hours)
        </h3>
        @if(!empty($resourcesReachingCapacity))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Village
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Wood
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Clay
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Iron
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Crop
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($resourcesReachingCapacity as $resource)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $resource['village']['name'] ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ number_format($resource['projected_wood'] ?? 0) }} / {{ number_format($resource['wood_capacity'] ?? 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ number_format($resource['projected_clay'] ?? 0) }} / {{ number_format($resource['clay_capacity'] ?? 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ number_format($resource['projected_iron'] ?? 0) }} / {{ number_format($resource['iron_capacity'] ?? 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ number_format($resource['projected_crop'] ?? 0) }} / {{ number_format($resource['crop_capacity'] ?? 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">No resources reaching capacity soon.</p>
        @endif
    </div>

    <!-- Code Examples -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Query Enrich Code Examples
        </h3>
        <div class="space-y-4">
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white mb-2">Active Players Query:</h4>
                <pre class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg text-sm overflow-x-auto"><code>// Using Query Enrich
$activePlayers = DB::table('players')
    ->where(c('last_activity'), '>=', QE::subDate(QE::now(), 7, QE::Unit::DAY))
    ->get();

// Instead of raw SQL
$activePlayers = DB::table('players')
    ->whereRaw('last_activity >= NOW() - INTERVAL ? DAY', [7])
    ->get();</code></pre>
            </div>
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white mb-2">Battle Statistics Query:</h4>
                <pre class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg text-sm overflow-x-auto"><code>// Using Query Enrich
$stats = DB::table('reports')
    ->select([
        QE::count(c('id'))->as('total_battles'),
        QE::count(QE::case()->when(QE::eq(c('status'), 'victory'), c('id')))->as('victories'),
        QE::sum(c('attacker_losses'))->as('total_attacker_losses')
    ])
    ->get();

// Instead of raw SQL
$stats = DB::table('reports')
    ->selectRaw('COUNT(*) as total_battles, SUM(CASE WHEN status = "victory" THEN 1 ELSE 0 END) as victories, SUM(attacker_losses) as total_attacker_losses')
    ->get();</code></pre>
            </div>
        </div>
    </div>
</div>



