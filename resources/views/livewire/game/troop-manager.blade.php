@extends('layouts.travian')

@section('title', 'Troop Manager')

@section('content')
    <div>
        <!-- Village Header -->
        <div class="village-info">
            <h3>{{ $village->name ?? 'Main Village' }}</h3>
            <p><strong>Coordinates:</strong> {{ $village->coordinates ?? '(0|0)' }}</p>
            <p><strong>Population:</strong> {{ $village->population ?? 100 }}</p>
        </div>

        <!-- Current Troops -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Current Troops</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($troops as $troop)
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <img src="{{ asset('img/units/' . $troop->unitType->key . '.gif') }}"
                                                 alt="{{ $troop->unitType->name }}" class="img-fluid mb-2"
                                                 style="max-width: 50px;">
                                            <h6>{{ $troop->unitType->name }}</h6>
                                            <h4 class="text-primary">{{ $troop->count }}</h4>
                                            <small class="text-muted">
                                                In Village: {{ $troop->in_village }}<br>
                                                In Attack: {{ $troop->in_attack }}<br>
                                                In Defense: {{ $troop->in_defense }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Training Queue -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Training Queue</h5>
                    </div>
                    <div class="card-body">
                        @if ($trainingQueues && $trainingQueues->count() > 0)
                            <div class="list-group">
                                @foreach ($trainingQueues as $queue)
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">{{ $queue->count }}x {{ $queue->unitType->name }}</h6>
                                            <small>{{ $queue->completed_at->diffForHumans() }}</small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: {{ $queue->progress }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No troops in training queue</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Train New Troops -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Train New Troops</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($availableUnits as $unit)
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="text-center">
                                                <img src="{{ asset('img/units/' . $unit->key . '.gif') }}"
                                                     alt="{{ $unit->name }}" class="img-fluid mb-2"
                                                     style="max-width: 60px;">
                                                <h6>{{ $unit->name }}</h6>
                                            </div>

                                            <div class="unit-stats">
                                                <small class="text-muted">
                                                    <strong>Attack:</strong> {{ $unit->attack }}<br>
                                                    <strong>Defense:</strong>
                                                    {{ $unit->defense_infantry }}/{{ $unit->defense_cavalry }}<br>
                                                    <strong>Speed:</strong> {{ $unit->speed }}<br>
                                                    <strong>Carry:</strong> {{ $unit->carry_capacity }}
                                                </small>
                                            </div>

                                            <div class="unit-cost mt-3">
                                                <h6>Cost:</h6>
                                                <div class="resource-cost">
                                                    <img src="{{ asset('img/r/1.gif') }}" alt="Wood"
                                                         class="resource-icon">
                                                    <span>{{ $unit->costs['wood'] ?? 0 }}</span>
                                                </div>
                                                <div class="resource-cost">
                                                    <img src="{{ asset('img/r/2.gif') }}" alt="Clay"
                                                         class="resource-icon">
                                                    <span>{{ $unit->costs['clay'] ?? 0 }}</span>
                                                </div>
                                                <div class="resource-cost">
                                                    <img src="{{ asset('img/r/3.gif') }}" alt="Iron"
                                                         class="resource-icon">
                                                    <span>{{ $unit->costs['iron'] ?? 0 }}</span>
                                                </div>
                                                <div class="resource-cost">
                                                    <img src="{{ asset('img/r/4.gif') }}" alt="Crop"
                                                         class="resource-icon">
                                                    <span>{{ $unit->costs['crop'] ?? 0 }}</span>
                                                </div>
                                            </div>

                                            <div class="training-controls mt-3">
                                                <div class="input-group">
                                                    <input type="number" class="form-control"
                                                           wire:model="trainingCount.{{ $unit->id }}"
                                                           min="1" max="100" value="1">
                                                    <button class="btn btn-primary"
                                                            wire:click="trainTroops({{ $unit->id }})">
                                                        Train
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Training Modal -->
        @if ($showTrainingModal)
            <div class="modal fade show" style="display: block;" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Train {{ $selectedUnit->name ?? 'Troops' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeTrainingModal"></button>
                        </div>
                        <div class="modal-body">
                            @if ($selectedUnit)
                                <div class="row">
                                    <div class="col-md-6">
                                        <img src="{{ asset('img/units/' . $selectedUnit->key . '.gif') }}"
                                             alt="{{ $selectedUnit->name }}" class="img-fluid">
                                    </div>
                                    <div class="col-md-6">
                                        <h6>{{ $selectedUnit->name }}</h6>
                                        <p>{{ $selectedUnit->description }}</p>

                                        <div class="unit-stats">
                                            <strong>Attack:</strong> {{ $selectedUnit->attack }}<br>
                                            <strong>Defense:</strong>
                                            {{ $selectedUnit->defense_infantry }}/{{ $selectedUnit->defense_cavalry }}<br>
                                            <strong>Speed:</strong> {{ $selectedUnit->speed }}<br>
                                            <strong>Carry:</strong> {{ $selectedUnit->carry_capacity }}
                                        </div>

                                        <div class="training-form mt-3">
                                            <label for="count">Number to train:</label>
                                            <input type="number" class="form-control"
                                                   wire:model="trainingCount"
                                                   min="1" max="100" value="1">

                                            <div class="total-cost mt-3">
                                                <h6>Total Cost:</h6>
                                                <div class="resource-cost">
                                                    <img src="{{ asset('img/r/1.gif') }}" alt="Wood"
                                                         class="resource-icon">
                                                    <span>{{ $totalCost['wood'] ?? 0 }}</span>
                                                </div>
                                                <div class="resource-cost">
                                                    <img src="{{ asset('img/r/2.gif') }}" alt="Clay"
                                                         class="resource-icon">
                                                    <span>{{ $totalCost['clay'] ?? 0 }}</span>
                                                </div>
                                                <div class="resource-cost">
                                                    <img src="{{ asset('img/r/3.gif') }}" alt="Iron"
                                                         class="resource-icon">
                                                    <span>{{ $totalCost['iron'] ?? 0 }}</span>
                                                </div>
                                                <div class="resource-cost">
                                                    <img src="{{ asset('img/r/4.gif') }}" alt="Crop"
                                                         class="resource-icon">
                                                    <span>{{ $totalCost['crop'] ?? 0 }}</span>
                                                </div>
                                            </div>

                                            <div class="training-time mt-3">
                                                <strong>Training Time:</strong> {{ $trainingTime ?? '0 seconds' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    wire:click="closeTrainingModal">Cancel</button>
                            <button type="button" class="btn btn-primary" wire:click="confirmTraining">
                                Start Training
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        @endif
    </div>

    @push('styles')
        <style>
            .unit-stats {
                background: #f8f9fa;
                padding: 10px;
                border-radius: 5px;
                margin: 10px 0;
            }

            .unit-cost {
                background: #e9ecef;
                padding: 10px;
                border-radius: 5px;
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

            .training-controls {
                margin-top: 15px;
            }

            .total-cost {
                background: #fff3cd;
                padding: 10px;
                border-radius: 5px;
                border: 1px solid #ffeaa7;
            }

            .training-time {
                background: #d1ecf1;
                padding: 10px;
                border-radius: 5px;
                border: 1px solid #bee5eb;
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
