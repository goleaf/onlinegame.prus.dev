@extends('layouts.travian')

@section('title', 'Battle Manager')

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
                        <h5>Available Troops</h5>
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
                                            <h4 class="text-primary">{{ $troop->in_village }}</h4>
                                            <small class="text-muted">Available</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attack Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Launch Attack</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="targetX">Target X Coordinate:</label>
                                    <input type="number" class="form-control" wire:model="targetX" id="targetX">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="targetY">Target Y Coordinate:</label>
                                    <input type="number" class="form-control" wire:model="targetY" id="targetY">
                                </div>
                                <button class="btn btn-primary" wire:click="selectTarget">Find Target</button>
                            </div>
                            <div class="col-md-6">
                                @if ($selectedTarget)
                                    <h6>Target Information</h6>
                                    <p><strong>Village:</strong> {{ $selectedTarget['name'] ?? 'Unknown' }}</p>
                                    <p><strong>Player:</strong> {{ $selectedTarget['player_name'] ?? 'Unknown' }}</p>
                                    <p><strong>Coordinates:</strong> ({{ $selectedTarget['x'] }},
                                        {{ $selectedTarget['y'] }})</p>
                                    <p><strong>Distance:</strong> {{ $selectedTarget['distance'] ?? 0 }} fields</p>
                                    <p><strong>Travel Time:</strong> {{ $selectedTarget['travel_time'] ?? '0 seconds' }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Troop Selection -->
        @if ($selectedTarget)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Select Troops for Attack</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($troops as $troop)
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <img src="{{ asset('img/units/' . $troop->unitType->key . '.gif') }}"
                                                         alt="{{ $troop->unitType->name }}" class="img-fluid me-3"
                                                         style="max-width: 40px;">
                                                    <div class="flex-grow-1">
                                                        <h6>{{ $troop->unitType->name }}</h6>
                                                        <small class="text-muted">Available:
                                                            {{ $troop->in_village }}</small>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <label for="troop_{{ $troop->id }}">Count:</label>
                                                    <input type="number" class="form-control"
                                                           wire:model="attackingTroops.{{ $troop->id }}"
                                                           min="0" max="{{ $troop->in_village }}"
                                                           id="troop_{{ $troop->id }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-danger" wire:click="launchAttack">
                                    Launch Attack
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Active Movements -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Active Movements</h5>
                    </div>
                    <div class="card-body">
                        @if ($movements && $movements->count() > 0)
                            <div class="list-group">
                                @foreach ($movements as $movement)
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                {{ ucfirst($movement->type) }} to ({{ $movement->to_village_id }})
                                            </h6>
                                            <small>{{ $movement->arrives_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-1">
                                            <strong>Status:</strong> {{ ucfirst($movement->status) }}<br>
                                            <strong>Arrives:</strong> {{ $movement->arrives_at->format('H:i:s') }}
                                        </p>
                                        @if ($movement->troops)
                                            <div class="troop-list">
                                                @foreach (json_decode($movement->troops, true) as $troop)
                                                    <span class="badge bg-primary me-1">
                                                        {{ $troop['count'] }}x {{ $troop['name'] }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No active movements</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Battle History -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Battles</h5>
                    </div>
                    <div class="card-body">
                        @if ($battles && $battles->count() > 0)
                            <div class="list-group">
                                @foreach ($battles as $battle)
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                {{ ucfirst($battle->result) }} at ({{ $battle->village_id }})
                                            </h6>
                                            <small>{{ $battle->occurred_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-1">
                                            <strong>Attacker:</strong> {{ $battle->attacker->name ?? 'Unknown' }}<br>
                                            <strong>Defender:</strong> {{ $battle->defender->name ?? 'Unknown' }}<br>
                                            <strong>Result:</strong> {{ ucfirst($battle->result) }}
                                        </p>
                                        @if ($battle->loot)
                                            <div class="loot-info">
                                                <strong>Loot:</strong>
                                                @foreach (json_decode($battle->loot, true) as $resource => $amount)
                                                    <span class="badge bg-success me-1">
                                                        {{ $amount }} {{ ucfirst($resource) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No recent battles</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Attack Confirmation Modal -->
        @if ($showAttackModal)
            <div class="modal fade show" style="display: block;" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Attack</h5>
                            <button type="button" class="btn-close" wire:click="closeAttackModal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Target Information</h6>
                                    <p><strong>Village:</strong> {{ $selectedTarget['name'] ?? 'Unknown' }}</p>
                                    <p><strong>Player:</strong> {{ $selectedTarget['player_name'] ?? 'Unknown' }}</p>
                                    <p><strong>Coordinates:</strong> ({{ $selectedTarget['x'] }},
                                        {{ $selectedTarget['y'] }})</p>
                                    <p><strong>Distance:</strong> {{ $selectedTarget['distance'] ?? 0 }} fields</p>
                                    <p><strong>Travel Time:</strong> {{ $selectedTarget['travel_time'] ?? '0 seconds' }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Attacking Troops</h6>
                                    @foreach ($attackingTroops as $troopId => $count)
                                        @if ($count > 0)
                                            @php
                                                $troop = $troops->firstWhere('id', $troopId);
                                            @endphp
                                            @if ($troop)
                                                <div class="troop-item">
                                                    <img src="{{ asset('img/units/' . $troop->unitType->key . '.gif') }}"
                                                         alt="{{ $troop->unitType->name }}" class="troop-icon">
                                                    <span>{{ $count }}x {{ $troop->unitType->name }}</span>
                                                </div>
                                            @endif
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    wire:click="closeAttackModal">Cancel</button>
                            <button type="button" class="btn btn-danger" wire:click="confirmAttack">
                                Launch Attack
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
            .troop-list {
                margin-top: 10px;
            }

            .loot-info {
                margin-top: 10px;
            }

            .troop-item {
                display: flex;
                align-items: center;
                margin: 5px 0;
            }

            .troop-item .troop-icon {
                width: 20px;
                height: 20px;
                margin-right: 5px;
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
