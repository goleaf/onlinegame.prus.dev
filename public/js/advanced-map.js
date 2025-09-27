class AdvancedMapManager {
    constructor() {
        this.canvas = null;
        this.ctx = null;
        this.villages = [];
        this.centerX = 500;
        this.centerY = 500;
        this.radius = 20;
        this.realWorldMode = false;
        this.selectedVillage = null;
        this.scale = 1;
        this.offsetX = 0;
        this.offsetY = 0;
        this.isDragging = false;
        this.lastMouseX = 0;
        this.lastMouseY = 0;
        
        // Color scheme
        this.colors = {
            player: '#10B981',      // Green
            alliance: '#F59E0B',    // Yellow
            enemy: '#EF4444',       // Red
            abandoned: '#6B7280',   // Gray
            neutral: '#3B82F6',     // Blue
            capital: '#8B5CF6',     // Purple
        };
    }

    init() {
        this.canvas = document.getElementById('gameMap');
        if (!this.canvas) {
            console.error('Canvas element not found');
            return;
        }

        this.ctx = this.canvas.getContext('2d');
        this.setupEventListeners();
        this.setupControls();
        this.render();
    }

    setupEventListeners() {
        // Mouse events
        this.canvas.addEventListener('mousedown', (e) => this.handleMouseDown(e));
        this.canvas.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        this.canvas.addEventListener('mouseup', (e) => this.handleMouseUp(e));
        this.canvas.addEventListener('wheel', (e) => this.handleWheel(e));
        this.canvas.addEventListener('click', (e) => this.handleClick(e));

        // Livewire events
        window.addEventListener('mapUpdated', (e) => this.handleMapUpdate(e.detail));
        window.addEventListener('villageSelected', (e) => this.handleVillageSelection(e.detail));
        window.addEventListener('worldChanged', (e) => this.handleWorldChange(e.detail));
        window.addEventListener('mapModeChanged', (e) => this.handleMapModeChange(e.detail));
    }

    setupControls() {
        // World selection
        const worldSelect = document.getElementById('worldSelect');
        if (worldSelect) {
            worldSelect.addEventListener('change', (e) => {
                this.centerX = 500;
                this.centerY = 500;
                this.radius = 20;
                this.updateLivewireData();
            });
        }

        // Center coordinates
        const centerXInput = document.getElementById('centerX');
        const centerYInput = document.getElementById('centerY');
        if (centerXInput && centerYInput) {
            centerXInput.addEventListener('change', () => this.updateLivewireData());
            centerYInput.addEventListener('change', () => this.updateLivewireData());
        }

        // Radius slider
        const radiusSlider = document.getElementById('radiusSlider');
        const radiusValue = document.getElementById('radiusValue');
        if (radiusSlider && radiusValue) {
            radiusSlider.addEventListener('input', (e) => {
                this.radius = parseInt(e.target.value);
                radiusValue.textContent = this.radius;
                this.updateLivewireData();
            });
        }

        // Filter selection
        const filterSelect = document.getElementById('filterSelect');
        if (filterSelect) {
            filterSelect.addEventListener('change', () => this.updateLivewireData());
        }

        // Toggle buttons
        const toggleRealWorld = document.getElementById('toggleRealWorld');
        const refreshMap = document.getElementById('refreshMap');
        
        if (toggleRealWorld) {
            toggleRealWorld.addEventListener('click', () => {
                this.realWorldMode = !this.realWorldMode;
                this.updateLivewireData();
            });
        }

        if (refreshMap) {
            refreshMap.addEventListener('click', () => {
                this.refreshMap();
            });
        }
    }

    handleMouseDown(e) {
        this.isDragging = true;
        this.lastMouseX = e.clientX;
        this.lastMouseY = e.clientY;
        this.canvas.style.cursor = 'grabbing';
    }

    handleMouseMove(e) {
        if (this.isDragging) {
            const deltaX = e.clientX - this.lastMouseX;
            const deltaY = e.clientY - this.lastMouseY;
            
            this.offsetX += deltaX;
            this.offsetY += deltaY;
            
            this.lastMouseX = e.clientX;
            this.lastMouseY = e.clientY;
            
            this.render();
        }
    }

    handleMouseUp(e) {
        this.isDragging = false;
        this.canvas.style.cursor = 'grab';
    }

    handleWheel(e) {
        e.preventDefault();
        
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        const newScale = Math.max(0.1, Math.min(5, this.scale * delta));
        
        // Zoom towards mouse position
        const rect = this.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        
        const worldX = (mouseX - this.offsetX) / this.scale;
        const worldY = (mouseY - this.offsetY) / this.scale;
        
        this.offsetX = mouseX - worldX * newScale;
        this.offsetY = mouseY - worldY * newScale;
        this.scale = newScale;
        
        this.render();
    }

    handleClick(e) {
        const rect = this.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        
        // Convert screen coordinates to world coordinates
        const worldX = (mouseX - this.offsetX) / this.scale;
        const worldY = (mouseY - this.offsetY) / this.scale;
        
        // Find village at click position
        const clickedVillage = this.findVillageAt(worldX, worldY);
        if (clickedVillage) {
            this.selectVillage(clickedVillage);
        } else {
            // Center map on clicked position
            this.centerX = Math.round(worldX);
            this.centerY = Math.round(worldY);
            this.updateLivewireData();
        }
    }

    findVillageAt(x, y) {
        const tolerance = 5 / this.scale; // 5 pixel tolerance
        
        for (const village of this.villages) {
            const distance = Math.sqrt(
                Math.pow(village.x_coordinate - x, 2) + 
                Math.pow(village.y_coordinate - y, 2)
            );
            
            if (distance <= tolerance) {
                return village;
            }
        }
        
        return null;
    }

    selectVillage(village) {
        this.selectedVillage = village;
        
        // Update village info panel
        this.updateVillageInfo(village);
        
        // Dispatch event to Livewire
        window.dispatchEvent(new CustomEvent('villageSelected', {
            detail: { village: village }
        }));
        
        this.render();
    }

    updateVillageInfo(village) {
        const villageInfo = document.getElementById('villageInfo');
        const villageDetails = document.getElementById('villageDetails');
        
        if (villageInfo && villageDetails) {
            villageDetails.innerHTML = `
                <div class="space-y-2">
                    <div><strong>Name:</strong> ${village.name}</div>
                    <div><strong>Coordinates:</strong> (${village.x_coordinate}, ${village.y_coordinate})</div>
                    <div><strong>Player:</strong> ${village.player_name}</div>
                    <div><strong>Population:</strong> ${village.population.toLocaleString()}</div>
                    <div><strong>Buildings:</strong> ${village.building_count}</div>
                    <div><strong>Troops:</strong> ${village.troop_count}</div>
                    <div><strong>Resources:</strong> ${village.total_resources.toLocaleString()}</div>
                    ${village.is_capital ? '<div class="text-purple-600 font-semibold">Capital Village</div>' : ''}
                </div>
            `;
            villageInfo.classList.remove('hidden');
        }
    }

    handleMapUpdate(data) {
        this.villages = data.villages || [];
        this.centerX = data.center?.x || 500;
        this.centerY = data.center?.y || 500;
        this.radius = data.radius || 20;
        this.realWorldMode = data.realWorldMode || false;
        
        this.updateOverlay();
        this.render();
    }

    handleVillageSelection(data) {
        this.selectedVillage = data.village;
        this.updateVillageInfo(data.village);
        this.render();
    }

    handleWorldChange(data) {
        // Reset view when world changes
        this.centerX = 500;
        this.centerY = 500;
        this.radius = 20;
        this.scale = 1;
        this.offsetX = 0;
        this.offsetY = 0;
        this.selectedVillage = null;
        
        // Hide village info
        const villageInfo = document.getElementById('villageInfo');
        if (villageInfo) {
            villageInfo.classList.add('hidden');
        }
        
        this.render();
    }

    handleMapModeChange(data) {
        this.realWorldMode = data.mode === 'real_world';
        this.updateOverlay();
        this.render();
    }

    updateOverlay() {
        const mapMode = document.getElementById('mapMode');
        const mapCenter = document.getElementById('mapCenter');
        const mapRadius = document.getElementById('mapRadius');
        const villageCount = document.getElementById('villageCount');
        
        if (mapMode) mapMode.textContent = this.realWorldMode ? 'Real World' : 'Game Coordinates';
        if (mapCenter) mapCenter.textContent = `${this.centerX}, ${this.centerY}`;
        if (mapRadius) mapRadius.textContent = this.radius;
        if (villageCount) villageCount.textContent = this.villages.length;
    }

    updateLivewireData() {
        // Update Livewire component data
        if (window.Livewire) {
            window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).set('centerX', this.centerX);
            window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).set('centerY', this.centerY);
            window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).set('radius', this.radius);
            window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).set('realWorldMode', this.realWorldMode);
        }
    }

    refreshMap() {
        if (window.Livewire) {
            window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('refreshMap');
        }
    }

    getVillageColor(village) {
        // Determine village color based on player relationship
        // This would need to be enhanced with actual player data
        if (village.population === 0) return this.colors.abandoned;
        if (village.is_capital) return this.colors.capital;
        
        // For now, use a simple color scheme
        const colors = [this.colors.player, this.colors.alliance, this.colors.enemy, this.colors.neutral];
        return colors[village.player_id % colors.length];
    }

    render() {
        if (!this.ctx) return;
        
        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Save context
        this.ctx.save();
        
        // Apply transformations
        this.ctx.translate(this.offsetX, this.offsetY);
        this.ctx.scale(this.scale, this.scale);
        
        // Draw grid
        this.drawGrid();
        
        // Draw radius circle
        this.drawRadiusCircle();
        
        // Draw villages
        this.drawVillages();
        
        // Draw selected village highlight
        if (this.selectedVillage) {
            this.drawSelectedVillage();
        }
        
        // Restore context
        this.ctx.restore();
    }

    drawGrid() {
        const gridSize = 50;
        const startX = Math.floor(-this.offsetX / this.scale / gridSize) * gridSize;
        const startY = Math.floor(-this.offsetY / this.scale / gridSize) * gridSize;
        const endX = startX + Math.ceil(this.canvas.width / this.scale / gridSize) * gridSize;
        const endY = startY + Math.ceil(this.canvas.height / this.scale / gridSize) * gridSize;
        
        this.ctx.strokeStyle = '#E5E7EB';
        this.ctx.lineWidth = 1 / this.scale;
        this.ctx.beginPath();
        
        // Vertical lines
        for (let x = startX; x <= endX; x += gridSize) {
            this.ctx.moveTo(x, startY);
            this.ctx.lineTo(x, endY);
        }
        
        // Horizontal lines
        for (let y = startY; y <= endY; y += gridSize) {
            this.ctx.moveTo(startX, y);
            this.ctx.lineTo(endX, y);
        }
        
        this.ctx.stroke();
    }

    drawRadiusCircle() {
        this.ctx.strokeStyle = '#3B82F6';
        this.ctx.lineWidth = 2 / this.scale;
        this.ctx.setLineDash([5 / this.scale, 5 / this.scale]);
        this.ctx.beginPath();
        this.ctx.arc(this.centerX, this.centerY, this.radius, 0, 2 * Math.PI);
        this.ctx.stroke();
        this.ctx.setLineDash([]);
    }

    drawVillages() {
        for (const village of this.villages) {
            this.drawVillage(village);
        }
    }

    drawVillage(village) {
        const x = village.x_coordinate;
        const y = village.y_coordinate;
        const radius = Math.max(2, 8 / this.scale);
        
        // Village circle
        this.ctx.fillStyle = this.getVillageColor(village);
        this.ctx.beginPath();
        this.ctx.arc(x, y, radius, 0, 2 * Math.PI);
        this.ctx.fill();
        
        // Village border
        this.ctx.strokeStyle = '#FFFFFF';
        this.ctx.lineWidth = 1 / this.scale;
        this.ctx.stroke();
        
        // Capital marker
        if (village.is_capital) {
            this.ctx.fillStyle = '#FFFFFF';
            this.ctx.beginPath();
            this.ctx.arc(x, y, radius * 0.6, 0, 2 * Math.PI);
            this.ctx.fill();
        }
        
        // Village name (if zoomed in enough)
        if (this.scale > 0.5) {
            this.ctx.fillStyle = '#000000';
            this.ctx.font = `${12 / this.scale}px Arial`;
            this.ctx.textAlign = 'center';
            this.ctx.fillText(village.name, x, y - radius - 5 / this.scale);
        }
    }

    drawSelectedVillage() {
        if (!this.selectedVillage) return;
        
        const x = this.selectedVillage.x_coordinate;
        const y = this.selectedVillage.y_coordinate;
        const radius = Math.max(2, 12 / this.scale);
        
        // Selection ring
        this.ctx.strokeStyle = '#F59E0B';
        this.ctx.lineWidth = 3 / this.scale;
        this.ctx.beginPath();
        this.ctx.arc(x, y, radius, 0, 2 * Math.PI);
        this.ctx.stroke();
        
        // Selection crosshair
        this.ctx.strokeStyle = '#F59E0B';
        this.ctx.lineWidth = 2 / this.scale;
        this.ctx.beginPath();
        this.ctx.moveTo(x - radius, y);
        this.ctx.lineTo(x + radius, y);
        this.ctx.moveTo(x, y - radius);
        this.ctx.lineTo(x, y + radius);
        this.ctx.stroke();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const mapManager = new AdvancedMapManager();
    mapManager.init();
});
