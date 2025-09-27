<div class="chat-manager">
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <!-- Chat Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Game Chat
                </h2>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $stats['total_messages'] ?? 0 }} messages
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $stats['recent_messages'] ?? 0 }} recent
                    </span>
                </div>
            </div>
        </div>

        <div class="flex h-96">
            <!-- Channel Sidebar -->
            <div class="w-64 bg-gray-50 dark:bg-gray-700 border-r border-gray-200 dark:border-gray-600">
                <div class="p-4">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        Channels
                    </h3>
                    
                    <!-- Channel List -->
                    <div class="space-y-2">
                        <button 
                            wire:click="changeChannel('global')"
                            class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors
                                {{ $selectedChannel === 'global' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
                        >
                            <div class="flex items-center">
                                <x-heroicon-o-globe-alt class="w-4 h-4 mr-2" />
                                Global Chat
                            </div>
                        </button>

                        @if(Auth::user()->player->alliance_id)
                        <button 
                            wire:click="changeChannel('alliance')"
                            class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors
                                {{ $selectedChannel === 'alliance' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
                        >
                            <div class="flex items-center">
                                <x-heroicon-o-users class="w-4 h-4 mr-2" />
                                Alliance Chat
                            </div>
                        </button>
                        @endif

                        <button 
                            wire:click="changeChannel('private')"
                            class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors
                                {{ $selectedChannel === 'private' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
                        >
                            <div class="flex items-center">
                                <x-heroicon-o-lock-closed class="w-4 h-4 mr-2" />
                                Private Chat
                            </div>
                        </button>

                        <button 
                            wire:click="changeChannel('trade')"
                            class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors
                                {{ $selectedChannel === 'trade' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
                        >
                            <div class="flex items-center">
                                <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                                Trade Chat
                            </div>
                        </button>

                        <button 
                            wire:click="changeChannel('diplomacy')"
                            class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors
                                {{ $selectedChannel === 'diplomacy' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
                        >
                            <div class="flex items-center">
                                <x-heroicon-o-users class="w-4 h-4 mr-2" />
                                Diplomacy Chat
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Search -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-600">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        Search Messages
                    </h3>
                    <div class="space-y-2">
                        <input 
                            type="text" 
                            wire:model.live="searchQuery"
                            placeholder="Search messages..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-white"
                        >
                        <select 
                            wire:model.live="searchChannelType"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-white"
                        >
                            <option value="">All Channels</option>
                            <option value="global">Global</option>
                            <option value="alliance">Alliance</option>
                            <option value="private">Private</option>
                            <option value="trade">Trade</option>
                            <option value="diplomacy">Diplomacy</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="flex-1 flex flex-col">
                <!-- Messages -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    @forelse($messages as $message)
                        <div class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                        {{ substr($message->sender->name, 0, 1) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Message Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $message->sender->name }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $message->created_at->diffForHumans() }}
                                    </span>
                                    @if($message->message_type !== 'text')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                            {{ $message->message_type === 'system' ? 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300' : '' }}
                                            {{ $message->message_type === 'announcement' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                            {{ $message->message_type === 'emote' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : '' }}
                                            {{ $message->message_type === 'command' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                                        ">
                                            {{ ucfirst($message->message_type) }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                    {{ $this->getFormattedMessage($message) }}
                                </p>
                            </div>

                            <!-- Actions -->
                            @if($this->canDeleteMessage($message))
                                <div class="flex-shrink-0">
                                    <button 
                                        wire:click="deleteMessage({{ $message->id }})"
                                        wire:confirm="Are you sure you want to delete this message? This action cannot be undone."
                                        class="text-gray-400 hover:text-red-500 transition-colors"
                                        title="Delete message"
                                    >
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <x-heroicon-o-chat-bubble-left-ellipsis class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <p class="text-gray-500 dark:text-gray-400">No messages in this channel yet.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Message Input -->
                <div class="border-t border-gray-200 dark:border-gray-600 p-4">
                    @if($selectedChannel === 'private')
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Recipient
                            </label>
                            <input 
                                type="number" 
                                wire:model="recipientId"
                                placeholder="Player ID"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-white"
                            >
                            @error('recipientId') 
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p> 
                            @enderror
                        </div>
                    @endif

                    <div class="flex space-x-3">
                        <div class="flex-1">
                            <textarea 
                                wire:model="message"
                                placeholder="Type your message..."
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-white resize-none"
                            ></textarea>
                            @error('message') 
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p> 
                            @enderror
                        </div>
                        
                        <div class="flex flex-col space-y-2">
                            <select 
                                wire:model="messageType"
                                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-white"
                            >
                                <option value="text">Text</option>
                                <option value="system">System</option>
                                <option value="announcement">Announcement</option>
                                <option value="emote">Emote</option>
                                <option value="command">Command</option>
                            </select>
                            
                            <button 
                                wire:click="sendMessage"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                            >
                                Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>