<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TripAssignmentNotificationService
{
    public function __construct(
        protected FirebaseNotificationService $firebaseService
    ) {
    }

    public function notifyDriverAssigned(Trip $trip, ?int $triggeredByUserId = null): void
    {
        $trip->loadMissing(['driver', 'crews']);

        if (!$trip->driver_id || !$trip->driver) {
            return;
        }

        $driver = $trip->driver;
        $tripDate = Carbon::parse($trip->trip_date)->format('M d, Y');
        $crewCount = $trip->crews->count();
        $title = 'New Trip Assigned';
        $message = "You have been assigned a new trip on {$tripDate} with {$crewCount} "
            . ($crewCount === 1 ? 'crew member' : 'crew members')
            . ". Trip: {$trip->title}";

        try {
            Notification::create([
                'user_id' => $triggeredByUserId,
                'driver_id' => $driver->id,
                'title' => $title,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create trip assignment notification record: ' . $e->getMessage());
        }

        if (!$driver->notification_token) {
            return;
        }

        try {
            $this->firebaseService->sendToDriver($driver, $title, $message, null, [
                'type' => 'trip_assigned',
                'trip_id' => $trip->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send trip assignment push notification: ' . $e->getMessage());
        }
    }
}
