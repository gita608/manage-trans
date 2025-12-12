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

        // Calculate statistics using optimized queries
        $tripsQuery = $driver->trips();
        
        $totalTrips = $tripsQuery->count();
        
        // Count completed trips: trips with completed status
        $completedTrips = $tripsQuery->clone()
            ->where('status', TripCrew::STATUS_COMPLETED)
            ->count();
        
        $todayTrips = $tripsQuery->clone()
            ->whereDate('trip_date', $today)
            ->count();
        
        $thisMonthTrips = $tripsQuery->clone()
            ->whereYear('trip_date', $now->year)
            ->whereMonth('trip_date', $now->month)
            ->count();

        // Fetch ongoing trip - trip with in_progress status
        $ongoingTrip = $driver->trips()
            ->where('status', TripCrew::STATUS_IN_PROGRESS)
            ->with(['crews.vessel'])
            ->orderBy('trip_date', 'desc')
            ->first();

        // Next trip - trip with assigned status, scheduled for future
        $nextTrip = $driver->trips()
            ->where('status', TripCrew::STATUS_ASSIGNED)
            ->where(function ($query) use ($today, $now) {
                $query->whereDate('trip_date', '>', $today)
                    ->orWhere(function ($q) use ($today, $now) {
                        $q->whereDate('trip_date', $today)
                            ->whereHas('crews', function($crewQ) use ($now) {
                                $crewQ->where('pick_up_time', '>', $now->format('H:i:s'));
                            });
                    });
            })
            ->with(['crews.vessel'])
            ->orderBy('trip_date', 'asc')
            ->first();

        // Last completed trip - trip with completed status
        $lastCompletedTrip = $driver->trips()
            ->where('status', TripCrew::STATUS_COMPLETED)
            ->with(['crews.vessel'])
            ->orderBy('trip_date', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user_profile' => [
                    'id' => $driver->id,
                    'name' => $driver->name ?? 'Guest',
                    'phone' => $driver->contact ?? 'No phone number',
                    'photo' => $driver->photo ? asset('storage/' . $driver->photo) : null,
                    'email' => $driver->email,
                ],
                'statistics' => [
                    'total_trips' => (int) $totalTrips,
                    'completed' => (int) $completedTrips,
                    'today_trips' => (int) $todayTrips,
                    'this_month' => (int) $thisMonthTrips,
                ],
                'ongoing_trip' => $ongoingTrip ? $this->formatTrip($ongoingTrip) : null,
                'next_trip' => $nextTrip ? $this->formatTrip($nextTrip) : null,
                'last_completed_trip' => $lastCompletedTrip ? $this->formatTrip($lastCompletedTrip, true) : null,
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
        $crews = $trip->crews;
        $tripDate = $trip->trip_date instanceof Carbon ? $trip->trip_date : Carbon::parse($trip->trip_date);

        // Format all crews with their basic details
        $formattedCrews = $crews->map(function ($crew) {
            return [
                'name' => $crew->name,
                'phone' => $crew->phone,
                'vessel' => $crew->vessel->name,
                'flight_number' => $crew->flight_number,
                'remarks' => $crew->remarks,
                'pick_up_time' => $crew->pick_up_time ? Carbon::parse($crew->pick_up_time)->subHours(12)->format('h:i A') : null,
                'from_location' => $crew->from_location,
                'to_location' => $crew->to_location,
            ];
        })->toArray();

        $formatted = [
            'trip_id' => $trip->id,
            'trip_title' => $trip->title,
            'trip_date' => $tripDate->format(format: 'd-m-Y'),
            'status' => $trip->status,
            'crews' => $formattedCrews,
            'total_crew_count' => $crews->count(),
        ];

        return $formatted;
    }
}

