<?php

namespace App\Providers;

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
    }
}
