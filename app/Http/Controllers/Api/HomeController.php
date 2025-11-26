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
        
        // Count completed trips: trips where all crews are completed (total_crews = completed_crews > 0)
        $completedTrips = $tripsQuery->clone()
            ->whereHas('crews')
            ->whereDoesntHave('crews', function($q) {
                $q->where('status', '!=', TripCrew::STATUS_COMPLETED);
            })
            ->count();
        
        $todayTrips = $tripsQuery->clone()
            ->whereDate('trip_date', $today)
            ->count();
        
        $thisMonthTrips = $tripsQuery->clone()
            ->whereYear('trip_date', $now->year)
            ->whereMonth('trip_date', $now->month)
            ->count();

        // Fetch ongoing trip - trip with in_progress crews but not all completed
        $ongoingTrip = $driver->trips()
            ->whereHas('crews', function($q) {
                $q->where('status', TripCrew::STATUS_IN_PROGRESS);
            })
            ->whereHas('crews', function($q) {
                $q->where('status', '!=', TripCrew::STATUS_COMPLETED);
            })
            ->with(['crews.vessel'])
            ->orderBy('trip_date', 'desc')
            ->get()
            ->first(function($trip) {
                if ($trip->crews->isEmpty()) {
                    return false;
                }
                $hasInProgress = $trip->crews->contains('status', TripCrew::STATUS_IN_PROGRESS);
                return $hasInProgress && !$trip->isCompleted();
            });

        // Next trip - trip with assigned crews, scheduled for future, not all completed
        $nextTrip = $driver->trips()
            ->whereHas('crews', function($q) {
                $q->where('status', TripCrew::STATUS_ASSIGNED);
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
            ->with(['crews.vessel'])
            ->orderBy('trip_date', 'asc')
            ->get()
            ->first(function($trip) {
                if ($trip->crews->isEmpty()) {
                    return false;
                }
                $hasAssigned = $trip->crews->contains('status', TripCrew::STATUS_ASSIGNED);
                return !$trip->isCompleted() && $hasAssigned;
            });

        // Last completed trip - trip where ALL crews are completed
        $lastCompletedTrip = $driver->trips()
            ->whereHas('crews')
            ->whereDoesntHave('crews', function($q) {
                $q->where('status', '!=', TripCrew::STATUS_COMPLETED);
            })
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
                    'photo' => $driver->photo,
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
        
        // Determine trip status and first crew based on crews collection
        if ($crews->isEmpty()) {
            $status = 'unknown';
            $firstCrew = null;
        } elseif ($isCompleted || $trip->isCompleted()) {
            $status = 'completed';
            $firstCrew = $crews->first();
        } elseif ($crews->contains('status', TripCrew::STATUS_IN_PROGRESS)) {
            $status = 'in_progress';
            $firstCrew = $crews->firstWhere('status', TripCrew::STATUS_IN_PROGRESS) ?? $crews->first();
        } else {
            $status = 'assigned';
            $firstCrew = $crews->first();
        }

        $formatted = [
            'trip_id' => $trip->id,
            'trip_title' => $trip->title,
            'trip_date' => $tripDate->format('Y-m-d'),
            'vessel' => $firstCrew && $firstCrew->relationLoaded('vessel') && $firstCrew->vessel ? [
                'id' => $firstCrew->vessel->id,
                'name' => $firstCrew->vessel->name,
            ] : null,
        ];

        if ($isCompleted && $firstCrew) {
            $formatted['started_at'] = $firstCrew->pick_up_time;
            $formatted['completed_at'] = $firstCrew->updated_at->format('H:i');
            $formatted['completed_date'] = $firstCrew->updated_at->format('Y-m-d');
        } elseif ($status === 'in_progress' && $firstCrew) {
            $formatted['started_at'] = $firstCrew->updated_at->format('H:i');
        }

        return $formatted;
    }
}

