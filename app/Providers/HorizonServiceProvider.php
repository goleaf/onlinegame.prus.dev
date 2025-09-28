<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

if (class_exists(\Laravel\Horizon\HorizonApplicationServiceProvider::class)) {
    class HorizonServiceProvider extends \Laravel\Horizon\HorizonApplicationServiceProvider
    {
        public function boot(): void
        {
            parent::boot();

            // Horizon::routeSmsNotificationsTo('15556667777');
            // Horizon::routeMailNotificationsTo('example@example.com');
            // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
        }

        protected function gate(): void
        {
            Gate::define('viewHorizon', function ($user = null) {
                return in_array(optional($user)->email, [
                    //
                ]);
            });
        }
    }
} else {
    class HorizonServiceProvider extends ServiceProvider
    {
        public function boot(): void
        {
            // Horizon is not installed; nothing to bootstrap.
        }

        protected function gate(): void
        {
            Gate::define('viewHorizon', fn ($user = null) => false);
        }
    }
}
