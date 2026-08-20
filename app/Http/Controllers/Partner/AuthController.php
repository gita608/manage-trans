<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the partner login form.
     */
    public function showLoginForm()
    {
        // If already authenticated as partner, redirect to dashboard
        if (Auth::guard('partner')->check()) {
            return redirect()->route('partner.dashboard');
        }

        return view('partner.auth.login');
    }

    /**
     * Handle partner login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        // Attempt authentication
        if (Auth::guard('partner')->attempt($credentials, $remember)) {
            $user = Auth::guard('partner')->user();

            // Check if account is active
            if (!$user->is_active) {
                // Logout ONLY the partner guard
                Auth::guard('partner')->logout();
                
                // Rotate session ID for security without destroying other guard data
                $request->session()->regenerate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => 'These credentials do not match our records.',
                ]);
            }

            // Update last login timestamp
            $user->last_login_at = now();
            $user->saveQuietly();

            // Regenerate session for security
            $request->session()->regenerate();

            return redirect()->intended(route('partner.dashboard'));
        }

        // Generic error message - don't reveal if account exists or is disabled
        throw ValidationException::withMessages([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    /**
     * Handle partner logout request.
     */
    public function logout(Request $request)
    {
        // Logout ONLY the partner guard (does not affect web guard)
        Auth::guard('partner')->logout();

        // Rotate session ID for security without destroying other guard data
        $request->session()->regenerate();
        
        // Regenerate CSRF token
        $request->session()->regenerateToken();

        return redirect()->route('partner.login');
    }
}
