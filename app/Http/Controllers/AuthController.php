<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        return view('auth.login');
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
        return redirect()->route('login');
    }

    /**
     * Handle a registration request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        return redirect()->route('login');
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
