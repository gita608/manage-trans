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
        'address',
        'pick_up_time',
        'from_location',
        'to_location',
        'flight_number',
        'remarks',
        'status',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }
}
