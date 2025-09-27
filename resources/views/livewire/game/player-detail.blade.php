<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Player Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ $player->name }}</h1>
                    <p class="text-blue-100 mt-2">
                        Level {{ $playerStats['level'] }} • {{ ucfirst($playerStats['tribe']) }} • {{ $playerStats['alliance_name'] }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold">{{ number_format($playerStats['total_points']) }} Points</div>
                    <div class="text-blue-100">Rank #{{ number_format($playerStats['total_rank']) }}</div>
                </div>
            </div>
        </div>

        <!-- Player Stats Grid -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ $playerStats['total_villages'] }}</div>
                    <div class="text-gray-600">Villages</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ $playerStats['level'] }}</div>
                    <div class="text-gray-600">Level</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">{{ number_format($playerStats['total_points']) }}</div>
                    <div class="text-gray-600">Total Points</div>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600">{{ $playerStats['last_active'] }}</div>
                    <div class="text-gray-600">Last Active</div>
                </div>
            </div>

            <!-- Villages Section -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold mb-4">Villages</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($villages as $village)
                        <div class="bg-gray-50 p-4 rounded-lg border">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold">{{ $village->name }}</h3>
                                @if($village->is_capital)
                                    <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Capital</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-600">
                                Coordinates: ({{ $village->coordinates->x ?? 'N/A' }}, {{ $village->coordinates->y ?? 'N/A' }})
                            </div>
                            <div class="text-sm text-gray-600">
                                Points: {{ number_format($village->points) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Battles Section -->
            @if($recentBattles->count() > 0)
                <div class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Recent Battles</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full bg-white border border-gray-200 rounded-lg">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Attacker</th>
                                    <th class="px-4 py-3 text-left">Defender</th>
                                    <th class="px-4 py-3 text-left">Village</th>
                                    <th class="px-4 py-3 text-left">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentBattles as $battle)
                                    <tr class="border-t">
                                        <td class="px-4 py-3">
                                            <span class="{{ $battle->attacker_id === $player->id ? 'font-bold text-blue-600' : '' }}">
                                                {{ $battle->attacker->name }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="{{ $battle->defender_id === $player->id ? 'font-bold text-blue-600' : '' }}">
                                                {{ $battle->defender->name }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">{{ $battle->village->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ $battle->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Recent Movements Section -->
            @if($recentMovements->count() > 0)
                <div class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Recent Movements</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full bg-white border border-gray-200 rounded-lg">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Type</th>
                                    <th class="px-4 py-3 text-left">From</th>
                                    <th class="px-4 py-3 text-left">To</th>
                                    <th class="px-4 py-3 text-left">Arrival</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentMovements as $movement)
                                    <tr class="border-t">
                                        <td class="px-4 py-3">
                                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                                {{ ucfirst($movement->type) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">{{ $movement->originVillage->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ $movement->targetVillage->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ $movement->arrival_at ? $movement->arrival_at->diffForHumans() : 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>