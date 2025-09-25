@extends('layouts.travian')

@section('title', 'Village Manager')

@section('content')
    <div>
        <!-- Notifications -->
        @if (count($notifications) > 0)
            <div class="notifications mb-3">
                @foreach ($notifications as $notification)
                    <div
                         class="alert alert-{{ $notification['type'] === 'error' ? 'danger' : $notification['type'] }} alert-dismissible fade show">
                        {{ $notification['message'] }}
                        <button type="button" class="btn-close"
                                wire:click="removeNotification('{{ $notification['id'] }}')"></button>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Village Header -->
        <div class="village-info">
            <h3>{{ $village->name ?? 'Main Village' }}</h3>
            <p><strong>Coordinates:</strong> {{ $village->coordinates ?? '(0|0)' }}</p>
            <p><strong>Population:</strong> {{ $village->population ?? 100 }}</p>
            <p><strong>Culture Points:</strong> {{ $village->culture_points ?? 1000 }}</p>
        </div>

        <!-- Building Grid -->
        <div class="building-grid">
            @for ($i = 0; $i < 36; $i++)
                <div class="building-slot {{ $i < count($buildings) ? 'occupied' : '' }}"
                     data-slot="{{ $i }}"
                     @if ($i < count($buildings)) @php $building = $buildings[$i]; @endphp
                         wire:click="selectBuilding({{ $building->id }})" @endif>
                    @if ($i < count($buildings))
                        @php $building = $buildings[$i]; @endphp
                        <img src="{{ asset('img/buildings/' . ($i + 1) . '.gif') }}" alt="{{ $building->name }}"
                             class="building-icon">
                        <div class="building-level">{{ $building->name }} Lv.{{ $building->level }}</div>
                    @else
                        <span class="text-muted">Empty</span>
                    @endif
                </div>
            @endfor
        </div>

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
