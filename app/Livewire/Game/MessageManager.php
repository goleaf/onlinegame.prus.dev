<?php

namespace App\Livewire\Game;

use App\Models\Game\Message;
use App\Models\Game\Player;
use App\Services\MessageService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Message Manager')]
#[Layout('layouts.game')]
class MessageManager extends Component
{
    use WithPagination;

    public $activeTab = 'inbox';
    public $selectedMessage = null;
    public $composeMode = false;
    public $replyMode = false;
    public $replyToMessage = null;
    // Compose form
    public $recipientId = '';
    public $recipientName = '';
    public $subject = '';
    public $body = '';
    public $priority = Message::PRIORITY_NORMAL;
    public $messageType = Message::TYPE_PRIVATE;
    // Search and filters
    public $search = '';
    public $filterType = 'all';
    public $filterPriority = 'all';
    public $filterRead = 'all';
    // Bulk actions
    public $selectedMessages = [];
    public $selectAll = false;

    protected $messageService;

    protected $listeners = [
        'messageReceived' => 'refreshMessages',
        'messageDeleted' => 'refreshMessages',
        'messageRead' => 'refreshMessages',
    ];

    public function boot()
    {
        $this->messageService = new MessageService();
    }

    public function mount()
    {
        $this->loadMessageStats();
    }

    public function render()
    {
        $messages = $this->getMessages();
        $messageStats = $this->getMessageStats();
        $players = $this->getPlayersForCompose();

        return view('livewire.game.message-manager', [
            'messages' => $messages,
            'messageStats' => $messageStats,
            'players' => $players,
        ]);
    }

    public function getMessages()
    {
        $query = Message::with(['sender', 'recipient', 'alliance'])
            ->forPlayer(auth()->user()->player->id);

        // Apply filters based on active tab
        switch ($this->activeTab) {
            case 'inbox':
                $query
                    ->where('recipient_id', auth()->user()->player->id)
                    ->where('is_deleted_by_recipient', false);
                break;
            case 'sent':
                $query
                    ->where('sender_id', auth()->user()->player->id)
                    ->where('is_deleted_by_sender', false);
                break;
            case 'alliance':
                $query
                    ->where('alliance_id', auth()->user()->player->alliance_id)
                    ->where('message_type', Message::TYPE_ALLIANCE);
                break;
            case 'system':
                $query
                    ->where('recipient_id', auth()->user()->player->id)
                    ->where('message_type', Message::TYPE_SYSTEM)
                    ->where('is_deleted_by_recipient', false);
                break;
        }

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q
                    ->where('subject', 'like', '%' . $this->search . '%')
                    ->orWhere('body', 'like', '%' . $this->search . '%');
            });
        }

        // Apply type filter
        if ($this->filterType !== 'all') {
            $query->where('message_type', $this->filterType);
        }

        // Apply priority filter
        if ($this->filterPriority !== 'all') {
            $query->where('priority', $this->filterPriority);
        }

        // Apply read filter
        if ($this->filterRead !== 'all') {
            $query->where('is_read', $this->filterRead === 'read');
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    public function getMessageStats()
    {
        return $this->messageService->getMessageStats(auth()->user()->player->id);
    }

    public function getPlayersForCompose()
    {
        return Player::where('id', '!=', auth()->user()->player->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->selectedMessages = [];
        $this->selectAll = false;
    }

    public function openMessage($messageId)
    {
        $this->selectedMessage = Message::with(['sender', 'recipient', 'alliance'])
            ->find($messageId);

        if ($this->selectedMessage && $this->selectedMessage->recipient_id === auth()->user()->player->id) {
            $this->messageService->markAsRead($messageId, auth()->user()->player->id);
        }
    }

    public function closeMessage()
    {
        $this->selectedMessage = null;
    }

    public function startCompose()
    {
        $this->composeMode = true;
        $this->replyMode = false;
        $this->resetComposeForm();
    }

    public function startReply($messageId)
    {
        $message = Message::find($messageId);
        if ($message) {
            $this->replyMode = true;
            $this->composeMode = true;
            $this->replyToMessage = $message;

            $this->recipientId = $message->sender_id;
            $this->recipientName = $message->sender->name ?? 'Unknown';
            $this->subject = 'Re: ' . $message->subject;
            $this->body = '';
            $this->priority = $message->priority;
        }
    }

    public function cancelCompose()
    {
        $this->composeMode = false;
        $this->replyMode = false;
        $this->replyToMessage = null;
        $this->resetComposeForm();
    }

    public function sendMessage()
    {
        $this->validate([
            'recipientId' => 'required|exists:players,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'priority' => 'required|in:' . implode(',', [Message::PRIORITY_LOW, Message::PRIORITY_NORMAL, Message::PRIORITY_HIGH, Message::PRIORITY_URGENT]),
        ]);

        try {
            $this->messageService->sendPrivateMessage(
                auth()->user()->player->id,
                $this->recipientId,
                $this->subject,
                $this->body,
                $this->priority
            );

            $this->dispatch('message-sent');
            $this->cancelCompose();

            session()->flash('success', 'Message sent successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to send message: ' . $e->getMessage());
        }
    }

    public function deleteMessage($messageId)
    {
        try {
            $this->messageService->deleteMessage($messageId, auth()->user()->player->id);
            $this->dispatch('message-deleted');

            if ($this->selectedMessage && $this->selectedMessage->id == $messageId) {
                $this->selectedMessage = null;
            }

            session()->flash('success', 'Message deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete message: ' . $e->getMessage());
        }
    }

    public function markAsRead($messageId)
    {
        try {
            $this->messageService->markAsRead($messageId, auth()->user()->player->id);
            $this->dispatch('message-read');

            session()->flash('success', 'Message marked as read!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to mark message as read: ' . $e->getMessage());
        }
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedMessages = $this->getMessages()->pluck('id')->toArray();
        } else {
            $this->selectedMessages = [];
        }
    }

    public function toggleMessageSelection($messageId)
    {
        if (in_array($messageId, $this->selectedMessages)) {
            $this->selectedMessages = array_diff($this->selectedMessages, [$messageId]);
        } else {
            $this->selectedMessages[] = $messageId;
        }

        $this->selectAll = count($this->selectedMessages) === $this->getMessages()->count();
    }

    public function bulkMarkAsRead()
    {
        if (empty($this->selectedMessages)) {
            session()->flash('error', 'No messages selected!');
            return;
        }

        try {
            $this->messageService->bulkMarkAsRead($this->selectedMessages, auth()->user()->player->id);
            $this->dispatch('messages-bulk-read');

            $this->selectedMessages = [];
            $this->selectAll = false;

            session()->flash('success', 'Messages marked as read!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to mark messages as read: ' . $e->getMessage());
        }
    }

    public function bulkDelete()
    {
        if (empty($this->selectedMessages)) {
            session()->flash('error', 'No messages selected!');
            return;
        }

        try {
            $this->messageService->bulkDeleteMessages($this->selectedMessages, auth()->user()->player->id);
            $this->dispatch('messages-bulk-deleted');

            $this->selectedMessages = [];
            $this->selectAll = false;

            session()->flash('success', 'Messages deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete messages: ' . $e->getMessage());
        }
    }

    public function refreshMessages()
    {
        $this->loadMessageStats();
    }

    public function loadMessageStats()
    {
        // This will trigger a re-render with updated stats
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedFilterPriority()
    {
        $this->resetPage();
    }

    public function updatedFilterRead()
    {
        $this->resetPage();
    }

    private function resetComposeForm()
    {
        $this->recipientId = '';
        $this->recipientName = '';
        $this->subject = '';
        $this->body = '';
        $this->priority = Message::PRIORITY_NORMAL;
        $this->messageType = Message::TYPE_PRIVATE;
    }

    public function getPriorityClass($priority)
    {
        return match ($priority) {
            Message::PRIORITY_URGENT => 'text-red-600 font-bold',
            Message::PRIORITY_HIGH => 'text-orange-600 font-semibold',
            Message::PRIORITY_NORMAL => 'text-gray-600',
            Message::PRIORITY_LOW => 'text-gray-400',
            default => 'text-gray-600',
        };
    }

    public function getMessageTypeIcon($type)
    {
        return match ($type) {
            Message::TYPE_PRIVATE => 'fas fa-envelope',
            Message::TYPE_ALLIANCE => 'fas fa-users',
            Message::TYPE_SYSTEM => 'fas fa-cog',
            Message::TYPE_BATTLE_REPORT => 'fas fa-sword',
            Message::TYPE_TRADE_OFFER => 'fas fa-exchange-alt',
            Message::TYPE_DIPLOMACY => 'fas fa-handshake',
            default => 'fas fa-envelope',
        };
    }
}
