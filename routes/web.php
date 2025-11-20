<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\VesselController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ActivityLogController;

// Root route - show login for guests, redirect to dashboard if authenticated
Route::get('/', [AuthController::class, 'root'])->name('home');

// Error Pages
Route::get('/403', function () {
    return view('errors.403', ['message' => session('error') ?? 'You do not have permission to access this resource.']);
})->name('error.403');

// Authentication Routes (Guest only)
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/password/reset', [AuthController::class, 'showPasswordRequestForm'])->name('password.request');
});

// Dashboard Routes (Protected)
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::middleware(['permission:view_dashboard'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Profile Routes (no permission needed - users can manage their own profile)
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
    // Driver Routes (order matters - specific routes before parameterized routes)
    Route::middleware(['permission:view_drivers'])->group(function () {
        Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
    });
    
    Route::middleware(['permission:create_drivers'])->group(function () {
        Route::get('/drivers/create', [DriverController::class, 'create'])->name('drivers.create');
        Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
    });
    
    Route::middleware(['permission:view_drivers'])->group(function () {
        Route::get('/drivers/{driver}', [DriverController::class, 'show'])->name('drivers.show');
    });
    
    Route::middleware(['permission:edit_drivers'])->group(function () {
        Route::get('/drivers/{driver}/edit', [DriverController::class, 'edit'])->name('drivers.edit');
        Route::put('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
    });
    
    Route::middleware(['permission:delete_drivers'])->group(function () {
        Route::delete('/drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');
    });
    
    // Vessel Routes (order matters - specific routes before parameterized routes)
    Route::middleware(['permission:view_vessels'])->group(function () {
        Route::get('/vessels', [VesselController::class, 'index'])->name('vessels.index');
    });
    
    Route::middleware(['permission:create_vessels'])->group(function () {
        Route::get('/vessels/create', [VesselController::class, 'create'])->name('vessels.create');
        Route::post('/vessels', [VesselController::class, 'store'])->name('vessels.store');
    });
    
    Route::middleware(['permission:view_vessels'])->group(function () {
        Route::get('/vessels/{vessel}', [VesselController::class, 'show'])->name('vessels.show');
    });
    
    Route::middleware(['permission:edit_vessels'])->group(function () {
        Route::get('/vessels/{vessel}/edit', [VesselController::class, 'edit'])->name('vessels.edit');
        Route::put('/vessels/{vessel}', [VesselController::class, 'update'])->name('vessels.update');
    });
    
    Route::middleware(['permission:delete_vessels'])->group(function () {
        Route::delete('/vessels/{vessel}', [VesselController::class, 'destroy'])->name('vessels.destroy');
    });
    
    // Trip Routes (order matters - specific routes before parameterized routes)
    Route::middleware(['permission:view_trips'])->group(function () {
        Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
    });
    
    Route::middleware(['permission:create_trips'])->group(function () {
        Route::get('/trips/create', [TripController::class, 'create'])->name('trips.create');
        Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
        Route::post('/trips/extract-from-image', [TripController::class, 'extractFromImage'])->name('trips.extract-from-image');
    });
    
    Route::middleware(['permission:view_trips'])->group(function () {
        Route::get('/trips/{trip}', [TripController::class, 'show'])->name('trips.show');
    });
    
    Route::middleware(['permission:edit_trips'])->group(function () {
        Route::get('/trips/{trip}/edit', [TripController::class, 'edit'])->name('trips.edit');
        Route::put('/trips/{trip}', [TripController::class, 'update'])->name('trips.update');
    });
    
    Route::middleware(['permission:delete_trips'])->group(function () {
        Route::delete('/trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
    });

    // Staff Routes
    Route::middleware(['permission:view_staff'])->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    });
    
    Route::middleware(['permission:create_staff'])->group(function () {
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    });
    
    Route::middleware(['permission:edit_staff'])->group(function () {
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    });
    
    Route::middleware(['permission:delete_staff'])->group(function () {
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
    });

    // Settings Routes
    Route::middleware(['permission:view_settings'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    });
    
    Route::middleware(['permission:edit_settings'])->group(function () {
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    // Notification Routes
    Route::middleware(['permission:view_notifications'])->group(function () {
        Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
        Route::get('/notifications/recent', [App\Http\Controllers\NotificationController::class, 'getRecent'])->name('notifications.recent');
        Route::post('/notifications/{notification}/mark-as-read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
        Route::post('/notifications/mark-all-as-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
    });

    // Permission Routes (Admin only)
    Route::middleware(['permission:manage_permissions'])->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions/role', [PermissionController::class, 'updateRolePermissions'])->name('permissions.updateRole');
        Route::post('/permissions/user', [PermissionController::class, 'updateUserPermissions'])->name('permissions.updateUser');
        Route::get('/permissions/user/{user}', [PermissionController::class, 'showUser'])->name('permissions.user');
    });

    // Activity Log Routes
    Route::middleware(['permission:view_activity_logs'])->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs');
    });
});
