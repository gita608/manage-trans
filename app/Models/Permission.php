<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'category',
        'description',
    ];

    /**
     * Get the roles that have this permission.
     */
    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * Get the users that have this permission.
     */
    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Check if a role has this permission.
     */
    public function hasRole(int $role): bool
    {
        return $this->rolePermissions()->where('role', $role)->exists();
    }
}
