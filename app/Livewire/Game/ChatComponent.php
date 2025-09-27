<?php

namespace App\Livewire\Game;

use App\Models\Game\ChatMessage;
use App\Models\Game\ChatChannel;
use App\Models\Game\Player;
use App\Services\ChatService;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ChatComponent extends Component
{
    use WithPagination;

    public $player;
    public $currentChannel;
    public $channels = [];
    public $messages = [];
    public $newMessage = '';
    public $isLoading = false;
    public $notifications = [];
    public $showChannels = true;
    public $showEmojis = true;
    public $autoScroll = true;
    public $realTimeUpdates = true;
    public $autoRefresh = true;
    public $refreshInterval = 5;
    public $messageTypes = [];
    public $selectedMessageType = 'text';
    public $searchQuery = '';
    public $filterByType = null;
    public $filterByPlayer = null;
    public $sortBy = 'created_at';
    public $sortOrder = 'desc';

    protected $listeners = [
        'messageReceived',
        'channelJoined',
        'channelLeft',
        'gameTickProcessed',
    ];

    public function mount($channelId = null)
    {
        $this->loadPlayer();
        $this->loadChannels();
        $this->loadChannel($channelId);
        $this->loadMessages();
        $this->initializeChatRealTime();
    }

    public function loadPlayer()
    {
        $this->player = Player::where('user_id', Auth::id())->first();
        
        if (!$this->player) {
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

            if (!$globalChannel) {
                $globalChannel = ChatChannel::getGlobalChannel();
                $this->channels[] = $globalChannel->toArray();
            }

        } catch (\Exception $e) {
            $this->addNotification('Failed to load channels: ' . $e->getMessage(), 'error');
        }
    }

    public function loadChannel($channelId = null)
    {
        if ($channelId) {
            $this->currentChannel = ChatChannel::find($channelId);
        } else {
            // Default to global channel
            $this->currentChannel = ChatChannel::getGlobalChannel();
        }

        if (!$this->currentChannel) {
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
                $query->where('message', 'like', '%' . $this->searchQuery . '%');
            }

            $this->messages = $query->get()->reverse()->values()->toArray();

        } catch (\Exception $e) {
            $this->addNotification('Failed to load messages: ' . $e->getMessage(), 'error');
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
            $this->addNotification('Failed to send message: ' . $e->getMessage(), 'error');
        }
    }

    public function joinChannel($channelId)
    {
        try {
            $channel = ChatChannel::find($channelId);
            
            if (!$channel) {
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
            $this->addNotification('Failed to join channel: ' . $e->getMessage(), 'error');
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
            $this->addNotification('Failed to leave channel: ' . $e->getMessage(), 'error');
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
            $this->addNotification('Failed to create channel: ' . $e->getMessage(), 'error');
        }
    }

    public function deleteMessage($messageId)
    {
        try {
            $message = ChatMessage::find($messageId);
            
            if (!$message) {
                $this->addNotification('Message not found', 'error');
                return;
            }

            // Check if user can delete this message
            if ($message->sender_id !== $this->player->id && !$this->player->isAdmin()) {
                $this->addNotification('You cannot delete this message', 'error');
                return;
            }

            $chatService = app(ChatService::class);
            $chatService->deleteMessage($messageId);

            $this->loadMessages();
            $this->addNotification('Message deleted successfully', 'success');

        } catch (\Exception $e) {
            $this->addNotification('Failed to delete message: ' . $e->getMessage(), 'error');
        }
    }

    public function toggleChannels()
    {
        $this->showChannels = !$this->showChannels;
    }

    public function toggleEmojis()
    {
        $this->showEmojis = !$this->showEmojis;
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
            '😹', '😻', '😼', '😽', '🙀', '😿', '😾'
        ];
    }

    public function render()
    {
        return view('livewire.game.chat-component');
    }
}
