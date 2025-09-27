<?php

namespace App\Livewire\Game;

use App\Models\Game\ChatMessage;
use App\Models\Game\ChatChannel;
use App\Services\ChatService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ChatManager extends Component
{
    use WithPagination;

    public $selectedChannel = 'global';
    public $message = '';
    public $messageType = 'text';
    public $recipientId = null;
    public $searchQuery = '';
    public $searchChannelType = '';
    
    protected $chatService;
    
    protected $listeners = [
        'messageSent' => 'refreshMessages',
        'channelChanged' => 'changeChannel',
        'messageDeleted' => 'refreshMessages',
    ];

    public function boot()
    {
        $this->chatService = new ChatService();
    }

    public function mount()
    {
        $this->selectedChannel = 'global';
    }

    public function render()
    {
        $channels = $this->chatService->getAvailableChannels(Auth::user()->player->id);
        $messages = $this->getMessages();
        $stats = $this->chatService->getMessageStats();

        return view('livewire.game.chat-manager', [
            'channels' => $channels,
            'messages' => $messages,
            'stats' => $stats,
        ]);
    }

    public function sendMessage()
    {
        $this->validate([
            'message' => 'required|string|max:1000',
            'messageType' => 'required|in:text,system,announcement,emote,command',
        ]);

        try {
            switch ($this->selectedChannel) {
                case 'global':
                    $this->chatService->sendGlobalMessage(
                        Auth::user()->player->id,
                        $this->message,
                        $this->messageType
                    );
                    break;
                case 'alliance':
                    $this->chatService->sendAllianceMessage(
                        Auth::user()->player->id,
                        Auth::user()->player->alliance_id,
                        $this->message,
                        $this->messageType
                    );
                    break;
                case 'private':
                    if (!$this->recipientId) {
                        $this->addError('recipientId', 'Recipient is required for private messages.');
                        return;
                    }
                    $this->chatService->sendPrivateMessage(
                        Auth::user()->player->id,
                        $this->recipientId,
                        $this->message
                    );
                    break;
                case 'trade':
                    $this->chatService->sendTradeMessage(
                        Auth::user()->player->id,
                        $this->message
                    );
                    break;
                case 'diplomacy':
                    $this->chatService->sendDiplomacyMessage(
                        Auth::user()->player->id,
                        $this->message
                    );
                    break;
            }

            $this->message = '';
            $this->emit('messageSent');
            $this->dispatch('message-sent');

        } catch (\Exception $e) {
            $this->addError('message', 'Failed to send message: ' . $e->getMessage());
        }
    }

    public function deleteMessage($messageId)
    {
        try {
            $success = $this->chatService->deleteMessage($messageId, Auth::user()->player->id);
            
            if ($success) {
                $this->emit('messageDeleted');
                $this->dispatch('message-deleted');
            } else {
                $this->addError('message', 'Failed to delete message or access denied.');
            }
        } catch (\Exception $e) {
            $this->addError('message', 'Failed to delete message: ' . $e->getMessage());
        }
    }

    public function changeChannel($channel)
    {
        $this->selectedChannel = $channel;
        $this->resetPage();
        $this->emit('channelChanged', $channel);
    }

    public function searchMessages()
    {
        $this->resetPage();
    }

    public function refreshMessages()
    {
        $this->resetPage();
    }

    protected function getMessages()
    {
        if ($this->searchQuery) {
            $result = $this->chatService->searchMessages(
                $this->searchQuery,
                $this->searchChannelType ?: null,
                50
            );
            return $result['messages'];
        }

        if ($this->selectedChannel === 'global') {
            $result = $this->chatService->getMessagesByType('global', 50, 0);
            return $result['messages'];
        }

        if ($this->selectedChannel === 'alliance') {
            $result = $this->chatService->getMessagesByType('alliance', 50, 0);
            return $result['messages'];
        }

        if ($this->selectedChannel === 'private') {
            $result = $this->chatService->getMessagesByType('private', 50, 0);
            return $result['messages'];
        }

        if ($this->selectedChannel === 'trade') {
            $result = $this->chatService->getMessagesByType('trade', 50, 0);
            return $result['messages'];
        }

        if ($this->selectedChannel === 'diplomacy') {
            $result = $this->chatService->getMessagesByType('diplomacy', 50, 0);
            return $result['messages'];
        }

        return collect();
    }

    public function getChannelTypeColor($channelType)
    {
        return match ($channelType) {
            'global' => 'blue',
            'alliance' => 'green',
            'private' => 'purple',
            'trade' => 'yellow',
            'diplomacy' => 'red',
            default => 'gray',
        };
    }

    public function getMessageTypeIcon($messageType)
    {
        return match ($messageType) {
            'text' => 'comment',
            'system' => 'cog',
            'announcement' => 'bullhorn',
            'emote' => 'smile',
            'command' => 'terminal',
            default => 'comment',
        };
    }

    public function canDeleteMessage($message)
    {
        return $message->sender_id === Auth::user()->player->id;
    }

    public function getFormattedMessage($message)
    {
        if ($message->message_type === 'emote') {
            return "*{$message->sender->name} {$message->message}*";
        }

        return $message->message;
    }
}