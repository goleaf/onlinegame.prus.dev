<div class="village-manager">
    <!-- Village Header -->
    <div class="village-header bg-gradient-to-r from-green-900 to-blue-900 text-white p-6 rounded-lg mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold">{{ $village->name }}</h1>
                <p class="text-lg opacity-90">{{ $village->coordinates }}</p>
                <div class="flex items-center space-x-4 mt-2">
                    <span class="text-sm">👥 Population:
                        {{ number_format($population) }}/{{ number_format($maxPopulation) }}</span>
                    <span class="text-sm">⭐ Culture: {{ number_format($culturePoints) }}</span>
                    @if ($village->is_capital)
                        <span class="text-yellow-400 text-sm">👑 Capital</span>
                    @endif
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm opacity-90">Player: {{ $player->name }}</div>
                <div class="text-sm opacity-90">Tribe: {{ ucfirst($player->tribe) }}</div>
                <div class="text-sm opacity-90">World: {{ $village->world->name ?? 'Unknown' }}</div>
            </div>
        </div>
    </div>

    <!-- Resource Bar -->
    <div class="resource-bar bg-gray-800 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach (['wood', 'clay', 'iron', 'crop'] as $resource)
                @php
                    $resourceData = $resources[$resource] ?? null;
                    $amount = $resourceData ? $resourceData->amount : 0;
                    $capacity = $storageCapacities[$resource] ?? 10000;
                    $production = $resourceProductionRates[$resource] ?? 0;
                @endphp
                <div class="resource-item flex items-center space-x-3">
                    <span class="text-3xl">{{ $this->getResourceIcon($resource) }}</span>
                    <div class="flex-1">
                        <div class="text-lg font-semibold">{{ ucfirst($resource) }}</div>
                        <div class="text-sm text-gray-400">{{ number_format($amount) }} /
                            {{ number_format($capacity) }}</div>
                        <div class="w-full bg-gray-700 rounded-full h-3">
                            <div class="bg-green-500 h-3 rounded-full transition-all duration-300"
                                 style="width: {{ min(100, ($amount / $capacity) * 100) }}%"></div>
                        </div>
                        <div class="text-sm text-green-400">+{{ number_format($production) }}/h</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Building Grid -->
        <div class="lg:col-span-3">
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">🏗️ Village Layout</h2>
                    <div class="flex space-x-2">
                        <button wire:click="refreshVillageData"
                                class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-sm">
                            Refresh
                        </button>
                        <button wire:click="toggleAutoRefresh"
                                class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded text-sm">
                            {{ $autoRefresh ? 'Stop Auto' : 'Start Auto' }}
                        </button>
                    </div>
                </div>

                <!-- Building Grid -->
                <div class="building-grid grid grid-cols-19 gap-1 bg-gray-900 p-4 rounded-lg">
                    @for ($y = 0; $y < 19; $y++)
                        @for ($x = 0; $x < 19; $x++)
                            @php
                                $building = $buildingGrid[$y][$x] ?? null;
                            @endphp
                            <div class="building-cell w-8 h-8 border border-gray-600 rounded flex items-center justify-center text-xs cursor-pointer hover:bg-gray-700 transition-colors
                                {{ $building ? 'bg-gray-700' : 'bg-gray-800' }}"
                                 wire:click="selectBuildingPosition({{ $x }}, {{ $y }})"
                                 title="{{ $building ? $building['name'] . ' (Level ' . $building['level'] . ')' : 'Empty' }}">
                                @if ($building)
                                    <span class="text-lg">{{ $building['icon'] }}</span>
                                    @if ($building['is_upgrading'])
                                        <div class="absolute top-0 right-0 w-2 h-2 bg-yellow-400 rounded-full"></div>
                                    @endif
                                @else
                                    <span class="text-gray-500">+</span>
                                @endif
                            </div>
                        @endfor
                    @endfor
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="space-y-6">
            <!-- Building Queues -->
            @if ($buildingQueues->count() > 0)
                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-lg font-bold mb-4">🏗️ Building Queues</h3>
                    <div class="space-y-3">
                        @foreach ($buildingQueues as $queue)
                            <div class="bg-gray-700 rounded-lg p-3">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span
                                          class="text-xl">{{ $this->getBuildingIcon($queue->buildingType->key) }}</span>
                                    <span class="font-semibold">{{ $queue->buildingType->name }} → Level
                                        {{ $queue->target_level }}</span>
                                </div>
                                <div class="text-sm text-gray-400 mb-2">
                                    {{ $queue->completed_at->diffForHumans() }}
                                </div>
                                <div class="w-full bg-gray-600 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                         style="width: {{ $queue->progress_percentage ?? 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Training Queues -->
            @if ($trainingQueues->count() > 0)
                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-lg font-bold mb-4">⚔️ Training Queues</h3>
                    <div class="space-y-3">
                        @foreach ($trainingQueues as $queue)
                            <div class="bg-gray-700 rounded-lg p-3">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="text-xl">⚔️</span>
                                    <span class="font-semibold">{{ $queue->quantity }}
                                        {{ $queue->unitType->name }}</span>
                                </div>
                                <div class="text-sm text-gray-400 mb-2">
                                    {{ $queue->completed_at->diffForHumans() }}
                                </div>
                                <div class="w-full bg-gray-600 rounded-full h-2">
                                    <div class="bg-red-500 h-2 rounded-full transition-all duration-300"
                                         style="width: {{ $queue->progress_percentage ?? 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Available Buildings -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4">🏗️ Available Buildings</h3>
                <div class="space-y-2">
                    @foreach ($availableBuildings as $buildingType)
                        <button wire:click="selectBuildingType({{ $buildingType->id }})"
                                class="w-full bg-gray-700 hover:bg-gray-600 rounded-lg p-3 text-left transition-colors">
                            <div class="flex items-center space-x-2">
                                <span class="text-xl">{{ $this->getBuildingIcon($buildingType->key) }}</span>
                                <div>
                                    <div class="font-semibold">{{ $buildingType->name }}</div>
                                    <div class="text-xs text-gray-400">Max Level: {{ $buildingType->max_level }}</div>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Available Units -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4">⚔️ Available Units</h3>
                <div class="space-y-2">
                    @foreach ($availableUnits as $unitType)
                        <button wire:click="selectUnitType({{ $unitType->id }})"
                                class="w-full bg-gray-700 hover:bg-gray-600 rounded-lg p-3 text-left transition-colors">
                            <div class="flex items-center space-x-2">
                                <span class="text-xl">⚔️</span>
                                <div>
                                    <div class="font-semibold">{{ $unitType->name }}</div>
                                    <div class="text-xs text-gray-400">
                                        Attack: {{ $unitType->attack }} | Defense: {{ $unitType->defense_infantry }}
                                    </div>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Building Modal -->
    @if ($showBuildingModal && $selectedBuildingType)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold mb-4">Build {{ $selectedBuildingType->name }}</h3>

                <div class="mb-4">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="text-2xl">{{ $this->getBuildingIcon($selectedBuildingType->key) }}</span>
                        <span class="font-semibold">{{ $selectedBuildingType->name }}</span>
                    </div>
                    <p class="text-sm text-gray-400 mb-4">{{ $selectedBuildingType->description }}</p>

                    <div class="text-sm">
                        <div class="font-semibold mb-2">Costs:</div>
                        @php
                            $costs = json_decode($selectedBuildingType->costs, true);
                        @endphp
                        @foreach ($costs as $resource => $amount)
                            <div class="flex justify-between">
                                <span>{{ $this->getResourceIcon($resource) }} {{ ucfirst($resource) }}:</span>
                                <span>{{ number_format($amount) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button wire:click="showBuildingModal = false"
                            class="flex-1 bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded">
                        Cancel
                    </button>
                    <button wire:click="confirmBuilding"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">
                        Build
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Training Modal -->
    @if ($showTrainingModal && $selectedUnitType)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold mb-4">Train {{ $selectedUnitType->name }}</h3>

                <div class="mb-4">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="text-2xl">⚔️</span>
                        <span class="font-semibold">{{ $selectedUnitType->name }}</span>
                    </div>
                    <p class="text-sm text-gray-400 mb-4">{{ $selectedUnitType->description }}</p>

                    <div class="text-sm mb-4">
                        <div class="font-semibold mb-2">Stats:</div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>Attack: {{ $selectedUnitType->attack }}</div>
                            <div>Defense: {{ $selectedUnitType->defense_infantry }}</div>
                            <div>Speed: {{ $selectedUnitType->speed }}</div>
                            <div>Carry: {{ $selectedUnitType->carry_capacity }}</div>
                        </div>
                    </div>

                    <div class="text-sm">
                        <div class="font-semibold mb-2">Costs (per unit):</div>
                        @php
                            $costs = json_decode($selectedUnitType->costs, true);
                        @endphp
                        @foreach ($costs as $resource => $amount)
                            <div class="flex justify-between">
                                <span>{{ $this->getResourceIcon($resource) }} {{ ucfirst($resource) }}:</span>
                                <span>{{ number_format($amount) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Quantity:</label>
                    <input type="number" wire:model="trainingQuantity"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2"
                           min="1" max="100" value="1">
                </div>

                <div class="flex space-x-3">
                    <button wire:click="showTrainingModal = false"
                            class="flex-1 bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded">
                        Cancel
                    </button>
                    <button wire:click="confirmTraining"
                            class="flex-1 bg-red-600 hover:bg-red-700 px-4 py-2 rounded">
                        Train
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Loading Overlay -->
    @if ($isLoading)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg p-6 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                <p class="text-white">Loading village data...</p>
            </div>
        </div>
    @endif
    <style>
        .building-grid {
            grid-template-columns: repeat(19, 1fr);
        }

        .building-cell {
            position: relative;
        }

        .building-cell:hover {
            background-color: #4b5563 !important;
        }
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('initializeVillageRealTime', (event) => {
                if (event.autoRefresh && event.realTimeUpdates) {
                    setInterval(() => {
                        @this.call('processVillageTick');
                    }, event.interval);
                }
            });

            Livewire.on('villageTickProcessed', () => {
                console.log('Village tick processed successfully');
            });

            Livewire.on('villageTickError', (event) => {
                console.error('Village tick error:', event.message);
            });
        });
    </script>
</div>
