<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'crew_name',
        'driver_id',
        'vessel_id',
        'trip_date',
        'pick_up_time',
        'from_location',
        'to_location',
        'crew_phone',
        'crew_address',
        'status',
    ];

    /**
     * Trip status constants
     */
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    /**
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    /**
     * Get status badge color class
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_ASSIGNED => 'bg-warning',
            self::STATUS_IN_PROGRESS => 'bg-info',
            self::STATUS_COMPLETED => 'bg-success',
            default => 'bg-secondary',
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
        ];
    }

    /**
     * Get the driver that owns the trip.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vessel that owns the trip.
     */
    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Get a human-readable description of the activity.
     * Override this method for custom descriptions in Trip model.
     *
     * @param string $action
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return string
     */
    protected function getActivityDescription(string $action, ?array $oldValues, ?array $newValues): string
    {
        // Custom descriptions for Trip model
        if ($action === 'created') {
            $crewName = $this->crew_name ?? 'Unknown';
            $driverId = $this->driver_id ?? null;
            $vesselId = $this->vessel_id ?? null;
            
            $driverName = 'Unknown';
            $vesselName = 'Unknown';
            
            if ($driverId) {
                $driver = Driver::find($driverId);
                $driverName = $driver->name ?? 'Unknown';
            }
            
            if ($vesselId) {
                $vessel = Vessel::find($vesselId);
                $vesselName = $vessel->name ?? 'Unknown';
            }
            
            return "Trip created for crew '{$crewName}' with driver '{$driverName}' and vessel '{$vesselName}'";
        }
        
        if ($action === 'updated') {
            $changes = [];
            if (isset($newValues['status'])) {
                $oldStatus = $oldValues['status'] ?? 'unknown';
                $newStatus = $newValues['status'];
                $changes[] = "status changed from '{$oldStatus}' to '{$newStatus}'";
            }
            if (isset($newValues['crew_name'])) {
                $oldName = $oldValues['crew_name'] ?? 'unknown';
                $newName = $newValues['crew_name'];
                $changes[] = "crew name changed from '{$oldName}' to '{$newName}'";
            }
            if (isset($newValues['driver_id'])) {
                $oldDriver = Driver::find($oldValues['driver_id'] ?? null);
                $newDriver = Driver::find($newValues['driver_id']);
                $oldDriverName = $oldDriver->name ?? 'Unknown';
                $newDriverName = $newDriver->name ?? 'Unknown';
                $changes[] = "driver changed from '{$oldDriverName}' to '{$newDriverName}'";
            }
            if (isset($newValues['vessel_id'])) {
                $oldVessel = Vessel::find($oldValues['vessel_id'] ?? null);
                $newVessel = Vessel::find($newValues['vessel_id']);
                $oldVesselName = $oldVessel->name ?? 'Unknown';
                $newVesselName = $newVessel->name ?? 'Unknown';
                $changes[] = "vessel changed from '{$oldVesselName}' to '{$newVesselName}'";
            }
            
            if (!empty($changes)) {
                return "Trip updated: " . implode(', ', $changes);
            }
            return "Trip updated";
        }
        
        if ($action === 'deleted') {
            return "Trip deleted for crew '{$this->crew_name}'";
        }
        
        // Fallback to default description
        return "Trip action: {$action}";
    }
}
