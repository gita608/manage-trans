<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'role',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Roles: 1 = Admin, 2 = Staff
    public const ROLE_ADMIN = 1;
    public const ROLE_STAFF = 2;

    public function isAdmin(): bool
    {
        return (int) $this->role === self::ROLE_ADMIN;
    }

    public function scopeStaff($query)
    {
        return $query->where('role', self::ROLE_STAFF);
    }

    /**
     * Get the notifications for the user.
     *
     * @return HasMany
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }
}
