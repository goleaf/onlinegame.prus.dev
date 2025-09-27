<div wire:poll.5s="refreshGameData">
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
                    <p class="text-info">{{ $gameStats['alliance_name'] ?? 'No Alliance' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Status</h5>
                    <p class="{{ $gameStats['online_status'] === 'Online' ? 'text-success' : 'text-danger' }}">
                        {{ $gameStats['online_status'] ?? 'Offline' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Village Selection -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Villages</h5>
                </div>
                <div class="card-body">
                    @foreach ($villages as $village)
                        <button wire:click="selectVillage({{ $village->id }})"
                                class="btn btn-outline-primary w-100 mb-2 {{ $currentVillage && $currentVillage->id === $village->id ? 'active' : '' }}">
                            <div class="d-flex justify-content-between">
                                <span>{{ $village->name }}</span>
                                <small>({{ $village->x_coordinate }}, {{ $village->y_coordinate }})</small>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Current Village Resources -->
        @if ($currentVillage)
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Resources - {{ $currentVillage->name }}</h5>
                    </div>
                    <div class="card-body">
                        @foreach ($currentVillage->resources as $resource)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('img/resources/' . $resource->type . '.gif') }}"
                                         alt="{{ ucfirst($resource->type) }}" width="20" height="20"
                                         class="me-2">
                                    <span class="fw-bold">{{ ucfirst($resource->type) }}</span>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">{{ number_format($resource->amount) }}</div>
                                    <small class="text-muted">+{{ $resource->production_rate }}/sec</small>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar"
                                     style="width: {{ min(100, ($resource->amount / $resource->storage_capacity) * 100) }}%">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Recent Events -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Events</h5>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    @forelse($recentEvents as $event)
                        <div class="alert alert-info py-2 mb-2">
                            <div class="fw-bold">{{ $event->description }}</div>
                            <small class="text-muted">{{ $event->occurred_at->diffForHumans() }}</small>
                        </div>
                    @empty
                        <p class="text-muted">No recent events</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Current Village Buildings -->
    @if ($currentVillage)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Buildings - {{ $currentVillage->name }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($currentVillage->buildings as $building)
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="card text-center h-100">
                                        <div class="card-body">
                                            <img src="{{ asset('img/buildings/' . $building->buildingType->key . '.gif') }}"
                                                 alt="{{ $building->name }}" class="img-fluid mb-2"
                                                 style="max-width: 50px;">
                                            <h6 class="card-title">{{ $building->name }}</h6>
                                            <h4 class="text-primary">Lv.{{ $building->level }}</h4>
                                            @if ($building->upgrade_started_at)
                                                <small class="text-warning">Upgrading...</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Real-time Update Indicator -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <div class="alert alert-success">
                <div class="real-time-indicator"></div>
                <strong>Live Updates Active</strong> - Game data refreshes every 5 seconds
                <br>
                <small>Last updated: {{ $lastUpdate?->format('Y-m-d H:i:s') ?? 'Never' }}</small>
            </div>
        </div>
    </div>
</div>

<style>
    .real-time-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #27ae60;
        margin-right: 5px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }

        100% {
            opacity: 1;
        }
    }
</style>
