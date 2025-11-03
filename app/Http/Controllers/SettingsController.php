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
            'enable_signup' => (object) [
                'key' => 'enable_signup',
                'value' => getSetting('enable_signup', 'true')
            ]
        ];

        return view('settings.index', compact('settings'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'enable_signup' => 'nullable|boolean'
        ]);

        // Update enable_signup setting
        $enableSignup = $request->has('enable_signup') ? 'true' : 'false';
        updateSetting('enable_signup', $enableSignup);

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }
}