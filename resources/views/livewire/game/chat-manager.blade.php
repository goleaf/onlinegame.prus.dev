<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Game Chat</h1>
                    <p class="text-blue-100 mt-1">Real-time communication with other players</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm">
                        <span class="text-blue-200">Online:</span>
                        <span class="font-semibold">{{ $chatStats['message_stats']['messages_today'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row h-96">
            <!-- Channel Sidebar -->
            <div class="lg:w-1/4 bg-gray-50 border-r">
                <div class="p-4">
                    <h3 class="font-semibold text-gray-700 mb-3">Channels</h3>
                    
                    <!-- Channel List -->
                    <div class="space-y-2">
                        <!-- Global Channel -->
                        <button wire:click="switchToGlobalChannel" 
                                class="w-full text-left px-3 py-2 rounded-lg transition-colors {{ $activeChannelType === 'global' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            <i class="fas fa-globe mr-2"></i>Global Chat
                        </button>
                        
                        <!-- Alliance Channel -->
                        @if(auth()->user()->player && auth()->user()->player->alliance_id)
                            <button wire:click="switchToAllianceChannel" 
                                    class="w-full text-left px-3 py-2 rounded-lg transition-colors {{ $activeChannelType === 'alliance' ? 'bg-green-100 text-green-700' : 'text-gray-600 hover:bg-gray-100' }}">
                                <i class="fas fa-users mr-2"></i>Alliance Chat
                            </button>
                        @endif
                        
                        <!-- Custom Channels -->
                        @foreach($availableChannels as $channel)
                            @if($channel['channel_type'] === 'custom')
                                <button wire:click="switchChannel({{ $channel['id'] }}, '{{ $channel['channel_type'] }}', '{{ $channel['name'] }}')" 
                                        class="w-full text-left px-3 py-2 rounded-lg transition-colors {{ $activeChannelId === $channel['id'] ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:bg-gray-100' }}">
                                    <i class="fas fa-comments mr-2"></i>{{ $channel['name'] }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Chat Stats -->
                <div class="p-4 border-t">
                    <h3 class="font-semibold text-gray-700 mb-3">Statistics</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Messages:</span>
                            <span class="font-medium">{{ $chatStats['message_stats']['total_messages'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Today:</span>
                            <span class="font-medium">{{ $chatStats['message_stats']['messages_today'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Active Channels:</span>
                            <span class="font-medium">{{ $chatStats['channel_stats']['total_channels'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="lg:w-3/4 flex flex-col">
                <!-- Channel Header -->
                <div class="bg-gray-100 px-4 py-3 border-b">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="{{ $this->getChannelIcon($activeChannelType) }} {{ $this->getChannelColor($activeChannelType) }}"></i>
                            <h2 class="font-semibold text-gray-800">{{ $activeChannel }}</h2>
                        </div>
                        <div class="text-sm text-gray-600">
                            {{ $messages['total'] ?? 0 }} messages
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-container">
                    @forelse($messages['messages'] ?? [] as $message)
                        <div class="flex items-start space-x-3" data-message-id="{{ $message->id }}">
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600 text-xs"></i>
                                </div>
                            </div>

                            <!-- Message Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium text-gray-900 text-sm">
                                        {{ $message->sender->name ?? 'Unknown' }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ $message->created_at->format('H:i') }}
                                    </span>
                                    @if($message->message_type === 'system')
                                        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded">System</span>
                                    @endif
                                    @if($message->message_type === 'announcement')
                                        <span class="text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded">Announcement</span>
                                    @endif
                                </div>
                                
                                <div class="mt-1 text-sm text-gray-700">
                                    {!! $this->formatMessage($message->message, $message->message_type) !!}
                                </div>
                            </div>

                            <!-- Message Actions -->
                            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                @if($message->sender_id === auth()->user()->player?->id)
                                    <button wire:click="deleteMessage({{ $message->id }})" 
                                            class="text-gray-400 hover:text-red-600 text-xs"
                                            onclick="return confirm('Delete this message?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <i class="fas fa-comments text-gray-400 text-4xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No messages yet</h3>
                            <p class="text-gray-600">Start the conversation by sending a message!</p>
                        </div>
                    @endforelse
                </div>

                <!-- Typing Indicator -->
                @if(!empty($typingUsers))
                    <div class="px-4 py-2 bg-gray-50 border-t">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-keyboard mr-1"></i>
                            {{ $this->getTypingText() }}
                        </div>
                    </div>
                @endif

                <!-- Message Input -->
                <div class="p-4 border-t bg-gray-50">
                    <form wire:submit.prevent="sendMessage">
                        <div class="flex space-x-3">
                            <div class="flex-1">
                                <input type="text" 
                                       wire:model.live="message" 
                                       wire:keydown="startTyping"
                                       wire:keyup="stopTyping"
                                       placeholder="Type your message..." 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       maxlength="500">
                            </div>
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            Press Enter to send, Shift+Enter for new line
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-scroll to bottom when new messages arrive
    function scrollToBottom() {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    // Scroll to bottom on page load
    document.addEventListener('DOMContentLoaded', scrollToBottom);

    // Listen for new messages and scroll to bottom
    document.addEventListener('livewire:updated', scrollToBottom);

    // Handle Enter key for sending messages
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            const messageInput = document.querySelector('input[wire\\:model\\.live="message"]');
            if (messageInput && document.activeElement === messageInput) {
                e.preventDefault();
                @this.call('sendMessage');
            }
        }
    });

    // Auto-refresh messages every 5 seconds
    setInterval(function() {
        @this.call('refreshMessages');
    }, 5000);
</script>
@endpush
