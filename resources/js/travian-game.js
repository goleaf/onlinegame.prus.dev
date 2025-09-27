// Travian Game JavaScript - Real-time features and game mechanics
// Enhanced with maximum Livewire capabilities and original Travian assets

// Import real-time features
import './travian-realtime.js';

// Game state management
window.TravianGame = {
    // Configuration
    config: {
        pollingInterval: 30000, // 30 seconds
        resourceUpdateInterval: 5000, // 5 seconds
        buildingUpdateInterval: 10000, // 10 seconds
        mapUpdateInterval: 15000, // 15 seconds
    },

    // State
    state: {
        isPolling: false,
        activeComponents: new Set(),
        notifications: [],
        gameData: {},
    },

    // Initialize the game
    init() {
        console.log('Travian Game initialized');
        this.setupEventListeners();
        this.startGlobalPolling();
        this.initializeComponents();
    },

    // Setup event listeners
    setupEventListeners() {
        // Listen for Livewire events
        document.addEventListener('livewire:init', () => {
            console.log('Livewire initialized');
        });

        // Listen for component updates
        document.addEventListener('livewire:update', (event) => {
            this.handleComponentUpdate(event);
        });

        // Listen for game events
        document.addEventListener('gameTickProcessed', (event) => {
            this.handleGameTick(event);
        });

        document.addEventListener('resources-updated', (event) => {
            this.handleResourceUpdate(event);
        });

        document.addEventListener('buildingCompleted', (event) => {
            this.handleBuildingCompleted(event);
        });

        document.addEventListener('villageUpdated', (event) => {
            this.handleVillageUpdate(event);
        });
    },

    // Start global polling
    startGlobalPolling() {
        if (this.state.isPolling) return;

        this.state.isPolling = true;

        // Poll for game updates
        setInterval(() => {
            this.pollGameUpdates();
        }, this.config.pollingInterval);

        console.log('Global polling started');
    },

    // Stop global polling
    stopGlobalPolling() {
        this.state.isPolling = false;
        console.log('Global polling stopped');
    },

    // Poll for game updates
    async pollGameUpdates() {
        try {
            const response = await fetch('/api/game/status', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.updateGameState(data);
            }
        } catch (error) {
            console.error('Failed to poll game updates:', error);
        }
    },

    // Update game state
    updateGameState(data) {
        this.state.gameData = { ...this.state.gameData, ...data };

        // Dispatch updates to active components
        this.state.activeComponents.forEach(component => {
            if (component.updateGameData) {
                component.updateGameData(data);
            }
        });
    },

    // Handle component updates
    handleComponentUpdate(event) {
        const component = event.detail.component;

        if (component.$wire) {
            this.state.activeComponents.add(component.$wire);
        }
    },

    // Handle game tick
    handleGameTick(event) {
        console.log('Game tick processed:', event.detail);
        this.showNotification('Game tick processed', 'info');
    },

    // Handle resource updates
    handleResourceUpdate(event) {
        console.log('Resources updated:', event.detail);
        this.animateResourceUpdate();
    },

    // Handle building completion
    handleBuildingCompleted(event) {
        console.log('Building completed:', event.detail);
        this.showNotification(`Building completed: ${event.detail.building_name}`, 'success');
        this.animateBuildingCompletion();
    },

    // Handle village updates
    handleVillageUpdate(event) {
        console.log('Village updated:', event.detail);
        this.showNotification('Village updated', 'info');
    },

    // Animate resource update
    animateResourceUpdate() {
        const resourceElements = document.querySelectorAll('.resource-item');

        resourceElements.forEach(element => {
            element.classList.add('bounce-in');
            setTimeout(() => {
                element.classList.remove('bounce-in');
            }, 600);
        });
    },

    // Animate building completion
    animateBuildingCompletion() {
        const buildingElements = document.querySelectorAll('.building-slot.occupied');

        buildingElements.forEach(element => {
            element.classList.add('bounce-in');
            setTimeout(() => {
                element.classList.remove('bounce-in');
            }, 600);
        });
    },

    // Show notification
    showNotification(message, type = 'info') {
        const notification = {
            id: Date.now(),
            message,
            type,
            timestamp: new Date()
        };

        this.state.notifications.push(notification);

        // Create notification element
        this.createNotificationElement(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            this.removeNotification(notification.id);
        }, 5000);
    },

    // Create notification element
    createNotificationElement(notification) {
        const container = document.getElementById('notification-container') || this.createNotificationContainer();

        const element = document.createElement('div');
        element.className = `travian-notification ${notification.type} fade-in`;
        element.setAttribute('data-notification-id', notification.id);

        element.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span>${notification.message}</span>
                <button type="button" class="btn-close" onclick="TravianGame.removeNotification(${notification.id})"></button>
            </div>
        `;

        container.appendChild(element);
    },

    // Create notification container
    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;

        document.body.appendChild(container);
        return container;
    },

    // Remove notification
    removeNotification(id) {
        // Remove from state
        this.state.notifications = this.state.notifications.filter(n => n.id !== id);

        // Remove from DOM
        const element = document.querySelector(`[data-notification-id="${id}"]`);
        if (element) {
            element.classList.add('fade-out');
            setTimeout(() => {
                element.remove();
            }, 300);
        }
    },

    // Initialize components
    initializeComponents() {
        // Initialize resource manager
        this.initializeResourceManager();

        // Initialize village manager
        this.initializeVillageManager();

        // Initialize world map
        this.initializeWorldMap();

        // Initialize game dashboard
        this.initializeGameDashboard();
    },

    // Initialize resource manager
    initializeResourceManager() {
        const resourceManager = document.querySelector('[wire\\:id*="resource-manager"]');
        if (resourceManager) {
            this.setupResourcePolling(resourceManager);
        }
    },

    // Setup resource polling
    setupResourcePolling(component) {
        const interval = this.config.resourceUpdateInterval;

        setInterval(() => {
            if (component && component.wire) {
                component.wire.call('updateResources');
            }
        }, interval);
    },

    // Initialize village manager
    initializeVillageManager() {
        const villageManager = document.querySelector('[wire\\:id*="village-manager"]');
        if (villageManager) {
            this.setupVillagePolling(villageManager);
        }
    },

    // Setup village polling
    setupVillagePolling(component) {
        const interval = this.config.buildingUpdateInterval;

        setInterval(() => {
            if (component && component.wire) {
                component.wire.call('handleBuildingProgress');
            }
        }, interval);
    },

    // Initialize world map
    initializeWorldMap() {
        const worldMap = document.querySelector('[wire\\:id*="world-map"]');
        if (worldMap) {
            this.setupMapPolling(worldMap);
            this.initializeMapControls();
        }
    },

    // Setup map polling
    setupMapPolling(component) {
        const interval = this.config.mapUpdateInterval;

        setInterval(() => {
            if (component && component.wire) {
                component.wire.call('refreshMap');
            }
        }, interval);
    },

    // Initialize map controls
    initializeMapControls() {
        // Add keyboard controls
        document.addEventListener('keydown', (event) => {
            const worldMap = document.querySelector('[wire\\:id*="world-map"]');
            if (!worldMap) return;

            switch (event.key) {
                case 'ArrowUp':
                    worldMap.wire.call('moveMap', 'north');
                    break;
                case 'ArrowDown':
                    worldMap.wire.call('moveMap', 'south');
                    break;
                case 'ArrowLeft':
                    worldMap.wire.call('moveMap', 'west');
                    break;
                case 'ArrowRight':
                    worldMap.wire.call('moveMap', 'east');
                    break;
                case '+':
                case '=':
                    worldMap.wire.call('zoomIn');
                    break;
                case '-':
                    worldMap.wire.call('zoomOut');
                    break;
            }
        });
    },

    // Initialize game dashboard
    initializeGameDashboard() {
        const gameDashboard = document.querySelector('[wire\\:id*="game-dashboard"]');
        if (gameDashboard) {
            this.setupDashboardPolling(gameDashboard);
        }
    },

    // Setup dashboard polling
    setupDashboardPolling(component) {
        const interval = this.config.pollingInterval;

        setInterval(() => {
            if (component && component.wire) {
                component.wire.call('refreshGameData');
            }
        }, interval);
    },

    // Utility functions
    utils: {
        // Format numbers with commas
        formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        // Format time duration
        formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            if (hours > 0) {
                return `${hours}h ${minutes}m ${secs}s`;
            } else if (minutes > 0) {
                return `${minutes}m ${secs}s`;
            } else {
                return `${secs}s`;
            }
        },

        // Calculate distance between coordinates
        calculateDistance(x1, y1, x2, y2) {
            return Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
        },

        // Generate random ID
        generateId() {
            return Math.random().toString(36).substr(2, 9);
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    TravianGame.init();
});

// Add CSS for fade-out animation
const style = document.createElement('style');
style.textContent = `
    .fade-out {
        animation: fadeOut 0.3s ease-out forwards;
    }
    
    @keyframes fadeOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100%); }
    }
`;
document.head.appendChild(style);

// Export for use in other scripts
window.TravianGameUtils = TravianGame.utils;