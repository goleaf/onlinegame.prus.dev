<div class="advanced-map-manager">
    <div class="map-container">
        <h2>Advanced Map Manager</h2>
        
        <div class="map-controls">
            <button wire:click="loadMapData" class="btn btn-primary">
                Load Map Data
            </button>
            
            <button wire:click="refreshGeographicData" class="btn btn-secondary">
                Refresh Geographic Data
            </button>
        </div>
        
        <div class="map-stats">
            <div class="stat-item">
                <span>World Size:</span>
                <span>{{ $worldSize ?? 'Loading...' }}</span>
            </div>
            
            <div class="stat-item">
                <span>Villages:</span>
                <span>{{ $villageCount ?? 'Loading...' }}</span>
            </div>
            
            <div class="stat-item">
                <span>Players:</span>
                <span>{{ $playerCount ?? 'Loading...' }}</span>
            </div>
        </div>
        
        @if($isLoading)
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading map data...</p>
            </div>
        @endif
        
        @if($error)
            <div class="error">
                <p>{{ $error }}</p>
            </div>
        @endif
    </div>
</div>