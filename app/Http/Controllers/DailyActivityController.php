<?php

namespace App\Http\Controllers;

use App\Models\DailyActivity;
use App\Models\Driver;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DailyActivityController extends Controller
{
    /**
     * Display a listing of daily activities.
     */
    public function index(Request $request)
    {
        $query = DailyActivity::with('driver');

        // Filter by date range preset
        if ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('activity_date', today());
                    break;
                case 'yesterday':
                    $query->whereDate('activity_date', today()->subDay());
                    break;
                case 'last_7_days':
                    $query->whereDate('activity_date', '>=', today()->subDays(6));
                    break;
                case 'this_month':
                    $query->whereMonth('activity_date', now()->month)
                          ->whereYear('activity_date', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('activity_date', now()->subMonth()->month)
                          ->whereYear('activity_date', now()->subMonth()->year);
                    break;
            }
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            // Filter by activity date range
            if ($request->filled('date_from')) {
                $query->whereDate('activity_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('activity_date', '<=', $request->date_to);
            }
        } else {
            // Default to today if no date filter is applied
            $query->whereDate('activity_date', today());
        }

        // Filter by driver
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        // Filter by has note
        if ($request->filled('has_note')) {
            if ($request->has_note == 'yes') {
                $query->whereNotNull('note')->where('note', '!=', '');
            } elseif ($request->has_note == 'no') {
                $query->where(function($q) {
                    $q->whereNull('note')->orWhere('note', '');
                });
            }
        }

        // Filter by has image
        if ($request->filled('has_image')) {
            if ($request->has_image == 'yes') {
                $query->whereNotNull('image');
            } elseif ($request->has_image == 'no') {
                $query->whereNull('image');
            }
        }

        // Get paginated results
        $activities = $query->latest('activity_date')
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        // Get all drivers for filter dropdown
        $drivers = Driver::orderBy('name')->get();

        // Calculate statistics
        $totalActivities = DailyActivity::count();
        $todayActivities = DailyActivity::whereDate('activity_date', today())->count();
        $thisMonthActivities = DailyActivity::whereMonth('activity_date', now()->month)
            ->whereYear('activity_date', now()->year)
            ->count();

        return view('daily-activities.index', compact(
            'activities',
            'drivers',
            'totalActivities',
            'todayActivities',
            'thisMonthActivities'
        ));
    }
}
