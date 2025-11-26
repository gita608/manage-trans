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
        'driver_id',
        'trip_date',
        'title',
    ];

    public function crews()
    {
        return $this->hasMany(TripCrew::class);
    }

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
     * Get the trip issues for this trip.
     */
    public function tripIssues()
    {
        return $this->hasMany(TripIssue::class);
    }

    /**
     * Get the trip expenses for this trip.
     */
    public function tripExpenses()
    {
        return $this->hasMany(TripExpense::class);
    }

    /**
     * Generate trip title based on driver and date
     * Format: "Trip 1", "Trip 2", etc. for each driver per day
     * 
     * @param int $driverId
     * @param string|Carbon $tripDate
     * @param int|null $excludeTripId Trip ID to exclude from count (for updates)
     * @return string
     */
    public static function generateTripTitle($driverId, $tripDate, $excludeTripId = null): string
    {
        // Count existing trips for this driver on this date
        $query = self::where('driver_id', $driverId)
            ->whereDate('trip_date', $tripDate);
        
        // Exclude current trip if updating
        if ($excludeTripId) {
            $query->where('id', '!=', $excludeTripId);
        }
        
        $tripCount = $query->count();
        
        // Next trip number is count + 1
        $tripNumber = $tripCount + 1;
        
        return "Trip {$tripNumber}";
    }

    /**
     * Get a custom human-readable description of the activity.
     * This method is called by the LogsActivity trait for complex cases.
     *
     * @param string $action
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return string
     */
    protected function getCustomActivityDescription(string $action, ?array $oldValues, ?array $newValues): string
    {
        // Custom descriptions for Trip model
        if ($action === 'created') {
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
            
            return "Trip created with driver '{$driverName}' and vessel '{$vesselName}'";
        }
        
        if ($action === 'updated') {
            $changes = [];
            if (isset($newValues['status'])) {
                $oldStatus = $oldValues['status'] ?? 'unknown';
                $newStatus = $newValues['status'];
                $changes[] = "status changed from '{$oldStatus}' to '{$newStatus}'";
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
            return "Trip deleted";
        }
        
        // Fallback to default description
        return "Trip action: {$action}";
    }
}
