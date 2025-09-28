<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">User Management</h2>
            <div class="flex space-x-2">
                <button wire:click="refreshUsers" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Refresh
                </button>
                <button wire:click="exportUsers" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Export
                </button>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ $statistics['total_users'] ?? 0 }}</div>
                <div class="text-sm text-blue-800">Total Users</div>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ $statistics['users_with_players'] ?? 0 }}</div>
                <div class="text-sm text-green-800">Game Players</div>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600">{{ $statistics['active_game_users'] ?? 0 }}</div>
                <div class="text-sm text-yellow-800">Active Players</div>
            </div>
            
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">{{ $statistics['online_users'] ?? 0 }}</div>
                <div class="text-sm text-purple-800">Online Now</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Name, email, or player name" class="w-full border border-gray-300 rounded-md px-3 py-2">
            </div>

            <!-- World Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">World</label>
                <select wire:model.live="filterByWorld" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">All Worlds</option>
                    @foreach($worlds as $world)
                        <option value="{{ $world->id }}">{{ $world->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tribe Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tribe</label>
                <select wire:model.live="filterByTribe" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">All Tribes</option>
                    @foreach($tribes as $tribe)
                        <option value="{{ $tribe }}">{{ ucfirst($tribe) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Alliance Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Alliance</label>
                <select wire:model.live="filterByAlliance" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">All Alliances</option>
                    @foreach($alliances as $alliance)
                        <option value="{{ $alliance->id }}">{{ $alliance->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select wire:model.live="filterByStatus" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">All Status</option>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                </select>
            </div>

            <!-- Sort -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                <select wire:model.live="sortBy" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="created_at">Registration Date</option>
                    <option value="name">Name</option>
                    <option value="email">Email</option>
                </select>
            </div>
        </div>

        <!-- Toggle Filters -->
        <div class="flex space-x-4 mb-4">
            <label class="flex items-center">
                <input type="checkbox" wire:model.live="showOnlyOnline" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <span class="ml-2 text-sm text-gray-700">Online Only</span>
            </label>
            
            <label class="flex items-center">
                <input type="checkbox" wire:model.live="showOnlyActive" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <span class="ml-2 text-sm text-gray-700">Active Only</span>
            </label>

            <button wire:click="$set('sortOrder', '{{ $sortOrder === 'asc' ? 'desc' : 'asc' }}')" class="text-sm text-blue-600 hover:text-blue-800">
                {{ $sortOrder === 'asc' ? '↑ Ascending' : '↓ Descending' }}
            </button>
        </div>
    </div>

    <!-- Bulk Actions -->
    @if($showBulkActions)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-yellow-800">
                        {{ count($selectedUserIds) }} user(s) selected
                    </span>
                    
                    <select wire:model="bulkAction" class="border border-yellow-300 rounded-md px-3 py-2 text-sm">
                        <option value="">Select Action</option>
                        <option value="activate">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="set_online">Set Online</option>
                        <option value="set_offline">Set Offline</option>
                    </select>
                    
                    <button wire:click="executeBulkAction" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm">
                        Execute
                    </button>
                </div>
                
                <button wire:click="clearSelection" class="text-sm text-yellow-600 hover:text-yellow-800">
                    Clear Selection
                </button>
            </div>
        </div>
    @endif

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" wire:click="selectAllUsers" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Player Info</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Game Stats</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Activity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($paginatedUsers->items() as $user)
                        <tr class="hover:bg-gray-50 {{ in_array($user['id'], $selectedUserIds) ? 'bg-blue-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" wire:click="toggleUserSelection({{ $user['id'] }})" {{ in_array($user['id'], $selectedUserIds) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $user['name'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $user['email'] }}</div>
                                        <div class="text-xs text-gray-400">ID: {{ $user['id'] }}</div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user['game_stats'])
                                    <div class="text-sm text-gray-900">{{ $user['game_stats']['player_name'] }}</div>
                                    <div class="text-sm text-gray-500">{{ ucfirst($user['game_stats']['tribe']) }}</div>
                                    <div class="text-xs text-gray-400">World: {{ $user['game_stats']['world_id'] }}</div>
                                @else
                                    <span class="text-sm text-gray-400">No Player</span>
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user['game_stats'])
                                    <div class="text-sm text-gray-900">{{ number_format($user['game_stats']['points']) }} pts</div>
                                    <div class="text-sm text-gray-500">{{ $user['game_stats']['village_count'] }} villages</div>
                                    <div class="text-xs text-gray-400">{{ number_format($user['game_stats']['total_population']) }} pop</div>
                                @else
                                    <span class="text-sm text-gray-400">-</span>
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col space-y-1">
                                    @if($user['is_online'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Online
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Offline
                                        </span>
                                    @endif
                                    
                                    @if($user['game_stats'] && $user['game_stats']['is_active'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Active
                                        </span>
                                    @elseif($user['game_stats'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($user['last_activity'])
                                    {{ $user['last_activity']->diffForHumans() }}
                                @else
                                    Never
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button wire:click="selectUser({{ $user['id'] }})" class="text-blue-600 hover:text-blue-900 mr-3">
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                @if($isLoading)
                                    <div class="flex justify-center items-center">
                                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                                        <span class="ml-2">Loading users...</span>
                                    </div>
                                @else
                                    No users found matching your criteria.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($paginatedUsers->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $paginatedUsers->links() }}
            </div>
        @endif
    </div>

    <!-- Selected User Details -->
    @if($selectedUser)
        <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">User Details</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Basic Information</h4>
                    <div class="space-y-2 text-sm">
                        <div><strong>Name:</strong> {{ $selectedUser['name'] }}</div>
                        <div><strong>Email:</strong> {{ $selectedUser['email'] }}</div>
                        <div><strong>ID:</strong> {{ $selectedUser['id'] }}</div>
                        <div><strong>Registered:</strong> {{ \Carbon\Carbon::parse($selectedUser['created_at'])->format('M d, Y H:i') }}</div>
                    </div>
                </div>
                
                @if($selectedUser['game_stats'])
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Game Information</h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Player Name:</strong> {{ $selectedUser['game_stats']['player_name'] }}</div>
                            <div><strong>Tribe:</strong> {{ ucfirst($selectedUser['game_stats']['tribe']) }}</div>
                            <div><strong>Points:</strong> {{ number_format($selectedUser['game_stats']['points']) }}</div>
                            <div><strong>Villages:</strong> {{ $selectedUser['game_stats']['village_count'] }}</div>
                            <div><strong>Population:</strong> {{ number_format($selectedUser['game_stats']['total_population']) }}</div>
                            <div><strong>Status:</strong> 
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $selectedUser['is_online'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $selectedUser['is_online'] ? 'Online' : 'Offline' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>



