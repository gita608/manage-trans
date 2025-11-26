<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, LogsActivity;

    /**
     * Driver type constants
     */
    const TYPE_INTERNAL = 1;    // Internal
    const TYPE_OUTSOURCING = 2; // Outside/Outsourcing

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'email',
        'password',
        'license_number',
        'contact',
        'vehicle_info',
        'age',
        'photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'password' => 'hashed',
        ];
    }

    /**
     * Get all available driver types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_INTERNAL => 'Internal',
            self::TYPE_OUTSOURCING => 'Outsourcing (Outside)',
        ];
    }

    /**
     * Get driver type label
     */
    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? 'Unknown';
    }

    /**
     * Get the trips for the driver.
     */
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get the documents for the driver.
     */
    public function documents()
    {
        return $this->hasMany(DriverDocument::class);
    }

    /**
     * Get the notifications for the driver.
     *
     * @return HasMany
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    /**
     * Get activity configuration for this model.
     *
     * @return array
     */
    protected function getActivityConfig(): array
    {
        return [
            'model_name' => 'Driver',
            'identifier_field' => 'name',
            'identifier_label_callback' => fn($model) => $model->getTypeLabel(),
            'field_mappings' => [
                'name' => 'name',
                'type' => [
                    'label' => 'type',
                    self::TYPE_INTERNAL => 'Internal',
                    self::TYPE_OUTSOURCING => 'Outsourcing (Outside)',
                ],
                'license_number' => 'license number',
                'contact' => 'contact information',
            ],
        ];
    }
}
