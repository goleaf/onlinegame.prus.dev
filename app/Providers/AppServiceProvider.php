<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
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

        // Register core game services
        $this->app->singleton(\App\Services\GameTickService::class);
        $this->app->singleton(\App\Services\BattleSimulationService::class);
        $this->app->singleton(\App\Services\DefenseCalculationService::class);
        $this->app->singleton(\App\Services\AllianceWarfareService::class);
        $this->app->singleton(\App\Services\ResourceProductionService::class);
        $this->app->singleton(\App\Services\TroopService::class);
        $this->app->singleton(\App\Services\BuildingService::class);
        $this->app->singleton(\App\Services\ChatService::class);
        $this->app->singleton(\App\Services\MessageService::class);
        $this->app->singleton(\App\Services\RealTimeGameService::class);

        // Register geographic and analysis services
        $this->app->singleton(\App\Services\GeographicService::class);
        $this->app->singleton(\App\Services\GeographicAnalysisService::class);

        // Register cache and performance services
        $this->app->singleton(\App\Services\GameCacheService::class);
        $this->app->singleton(\App\Services\CacheEvictionService::class);
        $this->app->singleton(\App\Services\QueryOptimizationService::class);
        $this->app->singleton(\App\Services\SmartCacheGameOptimizer::class);

        // Register SEO and analytics services
        $this->app->singleton(\App\Services\GameSeoService::class);
        $this->app->singleton(\App\Services\SeoCacheService::class);
        $this->app->singleton(\App\Services\GameNotificationService::class);
        $this->app->singleton(\App\Services\QueryEnrichService::class);
        $this->app->singleton(\App\Services\GameQueryEnrichService::class);
        $this->app->singleton(\App\Services\QueryEnrichAnalyticsService::class);

        // Register AI and integration services
        $this->app->singleton(\App\Services\AIService::class);
        $this->app->singleton(\App\Services\LarautilxIntegrationService::class);
        $this->app->singleton(\App\Services\GameIntegrationService::class);
        $this->app->singleton(\App\Services\IntrospectService::class);

        // Register RabbitMQ and messaging services
        $this->app->singleton(\App\Services\RabbitMQService::class);

        // Register security and value object services
        $this->app->singleton(\App\Services\GameSecurityService::class);
        $this->app->singleton(\App\Services\ValueObjectService::class);
        $this->app->singleton(\App\Services\UpdaterService::class);

        // Register artifact and specialized services
        $this->app->singleton(\App\Services\ArtifactEffectService::class);

        // Register LaraUtilX utilities with proper dependencies
        $this->app->bind(\LaraUtilX\Utilities\CachingUtil::class, function ($app) {
            return new \LaraUtilX\Utilities\CachingUtil(
                config('lara-util-x.cache.default_expiration', 3600),
                config('lara-util-x.cache.default_tags', ['game'])
            );
        });
        
        $this->app->bind(\LaraUtilX\Utilities\LoggingUtil::class, function ($app) {
            return new \LaraUtilX\Utilities\LoggingUtil();
        });

        // Register Basset helper
        $this->app->singleton(\App\Helpers\BassetHelper::class);
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

        // Register model observers
        User::observe(UserObserver::class);
    }
}
