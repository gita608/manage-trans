<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverCheckIn;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class DriverCheckInController extends Controller
{
    /**
     * Display a listing of driver check-ins.
     */
    public function index(Request $request)
    {
        $query = DriverCheckIn::with(['driver', 'vehicle'])->latest('check_in_at');

        if ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('check_in_date', today());
                    break;
                case 'yesterday':
                    $query->whereDate('check_in_date', today()->subDay());
                    break;
                case 'last_7_days':
                    $query->whereDate('check_in_date', '>=', today()->subDays(6));
                    break;
                case 'this_month':
                    $query->whereMonth('check_in_date', now()->month)
                        ->whereYear('check_in_date', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('check_in_date', now()->subMonth()->month)
                        ->whereYear('check_in_date', now()->subMonth()->year);
                    break;
            }
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            if ($request->filled('date_from')) {
                $query->whereDate('check_in_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('check_in_date', '<=', $request->date_to);
            }
        } else {
            $query->whereDate('check_in_date', today());
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $checkIns = $query->get();
        $drivers = Driver::query()->orderBy('name')->get(['id', 'name']);
        $vehicles = Vehicle::query()->orderBy('name')->get(['id', 'name', 'number']);

        $totalCheckIns = DriverCheckIn::count();
        $activeCheckIns = DriverCheckIn::active()->count();
        $todayCheckIns = DriverCheckIn::whereDate('check_in_date', today())->count();

        return view('check-ins.index', compact(
            'checkIns',
            'drivers',
            'vehicles',
            'totalCheckIns',
            'activeCheckIns',
            'todayCheckIns'
        ));
    }
}
