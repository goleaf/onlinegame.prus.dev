<div class="world-map-container">
    <div class="map-header">
        <h2 class="map-title">
            <img src="{{ asset('img/travian/interface/map_icon.png') }}" alt="Map" class="map-icon">
            World Map - {{ $world->name ?? 'Unknown World' }}
        </h2>

        <div class="map-controls">
            <button wire:click="zoomIn" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus"></i> Zoom In
            </button>
            <button wire:click="zoomOut" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-minus"></i> Zoom Out
            </button>
            <button wire:click="resetZoom" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-search"></i> Reset
            </button>
        </div>
    </div>

    <div class="map-filters">
        <!-- Enhanced Map Controls -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Map Theme:</label>
                    <select wire:model="mapTheme" class="form-control form-control-sm">
                        <option value="classic">Classic</option>
                        <option value="modern">Modern</option>
                        <option value="dark">Dark</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Coordinate System:</label>
                    <select wire:model="coordinateSystem" class="form-control form-control-sm">
                        <option value="game">Game (X|Y)</option>
                        <option value="decimal">Decimal</option>
                        <option value="dms">DMS</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Map Layers:</label>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" 
                                wire:click="toggleMapLayer('villages')"
                                :class="{'active': mapLayers.villages}">
                            Villages
                        </button>
                        <button type="button" class="btn btn-outline-primary" 
                                wire:click="toggleMapLayer('alliances')"
                                :class="{'active': mapLayers.alliances}">
                            Alliances
                        </button>
                        <button type="button" class="btn btn-outline-primary" 
                                wire:click="toggleMapLayer('resources')"
                                :class="{'active': mapLayers.resources}">
                            Resources
                        </button>
                        <button type="button" class="btn btn-outline-primary" 
                                wire:click="toggleMapLayer('movements')"
                                :class="{'active': mapLayers.movements}">
                            Movements
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Quick Actions:</label>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary" 
                                wire:click="$set('showGrid', !showGrid)">
                            {{ $showGrid ? 'Hide' : 'Show' }} Grid
                        </button>
                        <button type="button" class="btn btn-outline-secondary" 
                                wire:click="$set('showDistance', !showDistance)">
                            {{ $showDistance ? 'Hide' : 'Show' }} Distance
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Coordinate Selection -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Center X:</label>
                    <input type="number" wire:model="viewCenter.x" class="form-control form-control-sm" 
                           min="0" max="400">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Center Y:</label>
                    <input type="number" wire:model="viewCenter.y" class="form-control form-control-sm" 
                           min="0" max="400">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Actions:</label>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-primary" wire:click="loadVisibleVillages">
                            Refresh
                        </button>
                        <button type="button" class="btn btn-secondary" wire:click="calculateMapBounds">
                            Auto Bounds
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="filter-group">
            <label class="filter-label">Show:</label>
            <button wire:click="toggleCoordinates"
                    class="btn btn-sm {{ $showCoordinates ? 'btn-primary' : 'btn-outline-primary' }}">
                Coordinates
            </button>
            <button wire:click="toggleVillageNames"
                    class="btn btn-sm {{ $showVillageNames ? 'btn-primary' : 'btn-outline-primary' }}">
                Village Names
            </button>
            <button wire:click="toggleAlliances"
                    class="btn btn-sm {{ $showAlliances ? 'btn-primary' : 'btn-outline-primary' }}">
                Alliances
            </button>
        </div>

        <div class="filter-group">
            <label class="filter-label">Filter:</label>
            <select wire:model="filterTribe" class="form-select form-select-sm">
                <option value="">All Tribes</option>
                <option value="roman">Roman</option>
                <option value="teuton">Teuton</option>
                <option value="gaul">Gaul</option>
            </select>
            <button wire:click="clearFilters" class="btn btn-sm btn-outline-secondary">
                Clear Filters
            </button>
        </div>
    </div>

    <div class="map-navigation">
        <button wire:click="moveMap('north')" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-arrow-up"></i> North
        </button>
        <button wire:click="moveMap('south')" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-arrow-down"></i> South
        </button>
        <button wire:click="moveMap('east')" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-arrow-right"></i> East
        </button>
        <button wire:click="moveMap('west')" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-arrow-left"></i> West
        </button>
    </div>

    <div class="map-info">
        <div class="map-stats">
            <span class="stat-item">
                <i class="fas fa-map-marker-alt"></i>
                View Center: ({{ $viewCenter['x'] }}, {{ $viewCenter['y'] }})
            </span>
            <span class="stat-item">
                <i class="fas fa-search-plus"></i>
                Zoom: {{ $zoomLevel }}x
            </span>
            <span class="stat-item">
                <i class="fas fa-home"></i>
                Villages: {{ count($visibleVillages) }}
            </span>
        </div>
    </div>

    @if ($isLoading)
        <div class="map-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading map...</span>
            </div>
            <p>Loading map data...</p>
        </div>
    @else
        <div class="map-viewport {{ $this->getMapThemeClass() }}"
             style="width: 100%; height: 600px; position: relative; background: url('{{ asset('img/travian/interface/map_background.jpg') }}') center/cover; border: 2px solid #8B4513;">

            <!-- Grid Overlay -->
            @if ($showGrid)
                <div class="map-grid" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                    <!-- Grid lines would be rendered here via JavaScript -->
                </div>
            @endif

            <!-- Coordinate Selection Overlay -->
            <div class="coordinate-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                <!-- Coordinate selection would be handled via JavaScript -->
            </div>

            @foreach ($visibleVillages as $village)
                <div class="village-marker {{ $this->getVillageColor($village) }}"
                     style="position: absolute; 
                            left: {{ ($village['x'] - $viewCenter['x'] + 200) * $zoomLevel }}px; 
                            top: {{ ($village['y'] - $viewCenter['y'] + 200) * $zoomLevel }}px;
                            transform: translate(-50%, -50%);"
                     wire:click="selectVillage({{ $village['id'] }})"
                     title="{{ $village['name'] }} {{ $this->getCoordinateDisplay($village['x'], $village['y']) }}">

                    <img src="{{ asset('img/travian/interface/village_' . $village['tribe'] . '.png') }}"
                         alt="{{ $village['name'] }}"
                         class="village-icon"
                         style="width: {{ 20 * $zoomLevel }}px; height: {{ 20 * $zoomLevel }}px;">

                    @if ($showVillageNames)
                        <div class="village-name" style="font-size: {{ 10 * $zoomLevel }}px;">
                            {{ $village['name'] }}
                        </div>
                    @endif

                    @if ($showCoordinates)
                        <div class="village-coords" style="font-size: {{ 8 * $zoomLevel }}px;">
                            {{ $this->getCoordinateDisplay($village['x'], $village['y']) }}
                        </div>
                    @endif

                    @if ($showAlliances && $village['alliance_name'])
                        <div class="alliance-tag" style="font-size: {{ 8 * $zoomLevel }}px;">
                            [{{ $village['alliance_tag'] }}]
                        </div>
                    @endif

                    @if ($showDistance && $selectedVillage)
                        @php
                            $distance = $this->getVillageDistance($village['id']);
                        @endphp
                        @if ($distance)
                            <div class="village-distance" style="font-size: {{ 7 * $zoomLevel }}px;">
                                {{ number_format($distance, 1) }}
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach

            <!-- Selected Coordinates Marker -->
            @if ($selectedCoordinates)
                <div class="coordinate-marker"
                     style="position: absolute; 
                            left: {{ ($selectedCoordinates['x'] - $viewCenter['x'] + 200) * $zoomLevel }}px; 
                            top: {{ ($selectedCoordinates['y'] - $viewCenter['y'] + 200) * $zoomLevel }}px;
                            transform: translate(-50%, -50%);
                            width: {{ 10 * $zoomLevel }}px; 
                            height: {{ 10 * $zoomLevel }}px;
                            background: red; 
                            border: 2px solid white; 
                            border-radius: 50%;">
                </div>
            @endif
        </div>
    @endif

    <!-- Map Statistics -->
    <div class="map-stats mt-3">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Villages</h6>
                        <p class="card-text">{{ count($mapData) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="card-title">Visible Villages</h6>
                        <p class="card-text">{{ count($visibleVillages) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="card-title">Map Center</h6>
                        <p class="card-text">{{ $this->getCoordinateDisplay($viewCenter['x'], $viewCenter['y']) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="card-title">Zoom Level</h6>
                        <p class="card-text">{{ $zoomLevel }}x</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($selectedVillage)
        <div class="village-details mt-3">
            <div class="card">
                <div class="card-header">
                    <h4>Selected Village</h4>
                </div>
                <div class="card-body">
            <div class="village-info">
                <div class="info-row">
                    <span class="label">Name:</span>
                    <span class="value">{{ $selectedVillage['name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Coordinates:</span>
                    <span class="value">{{ $this->getCoordinateDisplay($selectedVillage['x'], $selectedVillage['y']) }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Population:</span>
                    <span class="value">{{ number_format($selectedVillage['population']) }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Player:</span>
                    <span class="value">{{ $selectedVillage['player_name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Tribe:</span>
                    <span class="value">{{ ucfirst($selectedVillage['tribe']) }}</span>
                </div>
                @if ($selectedVillage['alliance_name'])
                    <div class="info-row">
                        <span class="label">Alliance:</span>
                        <span class="value">{{ $selectedVillage['alliance_name'] }}</span>
                    </div>
                @endif
                <div class="info-row">
                    <span class="label">Capital:</span>
                    <span class="value">{{ $selectedVillage['is_capital'] ? 'Yes' : 'No' }}</span>
                </div>
            </div>

            <div class="village-actions">
                <button wire:click="centerOnVillage({{ $selectedVillage['id'] }})" class="btn btn-sm btn-primary">
                    <i class="fas fa-crosshairs"></i> Center on Village
                </button>
                <button wire:click="selectCoordinates({{ $selectedVillage['x'] }}, {{ $selectedVillage['y'] }})" class="btn btn-sm btn-secondary">
                    <i class="fas fa-map-marker-alt"></i> Select Coordinates
                </button>
            </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Map Theme Styles -->
    <style>
        .map-theme-classic {
            background: linear-gradient(45deg, #8B4513, #A0522D);
        }
        
        .map-theme-modern {
            background: linear-gradient(45deg, #2C3E50, #34495E);
        }
        
        .map-theme-dark {
            background: linear-gradient(45deg, #1a1a1a, #2d2d2d);
        }
        
        .village-marker.highlight {
            filter: drop-shadow(0 0 10px #ffd700);
        }
        
        .village-marker.capital {
            filter: drop-shadow(0 0 8px #ff6b6b);
        }
        
        .village-marker.alliance {
            filter: drop-shadow(0 0 6px #4ecdc4);
        }
        
        .coordinate-marker {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .map-grid {
            background-image: 
                linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>

    @if (count($notifications) > 0)
        <div class="map-notifications">
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
</div>

<style>
    .world-map-container {
        background: #f5f5f5;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .map-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #8B4513;
    }

    .map-title {
        color: #8B4513;
        font-weight: bold;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .map-icon {
        width: 24px;
        height: 24px;
    }

    .map-controls {
        display: flex;
        gap: 10px;
    }

    .map-filters {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        padding: 15px;
        background: #fff;
        border-radius: 5px;
        border: 1px solid #ddd;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-label {
        font-weight: bold;
        color: #8B4513;
        margin: 0;
    }

    .map-navigation {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        justify-content: center;
    }

    .map-info {
        margin-bottom: 20px;
    }

    .map-stats {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #666;
        font-size: 14px;
    }

    .map-loading {
        text-align: center;
        padding: 50px;
        color: #666;
    }

    .map-viewport {
        border-radius: 5px;
        overflow: hidden;
        position: relative;
    }

    .village-marker {
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .village-marker:hover {
        transform: translate(-50%, -50%) scale(1.2);
    }

    .village-icon {
        border-radius: 50%;
        border: 2px solid #8B4513;
    }

    .village-name {
        color: #8B4513;
        font-weight: bold;
        text-align: center;
        margin-top: 2px;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    }

    .village-coords {
        color: #666;
        text-align: center;
        margin-top: 1px;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    }

    .alliance-tag {
        color: #0066cc;
        text-align: center;
        margin-top: 1px;
        font-weight: bold;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    }

    .village-details {
        margin-top: 20px;
        padding: 20px;
        background: #fff;
        border-radius: 5px;
        border: 1px solid #ddd;
    }

    .village-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        margin-bottom: 15px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid #eee;
    }

    .label {
        font-weight: bold;
        color: #8B4513;
    }

    .value {
        color: #333;
    }

    .village-actions {
        display: flex;
        gap: 10px;
    }

    .map-notifications {
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .map-header {
            flex-direction: column;
            gap: 15px;
        }

        .map-filters {
            flex-direction: column;
            gap: 15px;
        }

        .map-stats {
            flex-direction: column;
            gap: 10px;
        }

        .village-info {
            grid-template-columns: 1fr;
        }
    }
</style>
