<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent lazy loading in non-production environments
        // This will throw exceptions when N+1 queries are detected
        Model::preventLazyLoading(! $this->app->isProduction());
        
        // Prevent accessing missing attributes silently
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        
        // Prevent accessing missing relationships silently
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
    }
}
