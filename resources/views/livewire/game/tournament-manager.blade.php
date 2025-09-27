<div class="tournament-manager">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Tournament Manager</h1>
            <p class="text-gray-600">Manage and participate in tournaments</p>
        </div>

        <!-- Tournament Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-blue-600">{{ $tournamentStats['total_tournaments'] }}</div>
                <div class="text-sm text-gray-600">Total Tournaments</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-green-600">{{ $tournamentStats['active_tournaments'] }}</div>
                <div class="text-sm text-gray-600">Active Tournaments</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-yellow-600">{{ $tournamentStats['upcoming_tournaments'] }}</div>
                <div class="text-sm text-gray-600">Upcoming Tournaments</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-purple-600">{{ $tournamentStats['completed_tournaments'] }}</div>
                <div class="text-sm text-gray-600">Completed Tournaments</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200">
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

            <!-- Filters -->
            <div class="p-6 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" wire:model.live="search" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Search tournaments...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select wire:model.live="filterType" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="pvp">PvP</option>
                            <option value="pve">PvE</option>
                            <option value="mixed">Mixed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                        <select wire:model.live="filterFormat" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Formats</option>
                            <option value="single_elimination">Single Elimination</option>
                            <option value="double_elimination">Double Elimination</option>
                            <option value="round_robin">Round Robin</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button wire:click="$refresh" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tournament List -->
            <div class="p-6">
                @if($tournaments->count() > 0)
                    <div class="space-y-4">
                        @foreach($tournaments as $tournament)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $tournament->name }}</h3>
                                        <p class="text-gray-600 mb-3">{{ $tournament->description }}</p>
                                        
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <span class="font-medium text-gray-700">Type:</span>
                                                <span class="text-gray-600">{{ ucfirst($tournament->type) }}</span>
                                            </div>
                                            <div>
                                                <span class="font-medium text-gray-700">Format:</span>
                                                <span class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $tournament->format)) }}</span>
                                            </div>
                                            <div>
                                                <span class="font-medium text-gray-700">Participants:</span>
                                                <span class="text-gray-600">{{ $tournament->participants->count() }}/{{ $tournament->max_participants }}</span>
                                            </div>
                                            <div>
                                                <span class="font-medium text-gray-700">Entry Fee:</span>
                                                <span class="text-gray-600">{{ $tournament->entry_fee }} Gold</span>
                                            </div>
                                        </div>

                                        <div class="mt-3 text-sm text-gray-600">
                                            <div>
                                                <span class="font-medium">Registration:</span>
                                                {{ $tournament->registration_start->format('M j, Y H:i') }} - {{ $tournament->registration_end->format('M j, Y H:i') }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Tournament:</span>
                                                {{ $tournament->start_date->format('M j, Y H:i') }} - {{ $tournament->end_date->format('M j, Y H:i') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col space-y-2 ml-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $tournament->status === 'upcoming' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($tournament->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ ucfirst($tournament->status) }}
                                        </span>

                                        @if($tournament->status === 'upcoming' && $tournament->registration_start <= now() && $tournament->registration_end >= now())
                                            <button wire:click="registerForTournament({{ $tournament->id }})" 
                                                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                Register
                                            </button>
                                        @endif

                                        <button wire:click="showTournamentDetails({{ $tournament->id }})" 
                                                class="px-4 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $tournaments->links() }}
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-gray-500 text-lg">No tournaments found</div>
                        <p class="text-gray-400 mt-2">Try adjusting your filters or check back later for new tournaments.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Notifications -->
        @if(count($notifications) > 0)
            <div class="fixed top-4 right-4 space-y-2 z-50">
                @foreach($notifications as $index => $notification)
                    <div class="bg-white border-l-4 {{ $notification['type'] === 'success' ? 'border-green-500' : ($notification['type'] === 'error' ? 'border-red-500' : 'border-blue-500') }} p-4 shadow-lg rounded-md max-w-sm">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $notification['message'] }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $notification['timestamp']->format('H:i:s') }}</p>
                            </div>
                            <button wire:click="removeNotification({{ $index }})" 
                                    class="ml-2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Tournament Details Modal -->
        @if($selectedTournament)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" 
                 wire:click="selectedTournament = null">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" 
                     wire:click.stop>
                    <div class="mt-3">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">{{ $selectedTournament->name }}</h3>
                            <button wire:click="selectedTournament = null" 
                                    class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">Description</h4>
                                <p class="text-gray-600">{{ $selectedTournament->description }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-2">Tournament Details</h4>
                                    <div class="space-y-1 text-sm">
                                        <div><span class="font-medium">Type:</span> {{ ucfirst($selectedTournament->type) }}</div>
                                        <div><span class="font-medium">Format:</span> {{ ucfirst(str_replace('_', ' ', $selectedTournament->format)) }}</div>
                                        <div><span class="font-medium">Max Participants:</span> {{ $selectedTournament->max_participants }}</div>
                                        <div><span class="font-medium">Entry Fee:</span> {{ $selectedTournament->entry_fee }} Gold</div>
                                        <div><span class="font-medium">Prize Pool:</span> {{ $selectedTournament->prize_pool }} Gold</div>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="font-medium text-gray-900 mb-2">Schedule</h4>
                                    <div class="space-y-1 text-sm">
                                        <div><span class="font-medium">Registration Start:</span> {{ $selectedTournament->registration_start->format('M j, Y H:i') }}</div>
                                        <div><span class="font-medium">Registration End:</span> {{ $selectedTournament->registration_end->format('M j, Y H:i') }}</div>
                                        <div><span class="font-medium">Tournament Start:</span> {{ $selectedTournament->start_date->format('M j, Y H:i') }}</div>
                                        <div><span class="font-medium">Tournament End:</span> {{ $selectedTournament->end_date->format('M j, Y H:i') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">Participants ({{ $selectedTournament->participants->count() }})</h4>
                                <div class="max-h-40 overflow-y-auto">
                                    @if($selectedTournament->participants->count() > 0)
                                        <div class="space-y-1">
                                            @foreach($selectedTournament->participants as $participant)
                                                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                                    <span class="text-sm">{{ $participant->player->name }}</span>
                                                    <span class="text-xs text-gray-500">{{ $participant->registered_at->format('M j, H:i') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500">No participants yet</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>