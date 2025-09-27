<?php

namespace App\Livewire\Game;

use Livewire\Component;
use App\Services\RealTimeGameService;
use App\Services\GameCacheService;
use App\Services\GameNotificationService;
use App\Utilities\GameUtility;
use Illuminate\Support\Facades\Auth;

class RealTimeGameComponent extends Component
{
    public $userId;
    public $updates = [];
    public $notifications = [];
    public $onlineUsers = 0;
    public $lastUpdate = null;
    public $isConnected = false;
    public $autoRefresh = true;
    public $refreshInterval = 30; // seconds

    protected $listeners = [
        'refreshUpdates' => 'loadUpdates',
        'refreshNotifications' => 'loadNotifications',
        'toggleAutoRefresh' => 'toggleAutoRefresh',
        'clearUpdates' => 'clearUpdates',
        'clearNotifications' => 'clearNotifications',
    ];

    public function mount()
    {
        $this->userId = Auth::id();
        $this->loadUpdates();
        $this->loadNotifications();
        $this->loadOnlineStats();
        
        if ($this->autoRefresh) {
            $this->startAutoRefresh();
        }
    }

    public function loadUpdates()
    {
        try {
            $this->updates = RealTimeGameService::getUserUpdates($this->userId, 20);
            $this->lastUpdate = now()->toISOString();
            $this->isConnected = true;
        } catch (\Exception $e) {
            $this->isConnected = false;
            session()->flash('error', 'Failed to load real-time updates');
        }
    }

    public function loadNotifications()
    {
        try {
            $this->notifications = GameNotificationService::getUserNotifications($this->userId, 10);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load notifications');
        }
    }

    public function loadOnlineStats()
    {
        try {
            $stats = RealTimeGameService::getRealTimeStats();
            $this->onlineUsers = $stats['online_users_count'] ?? 0;
        } catch (\Exception $e) {
            $this->onlineUsers = 0;
        }
    }

    public function clearUpdates()
    {
        try {
            RealTimeGameService::clearUserUpdates($this->userId);
            $this->updates = [];
            $this->lastUpdate = null;
            session()->flash('success', 'Updates cleared successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear updates');
        }
    }

    public function clearNotifications()
    {
        try {
            GameNotificationService::clearNotifications($this->userId);
            $this->notifications = [];
            session()->flash('success', 'Notifications cleared successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear notifications');
        }
    }

    public function markNotificationAsRead($notificationId)
    {
        try {
            GameNotificationService::markAsRead($this->userId, $notificationId);
            $this->loadNotifications();
            session()->flash('success', 'Notification marked as read');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to mark notification as read');
        }
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->startAutoRefresh();
        } else {
            $this->stopAutoRefresh();
        }
    }

    public function startAutoRefresh()
    {
        $this->js("
            if (window.gameRefreshInterval) {
                clearInterval(window.gameRefreshInterval);
            }
            
            window.gameRefreshInterval = setInterval(() => {
                Livewire.emit('refreshUpdates');
                Livewire.emit('refreshNotifications');
                Livewire.emit('loadOnlineStats');
            }, " . ($this->refreshInterval * 1000) . ");
        ");
    }

    public function stopAutoRefresh()
    {
        $this->js("
            if (window.gameRefreshInterval) {
                clearInterval(window.gameRefreshInterval);
                window.gameRefreshInterval = null;
            }
        ");
    }

    public function sendTestMessage()
    {
        try {
            RealTimeGameService::sendUpdate($this->userId, 'test_message', [
                'message' => 'Test message from Livewire component',
                'timestamp' => now()->toISOString(),
            ]);
            
            session()->flash('success', 'Test message sent successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to send test message');
        }
    }

    public function formatTimestamp($timestamp)
    {
        return \Carbon\Carbon::parse($timestamp)->diffForHumans();
    }

    public function getUpdateIcon($eventType)
    {
        $icons = [
            'village_update' => '🏘️',
            'resource_update' => '💰',
            'battle_update' => '⚔️',
            'movement_update' => '🚶',
            'building_update' => '🏗️',
            'alliance_update' => '🤝',
            'system_announcement' => '📢',
            'test_message' => '🧪',
        ];

        return $icons[$eventType] ?? '📄';
    }

    public function getNotificationIcon($type)
    {
        $icons = [
            'battle_attack' => '⚔️',
            'battle_defense' => '🛡️',
            'building_complete' => '🏗️',
            'research_complete' => '🔬',
            'movement_arrived' => '🚶',
            'alliance_invite' => '🤝',
            'alliance_message' => '💬',
            'resource_full' => '💰',
            'village_attacked' => '🚨',
            'achievement_unlocked' => '🏆',
            'quest_complete' => '✅',
            'system_message' => '📢',
        ];

        return $icons[$type] ?? '📄';
    }

    public function getPriorityColor($priority)
    {
        $colors = [
            'low' => 'text-gray-500',
            'normal' => 'text-blue-600',
            'high' => 'text-orange-600',
            'urgent' => 'text-red-600',
        ];

        return $colors[$priority] ?? 'text-blue-600';
    }

    public function getPriorityBadgeColor($priority)
    {
        $colors = [
            'low' => 'bg-gray-100 text-gray-800',
            'normal' => 'bg-blue-100 text-blue-800',
            'high' => 'bg-orange-100 text-orange-800',
            'urgent' => 'bg-red-100 text-red-800',
        ];

        return $colors[$priority] ?? 'bg-blue-100 text-blue-800';
    }

    public function render()
    {
        return view('livewire.game.real-time-game-component', [
            'formattedLastUpdate' => $this->lastUpdate ? $this->formatTimestamp($this->lastUpdate) : 'Never',
            'connectionStatus' => $this->isConnected ? 'Connected' : 'Disconnected',
            'connectionColor' => $this->isConnected ? 'text-green-600' : 'text-red-600',
        ]);
    }

    public function updatedRefreshInterval()
    {
        if ($this->autoRefresh) {
            $this->startAutoRefresh();
        }
    }

    public function dehydrate()
    {
        $this->stopAutoRefresh();
    }
}
