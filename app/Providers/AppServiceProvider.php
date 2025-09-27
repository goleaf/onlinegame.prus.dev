<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enhanced dependency injection for Laravel 12.29.0+ features
        $this->app->singleton('game.cache', function ($app) {
            return $app->make('cache.store');
        });

        $this->app->singleton('game.session', function ($app) {
            return $app->make('session.store');
        });

        // Auto-resolve common game services
        $this->app->bindIf(\App\Services\GameMechanicsService::class, function ($app) {
            return new \App\Services\GameMechanicsService(
                $app->make('game.cache'),
                $app->make('game.session')
            );
        });

        // Register enhanced services for Laravel 12.29.0+ features
        $this->app->singleton(\App\Services\EnhancedCacheService::class);
        $this->app->singleton(\App\Services\EnhancedSessionService::class);
        $this->app->singleton(\App\Services\GamePerformanceOptimizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent lazy loading in non-production environments
        // This will throw exceptions when N+1 queries are detected
        Model::preventLazyLoading(!$this->app->isProduction());

        // Prevent accessing missing attributes silently
        Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());

        // Prevent accessing missing relationships silently
        Model::preventAccessingMissingAttributes(!$this->app->isProduction());

        // Enhanced debug page configuration for Laravel 12.29.0+
        if ($this->app->environment('local', 'development')) {
            // Enable enhanced debug features
            config(['app.debug' => true]);

            // Configure enhanced error reporting
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }
    }
}
