<div>
    <div class="building-manager">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-hammer"></i> Building Manager
            </h4>
            <div class="building-stats">
                <span class="badge bg-primary">
                    <i class="fas fa-home"></i> Population: {{ $village->population ?? 0 }}
                </span>
            </div>
        </div>

        <!-- Building Grid -->
        <div class="building-grid">
            @foreach($availableBuildings as $type => $building)
                @php
                    $currentLevel = $buildings[$type]['level'] ?? 0;
                    $isBuilt = isset($buildings[$type]);
                @endphp
                
                <div class="building-card {{ $isBuilt ? 'built' : 'available' }}" 
                     wire:click="selectBuilding('{{ $type }}')">
                    <div class="building-icon">
                        @if($type === 'wood')
                            <i class="fas fa-tree"></i>
                        @elseif($type === 'clay')
                            <i class="fas fa-mountain"></i>
                        @elseif($type === 'iron')
                            <i class="fas fa-hammer"></i>
                        @elseif($type === 'crop')
                            <i class="fas fa-seedling"></i>
                        @elseif($type === 'warehouse')
                            <i class="fas fa-warehouse"></i>
                        @elseif($type === 'granary')
                            <i class="fas fa-bread-slice"></i>
                        @endif
                    </div>
                    
                    <div class="building-info">
                        <h6>{{ $building['name'] }}</h6>
                        <p class="building-description">{{ $building['description'] }}</p>
                        
                        @if($isBuilt)
                            <div class="building-level">
                                <span class="level-badge">Level {{ $currentLevel }}</span>
                            </div>
                        @else
                            <div class="building-status">
                                <span class="status-badge">Not Built</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="building-actions">
                        <button class="btn btn-sm {{ $isBuilt ? 'btn-warning' : 'btn-success' }}">
                            <i class="fas fa-{{ $isBuilt ? 'arrow-up' : 'plus' }}"></i>
                            {{ $isBuilt ? 'Upgrade' : 'Build' }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Construction Queue -->
        @if(count($constructionQueue) > 0)
            <div class="construction-queue mt-4">
                <h5><i class="fas fa-clock"></i> Construction Queue</h5>
                <div class="queue-items">
                    @foreach($constructionQueue as $item)
                        <div class="queue-item">
                            <span>{{ $item['building'] }}</span>
                            <span class="time-remaining">{{ $item['time_remaining'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Upgrade Modal -->
    @if($showUpgradeModal && $selectedBuilding)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content bg-dark">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-arrow-up"></i> 
                            {{ $isBuilt ? 'Upgrade' : 'Build' }} {{ $availableBuildings[$selectedBuilding]['name'] }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" 
                                wire:click="cancelUpgrade"
                                wire:confirm="Are you sure you want to cancel this upgrade? This action cannot be undone."></button>
                    </div>
                    <div class="modal-body">
                        <div class="upgrade-info">
                            <p>{{ $availableBuildings[$selectedBuilding]['description'] }}</p>
                            
                            @if($isBuilt)
                                <p><strong>Current Level:</strong> {{ $buildings[$selectedBuilding]['level'] ?? 0 }}</p>
                                <p><strong>New Level:</strong> {{ ($buildings[$selectedBuilding]['level'] ?? 0) + 1 }}</p>
                            @endif
                        </div>
                        
                        <div class="upgrade-costs">
                            <h6>Upgrade Costs:</h6>
                            <div class="cost-list">
                                @foreach($upgradeCosts as $resource => $cost)
                                    <div class="cost-item">
                                        <span class="resource-icon">
                                            @if($resource === 'wood')
                                                <i class="fas fa-tree"></i>
                                            @elseif($resource === 'clay')
                                                <i class="fas fa-mountain"></i>
                                            @elseif($resource === 'iron')
                                                <i class="fas fa-hammer"></i>
                                            @elseif($resource === 'crop')
                                                <i class="fas fa-seedling"></i>
                                            @endif
                                        </span>
                                        <span class="resource-name">{{ ucfirst($resource) }}</span>
                                        <span class="cost-amount">{{ number_format($cost) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" 
                                wire:click="cancelUpgrade"
                                wire:confirm="Are you sure you want to cancel this upgrade? This action cannot be undone.">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="upgradeBuilding">
                            <i class="fas fa-arrow-up"></i> {{ $isBuilt ? 'Upgrade' : 'Build' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .building-manager {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .building-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .building-card {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            border: 2px solid #3498db;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .building-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }

        .building-card.built {
            border-color: #27ae60;
        }

        .building-card.available {
            border-color: #f39c12;
        }

        .building-icon {
            font-size: 2.5rem;
            color: #3498db;
            min-width: 60px;
            text-align: center;
        }

        .building-card.built .building-icon {
            color: #27ae60;
        }

        .building-card.available .building-icon {
            color: #f39c12;
        }

        .building-info {
            flex: 1;
        }

        .building-info h6 {
            margin-bottom: 5px;
            font-weight: bold;
        }

        .building-description {
            font-size: 0.9rem;
            color: #bdc3c7;
            margin-bottom: 8px;
        }

        .level-badge {
            background: #27ae60;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .status-badge {
            background: #f39c12;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .building-actions {
            min-width: 80px;
        }

        .construction-queue {
            background: #34495e;
            border-radius: 8px;
            padding: 15px;
        }

        .queue-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #2c3e50;
        }

        .queue-item:last-child {
            border-bottom: none;
        }

        .time-remaining {
            color: #f39c12;
            font-weight: bold;
        }

        .upgrade-costs {
            margin-top: 15px;
        }

        .cost-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .cost-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            background: #2c3e50;
            border-radius: 6px;
        }

        .resource-icon {
            width: 20px;
            text-align: center;
        }

        .resource-name {
            flex: 1;
            font-weight: bold;
        }

        .cost-amount {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</div>