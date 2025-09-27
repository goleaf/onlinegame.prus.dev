<div class="max-w-7xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Training Queue Manager</h1>
            <div class="text-sm text-gray-600">
                Village: {{ $village->name }} ({{ $village->x }}|{{ $village->y }})
            </div>
        </div>

        <!-- Notifications -->
        @if (count($notifications) > 0)
            <div class="mb-6 space-y-2">
                @foreach ($notifications as $index => $notification)
                    <div class="p-3 rounded-md {{ $notification['type'] === 'success' ? 'bg-green-100 text-green-700' : ($notification['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700') }}">
                        <div class="flex justify-between items-center">
                            <span>{{ $notification['message'] }}</span>
                            <button wire:click="removeNotification({{ $index }})" 
                                    wire:confirm="Are you sure you want to dismiss this notification?"
                                    class="text-gray-500 hover:text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Training Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ $trainingStats['active_queues'] }}</div>
                <div class="text-sm text-gray-600">Active Queues</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ $trainingStats['total_units_training'] }}</div>
                <div class="text-sm text-gray-600">Units Training</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">
                    @if ($trainingStats['next_completion'])
                        {{ $trainingStats['next_completion']->diffForHumans() }}
                    @else
                        None
                    @endif
                </div>
                <div class="text-sm text-gray-600">Next Completion</div>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-orange-600">{{ $village->population }}</div>
                <div class="text-sm text-gray-600">Population</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="switchTab('active')" 
                        class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Active Queues
                </button>
                <button wire:click="switchTab('completed')" 
                        class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'completed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Completed
                </button>
                <button wire:click="switchTab('cancelled')" 
                        class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'cancelled' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Cancelled
                </button>
            </nav>
        </div>

        <!-- Training Form -->
        @if ($activeTab === 'active')
            <div class="bg-gray-50 p-6 rounded-lg mb-6">
                <h2 class="text-xl font-semibold mb-4">Start New Training</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Unit Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Unit Type</label>
                        <select wire:model="selectedUnitType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Choose a unit type...</option>
                            @foreach ($unitTypes as $unitType)
                                <option value="{{ $unitType->id }}">{{ $unitType->name }} ({{ $unitType->tribe }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" 
                               wire:model="trainingQuantity" 
                               wire:change="updateQuantity"
                               min="1" 
                               max="1000" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                @if ($selectedUnitType)
                    <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Training Costs -->
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Training Costs</h3>
                            <div class="space-y-2">
                                @foreach ($trainingCost as $resource => $cost)
                                    <div class="flex justify-between">
                                        <span class="capitalize">{{ $resource }}:</span>
                                        <span class="font-medium">{{ number_format($cost) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Training Info -->
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Training Information</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Training Time:</span>
                                    <span class="font-medium">{{ gmdate('H:i:s', $trainingTime) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Can Train:</span>
                                    <span class="font-medium {{ $canTrain ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $canTrain ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Start Training Button -->
                    <div class="mt-6">
                        <button wire:click="startTraining" 
                                @disabled(!$canTrain)
                                class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors">
                            Start Training {{ $trainingQuantity }} {{ $selectedUnitType->name ?? 'Units' }}
                        </button>
                    </div>
                @endif
            </div>
        @endif

        <!-- Training Queues List -->
        <div class="bg-white">
            <h2 class="text-xl font-semibold mb-4">
                @if ($activeTab === 'active')
                    Active Training Queues
                @elseif ($activeTab === 'completed')
                    Completed Training Queues
                @else
                    Cancelled Training Queues
                @endif
            </h2>

            @if ($queues->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                @if ($activeTab === 'active')
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($queues as $queue)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $queue->reference_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $queue->unitType->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($queue->count) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $queue->started_at->format('M j, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if ($queue->completed_at)
                                            {{ $queue->completed_at->format('M j, Y H:i') }}
                                        @else
                                            <span class="text-gray-500">In Progress</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $queue->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                               ($queue->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') }}">
                                            {{ ucfirst($queue->status) }}
                                        </span>
                                    </td>
                                    @if ($activeTab === 'active')
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button wire:click="cancelTraining({{ $queue->id }})" 
                                                    class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Are you sure you want to cancel this training? You will only get 50% of resources back.')">
                                                Cancel
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $queues->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-gray-500 text-lg">
                        @if ($activeTab === 'active')
                            No active training queues
                        @elseif ($activeTab === 'completed')
                            No completed training queues
                        @else
                            No cancelled training queues
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
