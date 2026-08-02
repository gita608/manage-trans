<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\VesselController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TripIssueTypeController;
use App\Http\Controllers\TripExpenseTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DailyActivityController;
use App\Http\Controllers\PublicPagesController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PwaController;

// Root route - show login for guests, redirect to dashboard if authenticated
Route::get('/', [AuthController::class, 'root'])->name('home');

// Error Pages
Route::get('/403', function () {
    return view('errors.403', ['message' => session('error') ?? 'You do not have permission to access this resource.']);
})->name('error.403');

// Installable web app manifest (must be reachable without authentication)
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');

// Public Pages (No authentication required)
Route::get('/privacy-policy', [PublicPagesController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/contact-us', [PublicPagesController::class, 'contactUs'])->name('contact-us');
Route::post('/contact-us', [PublicPagesController::class, 'submitContact'])->name('contact-us.submit');

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
        Route::get('/drivers/map', [DriverController::class, 'map'])->name('drivers.map'); 
        Route::get('/api/drivers/locations', [DriverController::class, 'locations'])->name('api.drivers.locations');
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
        Route::delete('/drivers/document/{document}', [DriverController::class, 'deleteDocument'])->name('drivers.delete-document');
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

    // Vehicle Routes (order matters - specific routes before parameterized routes)
    Route::middleware(['permission:view_vehicles'])->group(function () {
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    });

    Route::middleware(['permission:create_vehicles'])->group(function () {
        Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    });

    Route::middleware(['permission:view_vehicles'])->group(function () {
        Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
    });

    Route::middleware(['permission:edit_vehicles'])->group(function () {
        Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    });

    Route::middleware(['permission:delete_vehicles'])->group(function () {
        Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
    });
    
    // Trip Routes (order matters - specific routes before parameterized routes)
    Route::middleware(['permission:view_trips'])->group(function () {
        Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
    });
    
    Route::middleware(['permission:create_trips'])->group(function () {
        Route::get('/trips/create', [TripController::class, 'create'])->name('trips.create');
        Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
        Route::post('/trips/extract-from-image', [TripController::class, 'extractFromImage'])->name('trips.extract-from-image');
        Route::post('/trips/bulk-store', [TripController::class, 'storeBulk'])->name('trips.store-bulk');
        Route::get('/trips/generate-title', [TripController::class, 'generateTitle'])->name('trips.generate-title');
    });
    
    Route::middleware(['permission:view_trips'])->group(function () {
        Route::get('/trips/{trip}', [TripController::class, 'show'])->name('trips.show');
    });
    
    Route::middleware(['permission:edit_trips'])->group(function () {
        Route::get('/trips/{trip}/edit', [TripController::class, 'edit'])->name('trips.edit');
        Route::put('/trips/{trip}', [TripController::class, 'update'])->name('trips.update');
        Route::post('/trips/{trip}/cancel', [TripController::class, 'cancel'])->name('trips.cancel');
        Route::patch('/trips/{trip}/assign-driver', [TripController::class, 'assignDriver'])->name('trips.assign-driver');
    });
    
    Route::middleware(['permission:delete_trips'])->group(function () {
        Route::delete('/trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
    });

    // Trip Issue Type Routes
    Route::middleware(['permission:view_trips'])->group(function () {
        Route::get('/trip-issue-types', [TripIssueTypeController::class, 'index'])->name('trip-issue-types.index');
    });
    
    Route::middleware(['permission:create_trips'])->group(function () {
        Route::get('/trip-issue-types/create', [TripIssueTypeController::class, 'create'])->name('trip-issue-types.create');
        Route::post('/trip-issue-types', [TripIssueTypeController::class, 'store'])->name('trip-issue-types.store');
    });
    
    Route::middleware(['permission:edit_trips'])->group(function () {
        Route::get('/trip-issue-types/{tripIssueType}/edit', [TripIssueTypeController::class, 'edit'])->name('trip-issue-types.edit');
        Route::put('/trip-issue-types/{tripIssueType}', [TripIssueTypeController::class, 'update'])->name('trip-issue-types.update');
    });
    
    Route::middleware(['permission:delete_trips'])->group(function () {
        Route::delete('/trip-issue-types/{tripIssueType}', [TripIssueTypeController::class, 'destroy'])->name('trip-issue-types.destroy');
    });

    // Trip Expense Type Routes
    Route::middleware(['permission:view_trips'])->group(function () {
        Route::get('/trip-expense-types', [TripExpenseTypeController::class, 'index'])->name('trip-expense-types.index');
    });
    
    Route::middleware(['permission:create_trips'])->group(function () {
        Route::get('/trip-expense-types/create', [TripExpenseTypeController::class, 'create'])->name('trip-expense-types.create');
        Route::post('/trip-expense-types', [TripExpenseTypeController::class, 'store'])->name('trip-expense-types.store');
    });
    
    Route::middleware(['permission:edit_trips'])->group(function () {
        Route::get('/trip-expense-types/{tripExpenseType}/edit', [TripExpenseTypeController::class, 'edit'])->name('trip-expense-types.edit');
        Route::put('/trip-expense-types/{tripExpenseType}', [TripExpenseTypeController::class, 'update'])->name('trip-expense-types.update');
    });
    
    Route::middleware(['permission:delete_trips'])->group(function () {
        Route::delete('/trip-expense-types/{tripExpenseType}', [TripExpenseTypeController::class, 'destroy'])->name('trip-expense-types.destroy');
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
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/admin', [NotificationController::class, 'adminIndex'])->name('notifications.admin-index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
        Route::get('/notifications/recent', [NotificationController::class, 'getRecent'])->name('notifications.recent');
        Route::post('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
        Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
    });

    // Send Notification Routes (only for create_notifications)
    Route::middleware(['permission:create_notifications'])->group(function () {
        Route::get('/notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
        Route::post('/notifications', [NotificationController::class, 'store'])->name('notifications.store');
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

    // Report Routes
    Route::middleware(['permission:view_reports'])->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/trip-summary', [ReportController::class, 'tripSummary'])->name('reports.trip-summary');
        Route::get('/reports/trip-expenses', [ReportController::class, 'tripExpenses'])->name('reports.trip-expenses');
        Route::get('/reports/driver-performance', [ReportController::class, 'driverPerformance'])->name('reports.driver-performance');
    });

    // Daily Activity Routes
    Route::middleware(['permission:view_drivers'])->group(function () {
        Route::get('/daily-activities', [DailyActivityController::class, 'index'])->name('daily-activities.index');
    });

    // Partner Routes
    Route::middleware(['permission:view_partners'])->group(function () {
        Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
    });
    
    Route::middleware(['permission:create_partners'])->group(function () {
        Route::get('/partners/create', [PartnerController::class, 'create'])->name('partners.create');
        Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
    });
    
    Route::middleware(['permission:edit_partners'])->group(function () {
        Route::get('/partners/{partner}/edit', [PartnerController::class, 'edit'])->name('partners.edit');
        Route::put('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
    });
    
    Route::middleware(['permission:delete_partners'])->group(function () {
        Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');
    });
});
