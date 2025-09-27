<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Battle Simulator</h2>
        
        <!-- Village Selection -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Attacker Village -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Attacker Village</h3>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Village ID</label>
                    <input type="number" wire:model="attackerVillageId" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button wire:click="loadAttackerVillage" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Load Attacker Village
                    </button>
                </div>
                
                @if($attackerVillage)
                    <div class="mt-4 p-3 bg-white rounded border">
                        <h4 class="font-medium">{{ $attackerVillage->name }}</h4>
                        <p class="text-sm text-gray-600">Population: {{ $attackerVillage->population }}</p>
                        <p class="text-sm text-gray-600">Coordinates: ({{ $attackerVillage->x }}, {{ $attackerVillage->y }})</p>
                    </div>
                @endif
            </div>

            <!-- Defender Village -->
            <div class="bg-red-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Defender Village</h3>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Village ID</label>
                    <input type="number" wire:model="defenderVillageId" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    <button wire:click="loadDefenderVillage" 
                            class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                        Load Defender Village
                    </button>
                </div>
                
                @if($defenderVillage)
                    <div class="mt-4 p-3 bg-white rounded border">
                        <h4 class="font-medium">{{ $defenderVillage->name }}</h4>
                        <p class="text-sm text-gray-600">Population: {{ $defenderVillage->population }}</p>
                        <p class="text-sm text-gray-600">Coordinates: ({{ $defenderVillage->x }}, {{ $defenderVillage->y }})</p>
                        
                        @if($defenseReport)
                            <div class="mt-2 text-xs">
                                <span class="text-green-600">Defense: {{ number_format($defenseReport['defensive_bonus'] * 100, 1) }}%</span>
                                <span class="text-purple-600 ml-2">Spy Defense: {{ $defenseReport['spy_defense'] }}%</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Simulation Controls -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h3 class="text-lg font-semibold mb-4">Simulation Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Iterations</label>
                    <input type="number" wire:model="iterations" min="100" max="10000" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Troops (Optimization)</label>
                    <input type="number" wire:model="totalTroops" min="1" max="1000" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end space-x-2">
                    <button wire:click="runSimulation" 
                            class="flex-1 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Run Simulation
                    </button>
                    <button wire:click="optimizeTroopComposition" 
                            class="flex-1 bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                        Optimize
                    </button>
                </div>
            </div>
        </div>

        <!-- Attacking Troops -->
        @if(!empty($attackingTroops))
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Attacking Troops</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Attack Power</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total Power</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attackingTroops as $index => $troop)
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $troop['unit_type'] }}</td>
                                    <td class="px-4 py-2">
                                        <input type="number" wire:model="attackingTroops.{{ $index }}.count" 
                                               min="0" class="w-20 px-2 py-1 border border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-4 py-2">{{ $troop['attack'] }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $troop['count'] * $troop['attack'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Simulation Results -->
        @if($simulationResults)
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Simulation Results ({{ $iterations }} iterations)</h3>
                
                <!-- Win Rates -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <h4 class="font-semibold text-green-800">Attacker Wins</h4>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($simulationResults['attacker_win_rate'], 1) }}%</p>
                        <p class="text-sm text-gray-600">{{ $simulationResults['attacker_wins'] }} battles</p>
                    </div>
                    
                    <div class="bg-red-50 p-4 rounded-lg text-center">
                        <h4 class="font-semibold text-red-800">Defender Wins</h4>
                        <p class="text-2xl font-bold text-red-600">{{ number_format($simulationResults['defender_win_rate'], 1) }}%</p>
                        <p class="text-sm text-gray-600">{{ $simulationResults['defender_wins'] }} battles</p>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                        <h4 class="font-semibold text-gray-800">Draws</h4>
                        <p class="text-2xl font-bold text-gray-600">{{ number_format($simulationResults['draw_rate'], 1) }}%</p>
                        <p class="text-sm text-gray-600">{{ $simulationResults['draws'] }} battles</p>
                    </div>
                </div>

                <!-- Battle Power Statistics -->
                <div class="bg-blue-50 p-4 rounded-lg mb-6">
                    <h4 class="font-semibold mb-3">Battle Power Statistics</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Attacker Avg</p>
                            <p class="font-bold">{{ number_format($simulationResults['battle_power_stats']['attacker_avg'], 0) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Defender Avg</p>
                            <p class="font-bold">{{ number_format($simulationResults['battle_power_stats']['defender_avg'], 0) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Defensive Bonus</p>
                            <p class="font-bold">{{ number_format($simulationResults['defensive_bonus'] * 100, 1) }}%</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Power Range</p>
                            <p class="font-bold text-xs">
                                A: {{ number_format($simulationResults['battle_power_stats']['attacker_min']) }}-{{ number_format($simulationResults['battle_power_stats']['attacker_max']) }}<br>
                                D: {{ number_format($simulationResults['battle_power_stats']['defender_min']) }}-{{ number_format($simulationResults['battle_power_stats']['defender_max']) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Average Losses -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="font-semibold mb-3">Attacker Average Losses</h4>
                        @forelse($simulationResults['attacker_avg_losses'] as $unitType => $losses)
                            <div class="flex justify-between text-sm">
                                <span>{{ $unitType }}:</span>
                                <span class="font-medium">{{ $losses }}</span>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">No losses</p>
                        @endforelse
                    </div>
                    
                    <div class="bg-orange-50 p-4 rounded-lg">
                        <h4 class="font-semibold mb-3">Defender Average Losses</h4>
                        @forelse($simulationResults['defender_avg_losses'] as $unitType => $losses)
                            <div class="flex justify-between text-sm">
                                <span>{{ $unitType }}:</span>
                                <span class="font-medium">{{ $losses }}</span>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">No losses</p>
                        @endforelse
                    </div>
                </div>

                <!-- Average Resources Looted -->
                @if(!empty($simulationResults['avg_resources_looted']))
                    <div class="bg-green-50 p-4 rounded-lg mt-6">
                        <h4 class="font-semibold mb-3">Average Resources Looted</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($simulationResults['avg_resources_looted'] as $resource => $amount)
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 capitalize">{{ $resource }}</p>
                                    <p class="font-bold">{{ number_format($amount) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Optimization Results -->
        @if($showOptimization && $optimizationResults)
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Optimal Troop Composition</h3>
                <div class="bg-purple-50 p-4 rounded-lg mb-4">
                    <div class="text-center">
                        <h4 class="font-semibold text-purple-800">Best Win Rate</h4>
                        <p class="text-2xl font-bold text-purple-600">{{ number_format($optimizationResults['win_rate'], 1) }}%</p>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Attack Power</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total Power</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($optimizationResults['composition'] as $troop)
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $troop['unit_type'] }}</td>
                                    <td class="px-4 py-2">{{ $troop['count'] }}</td>
                                    <td class="px-4 py-2">{{ $troop['attack'] }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $troop['count'] * $troop['attack'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Battle History Analysis -->
        @if($defenderVillage && $battleHistory)
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Battle History Analysis (Last 30 days)</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <h4 class="font-semibold text-blue-800">Total Battles</h4>
                        <p class="text-2xl font-bold text-blue-600">{{ $battleHistory['total_battles'] }}</p>
                    </div>
                    
                    <div class="bg-red-50 p-4 rounded-lg text-center">
                        <h4 class="font-semibold text-red-800">Attacks Received</h4>
                        <p class="text-2xl font-bold text-red-600">{{ $battleHistory['attacks_received'] }}</p>
                    </div>
                    
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <h4 class="font-semibold text-green-800">Defense Success</h4>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($battleHistory['defense_success_rate'], 1) }}%</p>
                    </div>
                    
                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                        <h4 class="font-semibold text-purple-800">Avg Defense Bonus</h4>
                        <p class="text-2xl font-bold text-purple-600">{{ number_format($battleHistory['average_defensive_bonus'] * 100, 1) }}%</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Recommendations -->
        @if(!empty($recommendations))
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Battle Recommendations</h3>
                <div class="space-y-3">
                    @foreach($recommendations as $recommendation)
                        <div class="flex items-center p-3 rounded-lg 
                            {{ $recommendation['priority'] === 'high' ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200' }}">
                            <div class="flex-1">
                                <h4 class="font-medium 
                                    {{ $recommendation['priority'] === 'high' ? 'text-red-800' : 'text-yellow-800' }}">
                                    {{ ucfirst($recommendation['type']) }} Issue
                                </h4>
                                <p class="text-sm text-gray-600">{{ $recommendation['message'] }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-medium 
                                    {{ $recommendation['priority'] === 'high' ? 'text-red-600' : 'text-yellow-600' }}">
                                    {{ ucfirst($recommendation['priority']) }} Priority
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

