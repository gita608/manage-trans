<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vessel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display the reports index page
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Trip Summary Report
     */
    public function tripSummary(Request $request)
    {
        $query = Trip::with(['driver', 'vessel']);

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('trip_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('trip_date', '<=', $request->date_to);
        }

        // Driver filter
        if ($request->has('driver_id') && $request->driver_id) {
            $query->where('driver_id', $request->driver_id);
        }

        // Vessel filter
        if ($request->has('vessel_id') && $request->vessel_id) {
            $query->where('vessel_id', $request->vessel_id);
        }

        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Driver type filter
        if ($request->has('driver_type') && $request->driver_type) {
            $query->whereHas('driver', function($q) use ($request) {
                $q->where('type', $request->driver_type);
            });
        }

        $trips = $query->latest('trip_date')->latest('created_at')->get();

        // Calculate statistics
        $totalTrips = $trips->count();
        $assignedTrips = $trips->where('status', Trip::STATUS_ASSIGNED)->count();
        $inProgressTrips = $trips->where('status', Trip::STATUS_IN_PROGRESS)->count();
        $completedTrips = $trips->where('status', Trip::STATUS_COMPLETED)->count();

        // Trips by status for chart
        $statusData = [
            'Assigned' => $assignedTrips,
            'In Progress' => $inProgressTrips,
            'Completed' => $completedTrips,
        ];

        // Trips by date (for chart)
        $tripsByDate = $trips->groupBy(function($trip) {
            return $trip->trip_date->format('Y-m-d');
        })->map->count();

        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();

        return view('reports.trip-summary', compact(
            'trips',
            'totalTrips',
            'assignedTrips',
            'inProgressTrips',
            'completedTrips',
            'statusData',
            'tripsByDate',
            'drivers',
            'vessels'
        ));
    }

    /**
     * Driver Performance Report
     */
    public function driverPerformance(Request $request)
    {
        $query = Driver::withCount('trips');

        // Driver type filter
        if ($request->has('driver_type') && $request->driver_type) {
            $query->where('type', $request->driver_type);
        }

        // Date range filter for trips
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : now()->subMonth()->startOfMonth();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : now()->endOfMonth();

        $drivers = $query->orderBy('trips_count', 'desc')->get();

        // Get detailed trip statistics for each driver
        $driverStats = [];
        foreach ($drivers as $driver) {
            $trips = Trip::where('driver_id', $driver->id)
                ->whereBetween('trip_date', [$dateFrom, $dateTo])
                ->get();

            $driverStats[] = [
                'driver' => $driver,
                'total_trips' => $trips->count(),
                'assigned' => $trips->where('status', Trip::STATUS_ASSIGNED)->count(),
                'in_progress' => $trips->where('status', Trip::STATUS_IN_PROGRESS)->count(),
                'completed' => $trips->where('status', Trip::STATUS_COMPLETED)->count(),
            ];
        }

        // Sort by total trips
        usort($driverStats, function($a, $b) {
            return $b['total_trips'] <=> $a['total_trips'];
        });

        // Internal vs Outsourcing comparison
        $internalDrivers = Driver::where('type', Driver::TYPE_INTERNAL)->count();
        $outsourcingDrivers = Driver::where('type', Driver::TYPE_OUTSOURCING)->count();
        
        $internalTrips = Trip::whereHas('driver', function($q) {
            $q->where('type', Driver::TYPE_INTERNAL);
        })->whereBetween('trip_date', [$dateFrom, $dateTo])->count();
        
        $outsourcingTrips = Trip::whereHas('driver', function($q) {
            $q->where('type', Driver::TYPE_OUTSOURCING);
        })->whereBetween('trip_date', [$dateFrom, $dateTo])->count();

        return view('reports.driver-performance', compact(
            'driverStats',
            'internalDrivers',
            'outsourcingDrivers',
            'internalTrips',
            'outsourcingTrips',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Daily/Weekly Report
     */
    public function dailyWeekly(Request $request)
    {
        $reportType = $request->get('type', 'daily'); // daily or weekly
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : now()->startOfWeek();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : now()->endOfWeek();

        $query = Trip::with(['driver', 'vessel'])
            ->whereBetween('trip_date', [$dateFrom, $dateTo]);

        // Driver filter
        if ($request->has('driver_id') && $request->driver_id) {
            $query->where('driver_id', $request->driver_id);
        }

        // Vessel filter
        if ($request->has('vessel_id') && $request->vessel_id) {
            $query->where('vessel_id', $request->vessel_id);
        }

        $trips = $query->orderBy('trip_date')->orderBy('pick_up_time')->get();

        // Group trips by date or week
        if ($reportType === 'weekly') {
            $groupedTrips = $trips->groupBy(function($trip) {
                return $trip->trip_date->format('Y-W'); // Year-Week
            });
        } else {
            $groupedTrips = $trips->groupBy(function($trip) {
                return $trip->trip_date->format('Y-m-d');
            });
        }

        // Calculate daily/weekly statistics
        $dailyStats = [];
        foreach ($groupedTrips as $key => $dayTrips) {
            $dailyStats[] = [
                'date' => $key,
                'total' => $dayTrips->count(),
                'assigned' => $dayTrips->where('status', Trip::STATUS_ASSIGNED)->count(),
                'in_progress' => $dayTrips->where('status', Trip::STATUS_IN_PROGRESS)->count(),
                'completed' => $dayTrips->where('status', Trip::STATUS_COMPLETED)->count(),
            ];
        }

        // Peak hours analysis
        $peakHours = [];
        foreach ($trips as $trip) {
            $hour = Carbon::parse($trip->pick_up_time)->format('H:00');
            if (!isset($peakHours[$hour])) {
                $peakHours[$hour] = 0;
            }
            $peakHours[$hour]++;
        }
        arsort($peakHours);

        // Busiest days
        $dayOfWeek = [];
        foreach ($trips as $trip) {
            $day = $trip->trip_date->format('l');
            if (!isset($dayOfWeek[$day])) {
                $dayOfWeek[$day] = 0;
            }
            $dayOfWeek[$day]++;
        }
        arsort($dayOfWeek);

        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();

        return view('reports.daily-weekly', compact(
            'trips',
            'groupedTrips',
            'dailyStats',
            'peakHours',
            'dayOfWeek',
            'reportType',
            'dateFrom',
            'dateTo',
            'drivers',
            'vessels'
        ));
    }
}
