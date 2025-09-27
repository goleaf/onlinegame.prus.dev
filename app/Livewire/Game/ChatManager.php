<?php

namespace App\Livewire\Game;

use App\Models\Game\ChatMessage;
use App\Models\Game\ChatChannel;
use App\Services\ChatService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

#[Title('Chat Manager')]
#[Layout('layouts.game')]
class ChatManager extends Component
{
    use WithPagination;

    public $activeChannel = 'global';
    public $activeChannelId = 0;
    public $activeChannelType = 'global';
    public $message = '';
    public $availableChannels = [];
    public $isTyping = false;
    public $typingUsers = [];
    
    protected $chatService;
    
    protected $listeners = [
        'chatMessageReceived' => 'refreshMessages',
        'chatMessageDeleted' => 'refreshMessages',
        'userTyping' => 'handleUserTyping',
        'userStoppedTyping' => 'handleUserStoppedTyping',
    ];

    public function boot()
    {
        $this->chatService = new ChatService();
    }

    public function mount()
    {
        $this->loadAvailableChannels();
        $this->switchToGlobalChannel();
    }

    public function render()
    {
        $messages = $this->getMessages();
        $chatStats = $this->getChatStats();

        return view('livewire.game.chat-manager', [
            'messages' => $messages,
            'chatStats' => $chatStats,
        ]);
    }

    public function getMessages()
    {
        switch ($this->activeChannelType) {
            case 'global':
                return $this->chatService->getGlobalMessages(50, 0);
            case 'alliance':
                $player = auth()->user()->player;
                if ($player && $player->alliance_id) {
                    return $this->chatService->getAllianceMessages($player->alliance_id, 50, 0);
                }
                return ['messages' => collect(), 'total' => 0];
            case 'private':
                // This would need the other player ID
                return ['messages' => collect(), 'total' => 0];
            default:
                return $this->chatService->getChannelMessages($this->activeChannelId, $this->activeChannelType, 50, 0);
        }
    }

    public function getChatStats()
    {
        return $this->chatService->getChatStats();
    }

    public function loadAvailableChannels()
    {
        $player = auth()->user()->player;
        if ($player) {
            $this->availableChannels = $this->chatService->getAvailableChannels($player->id);
        }
    }

    public function switchChannel($channelId, $channelType, $channelName = null)
    {
        $this->activeChannel = $channelName ?? $channelType;
        $this->activeChannelId = $channelId;
        $this->activeChannelType = $channelType;
        $this->resetPage();
        $this->typingUsers = [];
    }

    public function switchToGlobalChannel()
    {
        $this->switchChannel(0, 'global', 'Global Chat');
    }

    public function switchToAllianceChannel()
    {
        $player = auth()->user()->player;
        if ($player && $player->alliance_id) {
            $this->switchChannel($player->alliance_id, 'alliance', 'Alliance Chat');
        }
    }

    public function sendMessage()
    {
        $this->validate([
            'message' => 'required|string|max:500',
        ]);

        if (empty(trim($this->message))) {
            return;
        }

        try {
            $player = auth()->user()->player;
            if (!$player) {
                session()->flash('error', 'Player not found!');
                return;
            }

            switch ($this->activeChannelType) {
                case 'global':
                    $this->chatService->sendGlobalMessage($player->id, $this->message);
                    break;
                case 'alliance':
                    if ($player->alliance_id) {
                        $this->chatService->sendAllianceMessage($player->id, $player->alliance_id, $this->message);
                    }
                    break;
                default:
                    $this->chatService->sendMessage($player->id, $this->activeChannelId, $this->activeChannelType, $this->message);
                    break;
            }

            $this->message = '';
            $this->dispatch('message-sent');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to send message: ' . $e->getMessage());
        }
    }

    public function deleteMessage($messageId)
    {
        try {
            $player = auth()->user()->player;
            if (!$player) {
                session()->flash('error', 'Player not found!');
                return;
            }

            $success = $this->chatService->deleteMessage($messageId, $player->id);
            
            if ($success) {
                $this->dispatch('message-deleted');
                session()->flash('success', 'Message deleted successfully!');
            } else {
                session()->flash('error', 'Cannot delete this message!');
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete message: ' . $e->getMessage());
        }
    }

    public function handleUserTyping($userId, $userName)
    {
        if ($userId !== auth()->id()) {
            $this->typingUsers[$userId] = $userName;
        }
    }

    public function handleUserStoppedTyping($userId)
    {
        unset($this->typingUsers[$userId]);
    }

    public function startTyping()
    {
        if (!$this->isTyping) {
            $this->isTyping = true;
            $this->dispatch('user-typing', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'channel_id' => $this->activeChannelId,
                'channel_type' => $this->activeChannelType,
            ]);
        }
    }

    public function stopTyping()
    {
        if ($this->isTyping) {
            $this->isTyping = false;
            $this->dispatch('user-stopped-typing', [
                'user_id' => auth()->id(),
                'channel_id' => $this->activeChannelId,
                'channel_type' => $this->activeChannelType,
            ]);
        }
    }

    public function refreshMessages()
    {
        // This will trigger a re-render with updated messages
    }

    public function updatedMessage()
    {
        if (!empty(trim($this->message))) {
            $this->startTyping();
        } else {
            $this->stopTyping();
        }
    }

    public function getChannelIcon($channelType)
    {
        return match ($channelType) {
            'global' => 'fas fa-globe',
            'alliance' => 'fas fa-users',
            'private' => 'fas fa-user-friends',
            'trade' => 'fas fa-exchange-alt',
            'diplomacy' => 'fas fa-handshake',
            default => 'fas fa-comments',
        };
    }

    public function getChannelColor($channelType)
    {
        return match ($channelType) {
            'global' => 'text-blue-600',
            'alliance' => 'text-green-600',
            'private' => 'text-purple-600',
            'trade' => 'text-yellow-600',
            'diplomacy' => 'text-red-600',
            default => 'text-gray-600',
        };
    }

    public function formatMessage($message, $messageType)
    {
        if ($messageType === ChatMessage::TYPE_SYSTEM) {
            return '<span class="text-gray-500 italic">' . e($message) . '</span>';
        }
        
        if ($messageType === ChatMessage::TYPE_ANNOUNCEMENT) {
            return '<span class="text-yellow-600 font-semibold">' . e($message) . '</span>';
        }
        
        return e($message);
    }

    public function getTypingText()
    {
        $count = count($this->typingUsers);
        
        if ($count === 0) {
            return '';
        }
        
        if ($count === 1) {
            $name = reset($this->typingUsers);
            return "{$name} is typing...";
        }
        
        return "{$count} people are typing...";
    }
}
