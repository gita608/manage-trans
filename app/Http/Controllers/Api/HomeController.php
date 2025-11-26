<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripCrew;
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

        // Calculate statistics from trip_crews (status is on trip_crews, not trips)
        $totalTrips = $driver->trips()->count();
        $completedCrews = TripCrew::whereHas('trip', function($q) use ($driver) {
            $q->where('driver_id', $driver->id);
        })->where('status', 'completed')->count();
        
        $todayTrips = $driver->trips()
            ->whereDate('trip_date', $today)
            ->count();
        
        $thisMonthTrips = $driver->trips()
            ->whereYear('trip_date', $currentYear)
            ->whereMonth('trip_date', $currentMonth)
            ->count();
        
        $statisticsResult = (object) [
            'total_trips' => $totalTrips,
            'completed' => $completedCrews,
            'today_trips' => $todayTrips,
            'this_month' => $thisMonthTrips,
        ];

        // Fetch trips that have crews with matching status
        // Status is now on trip_crews, so we need to find trips that have crews with the status
        $ongoingTrip = $driver->trips()
            ->whereHas('crews', function($q) {
                $q->where('status', 'in_progress');
            })
            ->with(['crews.vessel']) // Eager load crews and their vessels
            ->orderBy('trip_date', 'desc')
            ->first();

        // Next trip (assigned status, scheduled for future)
        $nextTrip = $driver->trips()
            ->whereHas('crews', function($q) {
                $q->where('status', 'assigned');
            })
            ->where(function ($query) use ($today, $now) {
                $query->whereDate('trip_date', '>', $today)
                    ->orWhere(function ($q) use ($today, $now) {
                        $q->whereDate('trip_date', $today)
                            ->whereHas('crews', function($crewQ) use ($now) {
                                $crewQ->where('pick_up_time', '>', $now->format('H:i:s'));
                            });
                    });
            })
            ->with(['crews.vessel']) // Eager load crews and their vessels
            ->orderBy('trip_date', 'asc')
            ->first();

        // Last completed trip (trip that has at least one completed crew)
        $lastCompletedTrip = $driver->trips()
            ->whereHas('crews', function($q) {
                $q->where('status', 'completed');
            })
            ->with(['crews.vessel']) // Eager load crews and their vessels
            ->orderBy('trip_date', 'desc')
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

        // Get the first crew member (or the one matching the status)
        $firstCrew = $trip->crews->first();
        $status = $firstCrew ? $firstCrew->status : 'unknown';
        
        // For completed trips, get the first completed crew
        if ($isCompleted) {
            $completedCrew = $trip->crews->where('status', 'completed')->first();
            if ($completedCrew) {
                $firstCrew = $completedCrew;
                $status = 'completed';
            }
        }

        $formatted = [
            'id' => $trip->id,
            'crew_name' => $firstCrew->name ?? null,
            'crew_phone' => $firstCrew->phone ?? null,
            'crew_address' => $firstCrew->address ?? null,
            'status' => $status,
            'status_label' => ucfirst(str_replace('_', ' ', $status)),
            'pickup' => [
                'location' => $firstCrew->from_location ?? null,
                'time' => $firstCrew->pick_up_time ?? null,
                'date' => $tripDate->format('Y-m-d'),
                'formatted_date' => $tripDate->format('d/m/Y'),
            ],
            'drop' => [
                'location' => $firstCrew->to_location ?? null,
            ],
            'trip_date' => $tripDate->format('Y-m-d'),
            'formatted_trip_date' => $tripDate->format('d/m/Y'),
            'vessel' => $firstCrew && $firstCrew->vessel ? [
                'id' => $firstCrew->vessel->id,
                'name' => $firstCrew->vessel->name,
            ] : null,
        ];

        // Add completed trip specific fields
        if ($isCompleted && $firstCrew) {
            $formatted['started_at'] = $firstCrew->pick_up_time;
            $formatted['completed_at'] = $firstCrew->updated_at->format('H:i');
            $formatted['completed_date'] = $firstCrew->updated_at->format('Y-m-d');
        } else {
            // For ongoing or next trips, add started_at if in progress
            if ($status === 'in_progress' && $firstCrew) {
                $formatted['started_at'] = $firstCrew->updated_at->format('H:i');
            }
        }

        return $formatted;
    }
}

