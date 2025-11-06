<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{
    /**
     * Get all permissions for the user (from role + user-specific).
     */
    public function permissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Check if user has a specific permission.
     * This checks:
     * 1. If user is Admin (role = 1), always return true (no restrictions)
     * 2. User-specific permissions (if granted = false, deny access)
     * 3. Role-based permissions (fallback if no user-specific permission)
     *
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission(string $permissionName): bool
    {
        // Admin (role = 1) has all permissions - no restrictions
        if ((int) $this->role === \App\Models\User::ROLE_ADMIN) {
            return true;
        }

        // Cache key for this user's permission check
        $cacheKey = "user_{$this->id}_permission_{$permissionName}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($permissionName) {
            // Find the permission
            $permission = Permission::where('name', $permissionName)->first();
            
            if (!$permission) {
                return false;
            }

            // Check user-specific permission (highest priority)
            $userPermission = UserPermission::where('user_id', $this->id)
                ->where('permission_id', $permission->id)
                ->first();

            if ($userPermission) {
                // User-specific permission found - return granted status
                return $userPermission->granted;
            }

            // No user-specific permission, check role-based permission
            $rolePermission = RolePermission::where('role', $this->role)
                ->where('permission_id', $permission->id)
                ->exists();

            return $rolePermission;
        });
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        // Admin (role = 1) has all permissions
        if ((int) $this->role === \App\Models\User::ROLE_ADMIN) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        // Admin (role = 1) has all permissions
        if ((int) $this->role === \App\Models\User::ROLE_ADMIN) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Grant a permission to this user.
     *
     * @param string $permissionName
     * @return void
     */
    public function grantPermission(string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->first();
        
        if ($permission) {
            UserPermission::updateOrCreate(
                [
                    'user_id' => $this->id,
                    'permission_id' => $permission->id,
                ],
                [
                    'granted' => true,
                ]
            );

            // Clear cache
            Cache::forget("user_{$this->id}_permission_{$permissionName}");
        }
    }

    /**
     * Revoke a permission from this user.
     *
     * @param string $permissionName
     * @return void
     */
    public function revokePermission(string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->first();
        
        if ($permission) {
            UserPermission::updateOrCreate(
                [
                    'user_id' => $this->id,
                    'permission_id' => $permission->id,
                ],
                [
                    'granted' => false,
                ]
            );

            // Clear cache
            Cache::forget("user_{$this->id}_permission_{$permissionName}");
        }
    }

    /**
     * Remove a user-specific permission (revert to role-based permission).
     *
     * @param string $permissionName
     * @return void
     */
    public function removeUserPermission(string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->first();
        
        if ($permission) {
            UserPermission::where('user_id', $this->id)
                ->where('permission_id', $permission->id)
                ->delete();

            // Clear cache
            Cache::forget("user_{$this->id}_permission_{$permissionName}");
        }
    }

    /**
     * Get all permissions for this user (including role and user-specific).
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions()
    {
        // Admin (role = 1) has all permissions - return all available permissions
        if ((int) $this->role === \App\Models\User::ROLE_ADMIN) {
            return Permission::all();
        }

        // Get role permissions
        $rolePermissions = RolePermission::where('role', $this->role)
            ->with('permission')
            ->get()
            ->pluck('permission');

        // Get user-specific permissions
        $userPermissions = UserPermission::where('user_id', $this->id)
            ->with('permission')
            ->get();

        // Merge and override with user-specific permissions
        $allPermissions = collect();
        
        foreach ($rolePermissions as $permission) {
            $userOverride = $userPermissions->firstWhere('permission_id', $permission->id);
            
            if ($userOverride) {
                // User has specific override
                if ($userOverride->granted) {
                    $allPermissions->push($permission);
                }
                // If granted = false, don't include this permission
            } else {
                // No override, include role permission
                $allPermissions->push($permission);
            }
        }

        // Add user-specific granted permissions that aren't in role permissions
        foreach ($userPermissions as $userPerm) {
            if ($userPerm->granted && !$rolePermissions->contains('id', $userPerm->permission_id)) {
                $allPermissions->push($userPerm->permission);
            }
        }

        return $allPermissions->unique('id');
    }

    /**
     * Clear all permission cache for this user.
     *
     * @return void
     */
    public function clearPermissionCache(): void
    {
        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            Cache::forget("user_{$this->id}_permission_{$permission->name}");
        }
    }
}

