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
        <div class="map-viewport"
             style="width: 100%; height: 600px; position: relative; background: url('{{ asset('img/travian/interface/map_background.jpg') }}') center/cover; border: 2px solid #8B4513;">

            @foreach ($visibleVillages as $village)
                <div class="village-marker"
                     style="position: absolute; 
                            left: {{ ($village['x'] - $viewCenter['x'] + 200) * $zoomLevel }}px; 
                            top: {{ ($village['y'] - $viewCenter['y'] + 200) * $zoomLevel }}px;
                            transform: translate(-50%, -50%);"
                     wire:click="selectVillage({{ $village['id'] }})"
                     title="{{ $village['name'] }} ({{ $village['x'] }}, {{ $village['y'] }})">

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
                            ({{ $village['x'] }}, {{ $village['y'] }})
                        </div>
                    @endif

                    @if ($showAlliances && $village['alliance_name'])
                        <div class="alliance-tag" style="font-size: {{ 8 * $zoomLevel }}px;">
                            [{{ $village['alliance_tag'] }}]
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($selectedVillage)
        <div class="village-details">
            <h4>Selected Village</h4>
            <div class="village-info">
                <div class="info-row">
                    <span class="label">Name:</span>
                    <span class="value">{{ $selectedVillage['name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Coordinates:</span>
                    <span class="value">({{ $selectedVillage['x'] }}, {{ $selectedVillage['y'] }})</span>
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
            </div>
        </div>
    @endif

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
