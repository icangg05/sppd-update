<?php

namespace App\Providers;

use App\Enums\ApprovalStatus;
use App\Models\SppdApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Share pending approval count with the sidebar view
        View::composer('components.sidebar', function ($view) {
            $pendingApprovalCount = 0;

            if (Auth::check()) {
                $pendingApprovalCount = SppdApproval::where('approver_id', Auth::id())
                    ->where('status', ApprovalStatus::PENDING)
                    ->count();
            }

            $view->with('pendingApprovalCount', $pendingApprovalCount);
        });

        // Force HTTPS in production
        $isHttps = request()->isSecure() || request()->header('x-forwarded-proto') === 'https';

        if (config('app.env') === 'production' || $isHttps) {
            URL::forceScheme('https');
        }
    }
}
