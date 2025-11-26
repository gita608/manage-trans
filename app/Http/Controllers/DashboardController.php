<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\Vessel;
use App\Models\Trip;
use App\Models\TripCrew;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get counts
        $totalDrivers = Driver::count();
        $totalVessels = Vessel::count();
        $totalTrips = Trip::count();
        $totalStaff = User::where('role', User::ROLE_STAFF)->count();
        
        // Get crew statistics (status is on trip_crews, not trips)
        $assignedTrips = TripCrew::where('status', TripCrew::STATUS_ASSIGNED)->count();
        $inProgressTrips = TripCrew::where('status', TripCrew::STATUS_IN_PROGRESS)->count();
        $completedTrips = TripCrew::where('status', TripCrew::STATUS_COMPLETED)->count();
        
        // Get recent trips
        $recentTrips = Trip::with(['driver', 'crews.vessel'])
            ->latest()
            ->take(5)
            ->get();
        
        // Get recent activity logs
        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(10)
            ->get();
        
        // Get trips by month for chart (last 6 months)
        $tripsByMonth = Trip::select(
                DB::raw('COUNT(*) as count'),
                DB::raw('MONTH(trip_date) as month'),
                DB::raw('YEAR(trip_date) as year')
            )
            ->where('trip_date', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
        
        // Get busiest driver (most trips)
        $busiestDriver = Driver::withCount('trips')
            ->orderBy('trips_count', 'desc')
            ->first();
        
        // Calculate completed trips count for busiest driver
        $busiestDriverCompletedTrips = 0;
        if ($busiestDriver) {
            $busiestDriverCompletedTrips = TripCrew::whereHas('trip', function($q) use ($busiestDriver) {
                $q->where('driver_id', $busiestDriver->id);
            })->where('status', TripCrew::STATUS_COMPLETED)->count();
        }
        
        // Get top 5 busiest drivers
        $topDrivers = Driver::withCount('trips')
            ->orderBy('trips_count', 'desc')
            ->take(5)
            ->get();
        
        return view('dashboard', compact(
            'totalDrivers',
            'totalVessels',
            'totalTrips',
            'totalStaff',
            'assignedTrips',
            'inProgressTrips',
            'completedTrips',
            'recentTrips',
            'recentActivities',
            'tripsByMonth',
            'busiestDriver',
            'busiestDriverCompletedTrips',
            'topDrivers'
        ));
    }
}

