<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Message Center</h1>
                    <p class="text-blue-100 mt-1">Manage your game communications</p>
                </div>
                <div class="flex space-x-2">
                    <button wire:click="startCompose" 
                            class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Compose
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="bg-gray-50 p-4 border-b">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $messageStats['total_messages'] }}</div>
                    <div class="text-sm text-gray-600">Total Messages</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">{{ $messageStats['unread_messages'] }}</div>
                    <div class="text-sm text-gray-600">Unread</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $messageStats['sent_messages'] }}</div>
                    <div class="text-sm text-gray-600">Sent</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $messageStats['alliance_messages'] }}</div>
                    <div class="text-sm text-gray-600">Alliance</div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row">
            <!-- Sidebar -->
            <div class="lg:w-1/4 bg-gray-50 border-r">
                <!-- Tabs -->
                <div class="p-4">
                    <nav class="space-y-2">
                        <button wire:click="switchTab('inbox')" 
                                class="w-full text-left px-4 py-2 rounded-lg transition-colors {{ $activeTab === 'inbox' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            <i class="fas fa-inbox mr-2"></i>Inbox
                            @if($messageStats['unread_messages'] > 0)
                                <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full ml-2">{{ $messageStats['unread_messages'] }}</span>
                            @endif
                        </button>
                        <button wire:click="switchTab('sent')" 
                                class="w-full text-left px-4 py-2 rounded-lg transition-colors {{ $activeTab === 'sent' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            <i class="fas fa-paper-plane mr-2"></i>Sent
                        </button>
                        <button wire:click="switchTab('alliance')" 
                                class="w-full text-left px-4 py-2 rounded-lg transition-colors {{ $activeTab === 'alliance' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            <i class="fas fa-users mr-2"></i>Alliance
                        </button>
                        <button wire:click="switchTab('system')" 
                                class="w-full text-left px-4 py-2 rounded-lg transition-colors {{ $activeTab === 'system' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            <i class="fas fa-cog mr-2"></i>System
                        </button>
                    </nav>
                </div>

                <!-- Filters -->
                <div class="p-4 border-t">
                    <h3 class="font-semibold text-gray-700 mb-3">Filters</h3>
                    
                    <!-- Search -->
                    <div class="mb-4">
                        <input type="text" wire:model.live="search" 
                               placeholder="Search messages..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Type Filter -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select wire:model.live="filterType" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Types</option>
                            <option value="private">Private</option>
                            <option value="alliance">Alliance</option>
                            <option value="system">System</option>
                            <option value="battle_report">Battle Report</option>
                        </select>
                    </div>

                    <!-- Priority Filter -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <select wire:model.live="filterPriority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Priorities</option>
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                        </select>
                    </div>

                    <!-- Read Filter -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select wire:model.live="filterRead" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Messages</option>
                            <option value="unread">Unread Only</option>
                            <option value="read">Read Only</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:w-3/4">
                @if($composeMode)
                    <!-- Compose Form -->
                    <div class="p-6">
                        <div class="bg-white border rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl font-semibold">
                                    {{ $replyMode ? 'Reply to Message' : 'Compose New Message' }}
                                </h2>
                                <button wire:click="cancelCompose" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <form wire:submit.prevent="sendMessage">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Recipient</label>
                                        <select wire:model="recipientId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Player</option>
                                            @foreach($players as $player)
                                                <option value="{{ $player->id }}">{{ $player->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('recipientId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                                        <select wire:model="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <option value="low">Low</option>
                                            <option value="normal">Normal</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                        @error('priority') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                                    <input type="text" wire:model="subject" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                           placeholder="Enter message subject">
                                    @error('subject') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                                    <textarea wire:model="body" rows="6" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                              placeholder="Enter your message"></textarea>
                                    @error('body') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <button type="button" wire:click="cancelCompose" 
                                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Cancel
                                    </button>
                                    <button type="submit" 
                                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-paper-plane mr-2"></i>Send Message
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <!-- Message List -->
                    <div class="p-6">
                        <!-- Bulk Actions -->
                        @if(count($selectedMessages) > 0)
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-blue-700 font-medium">
                                        {{ count($selectedMessages) }} message(s) selected
                                    </span>
                                    <div class="flex space-x-2">
                                        <button wire:click="bulkMarkAsRead" 
                                                class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                            Mark as Read
                                        </button>
                                        <button wire:click="bulkDelete" 
                                                class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Messages -->
                        <div class="space-y-2">
                            @forelse($messages as $message)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start space-x-3">
                                        <!-- Checkbox -->
                                        <input type="checkbox" 
                                               wire:model="selectedMessages" 
                                               value="{{ $message->id }}"
                                               class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        
                                        <!-- Message Icon -->
                                        <div class="flex-shrink-0">
                                            <i class="{{ $this->getMessageTypeIcon($message->message_type) }} text-gray-400"></i>
                                        </div>

                                        <!-- Message Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2">
                                                    <h3 class="text-sm font-medium text-gray-900 truncate">
                                                        {{ $message->subject }}
                                                    </h3>
                                                    @if(!$message->is_read && $message->recipient_id === auth()->user()->player->id)
                                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">New</span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xs {{ $this->getPriorityClass($message->priority) }}">
                                                        {{ ucfirst($message->priority) }}
                                                    </span>
                                                    <span class="text-xs text-gray-500">
                                                        {{ $message->created_at->diffForHumans() }}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-1 text-sm text-gray-600">
                                                @if($message->sender)
                                                    <span class="font-medium">From: {{ $message->sender->name }}</span>
                                                @else
                                                    <span class="font-medium text-gray-500">System Message</span>
                                                @endif
                                                
                                                @if($message->recipient && $message->recipient_id !== auth()->user()->player->id)
                                                    <span class="ml-2">To: {{ $message->recipient->name }}</span>
                                                @endif
                                            </div>
                                            
                                            <div class="mt-2 text-sm text-gray-700 line-clamp-2">
                                                {{ Str::limit($message->body, 150) }}
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex-shrink-0 flex space-x-1">
                                            <button wire:click="openMessage({{ $message->id }})" 
                                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($message->sender_id === auth()->user()->player->id)
                                                <button wire:click="startReply({{ $message->id }})" 
                                                        class="text-green-600 hover:text-green-800 text-sm">
                                                    <i class="fas fa-reply"></i>
                                                </button>
                                            @endif
                                            <button wire:click="deleteMessage({{ $message->id }})" 
                                                    class="text-red-600 hover:text-red-800 text-sm"
                                                    onclick="return confirm('Are you sure you want to delete this message?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12">
                                    <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No messages found</h3>
                                    <p class="text-gray-600">You don't have any messages in this folder.</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $messages->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Message Detail Modal -->
    @if($selectedMessage)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold">{{ $selectedMessage->subject }}</h2>
                        <button wire:click="closeMessage" class="text-white hover:text-gray-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <div class="mb-4 text-sm text-gray-600">
                        <div class="flex items-center space-x-4">
                            @if($selectedMessage->sender)
                                <span><strong>From:</strong> {{ $selectedMessage->sender->name }}</span>
                            @else
                                <span><strong>From:</strong> System</span>
                            @endif
                            
                            @if($selectedMessage->recipient && $selectedMessage->recipient_id !== auth()->user()->player->id)
                                <span><strong>To:</strong> {{ $selectedMessage->recipient->name }}</span>
                            @endif
                            
                            <span><strong>Date:</strong> {{ $selectedMessage->created_at->format('M j, Y g:i A') }}</span>
                            <span class="{{ $this->getPriorityClass($selectedMessage->priority) }}">
                                <strong>Priority:</strong> {{ ucfirst($selectedMessage->priority) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="prose max-w-none">
                        {!! nl2br(e($selectedMessage->body)) !!}
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                    @if($selectedMessage->sender_id === auth()->user()->player->id)
                        <button wire:click="startReply({{ $selectedMessage->id }})" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-reply mr-2"></i>Reply
                        </button>
                    @endif
                    <button wire:click="closeMessage" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Auto-refresh messages every 30 seconds
    setInterval(function() {
        @this.call('refreshMessages');
    }, 30000);
</script>
@endpush
