@extends('layouts.travian')

@section('title', 'Map Viewer')

@section('content')
    <div>
        <!-- Map Controls -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Map Controls</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="centerX">Center X:</label>
                                <input type="number" class="form-control" wire:model="centerX" id="centerX">
                            </div>
                            <div class="col-md-3">
                                <label for="centerY">Center Y:</label>
                                <input type="number" class="form-control" wire:model="centerY" id="centerY">
                            </div>
                            <div class="col-md-3">
                                <label for="mapSize">Map Size:</label>
                                <select class="form-control" wire:model="mapSize" id="mapSize">
                                    <option value="5">5x5</option>
                                    <option value="10">10x10</option>
                                    <option value="15">15x15</option>
                                    <option value="20">20x20</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary" wire:click="loadMapData">Refresh Map</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Display -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Map ({{ $centerX }}, {{ $centerY }})</h5>
                    </div>
                    <div class="card-body">
                        <div class="map-container" style="overflow: auto; max-height: 600px;">
                            <div class="map-grid"
                                 style="display: grid; grid-template-columns: repeat({{ $mapSize }}, 1fr); gap: 2px;">
                                @for ($y = $centerY - $mapSize / 2; $y <= $centerY + $mapSize / 2; $y++)
                                    @for ($x = $centerX - $mapSize / 2; $x <= $centerX + $mapSize / 2; $x++)
                                        <div class="map-tile"
                                             data-x="{{ $x }}"
                                             data-y="{{ $y }}"
                                             wire:click="selectVillage({{ $x }}, {{ $y }})"
                                             style="width: 60px; height: 60px; border: 1px solid #ddd; cursor: pointer; position: relative;">

                                            @php
                                                $tile = $mapGrid[$x][$y] ?? null;
                                            @endphp

                                            @if ($tile)
                                                <!-- Village -->
                                                @if ($tile['type'] === 'village')
                                                    <div class="village-tile"
                                                         style="background: #e8f5e8; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                                        <img src="{{ asset('img/map-t4/village.gif') }}" alt="Village"
                                                             style="max-width: 40px; max-height: 40px;">
                                                        <div class="village-info"
                                                             style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; font-size: 10px; text-align: center;">
                                                            {{ $tile['player_name'] ?? 'Unknown' }}
                                                        </div>
                                                    </div>
                                                @elseif($tile['type'] === 'oasis')
                                                    <div class="oasis-tile"
                                                         style="background: #f0f8ff; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                                        <img src="{{ asset('img/map-t4/oasis.gif') }}" alt="Oasis"
                                                             style="max-width: 40px; max-height: 40px;">
                                                    </div>
                                                @elseif($tile['type'] === 'nature')
                                                    <div class="nature-tile"
                                                         style="background: #f5f5f5; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                                        <img src="{{ asset('img/map-t4/nature.gif') }}" alt="Nature"
                                                             style="max-width: 40px; max-height: 40px;">
                                                    </div>
                                                @endif
                                            @else
                                                <!-- Empty tile -->
                                                <div class="empty-tile"
                                                     style="background: #f9f9f9; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                                    <span
                                                          style="color: #999; font-size: 10px;">{{ $x }},{{ $y }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endfor
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Village Details Modal -->
        @if ($selectedVillage)
            <div class="modal fade show" style="display: block;" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Village Details</h5>
                            <button type="button" class="btn-close" wire:click="selectVillage(null)"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Village Information</h6>
                                    <p><strong>Name:</strong> {{ $selectedVillage['name'] ?? 'Unknown Village' }}</p>
                                    <p><strong>Coordinates:</strong> ({{ $selectedVillage['x'] }},
                                        {{ $selectedVillage['y'] }})</p>
                                    <p><strong>Player:</strong> {{ $selectedVillage['player_name'] ?? 'Unknown Player' }}
                                    </p>
                                    <p><strong>Population:</strong> {{ $selectedVillage['population'] ?? 0 }}</p>
                                    <p><strong>Alliance:</strong> {{ $selectedVillage['alliance'] ?? 'No Alliance' }}</p>
                                    
                                    @if(isset($selectedVillage['latitude']) && isset($selectedVillage['longitude']))
                                        <p><strong>Latitude:</strong> {{ number_format($selectedVillage['latitude'], 6) }}°</p>
                                        <p><strong>Longitude:</strong> {{ number_format($selectedVillage['longitude'], 6) }}°</p>
                                    @endif
                                    
                                    @if(isset($selectedVillage['geohash']))
                                        <p><strong>Geohash:</strong> {{ $selectedVillage['geohash'] }}</p>
                                    @endif
                                    
                                    @if(isset($selectedVillage['elevation']))
                                        <p><strong>Elevation:</strong> {{ number_format($selectedVillage['elevation'], 2) }}m</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h6>Resources</h6>
                                    <div class="resource-list">
                                        <div class="resource-item">
                                            <img src="{{ asset('img/r/1.gif') }}" alt="Wood" class="resource-icon">
                                            <span>{{ $selectedVillage['wood'] ?? 0 }}</span>
                                        </div>
                                        <div class="resource-item">
                                            <img src="{{ asset('img/r/2.gif') }}" alt="Clay" class="resource-icon">
                                            <span>{{ $selectedVillage['clay'] ?? 0 }}</span>
                                        </div>
                                        <div class="resource-item">
                                            <img src="{{ asset('img/r/3.gif') }}" alt="Iron" class="resource-icon">
                                            <span>{{ $selectedVillage['iron'] ?? 0 }}</span>
                                        </div>
                                        <div class="resource-item">
                                            <img src="{{ asset('img/r/4.gif') }}" alt="Crop" class="resource-icon">
                                            <span>{{ $selectedVillage['crop'] ?? 0 }}</span>
                                        </div>
                                    </div>

                                    <h6>Buildings</h6>
                                    <div class="building-list">
                                        @if (isset($selectedVillage['buildings']))
                                            @foreach ($selectedVillage['buildings'] as $building)
                                                <div class="building-item">
                                                    <img src="{{ asset('img/buildings/' . $building['type'] . '.gif') }}"
                                                         alt="{{ $building['name'] }}" class="building-icon">
                                                    <span>{{ $building['name'] }} Lv.{{ $building['level'] }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-muted">No buildings visible</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    wire:click="selectVillage(null)">Close</button>
                            <button type="button" class="btn btn-primary"
                                    wire:click="moveMap({{ $selectedVillage['x'] }}, {{ $selectedVillage['y'] }})">
                                Center Map
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        @endif

        <!-- Map Navigation -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Map Navigation</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100"
                                        wire:click="moveMap({{ $centerX }}, {{ $centerY - $mapSize }})">
                                    ↑ North
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100"
                                        wire:click="moveMap({{ $centerX + $mapSize }}, {{ $centerY }})">
                                    → East
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100"
                                        wire:click="moveMap({{ $centerX }}, {{ $centerY + $mapSize }})">
                                    ↓ South
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100"
                                        wire:click="moveMap({{ $centerX - $mapSize }}, {{ $centerY }})">
                                    ← West
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100" wire:click="moveMap(0, 0)">
                                    Center (0,0)
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary w-100" wire:click="loadMapData">
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .map-container {
                border: 1px solid #ddd;
                border-radius: 5px;
                background: #f9f9f9;
            }

            .map-tile {
                transition: all 0.3s ease;
            }

            .map-tile:hover {
                border-color: #3498db !important;
                background: #e8f4f8 !important;
            }

            .village-tile {
                border: 2px solid #27ae60;
            }

            .oasis-tile {
                border: 2px solid #3498db;
            }

            .nature-tile {
                border: 2px solid #95a5a6;
            }

            .resource-item {
                display: flex;
                align-items: center;
                margin: 5px 0;
            }

            .resource-item .resource-icon {
                width: 16px;
                height: 16px;
                margin-right: 5px;
            }

            .building-item {
                display: flex;
                align-items: center;
                margin: 5px 0;
            }

            .building-item .building-icon {
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
