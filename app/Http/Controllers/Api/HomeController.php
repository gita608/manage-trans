<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get home page data for the authenticated driver.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $driver = $request->user();
        $today = Carbon::today();
        $now = Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;

        // Optimize: Calculate statistics in a single query using conditional aggregation
        // This reduces 4 separate COUNT queries to 1 aggregated query
        // Using the trips() relationship ensures proper foreign key filtering
        $statisticsResult = $driver->trips()
            ->selectRaw('
                COUNT(*) as total_trips,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN DATE(trip_date) = ? THEN 1 ELSE 0 END) as today_trips,
                SUM(CASE WHEN YEAR(trip_date) = ? AND MONTH(trip_date) = ? THEN 1 ELSE 0 END) as this_month
            ', [
                Trip::STATUS_COMPLETED,
                $today->format('Y-m-d'),
                $currentYear,
                $currentMonth
            ])
            ->first();

        // Optimize: Fetch all needed trips in parallel using the relationship
        // Using separate optimized queries with proper eager loading to prevent N+1 queries
        $ongoingTrip = $driver->trips()
            ->where('status', Trip::STATUS_IN_PROGRESS)
            ->with('vessel') // Eager load vessel relationship to avoid N+1
            ->orderBy('trip_date', 'desc')
            ->orderBy('pick_up_time', 'desc')
            ->first();

        // Next trip (assigned status, scheduled for future)
        $nextTrip = $driver->trips()
            ->where('status', Trip::STATUS_ASSIGNED)
            ->where(function ($query) use ($today, $now) {
                $query->whereDate('trip_date', '>', $today)
                    ->orWhere(function ($q) use ($today, $now) {
                        $q->whereDate('trip_date', $today)
                            ->where('pick_up_time', '>', $now->format('H:i:s'));
                    });
            })
            ->with('vessel') // Eager load vessel relationship
            ->orderBy('trip_date', 'asc')
            ->orderBy('pick_up_time', 'asc')
            ->first();

        // Last completed trip
        $lastCompletedTrip = $driver->trips()
            ->where('status', Trip::STATUS_COMPLETED)
            ->with('vessel') // Eager load vessel relationship
            ->orderBy('trip_date', 'desc')
            ->orderBy('pick_up_time', 'desc')
            ->first();

        // Format user profile
        $userProfile = [
            'id' => $driver->id,
            'name' => $driver->name ?? 'Guest',
            'phone' => $driver->contact ?? 'No phone number',
            'photo' => $driver->photo,
            'email' => $driver->email,
        ];

        // Format statistics (convert to integers)
        $statistics = [
            'total_trips' => (int) ($statisticsResult->total_trips ?? 0),
            'completed' => (int) ($statisticsResult->completed ?? 0),
            'today_trips' => (int) ($statisticsResult->today_trips ?? 0),
            'this_month' => (int) ($statisticsResult->this_month ?? 0),
        ];

        // Format ongoing trip
        $ongoingTripData = null;
        if ($ongoingTrip) {
            $ongoingTripData = $this->formatTrip($ongoingTrip);
        }

        // Format next trip
        $nextTripData = null;
        if ($nextTrip) {
            $nextTripData = $this->formatTrip($nextTrip);
        }

        // Format last completed trip
        $lastCompletedTripData = null;
        if ($lastCompletedTrip) {
            $lastCompletedTripData = $this->formatTrip($lastCompletedTrip, true);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_profile' => $userProfile,
                'statistics' => $statistics,
                'ongoing_trip' => $ongoingTripData,
                'next_trip' => $nextTripData,
                'last_completed_trip' => $lastCompletedTripData,
            ],
        ], 200);
    }

    /**
     * Format trip data for API response.
     *
     * @param  \App\Models\Trip  $trip
     * @param  bool  $isCompleted
     * @return array
     */
    private function formatTrip(Trip $trip, bool $isCompleted = false): array
    {
        // Ensure trip_date is a Carbon instance
        $tripDate = $trip->trip_date instanceof \Carbon\Carbon 
            ? $trip->trip_date 
            : Carbon::parse($trip->trip_date);

        $formatted = [
            'id' => $trip->id,
            'crew_name' => $trip->crew_name,
            'crew_phone' => $trip->crew_phone ?? null,
            'crew_address' => $trip->crew_address ?? null,
            'status' => $trip->status,
            'status_label' => ucfirst(str_replace('_', ' ', $trip->status)),
            'pickup' => [
                'location' => $trip->from_location,
                'time' => $trip->pick_up_time,
                'date' => $tripDate->format('Y-m-d'),
                'formatted_date' => $tripDate->format('d/m/Y'),
            ],
            'drop' => [
                'location' => $trip->to_location,
            ],
            'trip_date' => $tripDate->format('Y-m-d'),
            'formatted_trip_date' => $tripDate->format('d/m/Y'),
            'vessel' => $trip->vessel ? [
                'id' => $trip->vessel->id,
                'name' => $trip->vessel->name,
            ] : null,
        ];

        // Add completed trip specific fields
        if ($isCompleted) {
            $formatted['started_at'] = $trip->pick_up_time;
            $formatted['completed_at'] = $trip->updated_at->format('H:i');
            $formatted['completed_date'] = $trip->updated_at->format('Y-m-d');
        } else {
            // For ongoing or next trips, add started_at if in progress
            if ($trip->status === Trip::STATUS_IN_PROGRESS) {
                $formatted['started_at'] = $trip->updated_at->format('H:i');
            }
        }

        return $formatted;
    }
}

