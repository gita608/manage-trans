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
            $setting = \Illuminate\Support\Facades\DB::table('settings')->where('key', $key)->first();
            return $setting ? $setting->value : $default;
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
        } catch (\Exception $e) {
            // Silently fail if settings table doesn't exist
        }
    }
}