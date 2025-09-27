<?php

namespace App\Providers;

use App\Services\FathomAnalytics;
use Illuminate\Support\ServiceProvider;

class FathomServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FathomAnalytics::class, function ($app) {
            return new FathomAnalytics();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
