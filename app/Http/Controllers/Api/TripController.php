<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Trip;
use App\Models\TripCrew;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TripController extends Controller
{
    /**
     * Get today's schedule for the authenticated driver.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function schedule(Request $request)
    {
        $driver = $request->user();
        $today = Carbon::today();
        
        // Get all trip crews (jobs) for today's trips assigned to this driver
        $jobs = TripCrew::whereHas('trip', function($q) use ($driver, $today) {
                $q->where('driver_id', $driver->id)
                  ->whereDate('trip_date', $today);
            })
            ->with(['trip', 'vessel'])
            ->orderBy('pick_up_time', 'asc')
            ->get();

        // Calculate statistics
        $totalJobs = $jobs->count();
        $completedJobs = $jobs->where('status', 'completed')->count(); // Assuming status is on TripCrew now
        $pendingJobs = $jobs->whereIn('status', ['assigned', 'in_progress'])->count();

        // Format date
        $dateFormatted = $today->format('l, j F Y');
        $dateShort = $today->format('Y-m-d');

        // Categorize jobs
        $pending = [];
        $completed = [];

        foreach ($jobs as $job) {
            // Format pickup time
            $pickupTime = null;
            $pickupTimeFormatted = null;
            
            if ($job->pick_up_time) {
                try {
                    $pickupTimeCarbon = Carbon::parse($job->pick_up_time);
                    $pickupTime = $pickupTimeCarbon->format('H:i');
                    $pickupTimeFormatted = $pickupTimeCarbon->format('g:i A');
                } catch (\Exception $e) {
                    $pickupTime = $job->pick_up_time;
                    $pickupTimeFormatted = $job->pick_up_time;
                }
            }

            $jobData = [
                'id' => $job->id, // This is TripCrew ID
                'trip_id' => $job->trip_id,
                'crew_name' => $job->name,
                'crew_phone' => $job->phone,
                'crew_address' => $job->address,
                'status' => [
                    'value' => $job->status,
                    'label' => ucfirst(str_replace('_', ' ', $job->status)),
                    'is_in_progress' => $job->status === 'in_progress',
                    'is_completed' => $job->status === 'completed',
                    'is_upcoming' => $job->status === 'assigned',
                ],
                'pickup' => [
                    'address' => $job->from_location,
                    'time' => $pickupTime,
                    'time_formatted' => $pickupTimeFormatted,
                ],
                'drop' => [
                    'address' => $job->to_location,
                    'status' => $job->status === 'completed' 
                        ? 'Completed' 
                        : ($job->status === 'in_progress' ? 'In Progress' : 'Scheduled'),
                ],
                'vessel' => $job->vessel ? [
                    'id' => $job->vessel->id,
                    'name' => $job->vessel->name,
                ] : null,
                'remarks' => $job->remarks,
            ];

            if ($job->status === 'completed') {
                $completed[] = $jobData;
            } else {
                $pending[] = $jobData;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => [
                    'date' => $dateShort,
                    'formatted' => $dateFormatted,
                    'day' => $today->format('l'),
                    'day_number' => $today->format('j'),
                    'month' => $today->format('F'),
                    'year' => $today->format('Y'),
                ],
                'summary' => [
                    'total' => $totalJobs,
                    'completed' => $completedJobs,
                    'pending' => $pendingJobs,
                ],
                'tasks' => [
                    'pending' => $pending,
                    'completed' => $completed,
                ],
            ],
        ], 200);
    }

    /**
     * Get detailed information about a specific trip.
     * Returns all crews for the trip.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id Trip ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $driver = $request->user();

        // Find trip assigned to this driver
        $trip = Trip::where('driver_id', $driver->id)
            ->with(['crews.vessel'])
            ->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found or you do not have access to it.',
            ], 404);
        }

        // Format trip date
        $tripDate = $trip->trip_date instanceof \Carbon\Carbon 
            ? $trip->trip_date 
            : Carbon::parse($trip->trip_date);

        // Format all crews for this trip
        $crews = [];
        foreach ($trip->crews as $crew) {
            $pickupTime = null;
            $pickupTimeFormatted = null;
            if ($crew->pick_up_time) {
                $pickupTimeCarbon = Carbon::parse($crew->pick_up_time);
                $pickupTime = $pickupTimeCarbon->format('H:i');
                $pickupTimeFormatted = $pickupTimeCarbon->format('g:i A');
            }

            $crews[] = [
                'id' => $crew->id,
                'trip_id' => $crew->trip_id,
                'status' => [
                    'value' => $crew->status,
                    'label' => ucfirst(str_replace('_', ' ', $crew->status)),
                    'is_ongoing' => $crew->status === 'in_progress',
                    'is_completed' => $crew->status === 'completed',
                ],
                'crew_information' => [
                    'name' => $crew->name,
                    'phone' => $crew->phone,
                    'address' => $crew->address,
                ],
                'trip_date' => [
                    'date' => $tripDate->format('Y-m-d'),
                    'formatted' => $tripDate->format('l, F j, Y'),
                ],
                'locations' => [
                    'pickup' => [
                        'address' => $crew->from_location,
                        'time' => $pickupTime,
                        'time_formatted' => $pickupTimeFormatted,
                    ],
                    'drop' => [
                        'address' => $crew->to_location,
                    ],
                ],
                'vessel' => $crew->vessel ? [
                    'id' => $crew->vessel->id,
                    'name' => $crew->vessel->name,
                ] : null,
                'remarks' => $crew->remarks,
                'flight_number' => $crew->flight_number,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $trip->id,
                'trip_title' => $trip->title,
                'trip_date' => [
                    'date' => $tripDate->format('Y-m-d'),
                    'formatted' => $tripDate->format('l, F j, Y'),
                ],
                'crews' => $crews,
            ],
        ], 200);
    }

    /**
     * Update job status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $driver = $request->user();

        $job = TripCrew::whereHas('trip', function($q) use ($driver) {
                $q->where('driver_id', $driver->id);
            })->find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:assigned,in_progress,completed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $oldStatus = $job->status;
        $job->status = $request->status;
        $job->save();

        // Log activity on the parent Trip
        if ($oldStatus !== $job->status) {
            ActivityLog::create([
                'loggable_type' => Trip::class,
                'loggable_id' => $job->trip_id,
                'action' => 'updated',
                'driver_id' => $driver->id,
                'description' => "Job #{$job->id} status changed from {$oldStatus} to {$job->status} by driver {$driver->name}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => [
                'id' => $job->id,
                'status' => $job->status,
            ],
        ]);
    }

    // ... (Keep other methods like getIssueTypes, getExpenseTypes but update submit methods to use TripCrew ID to find Trip)

    public function getIssueTypes(Request $request)
    {
        $issueTypes = \App\Models\TripIssueType::orderBy('title')->get();
        return response()->json(['success' => true, 'data' => ['issue_types' => $issueTypes]]);
    }

    public function submitIssue(Request $request, $id)
    {
        $driver = $request->user();
        
        // $id is TripCrew ID (Job ID)
        $job = TripCrew::whereHas('trip', function($q) use ($driver) {
            $q->where('driver_id', $driver->id);
        })->find($id);

        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Job not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'issue_type_id' => ['required', 'exists:trip_issue_types,id'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // Create issue linked to the parent Trip
        $issue = \App\Models\TripIssue::create([
            'trip_id' => $job->trip_id,
            'driver_id' => $driver->id,
            'issue_type_id' => $request->issue_type_id,
            'description' => "Job #{$job->id}: " . $request->description,
        ]);

        return response()->json(['success' => true, 'message' => 'Issue submitted.'], 201);
    }

    public function getExpenseTypes(Request $request)
    {
        $expenseTypes = \App\Models\TripExpenseType::orderBy('title')->get();
        return response()->json(['success' => true, 'data' => ['expense_types' => $expenseTypes]]);
    }

    public function submitExpense(Request $request, $id)
    {
        $driver = $request->user();
        
        // $id is TripCrew ID (Job ID)
        $job = TripCrew::whereHas('trip', function($q) use ($driver) {
            $q->where('driver_id', $driver->id);
        })->find($id);

        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Job not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'expense_type_id' => ['required', 'exists:trip_expense_types,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'receipt' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        \App\Models\TripExpense::create([
            'trip_id' => $job->trip_id,
            'driver_id' => $driver->id,
            'expense_type_id' => $request->expense_type_id,
            'amount' => $request->amount,
            'receipt' => $receiptPath,
        ]);

        return response()->json(['success' => true, 'message' => 'Expense submitted.'], 201);
    }
}

