<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
    class TelescopeServiceProvider extends \Laravel\Telescope\TelescopeApplicationServiceProvider
    {
        public function register(): void
        {
            // \Laravel\Telescope\Telescope::night();

            $this->hideSensitiveRequestDetails();

            $isLocal = $this->app->environment('local');

            \Laravel\Telescope\Telescope::filter(function (\Laravel\Telescope\IncomingEntry $entry) use ($isLocal) {
                return $isLocal ||
                       $entry->isReportableException() ||
                       $entry->isFailedRequest() ||
                       $entry->isFailedJob() ||
                       $entry->isScheduledTask() ||
                       $entry->hasMonitoredTag();
            });
        }

        protected function hideSensitiveRequestDetails(): void
        {
            if ($this->app->environment('local')) {
                return;
            }

            \Laravel\Telescope\Telescope::hideRequestParameters(['_token']);

            \Laravel\Telescope\Telescope::hideRequestHeaders([
                'cookie',
                'x-csrf-token',
                'x-xsrf-token',
            ]);
        }

        protected function gate(): void
        {
            Gate::define('viewTelescope', function ($user) {
                return in_array($user->email, [
                    //
                ]);
            });
        }
    }
} else {
    class TelescopeServiceProvider extends ServiceProvider
    {
        public function register(): void
        {
            // Telescope is not installed; nothing to register.
        }

        protected function gate(): void
        {
            Gate::define('viewTelescope', fn ($user = null) => false);
        }
    }
}
