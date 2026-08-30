<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        // Get all settings from database or create default settings
        $settings = [
            'app_name' => (object) [
                'key' => 'app_name',
                'value' => getSetting('app_name', config('app.name')),
            ],
            'app_logo' => (object) [
                'key' => 'app_logo',
                'value' => getSetting('app_logo', ''),
            ],
            'favicon' => (object) [
                'key' => 'favicon',
                'value' => getSetting('favicon', ''),
            ],
            'app_timezone' => (object) [
                'key' => 'app_timezone',
                'value' => getSetting('app_timezone', config('app.timezone', 'Asia/Dubai')),
            ],
            'enable_forgot_password' => (object) [
                'key' => 'enable_forgot_password',
                'value' => getSetting('enable_forgot_password', 'true'),
            ],
            'android_version' => (object) [
                'key' => 'android_version',
                'value' => getSetting('android_version', '1.0.0'),
            ],
            'ios_version' => (object) [
                'key' => 'ios_version',
                'value' => getSetting('ios_version', '1.0.0'),
            ],
            'force_android_version' => (object) [
                'key' => 'force_android_version',
                'value' => getSetting('force_android_version', '1.0.0'),
            ],
            'force_ios_version' => (object) [
                'key' => 'force_ios_version',
                'value' => getSetting('force_ios_version', '1.0.0'),
            ],
            'location_sync_intervel' => (object) [
                'key' => 'location_sync_intervel',
                'value' => getSetting('location_sync_intervel', '30'),
            ],
            'check_in_auto_checkout_hours' => (object) [
                'key' => 'check_in_auto_checkout_hours',
                'value' => getSetting('check_in_auto_checkout_hours', '12'),
            ],
        ];

        // Get all available timezones
        $timezones = \DateTimeZone::listIdentifiers();
        $timezoneGroups = [];
        foreach ($timezones as $timezone) {
            $parts = explode('/', $timezone);
            $region = $parts[0];
            if (! isset($timezoneGroups[$region])) {
                $timezoneGroups[$region] = [];
            }
            $timezoneGroups[$region][] = $timezone;
        }
        ksort($timezoneGroups);

        return view('settings.index', compact('settings', 'timezoneGroups'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {

        // Convert checkbox values to boolean before validation
        $request->merge([
            'enable_forgot_password' => $request->has('enable_forgot_password') ? true : false,
        ]);

        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png|max:1024',
            'app_timezone' => 'nullable|string|max:255',
            'enable_forgot_password' => 'nullable|boolean',
            'android_version' => 'nullable|string|max:50',
            'ios_version' => 'nullable|string|max:50',
            'force_android_version' => 'nullable|string|max:50',
            'force_ios_version' => 'nullable|string|max:50',
            'location_sync_intervel' => 'nullable|integer|min:1|max:86400',
            'check_in_auto_checkout_hours' => 'nullable|integer|min:1|max:168',
        ]);

        // Capture old values before updating
        $oldAppName = getSetting('app_name', config('app.name'));
        $oldLogo = getSetting('app_logo', '');
        $oldFavicon = getSetting('favicon', '');
        $oldTimezone = getSetting('app_timezone', config('app.timezone', 'Asia/Dubai'));
        $oldEnableForgotPassword = getSetting('enable_forgot_password', 'true');
        $oldAndroidVersion = getSetting('android_version', '1.0.0');
        $oldIosVersion = getSetting('ios_version', '1.0.0');
        $oldForceAndroidVersion = getSetting('force_android_version', '1.0.0');
        $oldForceIosVersion = getSetting('force_ios_version', '1.0.0');
        $oldLocationSyncIntervel = getSetting('location_sync_intervel', '30');
        $oldCheckInAutoCheckoutHours = getSetting('check_in_auto_checkout_hours', '12');

        // Update app_name setting
        if ($request->filled('app_name')) {
            updateSetting('app_name', $request->app_name);
        }

        // Update app_timezone setting
        if ($request->filled('app_timezone')) {
            updateSetting('app_timezone', $request->app_timezone);
            // Clear config cache to apply new timezone immediately
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        }

        // Update app_logo setting
        $logoPath = null;
        if ($request->hasFile('app_logo')) {
            // Delete old logo if exists
            if ($oldLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldLogo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldLogo);
            }

            // Store new logo
            $logoPath = $request->file('app_logo')->store('logos', 'public');
            updateSetting('app_logo', $logoPath);
        }

        // Update favicon setting
        $faviconPath = null;
        if ($request->hasFile('favicon')) {
            // Delete old favicon if exists
            if ($oldFavicon && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldFavicon)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldFavicon);
            }

            // Store new favicon
            $faviconPath = $request->file('favicon')->store('favicons', 'public');
            updateSetting('favicon', $faviconPath);
        }

        // Update enable_forgot_password setting
        $enableForgotPassword = $request->has('enable_forgot_password') ? 'true' : 'false';
        updateSetting('enable_forgot_password', $enableForgotPassword);

        $mobileSettings = [
            'android_version' => $request->input('android_version'),
            'ios_version' => $request->input('ios_version'),
            'force_android_version' => $request->input('force_android_version'),
            'force_ios_version' => $request->input('force_ios_version'),
            'location_sync_intervel' => $request->filled('location_sync_intervel')
                ? (string) $request->integer('location_sync_intervel')
                : null,
            'check_in_auto_checkout_hours' => $request->filled('check_in_auto_checkout_hours')
                ? (string) $request->integer('check_in_auto_checkout_hours')
                : null,
        ];

        foreach ($mobileSettings as $key => $value) {
            if ($value !== null && $value !== '') {
                updateSetting($key, $value);
            }
        }

        // Log settings update
        $changes = [];
        $oldValues = [];
        $newValues = [];

        if ($request->filled('app_name') && $request->app_name !== $oldAppName) {
            $oldValues['app_name'] = $oldAppName;
            $newValues['app_name'] = $request->app_name;
            $changes[] = 'app_name';
        }
        if ($request->filled('app_timezone') && $request->app_timezone !== $oldTimezone) {
            $oldValues['app_timezone'] = $oldTimezone;
            $newValues['app_timezone'] = $request->app_timezone;
            $changes[] = 'app_timezone';
        }
        if ($logoPath) {
            $oldValues['app_logo'] = $oldLogo;
            $newValues['app_logo'] = $logoPath;
            $changes[] = 'app_logo';
        }
        if ($faviconPath) {
            $oldValues['favicon'] = $oldFavicon;
            $newValues['favicon'] = $faviconPath;
            $changes[] = 'favicon';
        }
        if ($enableForgotPassword !== $oldEnableForgotPassword) {
            $oldValues['enable_forgot_password'] = $oldEnableForgotPassword;
            $newValues['enable_forgot_password'] = $enableForgotPassword;
            $changes[] = 'enable_forgot_password';
        }

        $mobileOldValues = [
            'android_version' => $oldAndroidVersion,
            'ios_version' => $oldIosVersion,
            'force_android_version' => $oldForceAndroidVersion,
            'force_ios_version' => $oldForceIosVersion,
            'location_sync_intervel' => $oldLocationSyncIntervel,
            'check_in_auto_checkout_hours' => $oldCheckInAutoCheckoutHours,
        ];

        foreach ($mobileSettings as $key => $value) {
            if ($value !== null && $value !== '' && (string) $value !== (string) $mobileOldValues[$key]) {
                $oldValues[$key] = $mobileOldValues[$key];
                $newValues[$key] = $value;
                $changes[] = $key;
            }
        }

        if (! empty($changes)) {
            $description = 'Settings updated: '.implode(', ', $changes);
            ActivityLog::create([
                'loggable_type' => 'App\Models\Setting',
                'loggable_id' => 0,
                'action' => 'updated',
                'user_id' => Auth::id(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }
}
