<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

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
        'license_number',
        'contact',
        'vehicle_info',
        'age',
        'photo',
    ];

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
}
