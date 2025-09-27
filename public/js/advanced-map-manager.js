class AdvancedMapManager {
    constructor() {
        this.canvas = null;
        this.ctx = null;
        this.villages = [];
        this.centerX = 0;
        this.centerY = 0;
        this.radius = 20;
        this.mapMode = 'game';
        this.selectedVillage = null;
        this.showRealWorld = false;
        this.cellSize = 20;
        this.offsetX = 0;
        this.offsetY = 0;
    }

    init() {
        this.canvas = document.getElementById('gameMap');
        this.ctx = this.canvas.getContext('2d');
        
        this.setupEventListeners();
        this.loadMapData();
        this.drawMap();
    }

    setupEventListeners() {
        // World selection
        const worldSelect = document.getElementById('worldSelect');
        if (worldSelect) {
            worldSelect.addEventListener('change', (e) => {
                this.updateWorld(e.target.value);
            });
        }

        // Center coordinates
        const centerXInput = document.getElementById('centerX');
        if (centerXInput) {
            centerXInput.addEventListener('change', (e) => {
                this.centerX = parseInt(e.target.value) || 0;
                this.updateMap();
            });
        }

        const centerYInput = document.getElementById('centerY');
        if (centerYInput) {
            centerYInput.addEventListener('change', (e) => {
                this.centerY = parseInt(e.target.value) || 0;
                this.updateMap();
            });
        }

        // Radius slider
        const radiusSlider = document.getElementById('radiusSlider');
        const radiusValue = document.getElementById('radiusValue');
        
        if (radiusSlider && radiusValue) {
            radiusSlider.addEventListener('input', (e) => {
                this.radius = parseInt(e.target.value);
                radiusValue.textContent = this.radius;
                this.updateMap();
            });
        }

        // Filter selection
        const filterSelect = document.getElementById('filterSelect');
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                this.updateFilter(e.target.value);
            });
        }

        // Toggle buttons
        const toggleRealWorldBtn = document.getElementById('toggleRealWorld');
        if (toggleRealWorldBtn) {
            toggleRealWorldBtn.addEventListener('click', () => {
                this.toggleRealWorld();
            });
        }

        const refreshMapBtn = document.getElementById('refreshMap');
        if (refreshMapBtn) {
            refreshMapBtn.addEventListener('click', () => {
                this.refreshMap();
            });
        }

        // Canvas click events
        if (this.canvas) {
            this.canvas.addEventListener('click', (e) => {
                this.handleCanvasClick(e);
            });

            // Canvas mouse move events
            this.canvas.addEventListener('mousemove', (e) => {
                this.handleCanvasMouseMove(e);
            });
        }
    }

    loadMapData() {
        const mapData = document.getElementById('map-data');
        if (mapData) {
            this.villages = JSON.parse(mapData.dataset.villages || '[]');
            this.centerX = parseInt(mapData.dataset.centerX) || 0;
            this.centerY = parseInt(mapData.dataset.centerY) || 0;
            this.radius = parseInt(mapData.dataset.radius) || 20;
            this.mapMode = mapData.dataset.mapMode || 'game';
            
            // Update UI elements
            const centerXInput = document.getElementById('centerX');
            const centerYInput = document.getElementById('centerY');
            const radiusSlider = document.getElementById('radiusSlider');
            const radiusValue = document.getElementById('radiusValue');
            
            if (centerXInput) centerXInput.value = this.centerX;
            if (centerYInput) centerYInput.value = this.centerY;
            if (radiusSlider) radiusSlider.value = this.radius;
            if (radiusValue) radiusValue.textContent = this.radius;
            
            this.updateStatistics();
        }
    }

    updateStatistics() {
        const mapData = document.getElementById('map-data');
        if (mapData) {
            const totalVillages = document.getElementById('totalVillages');
            const myVillages = document.getElementById('myVillages');
            const allianceVillages = document.getElementById('allianceVillages');
            const enemyVillages = document.getElementById('enemyVillages');
            
            if (totalVillages) totalVillages.textContent = mapData.dataset.totalVillages || 0;
            if (myVillages) myVillages.textContent = mapData.dataset.myVillages || 0;
            if (allianceVillages) allianceVillages.textContent = mapData.dataset.allianceVillages || 0;
            if (enemyVillages) enemyVillages.textContent = mapData.dataset.enemyVillages || 0;
        }
    }

    drawMap() {
        if (!this.ctx) return;

        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Calculate grid dimensions
        const gridSize = this.radius * 2 + 1;
        const cellWidth = this.canvas.width / gridSize;
        const cellHeight = this.canvas.height / gridSize;

        // Draw grid
        this.drawGrid(cellWidth, cellHeight, gridSize);

        // Draw villages
        this.drawVillages(cellWidth, cellHeight, gridSize);

        // Draw center marker
        this.drawCenterMarker(cellWidth, cellHeight, gridSize);

        // Update overlay
        this.updateOverlay();
    }

    drawGrid(cellWidth, cellHeight, gridSize) {
        this.ctx.strokeStyle = '#e5e7eb';
        this.ctx.lineWidth = 1;

        // Vertical lines
        for (let i = 0; i <= gridSize; i++) {
            const x = i * cellWidth;
            this.ctx.beginPath();
            this.ctx.moveTo(x, 0);
            this.ctx.lineTo(x, this.canvas.height);
            this.ctx.stroke();
        }

        // Horizontal lines
        for (let i = 0; i <= gridSize; i++) {
            const y = i * cellHeight;
            this.ctx.beginPath();
            this.ctx.moveTo(0, y);
            this.ctx.lineTo(this.canvas.width, y);
            this.ctx.stroke();
        }
    }

    drawVillages(cellWidth, cellHeight, gridSize) {
        this.villages.forEach(village => {
            const gridX = village.x - (this.centerX - this.radius);
            const gridY = village.y - (this.centerY - this.radius);

            if (gridX >= 0 && gridX < gridSize && gridY >= 0 && gridY < gridSize) {
                const x = gridX * cellWidth + cellWidth / 2;
                const y = gridY * cellHeight + cellHeight / 2;

                // Determine village color
                let color = '#6b7280'; // Default gray
                if (village.is_my_village) {
                    color = '#10b981'; // Green
                } else if (village.is_alliance_village) {
                    color = '#3b82f6'; // Blue
                } else if (village.player_name !== 'Abandoned') {
                    color = '#ef4444'; // Red
                } else {
                    color = '#9ca3af'; // Light gray
                }

                // Draw village circle
                this.ctx.fillStyle = color;
                this.ctx.beginPath();
                this.ctx.arc(x, y, Math.min(cellWidth, cellHeight) * 0.3, 0, 2 * Math.PI);
                this.ctx.fill();

                // Draw village border
                this.ctx.strokeStyle = '#ffffff';
                this.ctx.lineWidth = 2;
                this.ctx.stroke();

                // Draw village name (abbreviated)
                this.ctx.fillStyle = '#ffffff';
                this.ctx.font = '10px Arial';
                this.ctx.textAlign = 'center';
                this.ctx.fillText(village.name.substring(0, 2), x, y + 3);
            }
        });
    }

    drawCenterMarker(cellWidth, cellHeight, gridSize) {
        const centerGridX = this.radius;
        const centerGridY = this.radius;
        const x = centerGridX * cellWidth + cellWidth / 2;
        const y = centerGridY * cellHeight + cellHeight / 2;

        // Draw center cross
        this.ctx.strokeStyle = '#dc2626';
        this.ctx.lineWidth = 3;
        
        // Horizontal line
        this.ctx.beginPath();
        this.ctx.moveTo(x - 10, y);
        this.ctx.lineTo(x + 10, y);
        this.ctx.stroke();

        // Vertical line
        this.ctx.beginPath();
        this.ctx.moveTo(x, y - 10);
        this.ctx.lineTo(x, y + 10);
        this.ctx.stroke();
    }

    updateOverlay() {
        const mapMode = document.getElementById('mapMode');
        const mapCenter = document.getElementById('mapCenter');
        const mapRadius = document.getElementById('mapRadius');
        const villageCount = document.getElementById('villageCount');
        
        if (mapMode) mapMode.textContent = this.showRealWorld ? 'Real World Coordinates' : 'Game Coordinates';
        if (mapCenter) mapCenter.textContent = `${this.centerX}, ${this.centerY}`;
        if (mapRadius) mapRadius.textContent = this.radius;
        if (villageCount) villageCount.textContent = this.villages.length;
    }

    handleCanvasClick(e) {
        const rect = this.canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const gridSize = this.radius * 2 + 1;
        const cellWidth = this.canvas.width / gridSize;
        const cellHeight = this.canvas.height / gridSize;

        const gridX = Math.floor(x / cellWidth);
        const gridY = Math.floor(y / cellHeight);

        const worldX = this.centerX - this.radius + gridX;
        const worldY = this.centerY - this.radius + gridY;

        // Find village at this position
        const village = this.villages.find(v => v.x === worldX && v.y === worldY);
        
        if (village) {
            this.showVillageInfo(village);
        } else {
            this.hideVillageInfo();
        }
    }

    handleCanvasMouseMove(e) {
        // Add hover effects here if needed
    }

    showVillageInfo(village) {
        const infoPanel = document.getElementById('villageInfo');
        const detailsDiv = document.getElementById('villageDetails');

        if (infoPanel && detailsDiv) {
            detailsDiv.innerHTML = `
                <div class="space-y-2">
                    <div><strong>Name:</strong> ${village.name}</div>
                    <div><strong>Coordinates:</strong> (${village.x}|${village.y})</div>
                    <div><strong>Player:</strong> ${village.player_name}</div>
                    ${village.alliance_name ? `<div><strong>Alliance:</strong> ${village.alliance_name}</div>` : ''}
                    <div><strong>Population:</strong> ${village.population.toLocaleString()}</div>
                    <div><strong>Distance:</strong> ${village.distance_from_center.toFixed(1)} units</div>
                    <div><strong>Bearing:</strong> ${village.bearing_from_center.toFixed(0)}°</div>
                    ${village.latitude ? `<div><strong>Latitude:</strong> ${village.latitude.toFixed(6)}°</div>` : ''}
                    ${village.longitude ? `<div><strong>Longitude:</strong> ${village.longitude.toFixed(6)}°</div>` : ''}
                    ${village.geohash ? `<div><strong>Geohash:</strong> ${village.geohash}</div>` : ''}
                    ${village.elevation ? `<div><strong>Elevation:</strong> ${village.elevation}m</div>` : ''}
                </div>
            `;

            infoPanel.classList.remove('hidden');
        }
    }

    hideVillageInfo() {
        const infoPanel = document.getElementById('villageInfo');
        if (infoPanel) {
            infoPanel.classList.add('hidden');
        }
    }

    updateWorld(worldId) {
        // This would trigger a Livewire update
        if (window.Livewire) {
            window.Livewire.emit('updateWorld', worldId);
        }
    }

    updateFilter(filter) {
        // This would trigger a Livewire update
        if (window.Livewire) {
            window.Livewire.emit('updateFilter', filter);
        }
    }

    toggleRealWorld() {
        this.showRealWorld = !this.showRealWorld;
        this.mapMode = this.showRealWorld ? 'real_world' : 'game';
        
        // This would trigger a Livewire update
        if (window.Livewire) {
            window.Livewire.emit('toggleRealWorld');
        }
        
        this.drawMap();
    }

    refreshMap() {
        // This would trigger a Livewire update
        if (window.Livewire) {
            window.Livewire.emit('refreshMap');
        }
    }

    updateMap() {
        // This would trigger a Livewire update
        if (window.Livewire) {
            window.Livewire.emit('updateMap', {
                centerX: this.centerX,
                centerY: this.centerY,
                radius: this.radius
            });
        }
    }
}

// Make it globally available
window.AdvancedMapManager = AdvancedMapManager;
