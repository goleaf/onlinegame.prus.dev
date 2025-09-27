<div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-semibold">Phone Number Statistics Dashboard</h3>
        <button 
            wire:click="refreshStatistics"
            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            <i class="fas fa-sync-alt mr-2"></i>Refresh
        </button>
    </div>

    @if($loading)
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
    @elseif(isset($stats['error']))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ $stats['error'] }}
        </div>
    @else
        <!-- Main Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-blue-50 p-6 rounded-lg">
                <div class="text-3xl font-bold text-blue-600">{{ number_format($stats['total_users']) }}</div>
                <div class="text-sm text-blue-800">Total Users</div>
            </div>
            
            <div class="bg-green-50 p-6 rounded-lg">
                <div class="text-3xl font-bold text-green-600">{{ number_format($stats['users_with_phone']) }}</div>
                <div class="text-sm text-green-800">Users with Phone</div>
            </div>
            
            <div class="bg-yellow-50 p-6 rounded-lg">
                <div class="text-3xl font-bold text-yellow-600">{{ $stats['phone_coverage_percentage'] }}%</div>
                <div class="text-sm text-yellow-800">Phone Coverage</div>
            </div>
            
            <div class="bg-purple-50 p-6 rounded-lg">
                <div class="text-3xl font-bold text-purple-600">{{ $stats['countries_with_phones'] }}</div>
                <div class="text-sm text-purple-800">Countries</div>
            </div>
        </div>

        <!-- Phone Format Statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h4 class="text-lg font-semibold mb-4">Phone Format Distribution</h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">E164 Format:</span>
                        <span class="font-semibold">{{ number_format($stats['phone_formats']['with_e164']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Normalized Format:</span>
                        <span class="font-semibold">{{ number_format($stats['phone_formats']['with_normalized']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">National Format:</span>
                        <span class="font-semibold">{{ number_format($stats['phone_formats']['with_national']) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h4 class="text-lg font-semibold mb-4">Recent Activity</h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Last 30 Days:</span>
                        <span class="font-semibold">{{ number_format($stats['recent_phone_registrations']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Validation Errors:</span>
                        <span class="font-semibold">{{ $stats['phone_validation_errors']['total_errors'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Countries -->
        @if($stats['top_countries']->count() > 0)
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
                <h4 class="text-lg font-semibold mb-4">Top Countries by Phone Usage</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($stats['top_countries'] as $country)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $country['country_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $country['country'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($country['count']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $stats['users_with_phone'] > 0 ? round(($country['count'] / $stats['users_with_phone']) * 100, 2) : 0 }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Coverage Progress Bar -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h4 class="text-lg font-semibold mb-4">Phone Coverage Progress</h4>
            <div class="w-full bg-gray-200 rounded-full h-4 mb-2">
                <div 
                    class="bg-blue-600 h-4 rounded-full transition-all duration-500" 
                    style="width: {{ $stats['phone_coverage_percentage'] }}%"
                ></div>
            </div>
            <div class="flex justify-between text-sm text-gray-600">
                <span>0%</span>
                <span class="font-semibold">{{ $stats['phone_coverage_percentage'] }}% Coverage</span>
                <span>100%</span>
            </div>
        </div>
    @endif
</div>