<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Advanced Map Viewer
        </h2>
        <div class="flex space-x-2">
            <button wire:click="toggleMapMode" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                {{ ucfirst($mapMode) }} Mode
            </button>
            <button wire:click="resetView" 
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                Reset View
            </button>
        </div>
    </div>

    <!-- Map Controls -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Coordinate Controls -->
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Coordinates</h3>
            <div class="space-y-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Center X</label>
                    <input type="number" wire:model.live="centerX" 
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Center Y</label>
                    <input type="number" wire:model.live="centerY" 
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Zoom</label>
                    <input type="range" min="1" max="5" wire:model.live="zoom" 
                           class="mt-1 block w-full">
                </div>
            </div>
        </div>

        <!-- Geographic Features -->
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Geographic Features</h3>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="showRealWorldCoordinates" 
                           class="rounded border-gray-300 dark:border-gray-600">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Real World Coords</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="showGeohash" 
                           class="rounded border-gray-300 dark:border-gray-600">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Geohash</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="showDistance" 
                           class="rounded border-gray-300 dark:border-gray-600">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Distance</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="showBearing" 
                           class="rounded border-gray-300 dark:border-gray-600">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Bearing</span>
                </label>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Filters</h3>
            <div class="space-y-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Radius Filter</label>
                    <input type="number" min="0" wire:model.live="radiusFilter" 
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Elevation Filter</label>
                    <input type="number" wire:model.live="elevationFilter" 
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Coordinate System</label>
                    <select wire:model.live="coordinateSystem" 
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="game">Game Coordinates</option>
                        <option value="decimal">Decimal Degrees</option>
                        <option value="dms">Degrees/Minutes/Seconds</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Display -->
    <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-{{ $mapSize }} gap-1" style="grid-template-columns: repeat({{ $mapSize }}, 1fr);">
            @for($y = $centerY - $mapSize/2; $y <= $centerY + $mapSize/2; $y++)
                @for($x = $centerX - $mapSize/2; $x <= $centerX + $mapSize/2; $x++)
                    @php
                        $village = $villages->firstWhere(function($v) use ($x, $y) {
                            return $v->x_coordinate == $x && $v->y_coordinate == $y;
                        });
                        $isCenter = $x == $centerX && $y == $centerY;
                        $distance = $isCenter ? 0 : $this->calculateDistance($x, $y);
                    @endphp
                    
                    <div class="aspect-square border border-gray-300 dark:border-gray-600 relative
                                {{ $isCenter ? 'bg-red-500' : ($village ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700') }}
                                hover:bg-blue-400 cursor-pointer transition-colors"
                         wire:click="selectVillage({{ $x }}, {{ $y }})"
                         title="X: {{ $x }}, Y: {{ $y }}{{ $village ? ' - ' . $village->name : '' }}">
                        
                        @if($village)
                            <div class="absolute inset-0 flex items-center justify-center text-xs text-white font-bold">
                                {{ substr($village->name, 0, 2) }}
                            </div>
                            
                            @if($showRealWorldCoordinates && $village->latitude && $village->longitude)
                                <div class="absolute top-0 left-0 text-xs bg-black bg-opacity-50 text-white p-1 rounded">
                                    {{ number_format($village->latitude, 4) }}, {{ number_format($village->longitude, 4) }}
                                </div>
                            @endif
                            
                            @if($showGeohash && $village->geohash)
                                <div class="absolute bottom-0 left-0 text-xs bg-black bg-opacity-50 text-white p-1 rounded">
                                    {{ substr($village->geohash, 0, 6) }}
                                </div>
                            @endif
                            
                            @if($showDistance && !$isCenter)
                                <div class="absolute top-0 right-0 text-xs bg-black bg-opacity-50 text-white p-1 rounded">
                                    {{ number_format($distance, 1) }}
                                </div>
                            @endif
                            
                            @if($showBearing && !$isCenter)
                                <div class="absolute bottom-0 right-0 text-xs bg-black bg-opacity-50 text-white p-1 rounded">
                                    {{ $this->calculateBearing($x, $y) }}°
                                </div>
                            @endif
                        @endif
                        
                        @if($isCenter)
                            <div class="absolute inset-0 flex items-center justify-center text-white font-bold">
                                C
                            </div>
                        @endif
                    </div>
                @endfor
            @endfor
        </div>
    </div>

    <!-- Village Information Panel -->
    @if($selectedVillage)
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Village Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Basic Info</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        <strong>Name:</strong> {{ $selectedVillage->name }}<br>
                        <strong>Player:</strong> {{ $selectedVillage->player->name ?? 'Unknown' }}<br>
                        <strong>Population:</strong> {{ number_format($selectedVillage->population) }}<br>
                        <strong>Coordinates:</strong> ({{ $selectedVillage->x_coordinate }}|{{ $selectedVillage->y_coordinate }})
                    </p>
                </div>
                
                @if($selectedVillage->latitude && $selectedVillage->longitude)
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">Geographic Info</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <strong>Latitude:</strong> {{ number_format($selectedVillage->latitude, 6) }}°<br>
                            <strong>Longitude:</strong> {{ number_format($selectedVillage->longitude, 6) }}°<br>
                            @if($selectedVillage->elevation)
                                <strong>Elevation:</strong> {{ number_format($selectedVillage->elevation, 2) }}m<br>
                            @endif
                            @if($selectedVillage->geohash)
                                <strong>Geohash:</strong> {{ $selectedVillage->geohash }}<br>
                            @endif
                            <strong>Distance from center:</strong> {{ number_format($this->calculateDistance($selectedVillage->x_coordinate, $selectedVillage->y_coordinate), 2) }} units
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Statistics -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
            <h4 class="font-semibold text-blue-900 dark:text-blue-100">Total Villages</h4>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $villages->count() }}</p>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
            <h4 class="font-semibold text-green-900 dark:text-green-100">With Coordinates</h4>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ $villages->where('latitude', '!=', null)->count() }}
            </p>
        </div>
        
        <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
            <h4 class="font-semibold text-purple-900 dark:text-purple-100">With Elevation</h4>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ $villages->where('elevation', '!=', null)->count() }}
            </p>
        </div>
        
        <div class="bg-orange-50 dark:bg-orange-900 p-4 rounded-lg">
            <h4 class="font-semibold text-orange-900 dark:text-orange-100">Map Coverage</h4>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                {{ number_format(($villages->count() / ($mapSize * $mapSize)) * 100, 1) }}%
            </p>
        </div>
    </div>
</div>



