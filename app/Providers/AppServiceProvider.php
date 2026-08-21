<?php

namespace App\Providers;

use App\Models\PartnerRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
        // Set application timezone from settings
        try {
            $timezone = getAppTimezone();
            if ($timezone && in_array($timezone, \DateTimeZone::listIdentifiers())) {
                date_default_timezone_set($timezone);
                config(['app.timezone' => $timezone]);
            }
        } catch (\Exception $e) {
            // Fallback to default timezone if settings table doesn't exist
        }

        // Configure rate limiters for Partner submission operations
        $this->configurePartnerRateLimiting();

        View::composer('partials.sidebar', function ($view) {
            $pendingPartnerRequestCount = 0;

            $user = Auth::guard('web')->user();

            if ($user && $user->hasPermission('view_trips')) {
                if (Schema::hasTable('partner_requests')) {
                    $pendingPartnerRequestCount = PartnerRequest::query()
                        ->where('status', PartnerRequest::STATUS_PENDING)
                        ->count();
                }
            }

            $view->with('pendingPartnerRequestCount', $pendingPartnerRequestCount);
        });
    }

    /**
     * Configure rate limiting for Partner submission operations.
     */
    protected function configurePartnerRateLimiting(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('partner-image-submission', function ($request) {
            $partnerId = Auth::guard('partner')->id();
            return $partnerId
                ? \Illuminate\Cache\RateLimiting\Limit::perMinute(6)->by($partnerId)
                : \Illuminate\Cache\RateLimiting\Limit::perMinute(6)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('partner-manual-submission', function ($request) {
            $partnerId = Auth::guard('partner')->id();
            return $partnerId
                ? \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by($partnerId)
                : \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by($request->ip());
        });
    }
}
