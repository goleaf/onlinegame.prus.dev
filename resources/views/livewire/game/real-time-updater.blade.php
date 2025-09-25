<div>
    <!-- Real-time Status Bar -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    @if ($autoRefresh)
                                        <span class="badge bg-success">Auto-refresh: ON</span>
                                    @else
                                        <span class="badge bg-secondary">Auto-refresh: OFF</span>
                                    @endif
                                </div>
                                <div class="me-3">
                                    <small class="text-muted">Interval: {{ $refreshInterval }}s</small>
                                </div>
                                <div>
                                    <small class="text-muted">Last update: {{ $lastUpdate->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group btn-group-sm">
                                <button wire:click="toggleAutoRefresh"
                                        class="btn {{ $autoRefresh ? 'btn-success' : 'btn-outline-success' }}">
                                    {{ $autoRefresh ? 'ON' : 'OFF' }}
                                </button>
                                <button wire:click="processGameTick"
                                        class="btn btn-primary"
                                        @if ($isProcessing) disabled @endif>
                                    @if ($isProcessing)
                                        <span class="spinner-border spinner-border-sm me-1"></span>
                                        Processing...
                                    @else
                                        Process Tick
                                    @endif
                                </button>
                                <button wire:click="refreshGameData"
                                        class="btn btn-outline-info">
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Processing Status -->
    @if ($isProcessing)
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        <span>{{ $processingMessage }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Game Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Villages</h5>
                    <h2 class="text-primary">{{ $gameStats['total_villages'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Points</h5>
                    <h2 class="text-success">{{ $gameStats['total_points'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Alliance</h5>
                    <p class="text-info mb-0">{{ $gameStats['alliance_name'] ?? 'No Alliance' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Status</h5>
                    <p class="{{ $gameStats['online_status'] === 'Online' ? 'text-success' : 'text-danger' }} mb-0">
                        {{ $gameStats['online_status'] ?? 'Offline' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Villages Overview -->
    <div class="row">
        @foreach ($villages as $village)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">{{ $village->name }}</h6>
                        <small class="text-muted">({{ $village->x_coordinate }}, {{ $village->y_coordinate }})</small>
                    </div>
                    <div class="card-body">
                        <!-- Resources -->
                        <div class="mb-3">
                            <h6 class="card-title">Resources</h6>
                            @foreach ($village->resources as $resource)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ asset('img/resources/' . $resource->type . '.gif') }}"
                                             alt="{{ ucfirst($resource->type) }}" width="16" height="16"
                                             class="me-2">
                                        <span class="fw-bold">{{ ucfirst($resource->type) }}</span>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">{{ number_format($resource->amount) }}</div>
                                        <small class="text-muted">+{{ $resource->production_rate }}/sec</small>
                                    </div>
                                </div>
                                <div class="progress mb-2" style="height: 4px;">
                                    <div class="progress-bar"
                                         style="width: {{ min(100, ($resource->amount / $resource->storage_capacity) * 100) }}%">
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Buildings -->
                        <div class="mb-3">
                            <h6 class="card-title">Buildings</h6>
                            <div class="row">
                                @foreach ($village->buildings->take(4) as $building)
                                    <div class="col-6 mb-2">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('img/buildings/' . $building->buildingType->key . '.gif') }}"
                                                 alt="{{ $building->name }}" width="20" height="20"
                                                 class="me-2">
                                            <div>
                                                <div class="fw-bold">{{ $building->name }}</div>
                                                <small class="text-muted">Lv.{{ $building->level }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Village Actions -->
                        <div class="d-grid gap-2">
                            <a href="{{ route('game.village', $village->id) }}" class="btn btn-primary btn-sm">Manage
                                Village</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Recent Events -->
    @if ($recentEvents->count() > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Events</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @foreach ($recentEvents as $event)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $event->description }}</h6>
                                            <small
                                                   class="text-muted">{{ $event->occurred_at->diffForHumans() }}</small>
                                        </div>
                                        <span class="badge bg-info">{{ $event->event_type }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Auto-refresh script -->
@if ($autoRefresh)
    <script>
        setInterval(function() {
            @this.call('refreshGameData');
        }, {{ $refreshInterval * 1000 }});
    </script>
@endif

<!-- Livewire event handlers -->
<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('gameTickProcessed', function() {
            // Show success notification
            console.log('Game tick processed successfully');
            // You can add a toast notification here
        });

        Livewire.on('gameTickError', function(data) {
            // Show error notification
            console.error('Game tick error:', data.message);
            // You can add an error toast here
        });

        Livewire.on('gameDataRefreshed', function() {
            console.log('Game data refreshed');
        });

        Livewire.on('autoRefreshToggled', function(data) {
            console.log('Auto-refresh toggled:', data.enabled);
        });

        Livewire.on('refreshIntervalChanged', function(data) {
            console.log('Refresh interval changed:', data.interval);
        });
    });
</script>
