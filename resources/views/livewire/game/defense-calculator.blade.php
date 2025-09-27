<div class="max-w-6xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Defense Calculator</h2>
        
        @if($village)
            <!-- Village Information -->
            <div class="bg-blue-50 p-4 rounded-lg mb-6">
                <h3 class="text-lg font-semibold mb-2">Village: {{ $village->name }}</h3>
                <p class="text-sm text-gray-600">Population: {{ $village->population }} | Coordinates: ({{ $village->x }}, {{ $village->y }})</p>
            </div>

            <!-- Defense Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-green-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-green-800">Defensive Bonus</h4>
                    <p class="text-2xl font-bold text-green-600">
                        {{ number_format($defenseReport['defensive_bonus'] * 100, 1) }}%
                    </p>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-purple-800">Spy Defense</h4>
                    <p class="text-2xl font-bold text-purple-600">
                        {{ $defenseReport['spy_defense'] }}%
                    </p>
                </div>
                
                <div class="bg-orange-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-orange-800">Resource Protection</h4>
                    <p class="text-2xl font-bold text-orange-600">
                        {{ number_format($defenseReport['resource_protection'] * 100, 1) }}%
                    </p>
                </div>
            </div>

            <!-- Building Details -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Defense Buildings</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Building</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Defense Bonus</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($defenseReport['building_details'] as $building)
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $building['building_name'] }}</td>
                                    <td class="px-4 py-2">{{ $building['level'] }}</td>
                                    <td class="px-4 py-2">{{ number_format($building['defense_bonus'], 3) }}</td>
                                    <td class="px-4 py-2">{{ number_format($building['defense_percentage'], 1) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-center text-gray-500">No defense buildings found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recommendations -->
            @if(!empty($recommendations))
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Defense Recommendations</h3>
                    <div class="space-y-3">
                        @foreach($recommendations as $recommendation)
                            <div class="flex items-center p-3 rounded-lg 
                                {{ $recommendation['priority'] === 'high' ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200' }}">
                                <div class="flex-1">
                                    <h4 class="font-medium 
                                        {{ $recommendation['priority'] === 'high' ? 'text-red-800' : 'text-yellow-800' }}">
                                        {{ ucfirst($recommendation['type']) }} (Level {{ $recommendation['current_level'] }})
                                    </h4>
                                    <p class="text-sm text-gray-600">{{ $recommendation['message'] }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-medium 
                                        {{ $recommendation['priority'] === 'high' ? 'text-red-600' : 'text-yellow-600' }}">
                                        Priority: {{ ucfirst($recommendation['priority']) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Building Upgrade Simulation -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Upgrade Simulation</h3>
                <div class="flex gap-4 mb-4">
                    <select wire:model="selectedBuilding" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Building</option>
                        @foreach($buildingTypes as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <button wire:click="simulateBuildingUpgrade" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Simulate Upgrades
                    </button>
                </div>

                @if(!empty($simulationResults))
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Building Bonus</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total Defense</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($simulationResults as $result)
                                    <tr class="border-t">
                                        <td class="px-4 py-2">{{ $result['level'] }}</td>
                                        <td class="px-4 py-2">{{ number_format($result['percentage'], 1) }}%</td>
                                        <td class="px-4 py-2 font-medium">{{ number_format($result['total_defense'], 1) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Additional Bonuses -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold mb-2">Training Speed Bonus</h4>
                    <p class="text-lg font-bold text-gray-700">
                        {{ number_format($defenseReport['training_speed_bonus'] * 100, 1) }}%
                    </p>
                    <p class="text-sm text-gray-600">From Barracks level</p>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold mb-2">Production Bonuses</h4>
                    <div class="space-y-1 text-sm">
                        @foreach(['wood', 'clay', 'iron', 'crop'] as $resource)
                            @php
                                $bonus = $defenseService->calculateProductionBonus($village, $resource);
                            @endphp
                            <div class="flex justify-between">
                                <span class="capitalize">{{ $resource }}:</span>
                                <span class="font-medium">{{ number_format($bonus * 100, 1) }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        @else
            <div class="text-center py-8">
                <p class="text-gray-500 mb-4">No village selected</p>
                <p class="text-sm text-gray-400">Please select a village to view defense calculations</p>
            </div>
        @endif
    </div>
</div>

