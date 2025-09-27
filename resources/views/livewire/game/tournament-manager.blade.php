<div>
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tournament Manager</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Manage and participate in game tournaments</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Tournaments</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $tournamentStats['total_tournaments'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Tournaments</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $tournamentStats['active_tournaments'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Upcoming Tournaments</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $tournamentStats['upcoming_tournaments'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Participants</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $tournamentStats['total_participants'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                    <input type="text" wire:model.live="search" placeholder="Search tournaments..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                    <select wire:model.live="filterType" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Types</option>
                        <option value="pvp">Player vs Player</option>
                        <option value="pve">Player vs Environment</option>
                        <option value="raid">Raid Tournament</option>
                        <option value="defense">Defense Challenge</option>
                        <option value="speed">Speed Competition</option>
                        <option value="endurance">Endurance Test</option>
                        <option value="resource_race">Resource Race</option>
                        <option value="building_contest">Building Contest</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Format</label>
                    <select wire:model.live="filterFormat" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Formats</option>
                        <option value="single_elimination">Single Elimination</option>
                        <option value="double_elimination">Double Elimination</option>
                        <option value="round_robin">Round Robin</option>
                        <option value="swiss">Swiss System</option>
                        <option value="bracket">Bracket System</option>
                        <option value="race">Race Format</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button wire:click="refreshTournaments" 
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8 px-6">
                    <button wire:click="switchTab('upcoming')" 
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'upcoming' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Upcoming
                    </button>
                    <button wire:click="switchTab('active')" 
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Active
                    </button>
                    <button wire:click="switchTab('completed')" 
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'completed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Completed
                    </button>
                    <button wire:click="switchTab('my_tournaments')" 
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'my_tournaments' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        My Tournaments
                    </button>
                </nav>
            </div>
        </div>

        <!-- Tournaments List -->
        <div class="space-y-4">
            @forelse($tournaments as $tournament)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $tournament->name }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $tournament->description }}</p>
                            
                            <div class="flex items-center space-x-4 mt-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $tournament->type_display_name }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ $tournament->format_display_name }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    {{ $tournament->status_display_name }}
                                </span>
                            </div>
                            
                            <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                                <p>Participants: {{ $tournament->participant_count }} / {{ $tournament->max_participants }}</p>
                                @if($tournament->time_remaining_formatted)
                                    <p>Time Remaining: {{ $tournament->time_remaining_formatted }}</p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button wire:click="showTournamentDetails({{ $tournament->id }})" 
                                    class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                Details
                            </button>
                            
                            @if($tournament->canRegister($player))
                                <button wire:click="registerForTournament({{ $tournament->id }})" 
                                        class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Register
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No tournaments found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No tournaments match your current filters.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $tournaments->links() }}
        </div>

        <!-- Notifications -->
        @if(count($notifications) > 0)
            <div class="fixed top-4 right-4 space-y-2 z-50">
                @foreach($notifications as $index => $notification)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 max-w-sm border-l-4 {{ $notification['type'] === 'success' ? 'border-green-500' : ($notification['type'] === 'error' ? 'border-red-500' : 'border-blue-500') }}">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $notification['message'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $notification['timestamp']->diffForHumans() }}</p>
                            </div>
                            <button wire:click="removeNotification({{ $index }})" class="ml-2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
