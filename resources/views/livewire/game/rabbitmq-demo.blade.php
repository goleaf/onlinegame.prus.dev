<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">RabbitMQ Integration Demo</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Configuration -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Configuration</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Player ID</label>
                        <input type="number" wire:model="playerId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Village ID</label>
                        <input type="number" wire:model="villageId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Game Events -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Game Events</h3>
                <div class="space-y-2">
                    <button wire:click="publishPlayerAction" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Publish Player Action
                    </button>
                    <button wire:click="publishBuildingEvent" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Publish Building Event
                    </button>
                    <button wire:click="publishBattleEvent" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Publish Battle Event
                    </button>
                    <button wire:click="publishResourceUpdate" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Publish Resource Update
                    </button>
                </div>
            </div>

            <!-- Notifications -->
            <div class="bg-green-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Notifications</h3>
                <div class="space-y-2">
                    <button wire:click="publishNotification" class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Publish In-Game Notification
                    </button>
                    <button wire:click="publishEmailNotification" class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Publish Email Notification
                    </button>
                </div>
            </div>

            <!-- Custom Events -->
            <div class="bg-purple-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Custom Events</h3>
                <div class="space-y-2">
                    <button wire:click="publishCustomEvent" class="w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                        Publish Custom Event
                    </button>
                </div>
            </div>
        </div>

        <!-- Messages Log -->
        <div class="mt-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Messages Log</h3>
                <button wire:click="clearMessages" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Clear
                </button>
            </div>
            
            <div class="bg-gray-900 text-green-400 p-4 rounded-lg h-64 overflow-y-auto font-mono text-sm">
                @if(empty($messages))
                    <div class="text-gray-500">No messages yet. Click the buttons above to publish events to RabbitMQ.</div>
                @else
                    @foreach($messages as $message)
                        <div class="mb-1">
                            <span class="text-gray-500">[{{ $message['timestamp'] }}]</span>
                            <span class="text-green-400">{{ $message['text'] }}</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Instructions -->
        <div class="mt-6 bg-yellow-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold mb-2">Instructions</h3>
            <div class="text-sm text-gray-700 space-y-2">
                <p>1. Make sure RabbitMQ server is running</p>
                <p>2. Start the consumer: <code class="bg-gray-200 px-2 py-1 rounded">php artisan amqp:consume default game_events</code></p>
                <p>3. Click the buttons above to publish events</p>
                <p>4. Check the consumer terminal for message processing</p>
                <p>5. Use <code class="bg-gray-200 px-2 py-1 rounded">php artisan rabbitmq:test</code> to run automated tests</p>
            </div>
        </div>
    </div>
</div>
