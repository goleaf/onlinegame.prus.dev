<?php

namespace App\Livewire\Game;

use App\Services\GameCacheService;
use App\Services\GameNotificationService;
use App\Services\GameIntegrationService;
use App\Services\RealTimeGameService;
use App\Utilities\GameUtility;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RealTimeGameComponent extends Component
{
    public $userId;
    public $updates = [];
    public $notifications = [];
    public $onlineUsers = 0;
    public $lastUpdate = null;
    public $isConnected = false;
    public $autoRefresh = true;
    public $refreshInterval = 30;  // seconds

    protected $listeners = [
        'refreshUpdates' => 'loadUpdates',
        'refreshNotifications' => 'loadNotifications',
        'toggleAutoRefresh' => 'toggleAutoRefresh',
        'clearUpdates' => 'clearUpdates',
        'clearNotifications' => 'clearNotifications',
        'realTimeEvent' => 'handleRealTimeEvent',
        'gameEvent' => 'handleGameEvent',
        'systemNotification' => 'handleSystemNotification',
        'userStatusUpdate' => 'handleUserStatusUpdate',
    ];

    public function mount()
    {
        $this->userId = Auth::id();
        $this->initializeWithIntegration();
        $this->loadUpdates();
        $this->loadNotifications();
        $this->loadOnlineStats();

        if ($this->autoRefresh) {
            $this->startAutoRefresh();
        }
    }

    /**
     * Initialize component with real-time integration
     */
    public function initializeWithIntegration(): void
    {
        try {
            // Initialize real-time features for the user
            GameIntegrationService::initializeUserRealTime($this->userId);
            
            $this->dispatch('realtime-initialized', [
                'message' => 'Real-time game component initialized',
                'user_id' => $this->userId,
                'refresh_interval' => $this->refreshInterval,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to initialize real-time features: ' . $e->getMessage(),
            ]);
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
            }, " . ($this->refreshInterval * 1000) . ');
        ');
    }

    public function stopAutoRefresh()
    {
        $this->js('
            if (window.gameRefreshInterval) {
                clearInterval(window.gameRefreshInterval);
                window.gameRefreshInterval = null;
            }
        ');
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

    /**
     * Initialize real-time features
     */
    public function initializeRealTimeFeatures()
    {
        try {
            if ($this->userId) {
                // Initialize real-time features for the user
                GameIntegrationService::initializeUserRealTime($this->userId);
                
                $this->dispatch('realtime-initialized', [
                    'message' => 'Real-time game component features activated',
                    'user_id' => $this->userId,
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to initialize real-time features: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear updates with real-time integration
     */
    public function clearUpdatesWithIntegration()
    {
        try {
            $this->clearUpdates();

            if ($this->userId) {
                // Send notification about clearing updates
                GameNotificationService::sendNotification(
                    $this->userId,
                    'updates_cleared',
                    [
                        'user_id' => $this->userId,
                        'timestamp' => now()->toISOString(),
                    ]
                );

                $this->dispatch('updates-cleared', [
                    'message' => 'Updates cleared successfully with notifications',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to clear updates: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear notifications with real-time integration
     */
    public function clearNotificationsWithIntegration()
    {
        try {
            $this->clearNotifications();

            if ($this->userId) {
                // Send notification about clearing notifications
                GameNotificationService::sendNotification(
                    $this->userId,
                    'notifications_cleared',
                    [
                        'user_id' => $this->userId,
                        'timestamp' => now()->toISOString(),
                    ]
                );

                $this->dispatch('notifications-cleared', [
                    'message' => 'Notifications cleared successfully with notifications',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to clear notifications: ' . $e->getMessage(),
            ]);
        }
    }

    public function dehydrate()
    {
        $this->stopAutoRefresh();
    }
}
