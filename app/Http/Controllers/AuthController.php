<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        $enableSignup = getSetting('enable_signup', 'true') === 'true';
        return view('auth.login', compact('enableSignup'));
    }

    /**
     * Handle root route - show login for guests, redirect to dashboard if authenticated.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function root()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return $this->showLoginForm();
    }

    /**
     * Handle a login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => __('The provided credentials do not match our records.'),
        ]);
    }

    /**
     * Handle a logout request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Show the registration form.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showRegistrationForm()
    {
        $enableSignup = getSetting('enable_signup', 'true') === 'true';
        if (!$enableSignup) {
            return redirect()->route('login')->with('error', 'Registration is currently disabled.');
        }
        return view('auth.register');
    }

    /**
     * Handle a registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $enableSignup = getSetting('enable_signup', 'true') === 'true';
        if (!$enableSignup) {
            return redirect()->route('login')->with('error', 'Registration is currently disabled.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Log registration (User model will also log via LogsActivity trait, but we log here for clarity)
        ActivityLog::create([
            'loggable_type' => 'App\Models\User',
            'loggable_id' => $user->id,
            'action' => 'registered',
            'user_id' => $user->id,
            'old_values' => null,
            'new_values' => ['name' => $user->name, 'email' => $user->email],
            'description' => "New user '{$user->name}' registered",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }

    /**
     * Show the password reset request form.
     *
     * @return \Illuminate\View\View
     */
    public function showPasswordRequestForm()
    {
        return view('auth.passwords.email');
    }
}

