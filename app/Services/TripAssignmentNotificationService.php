<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Notification;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TripAssignmentNotificationService
{
    public function __construct(
        protected FirebaseNotificationService $firebaseService
    ) {}

    public function notifyDriverAssigned(Trip $trip, ?int $triggeredByUserId = null): void
    {
        $trip->loadMissing(['driver', 'crews']);

        if (! $trip->driver_id || ! $trip->driver) {
            return;
        }

        $driver = $trip->driver;
        $tripDate = Carbon::parse($trip->trip_date)->format('M d, Y');
        $crewCount = $trip->crews->count();
        $title = 'New Trip Assigned';
        $message = "You have been assigned a new trip on {$tripDate} with {$crewCount} "
            .($crewCount === 1 ? 'crew member' : 'crew members')
            .". Trip: {$trip->title}";

        $this->persistAndPush($driver, $title, $message, $triggeredByUserId, [
            'type' => 'trip_assigned',
            'trip_id' => $trip->id,
        ], 'assignment');
    }

    public function notifyDriverTripUpdated(Trip $trip, ?int $triggeredByUserId = null): void
    {
        $trip->loadMissing(['driver']);

        if (! $trip->driver_id || ! $trip->driver) {
            return;
        }

        $driver = $trip->driver;
        $tripLabel = $trip->trip_reference ?: $trip->title;
        $title = 'Trip Updated';
        $message = "Your assigned trip {$tripLabel} has been updated. Please review the latest trip details.";

        $this->persistAndPush($driver, $title, $message, $triggeredByUserId, [
            'type' => 'trip_updated',
            'trip_id' => $trip->id,
        ], 'update');
    }

    protected function persistAndPush(
        Driver $driver,
        string $title,
        string $message,
        ?int $triggeredByUserId,
        array $pushData,
        string $context
    ): void {
        try {
            Notification::create([
                'user_id' => $triggeredByUserId,
                'driver_id' => $driver->id,
                'title' => $title,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create trip {$context} notification record: ".$e->getMessage());
        }

        if (! $driver->notification_token) {
            return;
        }

        try {
            $this->firebaseService->sendToDriver($driver, $title, $message, null, $pushData);
        } catch (\Exception $e) {
            Log::error("Failed to send trip {$context} push notification: ".$e->getMessage());
        }
    }
}
