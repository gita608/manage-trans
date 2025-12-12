<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\NotificationController;
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
    Route::get('/app-version', [DriverAuthController::class, 'app_version'])->name('api.app-version');
    
    // Protected routes (requires bearer token authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [DriverAuthController::class, 'logout'])->name('api.driver.logout');
        Route::get('/profile', [DriverAuthController::class, 'profile'])->name('api.driver.profile');
        Route::post('/profile', [DriverAuthController::class, 'updateProfile'])->name('api.driver.profile.update');
        Route::get('/home', [HomeController::class, 'index'])->name('api.driver.home');
        Route::get('/trips', [DriverAuthController::class, 'trips'])->name('api.driver.trips');
        
        // Location Route
        Route::post('/location/update', [DriverAuthController::class, 'updateLocation'])->name('api.driver.location.update');
        
        // Notification Token Route
        Route::post('/notification-token/update', [DriverAuthController::class, 'updateNotificationToken'])->name('api.driver.notification-token.update');
        
        // Schedule Route
        Route::get('/schedule', [TripController::class, 'schedule'])->name('api.driver.schedule');
        
        // Daily Activity Routes
        Route::get('/daily-activity', [TripController::class, 'dailyActivity'])->name('api.driver.daily-activity');
        Route::post('/daily-activity', [TripController::class, 'storeDailyActivity'])->name('api.driver.daily-activity.store');
        
        // Trip Details Routes
        Route::get('/trips/{id}', [TripController::class, 'show'])->name('api.driver.trip.show');
        Route::put('/trips/{id}/status', [TripController::class, 'updateStatus'])->name('api.driver.trip.update-status');
        Route::put('/trips/{id}/crew/{crew_id}', [TripController::class, 'updateCrewDetails'])->name('api.driver.trip.update-crew');
        
        // Trip Issue Routes
        Route::get('/trip-issue-types', [TripController::class, 'getIssueTypes'])->name('api.driver.trip-issue-types');
        Route::post('/trips/{id}/issues', [TripController::class, 'submitIssue'])->name('api.driver.trip.submit-issue');
        
        // Trip Expense Routes
        Route::get('/trip-expense-types', [TripController::class, 'getExpenseTypes'])->name('api.driver.trip-expense-types');
        Route::post('/trips/{id}/expenses', [TripController::class, 'submitExpense'])->name('api.driver.trip.submit-expense');
        
        // Notification Routes
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('api.driver.notifications');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('api.driver.notifications.unread-count');
            Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('api.driver.notifications.mark-as-read');
            Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('api.driver.notifications.mark-all-as-read');
        });
    });
