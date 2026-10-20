<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Satu pembacaan data.json per request/job.
        $this->app->scoped(\App\Services\AppSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        $isHttps = request()->isSecure() || request()->header('x-forwarded-proto') === 'https';

        if (config('app.env') === 'production' || $isHttps) {
            URL::forceScheme('https');
        }
    }
}
