<div class="game-dashboard">
    <!-- Game Header -->
    <div class="game-header bg-gradient-to-r from-blue-900 to-purple-900 text-white p-4 rounded-lg mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">{{ $player->name }}</h1>
                <p class="text-sm opacity-90">{{ $worldInfo->name ?? 'Unknown World' }}</p>
                <p class="text-xs opacity-75">{{ $player->tribe }} • {{ $gameStats['online_status'] }}</p>
            </div>
            <div class="text-right">
                <div class="text-sm opacity-90">World Rank: #{{ $gameStats['world_rank'] }}</div>
                <div class="text-sm opacity-90">Points: {{ number_format($gameStats['total_points']) }}</div>
                @if ($allianceInfo)
                    <div class="text-sm opacity-90">{{ $allianceInfo['tag'] }} - {{ $allianceInfo['name'] }}</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Resource Bar -->
    <div class="resource-bar bg-gray-800 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach (['wood', 'clay', 'iron', 'crop'] as $resource)
                @php
                    $resourceData = $currentVillage
                        ? $currentVillage->resources->where('type', $resource)->first()
                        : null;
                    $amount = $resourceData ? $resourceData->amount : 0;
                    $capacity = $resourceData ? $resourceData->storage_capacity : 10000;
                    $production = $resourceProductionRates[$resource] ?? 0;
                @endphp
                <div class="resource-item flex items-center space-x-2">
                    <span class="text-2xl">{{ $this->getResourceIcon($resource) }}</span>
                    <div class="flex-1">
                        <div class="text-sm font-medium">{{ ucfirst($resource) }}</div>
                        <div class="text-xs text-gray-400">{{ number_format($amount) }} /
                            {{ number_format($capacity) }}</div>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full"
                                 style="width: {{ min(100, ($amount / $capacity) * 100) }}%"></div>
                        </div>
                        <div class="text-xs text-green-400">+{{ number_format($production) }}/h</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Villages & Buildings -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Villages Section -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    🏘️ Villages ({{ $gameStats['total_villages'] }})
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($villages as $village)
                        <div class="village-card bg-gray-700 rounded-lg p-4 cursor-pointer hover:bg-gray-600 transition-colors
                            {{ $currentVillage && $currentVillage->id === $village->id ? 'ring-2 ring-blue-500' : '' }}"
                             wire:click="selectVillage({{ $village->id }})">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold">{{ $village->name }}</h3>
                                @if ($village->is_capital)
                                    <span class="text-yellow-400 text-sm">👑 Capital</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-400 mb-2">
                                {{ $village->coordinates }} • Pop: {{ number_format($village->population) }}
                            </div>
                            <div class="flex space-x-4 text-xs">
                                <span>🌲 {{ number_format($village->wood) }}</span>
                                <span>🏺 {{ number_format($village->clay) }}</span>
                                <span>⚒️ {{ number_format($village->iron) }}</span>
                                <span>🌾 {{ number_format($village->crop) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Building Queues -->
            @if (count($buildingQueues) > 0)
                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        🏗️ Building Queues
                    </h2>
                    <div class="space-y-3">
                        @foreach ($buildingQueues as $queue)
                            <div class="bg-gray-700 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span
                                              class="text-2xl">{{ $this->getBuildingIcon($queue->buildingType->key) }}</span>
                                        <span class="font-semibold">{{ $queue->buildingType->name }} → Level
                                            {{ $queue->target_level }}</span>
                                    </div>
                                    <span class="text-sm text-gray-400">
                                        {{ $queue->completed_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-600 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full"
                                         style="width: {{ $queue->progress_percentage ?? 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Training Queues -->
            @if (count($trainingQueues) > 0)
                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        ⚔️ Training Queues
                    </h2>
                    <div class="space-y-3">
                        @foreach ($trainingQueues as $queue)
                            <div class="bg-gray-700 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-2xl">⚔️</span>
                                        <span class="font-semibold">{{ $queue->quantity }}
                                            {{ $queue->unitType->name }}</span>
                                    </div>
                                    <span class="text-sm text-gray-400">
                                        {{ $queue->completed_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-600 rounded-full h-2">
                                    <div class="bg-red-500 h-2 rounded-full"
                                         style="width: {{ $queue->progress_percentage ?? 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column - Stats, Quests, Events -->
        <div class="space-y-6">
            <!-- Game Stats -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    📊 Game Statistics
                </h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Population:</span>
                        <span class="font-semibold">{{ number_format($gameStats['total_population']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Villages:</span>
                        <span class="font-semibold">{{ $gameStats['total_villages'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Points:</span>
                        <span class="font-semibold">{{ number_format($gameStats['total_points']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">World Rank:</span>
                        <span class="font-semibold">#{{ $gameStats['world_rank'] }}</span>
                    </div>
                    @if ($allianceInfo)
                        <div class="flex justify-between">
                            <span class="text-gray-400">Alliance:</span>
                            <span class="font-semibold">{{ $allianceInfo['tag'] }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Active Quests -->
            @if ($activeQuests->count() > 0)
                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        🎯 Active Quests
                    </h2>
                    <div class="space-y-3">
                        @foreach ($activeQuests as $playerQuest)
                            <div class="bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span
                                          class="text-xl">{{ $this->getQuestIcon($playerQuest->quest->category) }}</span>
                                    <span class="font-semibold">{{ $playerQuest->quest->name }}</span>
                                </div>
                                <div class="text-sm text-gray-400 mb-2">{{ $playerQuest->quest->description }}</div>
                                <div class="w-full bg-gray-600 rounded-full h-2">
                                    <div class="bg-yellow-500 h-2 rounded-full"
                                         style="width: {{ $playerQuest->progress }}%"></div>
                                </div>
                                <div class="text-xs text-gray-400 mt-1">{{ $playerQuest->progress }}% Complete</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Recent Events -->
            @if ($recentEvents->count() > 0)
                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        📰 Recent Events
                    </h2>
                    <div class="space-y-3">
                        @foreach ($recentEvents as $event)
                            <div class="bg-gray-700 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-semibold">{{ $event->title }}</span>
                                    <span
                                          class="text-xs text-gray-400">{{ $event->occurred_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-sm text-gray-400">{{ $event->description }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Notifications -->
            @if (count($notifications) > 0)
                <div class="bg-gray-800 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold flex items-center">
                            🔔 Notifications ({{ count($notifications) }})
                        </h2>
                        <button wire:click="markAllNotificationsAsRead"
                                class="text-xs bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded">
                            Mark All Read
                        </button>
                    </div>
                    <div class="space-y-3">
                        @foreach ($notifications as $notification)
                            <div class="bg-gray-700 rounded-lg p-4 cursor-pointer hover:bg-gray-600 transition-colors"
                                 wire:click="markNotificationAsRead({{ $notification['id'] }})">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-semibold">{{ $notification['message'] }}</span>
                                    <span
                                          class="text-xs text-gray-400">{{ $notification['timestamp']->diffForHumans() }}</span>
                                </div>
                                <div class="text-sm text-gray-400">{{ $notification['message'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Real-time Controls -->
    <div class="fixed bottom-4 right-4 bg-gray-800 rounded-lg p-4 shadow-lg">
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <input type="checkbox" wire:model.live="autoRefresh" class="rounded">
                <span class="text-sm">Auto Refresh</span>
            </div>
            <div class="flex items-center space-x-2">
                <label class="text-sm">Interval:</label>
                <select wire:model.live="refreshInterval" class="bg-gray-700 text-white text-sm rounded px-2 py-1">
                    <option value="1">1s</option>
                    <option value="5">5s</option>
                    <option value="10">10s</option>
                    <option value="30">30s</option>
                </select>
            </div>
            <button wire:click="refreshGameData"
                    class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-sm">
                Refresh Now
            </button>
        </div>
    </div>

    <!-- Loading Overlay -->
    @if ($isLoading)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg p-6 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                <p class="text-white">Loading game data...</p>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('initializeRealTime', (event) => {
            if (event.autoRefresh && event.realTimeUpdates) {
                setInterval(() => {
                    @this.call('processGameTick');
                }, event.interval);
            }
        });

        Livewire.on('gameTickProcessed', () => {
            // Show success notification
            console.log('Game tick processed successfully');
        });

        Livewire.on('gameTickError', (event) => {
            // Show error notification
            console.error('Game tick error:', event.message);
        });
    });
</script>
