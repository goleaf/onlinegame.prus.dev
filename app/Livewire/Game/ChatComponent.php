<?php

namespace App\Livewire\Game;

use App\Livewire\BaseSessionComponent;
use App\Models\Game\ChatChannel;
use App\Models\Game\ChatMessage;
use App\Models\Game\Player;
use App\Services\ChatService;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\WithPagination;

class ChatComponent extends BaseSessionComponent
{
    use WithPagination;

    public $player;

    public $currentChannel;

    public $channels = [];

    public $messages = [];

    public $newMessage = '';

    public $isLoading = false;

    public $notifications = [];

    // Chat-specific session properties
    #[Session]
    public $selectedChannelId = null;

    #[Session]
    public $showChannels = true;

    #[Session]
    public $showEmojis = true;

    #[Session]
    public $autoScroll = true;

    #[Session]
    public $selectedMessageType = 'text';

    #[Session]
    public $filterByType = null;

    #[Session]
    public $filterByPlayer = null;

    #[Session]
    public $messageFilters = [];

    #[Session]
    public $chatLayout = 'sidebar';

    #[Session]
    public $messageDisplayMode = 'bubbles';

    #[Session]
    public $showTimestamps = true;

    #[Session]
    public $showUserAvatars = true;

    #[Session]
    public $enableSounds = true;

    #[Session]
    public $enableNotifications = true;

    #[Session]
    public $fontSize = 'medium';

    #[Session]
    public $theme = 'light';

    public $messageTypes = [];

    protected $listeners = [
        'messageReceived',
        'channelJoined',
        'channelLeft',
        'gameTickProcessed',
    ];

    public function mount($channelId = null)
    {
        // Initialize session properties
        $this->initializeSessionProperties();

        // Override base refresh settings with chat-specific defaults
        $this->refreshInterval = $this->refreshInterval ?: 5;

        $this->loadPlayer();
        $this->loadChannels();

        // Use session channel ID or provided channel ID
        $selectedChannelId = $this->selectedChannelId ?: $channelId;
        $this->loadChannel($selectedChannelId);
        $this->loadMessages();
        $this->initializeChatRealTime();
    }

    public function loadPlayer()
    {
        $this->player = Player::where('user_id', Auth::id())->first();

        if (! $this->player) {
            $this->addNotification('Player not found', 'error');

            return;
        }
    }

    public function loadChannels()
    {
        try {
            $this->channels = ChatChannel::where('is_active', true)
                ->with(['lastMessage', 'participants'])
                ->orderBy('name')
                ->get()
                ->toArray();

            // Add global channel if not exists
            $globalChannel = collect($this->channels)
                ->where('type', 'global')
                ->first();

            if (! $globalChannel) {
                $globalChannel = ChatChannel::getGlobalChannel();
                $this->channels[] = $globalChannel->toArray();
            }

        } catch (\Exception $e) {
            $this->addNotification('Failed to load channels: '.$e->getMessage(), 'error');
        }
    }

    public function loadChannel($channelId = null)
    {
        if ($channelId) {
            $this->currentChannel = ChatChannel::find($channelId);
            $this->selectedChannelId = $channelId; // Persist selection in session
        } else {
            // Default to global channel
            $this->currentChannel = ChatChannel::getGlobalChannel();
            if ($this->currentChannel) {
                $this->selectedChannelId = $this->currentChannel->id;
            }
        }

        if (! $this->currentChannel) {
            $this->addNotification('Channel not found', 'error');

            return;
        }
    }

    public function loadMessages()
    {
        try {
            $this->isLoading = true;

            $query = ChatMessage::where('channel_id', $this->currentChannel->id)
                ->with(['sender'])
                ->orderBy('created_at', 'desc')
                ->limit(50);

            // Apply filters
            if ($this->filterByType) {
                $query->where('message_type', $this->filterByType);
            }

            if ($this->filterByPlayer) {
                $query->where('sender_id', $this->filterByPlayer);
            }

            // Apply search
            if ($this->searchQuery) {
                $query->where('message', 'like', '%'.$this->searchQuery.'%');
            }

            $this->messages = $query->get()->reverse()->values()->toArray();

        } catch (\Exception $e) {
            $this->addNotification('Failed to load messages: '.$e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function sendMessage()
    {
        try {
            if (empty(trim($this->newMessage))) {
                $this->addNotification('Message cannot be empty', 'error');

                return;
            }

            $chatService = app(ChatService::class);

            $message = $chatService->sendMessage(
                $this->player->id,
                $this->currentChannel->id,
                $this->currentChannel->type,
                trim($this->newMessage),
                $this->selectedMessageType
            );

            $this->newMessage = '';
            $this->loadMessages();
            $this->addNotification('Message sent successfully', 'success');

        } catch (\Exception $e) {
            $this->addNotification('Failed to send message: '.$e->getMessage(), 'error');
        }
    }

    public function joinChannel($channelId)
    {
        try {
            $channel = ChatChannel::find($channelId);

            if (! $channel) {
                $this->addNotification('Channel not found', 'error');

                return;
            }

            $chatService = app(ChatService::class);
            $chatService->joinChannel($this->player->id, $channelId);

            $this->currentChannel = $channel;
            $this->loadMessages();
            $this->addNotification('Joined channel successfully', 'success');
            $this->dispatch('channelJoined', $channelId);

        } catch (\Exception $e) {
            $this->addNotification('Failed to join channel: '.$e->getMessage(), 'error');
        }
    }

    public function leaveChannel($channelId)
    {
        try {
            $chatService = app(ChatService::class);
            $chatService->leaveChannel($this->player->id, $channelId);

            // Switch to global channel if leaving current channel
            if ($this->currentChannel && $this->currentChannel->id == $channelId) {
                $this->currentChannel = ChatChannel::getGlobalChannel();
                $this->loadMessages();
            }

            $this->loadChannels();
            $this->addNotification('Left channel successfully', 'success');
            $this->dispatch('channelLeft', $channelId);

        } catch (\Exception $e) {
            $this->addNotification('Failed to leave channel: '.$e->getMessage(), 'error');
        }
    }

    public function createChannel($name, $type = 'public', $description = null)
    {
        try {
            $chatService = app(ChatService::class);
            $channel = $chatService->createChannel($name, $type, $description, $this->player->id);

            $this->loadChannels();
            $this->addNotification('Channel created successfully', 'success');

        } catch (\Exception $e) {
            $this->addNotification('Failed to create channel: '.$e->getMessage(), 'error');
        }
    }

    public function deleteMessage($messageId)
    {
        try {
            $message = ChatMessage::find($messageId);

            if (! $message) {
                $this->addNotification('Message not found', 'error');

                return;
            }

            // Check if user can delete this message
            if ($message->sender_id !== $this->player->id && ! $this->player->isAdmin()) {
                $this->addNotification('You cannot delete this message', 'error');

                return;
            }

            $chatService = app(ChatService::class);
            $chatService->deleteMessage($messageId);

            $this->loadMessages();
            $this->addNotification('Message deleted successfully', 'success');

        } catch (\Exception $e) {
            $this->addNotification('Failed to delete message: '.$e->getMessage(), 'error');
        }
    }

    public function toggleChannels()
    {
        $this->showChannels = ! $this->showChannels;
    }

    public function toggleEmojis()
    {
        $this->showEmojis = ! $this->showEmojis;
    }

    public function refreshMessages()
    {
        $this->loadMessages();
        $this->addNotification('Messages refreshed', 'info');
    }

    public function applyFilters()
    {
        $this->loadMessages();
    }

    public function clearFilters()
    {
        $this->filterByType = null;
        $this->filterByPlayer = null;
        $this->searchQuery = '';
        $this->loadMessages();
    }

    public function addNotification(string $message, string $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
        ];
    }

    public function removeNotification($notificationId)
    {
        $this->notifications = array_filter($this->notifications, function ($notification) use ($notificationId) {
            return $notification['id'] !== $notificationId;
        });
    }

    #[On('messageReceived')]
    public function onMessageReceived($messageData)
    {
        if ($this->autoRefresh) {
            $this->loadMessages();
        }
    }

    #[On('gameTickProcessed')]
    public function onGameTickProcessed()
    {
        if ($this->autoRefresh) {
            $this->loadMessages();
        }
    }

    public function getMessageTypesProperty()
    {
        return [
            'text' => 'Text',
            'system' => 'System',
            'announcement' => 'Announcement',
            'warning' => 'Warning',
            'error' => 'Error',
            'success' => 'Success',
        ];
    }

    public function getChannelTypesProperty()
    {
        return [
            'global' => 'Global',
            'alliance' => 'Alliance',
            'private' => 'Private',
            'public' => 'Public',
        ];
    }

    public function getEmojisProperty()
    {
        return [
            '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣',
            '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰',
            '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜',
            '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏',
            '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣',
            '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠',
            '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨',
            '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥',
            '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧',
            '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐',
            '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑',
            '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻',
            '💀', '☠️', '👽', '👾', '🤖', '🎃', '😺', '😸',
            '😹', '😻', '😼', '😽', '🙀', '😿', '😾',
        ];
    }

    /**
     * Initialize chat real-time features
     */
    public function initializeChatRealTime()
    {
        try {
            if ($this->player) {
                // Initialize real-time features for the player
                GameIntegrationService::initializeUserRealTime($this->player->user_id);

                $this->dispatch('chat-initialized', [
                    'message' => 'Chat component real-time features activated',
                    'player_id' => $this->player->id,
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to initialize chat real-time features: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Send message with real-time integration
     */
    public function sendMessageWithIntegration()
    {
        try {
            $this->sendMessage();

            if ($this->player && $this->currentChannel) {
                // Send notification about chat message
                GameNotificationService::sendNotification(
                    $this->player->user_id,
                    'chat_message_sent',
                    [
                        'player_id' => $this->player->id,
                        'channel_id' => $this->currentChannel->id,
                        'channel_name' => $this->currentChannel->name,
                        'timestamp' => now()->toISOString(),
                    ]
                );

                $this->dispatch('message-sent', [
                    'message' => 'Chat message sent successfully with notifications',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to send chat message: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Join channel with real-time integration
     */
    public function joinChannelWithIntegration($channelId)
    {
        try {
            $this->joinChannel($channelId);

            if ($this->player) {
                // Send notification about channel join
                GameNotificationService::sendNotification(
                    $this->player->user_id,
                    'chat_channel_joined',
                    [
                        'player_id' => $this->player->id,
                        'channel_id' => $channelId,
                        'timestamp' => now()->toISOString(),
                    ]
                );

                $this->dispatch('channel-joined', [
                    'message' => 'Channel joined successfully with notifications',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to join channel: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Update chat layout preference
     */
    public function setChatLayout($layout)
    {
        $this->chatLayout = in_array($layout, ['sidebar', 'fullscreen', 'popup']) ? $layout : 'sidebar';
        $this->addNotification("Chat layout set to {$this->chatLayout}", 'info');
    }

    /**
     * Update message display mode
     */
    public function setMessageDisplayMode($mode)
    {
        $this->messageDisplayMode = in_array($mode, ['bubbles', 'list', 'compact']) ? $mode : 'bubbles';
        $this->addNotification("Message display mode set to {$this->messageDisplayMode}", 'info');
    }

    /**
     * Toggle timestamp display
     */
    public function toggleTimestamps()
    {
        $this->showTimestamps = ! $this->showTimestamps;
        $this->addNotification(
            $this->showTimestamps ? 'Timestamps enabled' : 'Timestamps disabled',
            'info'
        );
    }

    /**
     * Toggle user avatar display
     */
    public function toggleUserAvatars()
    {
        $this->showUserAvatars = ! $this->showUserAvatars;
        $this->addNotification(
            $this->showUserAvatars ? 'User avatars enabled' : 'User avatars disabled',
            'info'
        );
    }

    /**
     * Toggle chat sounds
     */
    public function toggleSounds()
    {
        $this->enableSounds = ! $this->enableSounds;
        $this->addNotification(
            $this->enableSounds ? 'Chat sounds enabled' : 'Chat sounds disabled',
            'info'
        );
    }

    /**
     * Toggle chat notifications
     */
    public function toggleChatNotifications(): void
    {
        $this->enableNotifications = ! $this->enableNotifications;
        $this->addNotification(
            $this->enableNotifications ? 'Chat notifications enabled' : 'Chat notifications disabled',
            'info'
        );
    }

    /**
     * Update font size
     */
    public function setFontSize($size)
    {
        $this->fontSize = in_array($size, ['small', 'medium', 'large']) ? $size : 'medium';
        $this->addNotification("Font size set to {$this->fontSize}", 'info');
    }

    /**
     * Update chat theme
     */
    public function setChatTheme($theme)
    {
        $this->theme = in_array($theme, ['light', 'dark', 'auto']) ? $theme : 'light';
        $this->addNotification("Chat theme set to {$this->theme}", 'info');
    }

    /**
     * Update message filters
     */
    public function updateMessageFilters(array $filters)
    {
        $this->messageFilters = array_filter($filters, fn ($value) => ! empty($value));
        $this->loadMessages();
        $this->addNotification('Message filters updated', 'info');
    }

    /**
     * Clear all message filters
     */
    public function clearMessageFilters(): void
    {
        $this->messageFilters = [];
        $this->filterByType = null;
        $this->filterByPlayer = null;
        $this->searchQuery = '';
        $this->loadMessages();
        $this->addNotification('All message filters cleared', 'info');
    }

    /**
     * Reset all chat preferences to defaults
     */
    public function resetChatPreferences()
    {
        $this->selectedChannelId = null;
        $this->showChannels = true;
        $this->showEmojis = true;
        $this->autoScroll = true;
        $this->selectedMessageType = 'text';
        $this->filterByType = null;
        $this->filterByPlayer = null;
        $this->messageFilters = [];
        $this->chatLayout = 'sidebar';
        $this->messageDisplayMode = 'bubbles';
        $this->showTimestamps = true;
        $this->showUserAvatars = true;
        $this->enableSounds = true;
        $this->enableNotifications = true;
        $this->fontSize = 'medium';
        $this->theme = 'light';

        // Reset base session properties
        $this->resetSessionProperties();

        $this->addNotification('All chat preferences reset to defaults', 'info');
    }

    public function render()
    {
        return view('livewire.game.chat-component');
    }
}
