<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the permissions.
     */
    public function index()
    {
        $permissions = Permission::all()->groupBy('category');
        $users = User::all();
        
        // Get role permissions
        $adminPermissions = RolePermission::where('role', User::ROLE_ADMIN)
            ->pluck('permission_id')
            ->toArray();
        
        $staffPermissions = RolePermission::where('role', User::ROLE_STAFF)
            ->pluck('permission_id')
            ->toArray();

        return view('permissions.index', compact('permissions', 'users', 'adminPermissions', 'staffPermissions'));
    }

    /**
     * Update role permissions.
     */
    public function updateRolePermissions(Request $request)
    {
        $request->validate([
            'role' => 'required|in:1,2',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = $request->role;
        $permissionIds = $request->permissions ?? [];

        // Delete all existing role permissions
        RolePermission::where('role', $role)->delete();

        // Create new role permissions
        foreach ($permissionIds as $permissionId) {
            RolePermission::create([
                'role' => $role,
                'permission_id' => $permissionId,
            ]);
        }

        // Clear cache for all users with this role
        User::where('role', $role)->each(function ($user) {
            $user->clearPermissionCache();
        });

        $roleName = $role == User::ROLE_ADMIN ? 'Admin' : 'Staff';
        
        return redirect()->route('permissions.index')
            ->with('success', "{$roleName} role permissions updated successfully.");
    }

    /**
     * Update user-specific permissions.
     */
    public function updateUserPermissions(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'permission_status' => 'nullable|array',
        ]);

        $user = User::findOrFail($request->user_id);
        $permissionIds = $request->permissions ?? [];
        $permissionStatus = $request->permission_status ?? [];

        // Delete all existing user permissions
        UserPermission::where('user_id', $user->id)->delete();

        // Create new user permissions
        foreach ($permissionIds as $permissionId) {
            $granted = isset($permissionStatus[$permissionId]) && $permissionStatus[$permissionId] == 'granted';
            
            UserPermission::create([
                'user_id' => $user->id,
                'permission_id' => $permissionId,
                'granted' => $granted,
            ]);
        }

        // Clear user's permission cache
        $user->clearPermissionCache();

        return redirect()->route('permissions.index')
            ->with('success', "Permissions updated successfully for {$user->name}.");
    }

    /**
     * Show user permissions details.
     */
    public function showUser(User $user)
    {
        $allPermissions = Permission::all()->groupBy('category');
        $userPermissions = $user->getAllPermissions();
        $userSpecificPermissions = UserPermission::where('user_id', $user->id)
            ->with('permission')
            ->get();
        
        // Get role permissions
        $rolePermissions = RolePermission::where('role', $user->role)
            ->pluck('permission_id')
            ->toArray();

        return view('permissions.user', compact('user', 'allPermissions', 'userPermissions', 'userSpecificPermissions', 'rolePermissions'));
    }
}
