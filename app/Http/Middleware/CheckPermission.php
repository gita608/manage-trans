<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = Auth::guard('web')->user();
        
        // Admin (role = 1) has all permissions - bypass check
        if ((int) $user->role === \App\Models\User::ROLE_ADMIN) {
            return $next($request);
        }

        if (!$user->hasPermission($permission)) {
            return redirect()->route('error.403')->with('error', 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
