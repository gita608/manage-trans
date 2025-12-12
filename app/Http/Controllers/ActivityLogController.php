<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        // Filter by user if requested
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by model type (loggable_type)
        if ($request->filled('model_type')) {
            $query->where('loggable_type', $request->model_type);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // If no date filters provided, show only today's data
        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            $query->whereDate('created_at', today());
        }

        $logs = $query->latest()->get();

        // Get unique model types for filter
        $modelTypes = ActivityLog::select('loggable_type')
            ->distinct()
            ->orderBy('loggable_type')
            ->pluck('loggable_type')
            ->map(function ($type) {
                return $type;
            })
            ->unique()
            ->values();

        // Get all users for filter
        $users = \App\Models\User::orderBy('name')->get();

        return view('activity-logs.index', compact('logs', 'modelTypes', 'users'));
    }
}
