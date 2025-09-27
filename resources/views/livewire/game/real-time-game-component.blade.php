<div class="bg-white rounded-lg shadow-md p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Real-Time Game Updates</h2>
            <p class="text-gray-600">Stay connected with live game events</p>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Connection Status -->
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full {{ $isConnected ? 'bg-green-500' : 'bg-red-500' }}"></div>
                <span class="text-sm {{ $connectionColor }}">{{ $connectionStatus }}</span>
            </div>
            
            <!-- Online Users -->
            <div class="text-sm text-gray-600">
                <span class="font-medium">{{ $onlineUsers }}</span> online users
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="flex flex-wrap gap-4 mb-6">
        <button wire:click="loadUpdates" 
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            🔄 Refresh Updates
        </button>
        
        <button wire:click="loadNotifications" 
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            🔔 Refresh Notifications
        </button>
        
        <button wire:click="sendTestMessage" 
                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
            🧪 Send Test Message
        </button>
        
        <button wire:click="toggleAutoRefresh" 
                class="px-4 py-2 {{ $autoRefresh ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white rounded-lg transition-colors">
            {{ $autoRefresh ? '⏸️ Stop Auto Refresh' : '▶️ Start Auto Refresh' }}
        </button>
    </div>

    <!-- Auto Refresh Settings -->
    @if($autoRefresh)
    <div class="mb-6 p-4 bg-blue-50 rounded-lg">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Refresh Interval (seconds)
        </label>
        <input type="number" 
               wire:model.live="refreshInterval" 
               min="5" 
               max="300" 
               class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <span class="ml-2 text-sm text-gray-600">Current: {{ $refreshInterval }}s</span>
    </div>
    @endif

    <!-- Last Update Info -->
    <div class="mb-6 text-sm text-gray-600">
        Last update: <span class="font-medium">{{ $formattedLastUpdate }}</span>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Real-Time Updates -->
        <div class="bg-gray-50 rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Live Updates</h3>
                <button wire:click="clearUpdates" 
                        class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition-colors">
                    Clear All
                </button>
            </div>
            
            @if(empty($updates))
                <div class="text-center py-8 text-gray-500">
                    <div class="text-4xl mb-2">📭</div>
                    <p>No updates available</p>
                </div>
            @else
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($updates as $update)
                        <div class="bg-white p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                            <div class="flex items-start space-x-3">
                                <div class="text-2xl">{{ $this->getUpdateIcon($update['event_type']) }}</div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-900 capitalize">
                                            {{ str_replace('_', ' ', $update['event_type']) }}
                                        </h4>
                                        <span class="text-xs text-gray-500">
                                            {{ $this->formatTimestamp($update['timestamp']) }}
                                        </span>
                                    </div>
                                    
                                    @if(!empty($update['data']))
                                        <div class="text-sm text-gray-600">
                                            @foreach($update['data'] as $key => $value)
                                                @if(is_array($value))
                                                    <div class="ml-2">
                                                        <strong>{{ ucfirst($key) }}:</strong>
                                                        <pre class="text-xs bg-gray-100 p-1 rounded mt-1">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @else
                                                    <div><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Notifications -->
        <div class="bg-gray-50 rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                <button wire:click="clearNotifications" 
                        class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition-colors">
                    Clear All
                </button>
            </div>
            
            @if(empty($notifications))
                <div class="text-center py-8 text-gray-500">
                    <div class="text-4xl mb-2">🔔</div>
                    <p>No notifications</p>
                </div>
            @else
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($notifications as $notification)
                        <div class="bg-white p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow {{ !$notification['read'] ? 'border-l-4 border-l-blue-500' : '' }}">
                            <div class="flex items-start space-x-3">
                                <div class="text-2xl">{{ $this->getNotificationIcon($notification['type']) }}</div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-900">{{ $notification['title'] }}</h4>
                                        <div class="flex items-center space-x-2">
                                            @if(!$notification['read'])
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">New</span>
                                            @endif
                                            <span class="px-2 py-1 {{ $this->getPriorityBadgeColor($notification['priority']) }} text-xs rounded-full">
                                                {{ ucfirst($notification['priority']) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 mb-2">
                                        {{ $this->formatTimestamp($notification['timestamp']) }}
                                    </p>
                                    
                                    @if(!empty($notification['data']))
                                        <div class="text-sm text-gray-600">
                                            @foreach($notification['data'] as $key => $value)
                                                @if(is_array($value))
                                                    <div class="ml-2">
                                                        <strong>{{ ucfirst($key) }}:</strong>
                                                        <pre class="text-xs bg-gray-100 p-1 rounded mt-1">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @else
                                                    <div><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    @if(!$notification['read'])
                                        <button wire:click="markNotificationAsRead('{{ $notification['id'] }}')" 
                                                class="mt-2 px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                                            Mark as Read
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Statistics -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-blue-600">{{ count($updates) }}</div>
            <div class="text-sm text-blue-800">Pending Updates</div>
        </div>
        
        <div class="bg-green-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-green-600">{{ count($notifications) }}</div>
            <div class="text-sm text-green-800">Notifications</div>
        </div>
        
        <div class="bg-purple-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-purple-600">{{ count(array_filter($notifications, fn($n) => !$n['read'])) }}</div>
            <div class="text-sm text-purple-800">Unread Notifications</div>
        </div>
    </div>
</div>

<!-- JavaScript for real-time updates -->
<script>
document.addEventListener('livewire:init', () => {
    // Listen for Livewire events
    Livewire.on('refreshUpdates', () => {
        console.log('Refreshing updates...');
    });
    
    Livewire.on('refreshNotifications', () => {
        console.log('Refreshing notifications...');
    });
    
    // Optional: Add sound notification for new updates
    function playNotificationSound() {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuF0fPTgjMGHm7A7+OZURE');
        audio.volume = 0.3;
        audio.play().catch(() => {}); // Ignore errors if audio is blocked
    }
    
    // Play sound for new notifications
    Livewire.on('notificationReceived', () => {
        playNotificationSound();
    });
});
</script>

