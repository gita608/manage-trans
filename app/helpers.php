<?php

if (!function_exists('getSetting')) {
    /**
     * Get a setting value from database or return default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getSetting($key, $default = null)
    {
        try {
            return \Illuminate\Support\Facades\Cache::remember("setting.{$key}", 3600, function() use ($key, $default) {
                $setting = \Illuminate\Support\Facades\DB::table('settings')->where('key', $key)->first();
                return $setting ? $setting->value : $default;
            });
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('updateSetting')) {
    /**
     * Update or create a setting in the database.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    function updateSetting($key, $value)
    {
        try {
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
            \Illuminate\Support\Facades\Cache::forget("setting.{$key}");
        } catch (\Exception $e) {
            // Silently fail if settings table doesn't exist
        }
    }
}

if (!function_exists('brandingUrl')) {
    /**
     * URL for a branding image stored in settings, falling back to a bundled asset.
     *
     * Settings can outlive the uploaded file (e.g. storage cleared between environments),
     * so the file is checked on disk before its URL is used.
     *
     * @param string $settingKey
     * @param string $fallbackAsset
     * @return string
     */
    function brandingUrl($settingKey, $fallbackAsset)
    {
        $path = getSetting($settingKey);

        if ($path && is_file(storage_path('app/public/' . $path))) {
            return asset('storage/' . $path);
        }

        return asset($fallbackAsset);
    }
}

if (!function_exists('assetVersioned')) {
    /**
     * Asset URL with a cache-busting version based on the file's last modified time.
     *
     * @param string $path
     * @return string
     */
    function assetVersioned($path)
    {
        $url = asset($path);

        try {
            $file = public_path($path);
            if (is_file($file)) {
                return $url . '?v=' . filemtime($file);
            }
        } catch (\Exception $e) {
            // Fall through to the unversioned URL.
        }

        return $url;
    }
}

if (!function_exists('getAppTimezone')) {
    /**
     * Get the application timezone from settings or config.
     *
     * @return string
     */
    function getAppTimezone()
    {
        return getSetting('app_timezone', config('app.timezone', 'Asia/Dubai'));
    }
}

if (!function_exists('formatDate')) {
    /**
     * Format a date using the application timezone.
     *
     * @param mixed $date
     * @param string $format
     * @return string
     */
    function formatDate($date, $format = 'M d, Y h:i A')
    {
        if (!$date) {
            return 'N/A';
        }
        
        try {
            $timezone = getAppTimezone();
            $timezoneObj = new \DateTimeZone($timezone);
            
            if ($date instanceof \Carbon\Carbon) {
                return $date->setTimezone($timezoneObj)->format($format);
            }
            if ($date instanceof \DateTime) {
                $date->setTimezone($timezoneObj);
                return $date->format($format);
            }
            return \Carbon\Carbon::parse($date)->setTimezone($timezoneObj)->format($format);
        } catch (\Exception $e) {
            return 'Invalid Date';
        }
    }
}