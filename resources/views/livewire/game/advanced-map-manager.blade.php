<div>
    <!-- Map Controls -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Map Controls</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- World Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">World</label>
                <select wire:model.live="selectedWorld" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">Select World</option>
                    @foreach($worlds as $world)
                        <option value="{{ $world->id }}">{{ $world->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Center Coordinates -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Center Coordinates</label>
                <div class="flex space-x-2">
                    <input type="number" wire:model.live="centerX" placeholder="X" class="w-1/2 border border-gray-300 rounded-md px-3 py-2">
                    <input type="number" wire:model.live="centerY" placeholder="Y" class="w-1/2 border border-gray-300 rounded-md px-3 py-2">
                </div>
            </div>

            <!-- Radius -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Radius: {{ $radius }}</label>
                <input type="range" wire:model.live="radius" min="5" max="50" class="w-full">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <!-- Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter</label>
                <select wire:model.live="filter" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">All Villages</option>
                    <option value="player">My Villages</option>
                    <option value="alliance">Alliance Villages</option>
                    <option value="enemy">Enemy Villages</option>
                    <option value="abandoned">Abandoned</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-end space-x-2">
                <button wire:click="refreshMap" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Refresh Map
                </button>
                <button wire:click="toggleRealWorldView" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                    {{ $realWorldMode ? 'Game View' : 'Real World View' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-blue-600">{{ $statistics['total_villages'] ?? 0 }}</div>
            <div class="text-sm text-blue-800">Total Villages</div>
        </div>
        
        <div class="bg-green-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-green-600">{{ $statistics['my_villages'] ?? 0 }}</div>
            <div class="text-sm text-green-800">My Villages</div>
        </div>
        
        <div class="bg-yellow-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-yellow-600">{{ $statistics['alliance_villages'] ?? 0 }}</div>
            <div class="text-sm text-yellow-800">Alliance Villages</div>
        </div>
        
        <div class="bg-red-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-red-600">{{ $statistics['enemy_villages'] ?? 0 }}</div>
            <div class="text-sm text-red-800">Enemy Villages</div>
        </div>
    </div>

    <!-- Selected Village Info -->
    @if($selectedVillage)
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Selected Village</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-semibold text-gray-700">{{ $selectedVillage['name'] }}</h4>
                    <p class="text-sm text-gray-600">Coordinates: ({{ $selectedVillage['x_coordinate'] }}, {{ $selectedVillage['y_coordinate'] }})</p>
                    <p class="text-sm text-gray-600">Player: {{ $selectedVillage['player_name'] }}</p>
                    <p class="text-sm text-gray-600">Population: {{ number_format($selectedVillage['population']) }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Buildings: {{ $selectedVillage['building_count'] }}</p>
                    <p class="text-sm text-gray-600">Troops: {{ $selectedVillage['troop_count'] }}</p>
                    <p class="text-sm text-gray-600">Resources: {{ number_format($selectedVillage['total_resources']) }}</p>
                    @if($selectedVillage['is_capital'])
                        <span class="inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">Capital</span>
                    @endif
                </div>
            </div>
            
            <div class="mt-4 flex space-x-2">
                <button wire:click="centerOnVillage({{ $selectedVillage['id'] }})" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                    Center on Village
                </button>
                <button wire:click="getVillageDetails({{ $selectedVillage['id'] }})" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                    Get Details
                </button>
                <button wire:click="findNearbyVillages({{ $selectedVillage['id'] }})" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                    Find Nearby
                </button>
            </div>
        </div>
    @endif

    <!-- Loading Indicator -->
    @if($isLoading)
        <div class="flex justify-center items-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <span class="ml-2 text-gray-600">Loading map data...</span>
        </div>
    @endif

    <!-- Map Data Debug (for development) -->
    @if(config('app.debug'))
        <div class="bg-gray-100 rounded-lg p-4 mt-6">
            <h4 class="font-semibold text-gray-700 mb-2">Debug Info</h4>
            <div class="text-xs text-gray-600">
                <p>Selected World: {{ $selectedWorld }}</p>
                <p>Center: ({{ $centerX }}, {{ $centerY }})</p>
                <p>Radius: {{ $radius }}</p>
                <p>Filter: {{ $filter ?: 'None' }}</p>
                <p>Real World Mode: {{ $realWorldMode ? 'Yes' : 'No' }}</p>
                <p>Villages Loaded: {{ count($villages) }}</p>
                <p>Map Data: {{ count($mapData) }}</p>
            </div>
        </div>
    @endif
</div>
