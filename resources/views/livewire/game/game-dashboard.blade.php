<div>
    <div class="min-h-screen bg-gray-100">
        <!-- Game Header -->
        <div class="bg-blue-600 text-white p-4">
            <div class="container mx-auto flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Travian Game</h1>
                    <p class="text-blue-200">Welcome, {{ $player->name ?? 'Player' }}</p>
                </div>
                <div class="flex space-x-4">
                    <button wire:click="toggleAutoRefresh"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-700 rounded">
                        {{ $autoRefresh ? 'Auto Refresh: ON' : 'Auto Refresh: OFF' }}
                    </button>
                    <button wire:click="processGameTick"
                            class="px-4 py-2 bg-green-500 hover:bg-green-700 rounded">
                        Process Tick
                    </button>
                </div>
            </div>
        </div>

        <!-- Game Stats -->
        <div class="container mx-auto p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="font-semibold text-gray-700">Villages</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $gameStats['total_villages'] ?? 0 }}</p>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="font-semibold text-gray-700">Points</h3>
                    <p class="text-2xl font-bold text-green-600">{{ $gameStats['total_points'] ?? 0 }}</p>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="font-semibold text-gray-700">Alliance</h3>
                    <p class="text-lg text-purple-600">{{ $gameStats['alliance_name'] ?? 'No Alliance' }}</p>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="font-semibold text-gray-700">Status</h3>
                    <p
                       class="text-lg {{ $gameStats['online_status'] === 'Online' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $gameStats['online_status'] ?? 'Offline' }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Village Selection -->
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="font-semibold text-gray-700 mb-4">Villages</h3>
                    <div class="space-y-2">
                        @foreach ($villages as $village)
                            <button wire:click="selectVillage({{ $village->id }})"
                                    class="w-full text-left p-2 rounded {{ $currentVillage && $currentVillage->id === $village->id ? 'bg-blue-100 border-l-4 border-blue-500' : 'hover:bg-gray-100' }}">
                                <div class="font-medium">{{ $village->name }}</div>
                                <div class="text-sm text-gray-600">
                                    ({{ $village->x }}, {{ $village->y }})
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Current Village Resources -->
                @if ($currentVillage)
                    <div class="bg-white p-4 rounded shadow">
                        <h3 class="font-semibold text-gray-700 mb-4">Resources - {{ $currentVillage->name }}</h3>
                        <div class="space-y-3">
                            @foreach ($currentVillage->resources as $resource)
                                <div class="flex justify-between items-center">
                                    <span class="font-medium capitalize">{{ $resource->type }}</span>
                                    <div class="text-right">
                                        <div class="text-lg font-bold">{{ number_format($resource->amount) }}</div>
                                        <div class="text-sm text-gray-600">
                                            +{{ $resource->production_rate }}/sec
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full"
                                         style="width: {{ min(100, ($resource->amount / $resource->storage_capacity) * 100) }}%">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Recent Events -->
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="font-semibold text-gray-700 mb-4">Recent Events</h3>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @forelse($recentEvents as $event)
                            <div class="p-2 bg-gray-50 rounded text-sm">
                                <div class="font-medium">{{ $event->description }}</div>
                                <div class="text-gray-600">{{ $event->occurred_at->diffForHumans() }}</div>
                            </div>
                        @empty
                            <p class="text-gray-500">No recent events</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Current Village Buildings -->
            @if ($currentVillage)
                <div class="mt-6 bg-white p-4 rounded shadow">
                    <h3 class="font-semibold text-gray-700 mb-4">Buildings - {{ $currentVillage->name }}</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach ($currentVillage->buildings as $building)
                            <div class="text-center p-3 border rounded">
                                <div class="font-medium">{{ $building->name }}</div>
                                <div class="text-2xl font-bold text-blue-600">Lv.{{ $building->level }}</div>
                                @if ($building->upgrade_started_at)
                                    <div class="text-sm text-orange-600">Upgrading...</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Auto-refresh script -->
    @if ($autoRefresh)
        <script>
            setInterval(function() {
                @this.call('refreshGameData');
            }, {{ $refreshInterval * 1000 }});
        </script>
    @endif

    <!-- Game tick processing script -->
    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.on('gameTickProcessed', function() {
                // Show success message
                console.log('Game tick processed successfully');
            });

            Livewire.on('gameTickError', function(data) {
                // Show error message
                console.error('Game tick error:', data.message);
            });
        });
    </script>
</div>
