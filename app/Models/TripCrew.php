<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripCrew extends Model
{
    protected $fillable = [
        'trip_id',
        'vessel_id',
        'name',
        'phone',
        'phone_2',
        'address',
        'pick_up_time',
        'from_location',
        'to_location',
        'flight_number',
        'remarks',
        'sub_remark',
        // Note: status is now stored on trips table, not trip_crews
        // Keeping constants here for backward compatibility
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Trip status constants
     */
    const STATUS_UNASSIGNED = 'unassigned';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_UNASSIGNED => 'Unassigned',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }
}
