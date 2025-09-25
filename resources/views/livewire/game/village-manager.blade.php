<div class="travian-village-manager">
    <!-- Enhanced Village Header with Real-time Controls -->
    <div class="village-header bg-gradient-to-r from-blue-600 to-blue-800 text-white p-4 rounded-lg shadow-lg mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold flex items-center space-x-2">
                    <span class="text-3xl">🏘️</span>
                    <span>{{ $village->name ?? 'Main Village' }}</span>
                </h2>
                <div class="flex space-x-6 mt-2 text-blue-100">
                    <div class="flex items-center space-x-1">
                        <span class="text-lg">📍</span>
                        <span>Coordinates: {{ $village->x_coordinate ?? 0 }}|{{ $village->y_coordinate ?? 0 }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <span class="text-lg">👥</span>
                        <span>Population: {{ number_format($village->population ?? 100) }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <span class="text-lg">⭐</span>
                        <span>Culture: {{ number_format($village->culture_points ?? 1000) }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <button wire:click="toggleRealTimeUpdates"
                            class="px-3 py-1 text-xs rounded {{ $realTimeUpdates ? 'bg-green-500' : 'bg-gray-500' }} text-white">
                        {{ $realTimeUpdates ? 'Real-time ON' : 'Real-time OFF' }}
                    </button>
                    <button wire:click="toggleAutoRefresh"
                            class="px-3 py-1 text-xs rounded {{ $autoRefresh ? 'bg-green-500' : 'bg-gray-500' }} text-white">
                        {{ $autoRefresh ? 'Auto-refresh ON' : 'Auto-refresh OFF' }}
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm">Speed:</span>
                    <select wire:model.live="gameSpeed" class="bg-gray-800 text-white rounded px-2 py-1 text-sm">
                        <option value="0.5">0.5x</option>
                        <option value="1">1x</option>
                        <option value="2">2x</option>
                        <option value="3">3x</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Notifications -->
    @if (count($notifications) > 0)
        <div class="notifications mb-4">
            @foreach ($notifications as $notification)
                <div
                     class="bg-{{ $notification['type'] === 'error' ? 'red' : ($notification['type'] === 'success' ? 'green' : 'blue') }}-100 border-l-4 border-{{ $notification['type'] === 'error' ? 'red' : ($notification['type'] === 'success' ? 'green' : 'blue') }}-500 text-{{ $notification['type'] === 'error' ? 'red' : ($notification['type'] === 'success' ? 'green' : 'blue') }}-700 p-3 mb-2 rounded">
                    <div class="flex justify-between items-center">
                        <span>{{ $notification['message'] }}</span>
                        <button wire:click="removeNotification('{{ $notification['id'] }}')"
                                class="text-gray-500 hover:text-gray-700">
                            ✕
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Real-time Resources Display -->
    <div class="bg-gradient-to-br from-yellow-50 to-orange-100 p-4 rounded-lg shadow-lg mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center space-x-2">
            <span class="text-xl">💰</span>
            <span>Resources - Real-time Updates</span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ($resources as $resource)
                <div class="bg-white p-3 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-xl">{{ $this->getResourceIcon($resource->type) }}</span>
                            <span class="font-medium capitalize text-gray-700">{{ $resource->type }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-blue-600"
                                 wire:poll.5s="loadVillageData">
                                {{ number_format($resource->amount) }}
                            </div>
                            <div class="text-sm text-green-600 flex items-center">
                                <span class="mr-1">+</span>
                                <span>{{ $resource->production_rate }}/sec</span>
                            </div>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-500"
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

    <!-- Enhanced Building Grid with Real-time Progress -->
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-6 rounded-lg shadow-lg mb-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-700 flex items-center space-x-2">
                <span class="text-xl">🏗️</span>
                <span>Village Buildings</span>
            </h3>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">Refresh:</span>
                <select wire:model.live="refreshInterval" class="bg-white border rounded px-2 py-1 text-sm">
                    <option value="5">5s</option>
                    <option value="10">10s</option>
                    <option value="30">30s</option>
                    <option value="60">60s</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-6 md:grid-cols-8 lg:grid-cols-12 gap-4">
            @for ($i = 0; $i < 36; $i++)
                <div class="building-slot bg-white p-3 rounded-lg border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-200 {{ $i < count($buildings) ? 'occupied' : 'empty' }}"
                     data-slot="{{ $i }}"
                     @if ($i < count($buildings)) @php $building = $buildings[$i]; @endphp
                         wire:click="selectBuilding({{ $building->id }})" @endif>
                    @if ($i < count($buildings))
                        @php $building = $buildings[$i]; @endphp
                        <div class="text-center">
                            <div class="text-2xl mb-1">
                                {{ $this->getBuildingIcon($building->buildingType->key ?? 'building') }}</div>
                            <div class="text-xs font-medium text-gray-700 mb-1">
                                {{ $building->name ?? $building->buildingType->name }}</div>
                            <div class="text-sm font-bold text-blue-600">Lv.{{ $building->level }}</div>
                            @if ($building->upgrade_started_at)
                                <div class="text-xs text-orange-600 bg-orange-100 px-1 py-0.5 rounded mt-1">
                                    ⏳ Upgrading...
                                </div>
                            @elseif ($building->level < ($building->buildingType->max_level ?? 20))
                                <div class="text-xs text-green-600 bg-green-100 px-1 py-0.5 rounded mt-1">
                                    ✅ Ready
                                </div>
                            @else
                                <div class="text-xs text-gray-600 bg-gray-100 px-1 py-0.5 rounded mt-1">
                                    🏆 Max
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center text-gray-400">
                            <div class="text-2xl mb-1">🏗️</div>
                            <div class="text-xs">Empty</div>
                        </div>
                    @endif
                </div>
            @endfor
        </div>
    </div>

    <!-- Building Progress Queue -->
    @if (count($buildingQueues) > 0)
        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg shadow-lg mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center space-x-2">
                <span class="text-xl">⏳</span>
                <span>Building Progress</span>
            </h3>
            <div class="space-y-3">
                @foreach ($buildingQueues as $queue)
                    @if (isset($buildingProgress[$queue->id]))
                        @php $progress = $buildingProgress[$queue->id]; @endphp
                        <div class="bg-white p-3 rounded-lg border border-green-200">
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center space-x-2">
                                    <span
                                          class="text-lg">{{ $this->getBuildingIcon($queue->buildingType->key ?? 'building') }}</span>
                                    <div>
                                        <div class="font-medium">{{ $progress['building_name'] }}</div>
                                        <div class="text-sm text-gray-600">Level {{ $progress['target_level'] }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-green-600">
                                        {{ number_format($progress['progress'], 1) }}%</div>
                                    <div class="text-xs text-gray-500">{{ gmdate('H:i:s', $progress['remaining']) }}
                                        remaining</div>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full transition-all duration-1000"
                                     style="width: {{ $progress['progress'] }}%">
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <!-- Building Details Modal -->
    @if ($selectedBuilding !== null)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $selectedBuilding->name }} (Level {{ $selectedBuilding->level }})
                        </h5>
                        <button type="button" class="btn-close" wire:click="selectBuilding(null)"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Existing Building -->
                        <div class="row">
                            <div class="col-md-6">
                                <img src="{{ asset('img/buildings/1.gif') }}"
                                     alt="Building" class="img-fluid">
                            </div>
                            <div class="col-md-6">
                                <h6>Current Level: {{ $selectedBuilding->level }}</h6>
                                <p>Building Type: {{ $selectedBuilding->buildingType->name ?? 'Unknown' }}</p>

                                @if ($canUpgrade)
                                    <div class="upgrade-cost">
                                        <h6>Upgrade Cost:</h6>
                                        <div class="resource-cost">
                                            <img src="{{ asset('img/r/1.gif') }}" alt="Wood"
                                                 class="resource-icon">
                                            <span>{{ $upgradeCost['wood'] ?? 0 }}</span>
                                        </div>
                                        <div class="resource-cost">
                                            <img src="{{ asset('img/r/2.gif') }}" alt="Clay"
                                                 class="resource-icon">
                                            <span>{{ $upgradeCost['clay'] ?? 0 }}</span>
                                        </div>
                                        <div class="resource-cost">
                                            <img src="{{ asset('img/r/3.gif') }}" alt="Iron"
                                                 class="resource-icon">
                                            <span>{{ $upgradeCost['iron'] ?? 0 }}</span>
                                        </div>
                                        <div class="resource-cost">
                                            <img src="{{ asset('img/r/4.gif') }}" alt="Crop"
                                                 class="resource-icon">
                                            <span>{{ $upgradeCost['crop'] ?? 0 }}</span>
                                        </div>
                                    </div>

                                    <button class="btn btn-primary" wire:click="upgradeBuilding">
                                        Upgrade to Level {{ $buildingLevel + 1 }}
                                    </button>
                                @else
                                    <div class="alert alert-warning">
                                        Insufficient resources for upgrade
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Building Queue -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Building Queue</h5>
                </div>
                <div class="card-body">
                    @if ($buildingQueues && $buildingQueues->count() > 0)
                        <div class="list-group">
                            @foreach ($buildingQueues as $queue)
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ $queue->building->name }} to Level
                                            {{ $queue->target_level }}</h6>
                                        <small>{{ $queue->completed_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: {{ $queue->progress }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No buildings in queue</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .building-slot {
            position: relative;
        }

        .building-level {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        .resource-cost {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }

        .resource-cost .resource-icon {
            width: 16px;
            height: 16px;
            margin-right: 5px;
        }

        .upgrade-cost {
            margin: 15px 0;
        }

        .modal.show {
            display: block;
        }

        .modal-backdrop.show {
            opacity: 0.5;
        }
    </style>
@endpush
@endsection
