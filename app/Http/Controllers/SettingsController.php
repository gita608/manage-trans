<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            'enable_signup' => (object) [
                'key' => 'enable_signup',
                'value' => getSetting('enable_signup', 'true')
            ],
            'enable_forgot_password' => (object) [
                'key' => 'enable_forgot_password',
                'value' => getSetting('enable_forgot_password', 'true')
            ]
        ];

        return view('settings.index', compact('settings'));
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
            'enable_forgot_password' => 'nullable|boolean'
        ]);

        // Update enable_signup setting
        $enableSignup = $request->has('enable_signup') ? 'true' : 'false';
        updateSetting('enable_signup', $enableSignup);

        // Update app_name setting
        if ($request->filled('app_name')) {
            updateSetting('app_name', $request->app_name);
        }

        // Update app_logo setting
        if ($request->hasFile('app_logo')) {
            // Delete old logo if exists
            $oldLogo = getSetting('app_logo');
            if ($oldLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldLogo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldLogo);
            }
            
            // Store new logo
            $logoPath = $request->file('app_logo')->store('logos', 'public');
            updateSetting('app_logo', $logoPath);
        }

        // Update favicon setting
        if ($request->hasFile('favicon')) {
            // Delete old favicon if exists
            $oldFavicon = getSetting('favicon');
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

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }
}