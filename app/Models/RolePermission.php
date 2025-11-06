<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'permission_id',
    ];

    /**
     * Get the permission that owns the role permission.
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
