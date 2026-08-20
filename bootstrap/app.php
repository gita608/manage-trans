<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'partner.active' => \App\Http\Middleware\EnsurePartnerIsActive::class,
        ]);

        // Custom redirect for partner guard
        $middleware->redirectGuestsTo(function ($request) {
            // Partner routes should redirect to partner login
            if ($request->is('partner') || $request->is('partner/*')) {
                return route('partner.login');
            }
            // Default to internal login
            return route('login');
        });

        // Custom redirect for already authenticated users visiting login pages
        $middleware->redirectUsersTo(function ($request) {
            // Partner login should redirect to partner dashboard if already authenticated as partner
            if ($request->is('partner/login') && auth()->guard('partner')->check()) {
                return route('partner.dashboard');
            }
            // Internal login should redirect to internal dashboard if already authenticated as user
            if ($request->is('login') && auth()->guard('web')->check()) {
                return route('dashboard');
            }
            // Default behavior
            return $request->is('partner/login') ? route('partner.dashboard') : route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render custom 403 error page
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to access this resource.',
                ], 403);
            }
            return redirect()->route('error.403')->with('error', $e->getMessage() ?: 'You do not have permission to access this resource.');
        });
    })->create();
