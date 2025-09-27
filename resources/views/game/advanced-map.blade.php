@extends('layouts.app')

@section('title', 'Advanced Map - Game')

@section('content')
<div class="min-h-screen bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Advanced Game Map</h1>
                <div class="flex space-x-4">
                    <button id="toggleRealWorld" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                        Toggle Real World View
                    </button>
                    <button id="refreshMap" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                        Refresh Map
                    </button>
                </div>
            </div>

            <!-- Map Controls -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">World</label>
                    <select id="worldSelect" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Select World</option>
                    </select>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Center Coordinates</label>
                    <div class="flex space-x-2">
                        <input type="number" id="centerX" placeholder="X" class="w-1/2 border border-gray-300 rounded-md px-3 py-2">
                        <input type="number" id="centerY" placeholder="Y" class="w-1/2 border border-gray-300 rounded-md px-3 py-2">
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Radius</label>
                    <input type="range" id="radiusSlider" min="5" max="50" value="20" class="w-full">
                    <span id="radiusValue" class="text-sm text-gray-600">20</span>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter</label>
                    <select id="filterSelect" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">All Villages</option>
                        <option value="player">My Villages</option>
                        <option value="alliance">Alliance Villages</option>
                        <option value="enemy">Enemy Villages</option>
                        <option value="abandoned">Abandoned</option>
                    </select>
                </div>
            </div>

            <!-- Map Container -->
            <div class="relative bg-gray-200 rounded-lg overflow-hidden" style="height: 600px;">
                <canvas id="gameMap" width="800" height="600" class="w-full h-full"></canvas>
                
                <!-- Map Overlay -->
                <div id="mapOverlay" class="absolute top-4 left-4 bg-black bg-opacity-75 text-white p-3 rounded-lg">
                    <div class="text-sm">
                        <div>Mode: <span id="mapMode">Game Coordinates</span></div>
                        <div>Center: <span id="mapCenter">0, 0</span></div>
                        <div>Radius: <span id="mapRadius">20</span></div>
                        <div>Villages: <span id="villageCount">0</span></div>
                    </div>
                </div>

                <!-- Village Info Panel -->
                <div id="villageInfo" class="absolute top-4 right-4 bg-white rounded-lg shadow-lg p-4 hidden" style="width: 300px;">
                    <h3 class="font-bold text-lg mb-2">Village Information</h3>
                    <div id="villageDetails" class="text-sm text-gray-600">
                        <!-- Village details will be populated here -->
                    </div>
                </div>
            </div>

            <!-- Statistics Panel -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600" id="totalVillages">0</div>
                    <div class="text-sm text-blue-800">Total Villages</div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600" id="myVillages">0</div>
                    <div class="text-sm text-green-800">My Villages</div>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600" id="allianceVillages">0</div>
                    <div class="text-sm text-yellow-800">Alliance Villages</div>
                </div>
                
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-600" id="enemyVillages">0</div>
                    <div class="text-sm text-red-800">Enemy Villages</div>
                </div>
            </div>
        </div>
    </div>
</div>

@livewire('game.advanced-map-manager')

@push('scripts')
<script src="{{ asset('js/advanced-map-manager.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the advanced map
    const mapManager = new AdvancedMapManager();
    mapManager.init();
});
</script>
@endpush
@endsection