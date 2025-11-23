<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\TripController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Driver Authentication Routes

    // Public routes
    Route::post('/login', [DriverAuthController::class, 'login'])->name('api.driver.login');
    
    // Protected routes (requires bearer token authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [DriverAuthController::class, 'logout'])->name('api.driver.logout');
        Route::get('/home', [HomeController::class, 'index'])->name('api.driver.home');
        Route::get('/trips', [DriverAuthController::class, 'trips'])->name('api.driver.trips');
        
        // Trip Details Routes
        Route::get('/trips/{id}', [TripController::class, 'show'])->name('api.driver.trip.show');
        Route::put('/trips/{id}/status', [TripController::class, 'updateStatus'])->name('api.driver.trip.update-status');
    });
