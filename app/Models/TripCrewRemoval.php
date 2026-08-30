<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripCrewRemoval extends Model
{
    protected $fillable = [
        'trip_id',
        'trip_crew_id',
        'crew_name',
        'phone',
        'phone_2',
        'address',
        'vessel_id',
        'vessel_name',
        'pick_up_time',
        'from_location',
        'to_location',
        'flight_number',
        'remarks',
        'sub_remark',
        'driver_id',
        'driver_name',
        'removed_by',
        'removed_at',
        'removal_remark',
    ];

    protected function casts(): array
    {
        return [
            'removed_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function removedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    /**
     * Build a snapshot row from an existing TripCrew at removal time.
     */
    public static function snapshotFromCrew(
        TripCrew $crew,
        Trip $trip,
        ?int $removedByUserId,
        ?string $removalRemark = null
    ): array {
        $crew->loadMissing('vessel');
        $trip->loadMissing('driver');

        return [
            'trip_id' => $trip->id,
            'trip_crew_id' => $crew->id,
            'crew_name' => $crew->name,
            'phone' => $crew->phone,
            'phone_2' => $crew->phone_2,
            'address' => $crew->address,
            'vessel_id' => $crew->vessel_id,
            'vessel_name' => $crew->vessel?->name,
            'pick_up_time' => $crew->pick_up_time,
            'from_location' => $crew->from_location,
            'to_location' => $crew->to_location,
            'flight_number' => $crew->flight_number,
            'remarks' => $crew->remarks,
            'sub_remark' => $crew->sub_remark,
            'driver_id' => $trip->driver_id,
            'driver_name' => $trip->driver?->name,
            'removed_by' => $removedByUserId,
            'removed_at' => now(),
            'removal_remark' => $removalRemark !== null && $removalRemark !== '' ? $removalRemark : null,
        ];
    }
}
