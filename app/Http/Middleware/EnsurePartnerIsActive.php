<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartnerIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('partner')->user();

        // If no partner user or account is inactive
        if (!$user || !$user->is_active) {
            // Logout ONLY the partner guard (does not affect web guard)
            Auth::guard('partner')->logout();

            // Rotate session ID for security without destroying other guard data
            $request->session()->regenerate();
            
            // Regenerate CSRF token
            $request->session()->regenerateToken();

            return redirect()->route('partner.login')
                ->with('error', 'Your account has been deactivated. Please contact support.');
        }

        return $next($request);
    }
}
