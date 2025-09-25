<div class="travian-game">
    <!-- Travian Game Header with Original Styling -->
    <div class="game-header">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="{{ asset('game/logo.png') }}" alt="Travian" class="h-12 w-auto"
                     onerror="this.style.display='none'">
                <div>
                    <h1 class="text-2xl font-bold text-white">Travian Game</h1>
                    <p class="text-blue-200">Welcome, {{ $player->name ?? 'Player' }}</p>
                </div>
            </div>
            <div class="flex space-x-4">
                <button wire:click="toggleAutoRefresh"
                        class="px-4 py-2 bg-blue-500 hover:bg-blue-700 rounded text-white font-semibold">
                    {{ $autoRefresh ? 'Auto Refresh: ON' : 'Auto Refresh: OFF' }}
                </button>
                <button wire:click="processGameTick"
                        class="px-4 py-2 bg-green-500 hover:bg-green-700 rounded text-white font-semibold">
                    Process Tick
                </button>
                <div class="flex items-center space-x-2 text-white">
                    <span class="text-sm">Refresh:</span>
                    <select wire:model.live="refreshInterval" class="bg-gray-800 text-white rounded px-2 py-1">
                        <option value="5">5s</option>
                        <option value="10">10s</option>
                        <option value="30">30s</option>
                        <option value="60">60s</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Game Stats with Travian Styling -->
    <div class="container mx-auto p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg shadow-lg border border-blue-200">
                <div class="flex items-center space-x-2">
                    <span class="text-2xl">🏘️</span>
                    <div>
                        <h3 class="font-semibold text-gray-700">Villages</h3>
                        <p class="text-2xl font-bold text-blue-600">{{ $gameStats['total_villages'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg shadow-lg border border-green-200">
                <div class="flex items-center space-x-2">
                    <span class="text-2xl">⭐</span>
                    <div>
                        <h3 class="font-semibold text-gray-700">Points</h3>
                        <p class="text-2xl font-bold text-green-600">
                            {{ number_format($gameStats['total_points'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div
                 class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg shadow-lg border border-purple-200">
                <div class="flex items-center space-x-2">
                    <span class="text-2xl">🤝</span>
                    <div>
                        <h3 class="font-semibold text-gray-700">Alliance</h3>
                        <p class="text-lg text-purple-600">{{ $gameStats['alliance_name'] ?? 'No Alliance' }}</p>
                    </div>
                </div>
            </div>
            <div
                 class="bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg shadow-lg border border-orange-200">
                <div class="flex items-center space-x-2">
                    <span class="text-2xl">{{ $gameStats['online_status'] === 'Online' ? '🟢' : '🔴' }}</span>
                    <div>
                        <h3 class="font-semibold text-gray-700">Status</h3>
                        <p
                           class="text-lg {{ $gameStats['online_status'] === 'Online' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $gameStats['online_status'] ?? 'Offline' }}
                        </p>
                    </div>
                </div>
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

            <!-- Current Village Resources with Real-time Updates -->
            @if ($currentVillage)
                <div
                     class="bg-gradient-to-br from-yellow-50 to-orange-100 p-4 rounded-lg shadow-lg border border-yellow-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-700 flex items-center space-x-2">
                            <span class="text-xl">💰</span>
                            <span>Resources - {{ $currentVillage->name }}</span>
                        </h3>
                        <div class="flex items-center space-x-2">
                            <button wire:click="toggleRealTimeUpdates"
                                    class="px-2 py-1 text-xs rounded {{ $realTimeUpdates ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $realTimeUpdates ? 'Real-time ON' : 'Real-time OFF' }}
                            </button>
                        </div>
                    </div>
                    <div class="space-y-4">
                        @foreach ($currentVillage->resources as $resource)
                            <div class="bg-white p-3 rounded-lg border border-gray-200">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xl">{{ $this->getResourceIcon($resource->type) }}</span>
                                        <span class="font-medium capitalize text-gray-700">{{ $resource->type }}</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-blue-600"
                                             wire:poll.5s="loadGameData">
                                            {{ number_format($resource->amount) }}
                                        </div>
                                        <div class="text-sm text-green-600 flex items-center">
                                            <span class="mr-1">+</span>
                                            <span>{{ $resource->production_rate }}/sec</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-500"
                                         style="width: {{ min(100, ($resource->amount / $resource->storage_capacity) * 100) }}%">
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>Capacity: {{ number_format($resource->storage_capacity) }}</span>
                                    <span>{{ number_format(($resource->amount / $resource->storage_capacity) * 100, 1) }}%</span>
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

        <!-- Current Village Buildings with Travian Styling -->
        @if ($currentVillage)
            <div
                 class="mt-6 bg-gradient-to-br from-gray-50 to-gray-100 p-6 rounded-lg shadow-lg border border-gray-200">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-gray-700 flex items-center space-x-2">
                        <span class="text-xl">🏗️</span>
                        <span>Buildings - {{ $currentVillage->name }}</span>
                    </h3>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Game Speed:</span>
                        <select wire:model.live="gameSpeed" class="bg-white border rounded px-2 py-1 text-sm">
                            <option value="0.5">0.5x</option>
                            <option value="1">1x</option>
                            <option value="2">2x</option>
                            <option value="3">3x</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach ($currentVillage->buildings as $building)
                        <div
                             class="bg-white p-4 rounded-lg border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-200">
                            <div class="text-center">
                                <div class="text-3xl mb-2">
                                    {{ $this->getBuildingIcon($building->buildingType->key ?? 'building') }}</div>
                                <div class="font-medium text-gray-700 text-sm mb-1">
                                    {{ $building->name ?? $building->buildingType->name }}</div>
                                <div class="text-xl font-bold text-blue-600 mb-1">Lv.{{ $building->level }}</div>
                                @if ($building->upgrade_started_at)
                                    <div class="text-xs text-orange-600 bg-orange-100 px-2 py-1 rounded">
                                        ⏳ Upgrading...
                                    </div>
                                @elseif ($building->level < ($building->buildingType->max_level ?? 20))
                                    <div class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded">
                                        ✅ Ready to upgrade
                                    </div>
                                @else
                                    <div class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                        🏆 Max Level
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Enhanced Real-time Features -->
@if ($autoRefresh)
    <script>
        // Advanced polling with exponential backoff
        let pollInterval = {{ $refreshInterval * 1000 }};
        let maxInterval = 60000; // 1 minute max
        let minInterval = 5000; // 5 seconds min

        function startAdvancedPolling() {
            if ({{ $autoRefresh ? 'true' : 'false' }}) {
                setTimeout(function() {
                    @this.call('refreshGameData');
                    startAdvancedPolling();
                }, pollInterval);
            }
        }

        startAdvancedPolling();
    </script>
@endif

<!-- Enhanced Game Features Script -->
<script>
    document.addEventListener('livewire:init', function() {
        // Initialize Travian Game Utils
        if (window.TravianGameUtils) {
            window.TravianGameUtils.initialize();
        }

        // Enhanced event listeners
        Livewire.on('gameTickProcessed', function() {
            console.log('✅ Game tick processed successfully');
            showNotification('Game tick processed', 'success');
        });

        Livewire.on('gameTickError', function(data) {
            console.error('❌ Game tick error:', data.message);
            showNotification('Game tick error: ' + data.message, 'error');
        });

        Livewire.on('buildingCompleted', function(data) {
            console.log('🏗️ Building completed:', data);
            showNotification('Building completed: ' + data.building_name, 'success');
        });

        Livewire.on('resourceUpdated', function(data) {
            console.log('💰 Resources updated:', data);
            // Animate resource counters
            animateResourceCounters();
        });

        Livewire.on('villageUpdated', function(data) {
            console.log('🏘️ Village updated:', data);
            showNotification('Village updated', 'info');
        });

        // Real-time resource counter animation
        function animateResourceCounters() {
            const counters = document.querySelectorAll('[wire\\:poll]');
            counters.forEach(counter => {
                counter.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    counter.style.transform = 'scale(1)';
                }, 200);
            });
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                    type === 'success' ? 'bg-green-500 text-white' :
                    type === 'error' ? 'bg-red-500 text-white' :
                    type === 'warning' ? 'bg-yellow-500 text-white' :
                    'bg-blue-500 text-white'
                }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Initialize real-time features
        Livewire.on('initializeRealTime', function(data) {
            console.log('🚀 Initializing real-time features:', data);

            if (data.realTimeUpdates) {
                // Start resource production animation
                setInterval(() => {
                    animateResourceProduction();
                }, 1000);
            }
        });

        // Animate resource production
        function animateResourceProduction() {
            const productionElements = document.querySelectorAll('.text-green-600');
            productionElements.forEach(element => {
                if (element.textContent.includes('+')) {
                    element.style.animation = 'pulse 2s infinite';
                }
            });
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.7; }
                }
                .resource-counter {
                    transition: all 0.3s ease;
                }
                .building-card:hover {
                    transform: translateY(-2px);
                }
            `;
        document.head.appendChild(style);
    });
</script>
</div>
