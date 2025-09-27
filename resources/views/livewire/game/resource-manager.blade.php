<div>
    <div class="resource-manager">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                <i class="fas fa-coins"></i> Resource Manager
            </h4>
            <div class="d-flex gap-2">
                <button 
                    wire:click="toggleAutoUpdate" 
                    class="btn btn-sm {{ $autoUpdate ? 'btn-success' : 'btn-secondary' }}"
                >
                    <i class="fas fa-{{ $autoUpdate ? 'play' : 'pause' }}"></i>
                    {{ $autoUpdate ? 'Auto Update ON' : 'Auto Update OFF' }}
                </button>
                <button 
                    wire:click="updateResources" 
                    class="btn btn-sm btn-primary"
                >
                    <i class="fas fa-sync"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Resource Display -->
        <div class="row">
            <div class="col-md-3">
                <div class="resource-card wood">
                    <div class="resource-icon">
                        <i class="fas fa-tree"></i>
                    </div>
                    <div class="resource-info">
                        <h6>Wood</h6>
                        <div class="resource-amount">
                            <span class="current">{{ number_format($resources['wood']) }}</span>
                            <span class="capacity">/ {{ number_format($capacities['wood']) }}</span>
                        </div>
                        <div class="production-rate">
                            <i class="fas fa-arrow-up text-success"></i>
                            +{{ $productionRates['wood'] }}/sec
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="resource-card clay">
                    <div class="resource-icon">
                        <i class="fas fa-mountain"></i>
                    </div>
                    <div class="resource-info">
                        <h6>Clay</h6>
                        <div class="resource-amount">
                            <span class="current">{{ number_format($resources['clay']) }}</span>
                            <span class="capacity">/ {{ number_format($capacities['clay']) }}</span>
                        </div>
                        <div class="production-rate">
                            <i class="fas fa-arrow-up text-success"></i>
                            +{{ $productionRates['clay'] }}/sec
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="resource-card iron">
                    <div class="resource-icon">
                        <i class="fas fa-hammer"></i>
                    </div>
                    <div class="resource-info">
                        <h6>Iron</h6>
                        <div class="resource-amount">
                            <span class="current">{{ number_format($resources['iron']) }}</span>
                            <span class="capacity">/ {{ number_format($capacities['iron']) }}</span>
                        </div>
                        <div class="production-rate">
                            <i class="fas fa-arrow-up text-success"></i>
                            +{{ $productionRates['iron'] }}/sec
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="resource-card crop">
                    <div class="resource-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div class="resource-info">
                        <h6>Crop</h6>
                        <div class="resource-amount">
                            <span class="current">{{ number_format($resources['crop']) }}</span>
                            <span class="capacity">/ {{ number_format($capacities['crop']) }}</span>
                        </div>
                        <div class="production-rate">
                            <i class="fas fa-arrow-up text-success"></i>
                            +{{ $productionRates['crop'] }}/sec
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resource Progress Bars -->
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="progress-container">
                    <label>Wood Storage</label>
                    <div class="progress">
                        <div 
                            class="progress-bar bg-success" 
                            style="width: {{ $capacities['wood'] > 0 ? ($resources['wood'] / $capacities['wood']) * 100 : 0 }}%"
                        ></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="progress-container">
                    <label>Clay Storage</label>
                    <div class="progress">
                        <div 
                            class="progress-bar bg-warning" 
                            style="width: {{ $capacities['clay'] > 0 ? ($resources['clay'] / $capacities['clay']) * 100 : 0 }}%"
                        ></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="progress-container">
                    <label>Iron Storage</label>
                    <div class="progress">
                        <div 
                            class="progress-bar bg-secondary" 
                            style="width: {{ $capacities['iron'] > 0 ? ($resources['iron'] / $capacities['iron']) * 100 : 0 }}%"
                        ></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="progress-container">
                    <label>Crop Storage</label>
                    <div class="progress">
                        <div 
                            class="progress-bar bg-success" 
                            style="width: {{ $capacities['crop'] > 0 ? ($resources['crop'] / $capacities['crop']) * 100 : 0 }}%"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Update Info -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="text-center text-muted">
                    <small>
                        <i class="fas fa-clock"></i> 
                        Last updated: {{ $lastUpdate ? $lastUpdate->diffForHumans() : 'Never' }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <style>
        .resource-manager {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .resource-card {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            border: 2px solid #3498db;
            border-radius: 12px;
            padding: 15px;
            transition: all 0.3s ease;
            height: 100%;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }

        .resource-card.wood {
            border-color: #8B4513;
        }

        .resource-card.clay {
            border-color: #CD853F;
        }

        .resource-card.iron {
            border-color: #708090;
        }

        .resource-card.crop {
            border-color: #32CD32;
        }

        .resource-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            text-align: center;
        }

        .resource-card.wood .resource-icon {
            color: #8B4513;
        }

        .resource-card.clay .resource-icon {
            color: #CD853F;
        }

        .resource-card.iron .resource-icon {
            color: #708090;
        }

        .resource-card.crop .resource-icon {
            color: #32CD32;
        }

        .resource-info h6 {
            margin-bottom: 8px;
            font-weight: bold;
        }

        .resource-amount {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .resource-amount .current {
            font-weight: bold;
            color: #fff;
        }

        .resource-amount .capacity {
            color: #bdc3c7;
        }

        .production-rate {
            font-size: 0.9rem;
            color: #27ae60;
        }

        .progress-container {
            margin-bottom: 10px;
        }

        .progress-container label {
            font-size: 0.9rem;
            color: #bdc3c7;
            margin-bottom: 5px;
            display: block;
        }

        .progress {
            height: 8px;
            background: #2c3e50;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.3s ease;
        }
    </style>

    <script>
        // Auto-refresh resources every 5 seconds
        setInterval(() => {
            @this.call('processTick');
        }, 5000);
    </script>
</div>