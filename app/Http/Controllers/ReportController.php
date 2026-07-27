<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vessel;
use App\Models\Partner;
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
        // Query trip crews instead of trips, with trip and related data
        $query = \App\Models\TripCrew::with(['trip.driver', 'trip.partner', 'trip.tripExpenses', 'vessel']);

        // Date range filter (on trip)
        if ($request->has('date_from') && $request->date_from) {
            $query->whereHas('trip', function($q) use ($request) {
                $q->whereDate('trip_date', '>=', $request->date_from);
            });
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereHas('trip', function($q) use ($request) {
                $q->whereDate('trip_date', '<=', $request->date_to);
            });
        }

        // Driver filter (on trip)
        if ($request->has('driver_id') && $request->driver_id) {
            $query->whereHas('trip', function($q) use ($request) {
                $q->where('driver_id', $request->driver_id);
            });
        }

        // Vessel filter (on crew)
        if ($request->has('vessel_id') && $request->vessel_id) {
            $query->where('vessel_id', $request->vessel_id);
        }

        // Status filter (on trip)
        if ($request->has('status') && $request->status) {
            $query->whereHas('trip', function($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        // Driver type filter (on trip's driver)
        if ($request->has('driver_type') && $request->driver_type) {
            $query->whereHas('trip.driver', function($q) use ($request) {
                $q->where('type', $request->driver_type);
            });
        }

        // Partner filter (on trip)
        if ($request->has('partner_id') && $request->partner_id) {
            $query->whereHas('trip', function($q) use ($request) {
                $q->where('partner_id', $request->partner_id);
            });
        }

        $crews = $query->latest('created_at')->get();

        // Transform data to uppercase
        $crews->transform(function($crew) {
            if ($crew->trip) {
                if ($crew->trip->title) {
                    $crew->trip->title = strtoupper($crew->trip->title);
                }
                if ($crew->trip->driver && $crew->trip->driver->name) {
                    $crew->trip->driver->name = strtoupper($crew->trip->driver->name);
                }
                if ($crew->trip->partner && $crew->trip->partner->title) {
                    $crew->trip->partner->title = strtoupper($crew->trip->partner->title);
                }
            }
            if ($crew->name) {
                $crew->name = strtoupper($crew->name);
            }
            if ($crew->vessel && $crew->vessel->name) {
                $crew->vessel->name = strtoupper($crew->vessel->name);
            }
            if ($crew->from_location) {
                $crew->from_location = strtoupper($crew->from_location);
            }
            if ($crew->to_location) {
                $crew->to_location = strtoupper($crew->to_location);
            }
            return $crew;
        });

        // Get unique trips for statistics
        $uniqueTrips = $crews->pluck('trip')->unique('id');
        
        // Calculate statistics based on unique trips
        $totalTrips = $uniqueTrips->count();
        $assignedTrips = $uniqueTrips->where('status', \App\Models\TripCrew::STATUS_ASSIGNED)->count();
        $inProgressTrips = $uniqueTrips->where('status', \App\Models\TripCrew::STATUS_IN_PROGRESS)->count();
        $completedTrips = $uniqueTrips->where('status', \App\Models\TripCrew::STATUS_COMPLETED)->count();

        // Trips by status for chart
        $statusData = [
            'Assigned' => $assignedTrips,
            'In Progress' => $inProgressTrips,
            'Completed' => $completedTrips,
        ];

        // Trips by date (for chart) - based on unique trips
        $tripsByDate = $uniqueTrips->groupBy(function($trip) {
            return $trip->trip_date->format('Y-m-d');
        })->map->count();

        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();
        $partners = Partner::orderBy('is_default', 'desc')->orderBy('title')->get();

        return view('reports.trip-summary', compact(
            'crews',
            'totalTrips',
            'assignedTrips',
            'inProgressTrips',
            'completedTrips',
            'statusData',
            'tripsByDate',
            'drivers',
            'vessels',
            'partners'
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
            $tripsQuery = Trip::where('driver_id', $driver->id)
                ->whereBetween('trip_date', [$dateFrom, $dateTo]);
            
            // Partner filter
            if ($request->has('partner_id') && $request->partner_id) {
                $tripsQuery->where('partner_id', $request->partner_id);
            }
            
            $trips = $tripsQuery->get();

            // Transform driver name to uppercase
            if ($driver->name) {
                $driver->name = strtoupper($driver->name);
            }

            $driverStats[] = [
                'driver' => $driver,
                'total_trips' => $trips->count(),
                'assigned' => $trips->where('status', \App\Models\TripCrew::STATUS_ASSIGNED)->count(),
                'in_progress' => $trips->where('status', \App\Models\TripCrew::STATUS_IN_PROGRESS)->count(),
                'completed' => $trips->where('status', \App\Models\TripCrew::STATUS_COMPLETED)->count(),
            ];
        }

        // Sort by total trips
        usort($driverStats, function($a, $b) {
            return $b['total_trips'] <=> $a['total_trips'];
        });

        // Internal vs Outsourcing comparison
        $internalDrivers = Driver::where('type', Driver::TYPE_INTERNAL)->count();
        $outsourcingDrivers = Driver::where('type', Driver::TYPE_OUTSOURCING)->count();
        
        $internalTripsQuery = Trip::whereHas('driver', function($q) {
            $q->where('type', Driver::TYPE_INTERNAL);
        })->whereBetween('trip_date', [$dateFrom, $dateTo]);
        
        $outsourcingTripsQuery = Trip::whereHas('driver', function($q) {
            $q->where('type', Driver::TYPE_OUTSOURCING);
        })->whereBetween('trip_date', [$dateFrom, $dateTo]);
        
        // Partner filter
        if ($request->has('partner_id') && $request->partner_id) {
            $internalTripsQuery->where('partner_id', $request->partner_id);
            $outsourcingTripsQuery->where('partner_id', $request->partner_id);
        }
        
        $internalTrips = $internalTripsQuery->count();
        $outsourcingTrips = $outsourcingTripsQuery->count();
        
        $partners = Partner::orderBy('is_default', 'desc')->orderBy('title')->get();

        return view('reports.driver-performance', compact(
            'driverStats',
            'internalDrivers',
            'outsourcingDrivers',
            'internalTrips',
            'outsourcingTrips',
            'dateFrom',
            'dateTo',
            'partners'
        ));
    }

    /**
     * Trip Expenses Report
     */
    public function tripExpenses(Request $request)
    {
        $query = \App\Models\TripExpense::with(['trip.partner', 'trip.crews.vessel', 'driver', 'expenseType']);

        // Date range filter (based on trip date)
        if ($request->has('date_from') && $request->date_from) {
            $query->whereHas('trip', function($q) use ($request) {
                $q->whereDate('trip_date', '>=', $request->date_from);
            });
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereHas('trip', function($q) use ($request) {
                $q->whereDate('trip_date', '<=', $request->date_to);
            });
        }

        // Driver filter (submitted by)
        if ($request->has('driver_id') && $request->driver_id) {
            $query->where('driver_id', $request->driver_id);
        }

        // Vessel filter (vessels are on trip crews, not trips)
        if ($request->has('vessel_id') && $request->vessel_id) {
            $query->whereHas('trip.crews', function($q) use ($request) {
                $q->where('vessel_id', $request->vessel_id);
            });
        }

        // Expense Type filter
        if ($request->has('expense_type_id') && $request->expense_type_id) {
            $query->where('expense_type_id', $request->expense_type_id);
        }

        // Partner filter (on trip)
        if ($request->has('partner_id') && $request->partner_id) {
            $query->whereHas('trip', function($q) use ($request) {
                $q->where('partner_id', $request->partner_id);
            });
        }

        $expenses = $query->latest()->get();

        // Transform data to uppercase
        $expenses->transform(function($expense) {
            if ($expense->driver && $expense->driver->name) {
                $expense->driver->name = strtoupper($expense->driver->name);
            }
            if ($expense->expenseType && $expense->expenseType->title) {
                $expense->expenseType->title = strtoupper($expense->expenseType->title);
            }
            if ($expense->trip) {
                if ($expense->trip->partner && $expense->trip->partner->title) {
                    $expense->trip->partner->title = strtoupper($expense->trip->partner->title);
                }
                if ($expense->trip->crews) {
                    foreach ($expense->trip->crews as $crew) {
                        if ($crew->vessel && $crew->vessel->name) {
                            $crew->vessel->name = strtoupper($crew->vessel->name);
                        }
                    }
                }
            }
            return $expense;
        });

        // Statistics
        $totalExpenses = $expenses->sum('amount');
        
        // Expenses by Type (for chart)
        $expensesByType = $expenses->groupBy(function($expense) {
            return $expense->expenseType->title ?? 'Unknown';
        })->map->sum('amount');

        // Expenses by Date (for chart)
        $expensesByDate = $expenses->groupBy(function($expense) {
            return ($expense->trip && $expense->trip->trip_date) ? $expense->trip->trip_date->format('Y-m-d') : 'Unknown';
        })->map->sum('amount');

        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();
        $expenseTypes = \App\Models\TripExpenseType::orderBy('title')->get();
        $partners = Partner::orderBy('is_default', 'desc')->orderBy('title')->get();

        return view('reports.trip-expenses', compact(
            'expenses',
            'totalExpenses',
            'expensesByType',
            'expensesByDate',
            'drivers',
            'vessels',
            'expenseTypes',
            'partners'
        ));
    }
}
