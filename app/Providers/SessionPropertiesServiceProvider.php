<?php

namespace App\Providers;

use App\Services\SessionPropertiesService;
use Illuminate\Support\ServiceProvider;

class SessionPropertiesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SessionPropertiesService::class, function ($app) {
            return new SessionPropertiesService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register default component properties
        $sessionService = $this->app->make(SessionPropertiesService::class);

        // Register GameDashboard properties
        $sessionService->registerComponentProperties(
            \App\Livewire\Game\GameDashboard::class,
            [
                'selectedVillageId' => [
                    'default' => null,
                    'validation' => ['type' => 'integer'],
                ],
                'gameSpeed' => [
                    'default' => 1,
                    'validation' => ['type' => 'float', 'min' => 0.5, 'max' => 3.0],
                ],
                'showNotifications' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'realTimeUpdates' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'dashboardLayout' => [
                    'default' => 'grid',
                    'validation' => ['in' => ['grid', 'list', 'compact']],
                ],
                'resourceViewMode' => [
                    'default' => 'detailed',
                    'validation' => ['in' => ['detailed', 'compact', 'minimal']],
                ],
                'buildingViewMode' => [
                    'default' => 'list',
                    'validation' => ['in' => ['list', 'grid', 'detailed']],
                ],
            ]
        );

        // Register ChatComponent properties
        $sessionService->registerComponentProperties(
            \App\Livewire\Game\ChatComponent::class,
            [
                'selectedChannelId' => [
                    'default' => null,
                    'validation' => ['type' => 'integer'],
                ],
                'showChannels' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'showEmojis' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'autoScroll' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'selectedMessageType' => [
                    'default' => 'text',
                    'validation' => ['in' => ['text', 'image', 'file', 'link']],
                ],
                'chatLayout' => [
                    'default' => 'sidebar',
                    'validation' => ['in' => ['sidebar', 'fullscreen', 'popup']],
                ],
                'messageDisplayMode' => [
                    'default' => 'bubbles',
                    'validation' => ['in' => ['bubbles', 'list', 'compact']],
                ],
                'showTimestamps' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'showUserAvatars' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'enableSounds' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'enableNotifications' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'fontSize' => [
                    'default' => 'medium',
                    'validation' => ['in' => ['small', 'medium', 'large']],
                ],
                'theme' => [
                    'default' => 'light',
                    'validation' => ['in' => ['light', 'dark', 'auto']],
                ],
            ]
        );

        // Register RealTimeGameComponent properties
        $sessionService->registerComponentProperties(
            \App\Livewire\Game\RealTimeGameComponent::class,
            [
                'autoRefresh' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'refreshInterval' => [
                    'default' => 30,
                    'validation' => ['type' => 'integer', 'min' => 5, 'max' => 300],
                ],
                'realTimeUpdates' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'showNotifications' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
            ]
        );

        // Register AdminDashboard properties
        $sessionService->registerComponentProperties(
            \App\Livewire\Admin\AdminDashboard::class,
            [
                'activeTab' => [
                    'default' => 'overview',
                    'validation' => ['in' => ['overview', 'statistics', 'users', 'system']],
                ],
                'showSystemInfo' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'showRecentUpdates' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'autoRefresh' => [
                    'default' => true,
                    'validation' => ['type' => 'boolean'],
                ],
                'refreshInterval' => [
                    'default' => 60,
                    'validation' => ['type' => 'integer', 'min' => 10, 'max' => 600],
                ],
                'dashboardLayout' => [
                    'default' => 'grid',
                    'validation' => ['in' => ['grid', 'list', 'compact']],
                ],
            ]
        );
    }
}
