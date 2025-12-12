<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                'value' => getSetting('app_name', config('app.name'))
            ],
            'app_logo' => (object) [
                'key' => 'app_logo',
                'value' => getSetting('app_logo', '')
            ],
            'favicon' => (object) [
                'key' => 'favicon',
                'value' => getSetting('favicon', '')
            ],
            'app_timezone' => (object) [
                'key' => 'app_timezone',
                'value' => getSetting('app_timezone', config('app.timezone', 'Asia/Dubai'))
            ],
            'enable_signup' => (object) [
                'key' => 'enable_signup',
                'value' => getSetting('enable_signup', 'true')
            ],
            'enable_forgot_password' => (object) [
                'key' => 'enable_forgot_password',
                'value' => getSetting('enable_forgot_password', 'true')
            ]
        ];

        // Get all available timezones
        $timezones = \DateTimeZone::listIdentifiers();
        $timezoneGroups = [];
        foreach ($timezones as $timezone) {
            $parts = explode('/', $timezone);
            $region = $parts[0];
            if (!isset($timezoneGroups[$region])) {
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
            'enable_signup' => $request->has('enable_signup') ? true : false,
            'enable_forgot_password' => $request->has('enable_forgot_password') ? true : false
        ]);

        $request->validate([
            'enable_signup' => 'nullable|boolean',
            'app_name' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png|max:1024',
            'app_timezone' => 'nullable|string|max:255',
            'enable_forgot_password' => 'nullable|boolean'
        ]);

        // Capture old values before updating
        $oldAppName = getSetting('app_name', config('app.name'));
        $oldLogo = getSetting('app_logo', '');
        $oldFavicon = getSetting('favicon', '');
        $oldTimezone = getSetting('app_timezone', config('app.timezone', 'Asia/Dubai'));
        $oldEnableSignup = getSetting('enable_signup', 'true');
        $oldEnableForgotPassword = getSetting('enable_forgot_password', 'true');

        // Update enable_signup setting
        $enableSignup = $request->has('enable_signup') ? 'true' : 'false';
        updateSetting('enable_signup', $enableSignup);

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
        if ($enableSignup !== $oldEnableSignup) {
            $oldValues['enable_signup'] = $oldEnableSignup;
            $newValues['enable_signup'] = $enableSignup;
            $changes[] = 'enable_signup';
        }
        if ($enableForgotPassword !== $oldEnableForgotPassword) {
            $oldValues['enable_forgot_password'] = $oldEnableForgotPassword;
            $newValues['enable_forgot_password'] = $enableForgotPassword;
            $changes[] = 'enable_forgot_password';
        }

        if (!empty($changes)) {
            $description = 'Settings updated: ' . implode(', ', $changes);
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