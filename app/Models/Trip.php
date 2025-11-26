<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Carbon\Carbon;
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
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return TripCrew::getStatuses();
    }

    /**
     * Check if a trip is completed based on all crews' status.
     * Returns 'completed' if all crews are completed, otherwise 'pending'.
     *
     * @param int $tripId
     * @return string 'completed' or 'pending'
     */
    public static function checkTripCompletionStatus(int $tripId): string
    {
        $trip = self::with('crews')->find($tripId);
        
        if (!$trip) {
            return 'pending';
        }
        
        return $trip->isCompleted() ? 'completed' : 'pending';
    }

    /**
     * Check if this trip is completed (all crews are completed).
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        if ($this->crews->isEmpty()) {
            return false;
        }
        
        return $this->crews->where('status', '!=', TripCrew::STATUS_COMPLETED)->isEmpty();
    }

    /**
     * Get the trip status based on crews.
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        if ($this->crews->isEmpty()) {
            return TripCrew::STATUS_ASSIGNED;
        }
        
        if ($this->isCompleted()) {
            return TripCrew::STATUS_COMPLETED;
        }
        
        $inProgress = $this->crews->where('status', TripCrew::STATUS_IN_PROGRESS)->count();
        $completed = $this->crews->where('status', TripCrew::STATUS_COMPLETED)->count();
        
        // If any crew is in progress OR completed (but not all), the trip is in progress
        if ($inProgress > 0 || $completed > 0) {
            return TripCrew::STATUS_IN_PROGRESS;
        }
        
        return TripCrew::STATUS_ASSIGNED;
    }

    /**
     * Get status badge color class based on crew statuses
     */
    public function getStatusBadgeClass(): string
    {
        $totalCrews = $this->crews->count();
        if ($totalCrews === 0) {
            return 'bg-secondary';
        }
        
        $completedCrews = $this->crews->where('status', TripCrew::STATUS_COMPLETED)->count();
        $inProgressCrews = $this->crews->where('status', TripCrew::STATUS_IN_PROGRESS)->count();
        
        if ($completedCrews === $totalCrews) {
            return 'bg-success';
        } elseif ($inProgressCrews > 0) {
            return 'bg-info';
        } else {
            return 'bg-warning';
        }
    }

    /**
     * Get status badge color (without bg- prefix) for Bootstrap badges
     *
     * @return string
     */
    public function getStatusBadge(): string
    {
        if ($this->isCompleted()) {
            return 'success';
        } elseif ($this->status === TripCrew::STATUS_IN_PROGRESS) {
            return 'warning';
        } else {
            return 'primary';
        }
    }

    /**
     * Get human-readable status text
     *
     * @return string
     */
    public function getStatusText(): string
    {
        if ($this->isCompleted() && $this->crews->count() > 0) {
            return 'All Completed';
        } elseif ($this->status === TripCrew::STATUS_IN_PROGRESS) {
            return 'In Progress';
        } else {
            return 'Pending';
        }
    }

    /**
     * Get completed crews count
     *
     * @return int
     */
    public function getCompletedCrewsCount(): int
    {
        return $this->crews->where('status', TripCrew::STATUS_COMPLETED)->count();
    }

    /**
     * Get in-progress crews count
     *
     * @return int
     */
    public function getInProgressCrewsCount(): int
    {
        return $this->crews->where('status', TripCrew::STATUS_IN_PROGRESS)->count();
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
            $driverName = $this->relationLoaded('driver') && $this->driver 
                ? $this->driver->name 
                : 'Unknown';
            
            return "Trip created for driver '{$driverName}'";
        }
        
        if ($action === 'updated') {
            $changes = [];
            if (isset($newValues['status'])) {
                $oldStatus = $oldValues['status'] ?? 'unknown';
                $newStatus = $newValues['status'];
                $changes[] = "status changed from '{$oldStatus}' to '{$newStatus}'";
            }
            if (isset($newValues['driver_id'])) {
                $oldDriverId = $oldValues['driver_id'] ?? null;
                $newDriverId = $newValues['driver_id'];
                
                $oldDriverName = $oldDriverId ? (Driver::find($oldDriverId)->name ?? 'Unknown') : 'Unknown';
                $newDriverName = Driver::find($newDriverId)->name ?? 'Unknown';
                
                $changes[] = "driver changed from '{$oldDriverName}' to '{$newDriverName}'";
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
