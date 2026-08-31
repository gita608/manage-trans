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
        'partner_id',
        'partner_request_id',
        'trip_date',
        'title',
        'status',
        'trip_reference',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($trip) {
            if (empty($trip->trip_reference)) {
                $trip->trip_reference = sprintf('TRP-%06d', $trip->id);
                $trip->saveQuietly();
            }
        });
    }

    public function crews()
    {
        return $this->hasMany(TripCrew::class);
    }

    /**
     * Genuine crew removals (not delete/recreate during normal trip edits).
     */
    public function crewRemovals()
    {
        return $this->hasMany(TripCrewRemoval::class)->latest('removed_at');
    }

    /**
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return TripCrew::getStatuses();
    }

    // Removed getStatusAttribute() - status is now a real database column

    /**
     * Get status badge color class based on trip status
     */
    public function getStatusBadgeClass(): string
    {
        switch ($this->status) {
            case TripCrew::STATUS_COMPLETED:
                return 'bg-success';
            case TripCrew::STATUS_IN_PROGRESS:
                return 'bg-info';
            case TripCrew::STATUS_ASSIGNED:
                return 'bg-warning';
            case TripCrew::STATUS_UNASSIGNED:
                return 'bg-secondary';
            case TripCrew::STATUS_CANCELLED:
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }

    /**
     * Check if the trip is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === TripCrew::STATUS_COMPLETED;
    }

    /**
     * Check if the trip is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === TripCrew::STATUS_CANCELLED;
    }

    // Removed getCompletedCrewsCount() and getInProgressCrewsCount() - crews no longer have status

    /**
     * Get status badge color class (without 'bg-' prefix)
     */
    public function getStatusBadge(): string
    {
        $badgeClass = $this->getStatusBadgeClass();

        // Remove 'bg-' prefix if present
        return str_replace('bg-', '', $badgeClass);
    }

    /**
     * Get human-readable status text
     */
    public function getStatusText(): string
    {
        switch ($this->status) {
            case TripCrew::STATUS_COMPLETED:
                return 'Completed';
            case TripCrew::STATUS_IN_PROGRESS:
                return 'In Progress';
            case TripCrew::STATUS_ASSIGNED:
                return 'Assigned';
            case TripCrew::STATUS_UNASSIGNED:
                return 'Unassigned';
            case TripCrew::STATUS_CANCELLED:
                return 'Cancelled';
            default:
                return ucfirst($this->status ?? 'Unknown');
        }
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
     * Get the partner for this trip.
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the partner request that this trip was created from.
     */
    public function partnerRequest()
    {
        return $this->belongsTo(PartnerRequest::class);
    }

    /**
     * Universal Trip Search: match the trip and related enquiry fields.
     *
     * All OR conditions stay inside one grouped constraint so other filters
     * continue to combine with AND semantics.
     */
    public function scopeUniversalSearch($query, ?string $term)
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $pattern = '%'.$term.'%';

        return $query->where(function ($builder) use ($pattern) {
            $builder->whereLike('trip_reference', $pattern)
                ->orWhereLike('title', $pattern)
                ->orWhereHas('partner', function ($partnerQuery) use ($pattern) {
                    $partnerQuery->whereLike('title', $pattern);
                })
                ->orWhereHas('driver', function ($driverQuery) use ($pattern) {
                    $driverQuery->where(function ($driver) use ($pattern) {
                        $driver->whereLike('name', $pattern)
                            ->orWhereLike('contact', $pattern);
                    });
                })
                ->orWhereHas('partnerRequest', function ($requestQuery) use ($pattern) {
                    $requestQuery->whereLike('request_reference', $pattern);
                })
                ->orWhereHas('crews', function ($crewQuery) use ($pattern) {
                    $crewQuery->where(function ($crew) use ($pattern) {
                        $crew->whereLike('name', $pattern)
                            ->orWhereLike('phone', $pattern)
                            ->orWhereLike('phone_2', $pattern)
                            ->orWhereLike('address', $pattern)
                            ->orWhereLike('from_location', $pattern)
                            ->orWhereLike('to_location', $pattern)
                            ->orWhereLike('flight_number', $pattern)
                            ->orWhereLike('remarks', $pattern)
                            ->orWhereLike('sub_remark', $pattern)
                            ->orWhereHas('vessel', function ($vesselQuery) use ($pattern) {
                                $vesselQuery->whereLike('name', $pattern);
                            });
                    });
                })
                ->orWhereHas('crewRemovals', function ($removalQuery) use ($pattern) {
                    $removalQuery->where(function ($removal) use ($pattern) {
                        $removal->whereLike('crew_name', $pattern)
                            ->orWhereLike('phone', $pattern)
                            ->orWhereLike('phone_2', $pattern)
                            ->orWhereLike('address', $pattern)
                            ->orWhereLike('vessel_name', $pattern)
                            ->orWhereLike('from_location', $pattern)
                            ->orWhereLike('to_location', $pattern)
                            ->orWhereLike('flight_number', $pattern)
                            ->orWhereLike('remarks', $pattern)
                            ->orWhereLike('sub_remark', $pattern)
                            ->orWhereLike('removal_remark', $pattern)
                            ->orWhereLike('driver_name', $pattern);
                    });
                });
        });
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
     * @param  int  $driverId
     * @param  string|Carbon  $tripDate
     * @param  int|null  $excludeTripId  Trip ID to exclude from count (for updates)
     */
    public static function generateTripTitle($driverId, $tripDate, $excludeTripId = null): string
    {
        if ($driverId) {
            $query = self::where('driver_id', $driverId)
                ->whereDate('trip_date', $tripDate);
        } else {
            $query = self::whereNull('driver_id')
                ->whereDate('trip_date', $tripDate);
        }

        if ($excludeTripId) {
            $query->where('id', '!=', $excludeTripId);
        }

        $tripCount = $query->count();
        $tripNumber = $tripCount + 1;

        return "Trip {$tripNumber}";
    }

    /**
     * Get a custom human-readable description of the activity.
     * This method is called by the LogsActivity trait for complex cases.
     */
    protected function getCustomActivityDescription(string $action, ?array $oldValues, ?array $newValues): string
    {
        // Custom descriptions for Trip model (stored for audit; Trip Details uses TripLifecyclePresenter)
        if ($action === 'created') {
            $driverId = $this->driver_id ?? null;

            if ($driverId) {
                $driver = Driver::find($driverId);
                $driverName = $driver->name ?? 'Unknown';

                return "Schedule created for driver '{$driverName}'";
            }

            return 'Schedule created — awaiting driver assignment';
        }

        if ($action === 'updated') {
            $changes = [];
            if (isset($newValues['status'])) {
                $newStatus = $newValues['status'];
                $changes[] = match ($newStatus) {
                    TripCrew::STATUS_IN_PROGRESS => 'trip started',
                    TripCrew::STATUS_COMPLETED => 'trip completed',
                    TripCrew::STATUS_CANCELLED => 'trip cancelled',
                    TripCrew::STATUS_ASSIGNED => 'driver assigned',
                    default => 'status updated',
                };
            }
            if (isset($newValues['driver_id'])) {
                $oldDriver = Driver::find($oldValues['driver_id'] ?? null);
                $newDriver = Driver::find($newValues['driver_id']);
                $oldDriverName = $oldDriver->name ?? 'Unassigned';
                $newDriverName = $newDriver->name ?? 'Unassigned';
                if (empty($oldValues['driver_id']) && ! empty($newValues['driver_id'])) {
                    $changes[] = "driver assigned ({$newDriverName})";
                } else {
                    $changes[] = "driver changed ({$oldDriverName} → {$newDriverName})";
                }
            }

            if (! empty($changes)) {
                return 'Schedule updated: '.implode(', ', $changes);
            }

            return 'Schedule updated';
        }

        if ($action === 'deleted') {
            return 'Trip deleted';
        }

        return "Trip action: {$action}";
    }
}
