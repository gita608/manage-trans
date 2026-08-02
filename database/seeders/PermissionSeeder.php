<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions
        $permissions = [
            // Dashboard
            ['name' => 'view_dashboard', 'display_name' => 'View Dashboard', 'category' => 'dashboard', 'description' => 'Access to dashboard page'],
            
            // Trips
            ['name' => 'view_trips', 'display_name' => 'View Trips', 'category' => 'trips', 'description' => 'View trips list'],
            ['name' => 'create_trips', 'display_name' => 'Create Trips', 'category' => 'trips', 'description' => 'Create new trips'],
            ['name' => 'edit_trips', 'display_name' => 'Edit Trips', 'category' => 'trips', 'description' => 'Edit existing trips'],
            ['name' => 'delete_trips', 'display_name' => 'Delete Trips', 'category' => 'trips', 'description' => 'Delete trips'],
            
            // Drivers
            ['name' => 'view_drivers', 'display_name' => 'View Drivers', 'category' => 'drivers', 'description' => 'View drivers list'],
            ['name' => 'create_drivers', 'display_name' => 'Create Drivers', 'category' => 'drivers', 'description' => 'Create new drivers'],
            ['name' => 'edit_drivers', 'display_name' => 'Edit Drivers', 'category' => 'drivers', 'description' => 'Edit existing drivers'],
            ['name' => 'delete_drivers', 'display_name' => 'Delete Drivers', 'category' => 'drivers', 'description' => 'Delete drivers'],
            
            // Vessels
            ['name' => 'view_vessels', 'display_name' => 'View Vessels', 'category' => 'vessels', 'description' => 'View vessels list'],
            ['name' => 'create_vessels', 'display_name' => 'Create Vessels', 'category' => 'vessels', 'description' => 'Create new vessels'],
            ['name' => 'edit_vessels', 'display_name' => 'Edit Vessels', 'category' => 'vessels', 'description' => 'Edit existing vessels'],
            ['name' => 'delete_vessels', 'display_name' => 'Delete Vessels', 'category' => 'vessels', 'description' => 'Delete vessels'],

            // Vehicles
            ['name' => 'view_vehicles', 'display_name' => 'View Vehicles', 'category' => 'vehicles', 'description' => 'View vehicles list'],
            ['name' => 'create_vehicles', 'display_name' => 'Create Vehicles', 'category' => 'vehicles', 'description' => 'Create new vehicles'],
            ['name' => 'edit_vehicles', 'display_name' => 'Edit Vehicles', 'category' => 'vehicles', 'description' => 'Edit existing vehicles'],
            ['name' => 'delete_vehicles', 'display_name' => 'Delete Vehicles', 'category' => 'vehicles', 'description' => 'Delete vehicles'],
            
            // Staff
            ['name' => 'view_staff', 'display_name' => 'View Staff', 'category' => 'staff', 'description' => 'View staff list'],
            ['name' => 'create_staff', 'display_name' => 'Create Staff', 'category' => 'staff', 'description' => 'Create new staff members'],
            ['name' => 'edit_staff', 'display_name' => 'Edit Staff', 'category' => 'staff', 'description' => 'Edit existing staff members'],
            ['name' => 'delete_staff', 'display_name' => 'Delete Staff', 'category' => 'staff', 'description' => 'Delete staff members'],
            
            // Partners
            ['name' => 'view_partners', 'display_name' => 'View Partners', 'category' => 'partners', 'description' => 'View partners list'],
            ['name' => 'create_partners', 'display_name' => 'Create Partners', 'category' => 'partners', 'description' => 'Create new partners'],
            ['name' => 'edit_partners', 'display_name' => 'Edit Partners', 'category' => 'partners', 'description' => 'Edit existing partners'],
            ['name' => 'delete_partners', 'display_name' => 'Delete Partners', 'category' => 'partners', 'description' => 'Delete partners'],
            
            // Settings
            ['name' => 'view_settings', 'display_name' => 'View Settings', 'category' => 'settings', 'description' => 'View system settings'],
            ['name' => 'edit_settings', 'display_name' => 'Edit Settings', 'category' => 'settings', 'description' => 'Edit system settings'],
            
            // Permissions
            ['name' => 'manage_permissions', 'display_name' => 'Manage Permissions', 'category' => 'permissions', 'description' => 'Manage user and role permissions'],
            
            // Activity Logs
            ['name' => 'view_activity_logs', 'display_name' => 'View Activity Logs', 'category' => 'logs', 'description' => 'View system activity logs'],
            
            // Notifications
            ['name' => 'view_notifications', 'display_name' => 'View Notifications', 'category' => 'notifications', 'description' => 'View notifications'],
            ['name' => 'create_notifications', 'display_name' => 'Create Notifications', 'category' => 'notifications', 'description' => 'Create and send notifications to drivers'],
            
            // Reports
            ['name' => 'view_reports', 'display_name' => 'View Reports', 'category' => 'reports', 'description' => 'View and access reports'],
        ];

        // Create permissions
        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assign all permissions to Admin role (role = 1)
        $allPermissions = Permission::all();
        foreach ($allPermissions as $permission) {
            RolePermission::firstOrCreate([
                'role' => User::ROLE_ADMIN,
                'permission_id' => $permission->id,
            ]);
        }

        // Assign limited permissions to Staff role (role = 2)
        $staffPermissions = [
            'view_dashboard',
            'view_trips',
            'create_trips',
            'edit_trips',
            'view_drivers',
            'view_vessels',
            'view_vehicles',
            'view_notifications',
            'view_reports',
        ];

        foreach ($staffPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                RolePermission::firstOrCreate([
                    'role' => User::ROLE_STAFF,
                    'permission_id' => $permission->id,
                ]);
            }
        }

        $this->command->info('Permissions seeded successfully!');
        $this->command->info('Admin role has all permissions.');
        $this->command->info('Staff role has limited permissions.');
    }
}
