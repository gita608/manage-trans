<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Trip;
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
        $now = Carbon::now();

        // Get all trips for today
        $todayTrips = $driver->trips()
            ->whereDate('trip_date', $today)
            ->with('vessel')
            ->orderBy('pick_up_time', 'asc')
            ->get();

        // Calculate statistics
        $totalTrips = $todayTrips->count();
        $completedTrips = $todayTrips->where('status', Trip::STATUS_COMPLETED)->count();
        $pendingTrips = $todayTrips->whereIn('status', [Trip::STATUS_ASSIGNED, Trip::STATUS_IN_PROGRESS])->count();

        // Format date
        $dateFormatted = $today->format('l, j F Y'); // Sunday, 23 November 2025
        $dateShort = $today->format('Y-m-d');

        // Categorize trips
        $statuses = Trip::getStatuses();
        $pending = [];
        $completed = [];

        foreach ($todayTrips as $trip) {
            $tripDate = $trip->trip_date instanceof \Carbon\Carbon 
                ? $trip->trip_date 
                : Carbon::parse($trip->trip_date);

            // Format pickup time
            $pickupTime = null;
            $pickupTimeFormatted = null;
            $startTime = null;
            $startTimeFormatted = null;
            
            // Parse scheduled pickup time
            if ($trip->pick_up_time) {
                try {
                    $pickupTimeCarbon = Carbon::parse($trip->pick_up_time);
                    $pickupTime = $pickupTimeCarbon->format('H:i');
                    $pickupTimeFormatted = $pickupTimeCarbon->format('g:i A');
                } catch (\Exception $e) {
                    // If parsing fails, try to parse as time string
                    try {
                        $pickupTimeCarbon = Carbon::createFromFormat('H:i:s', $trip->pick_up_time);
                        $pickupTime = $pickupTimeCarbon->format('H:i');
                        $pickupTimeFormatted = $pickupTimeCarbon->format('g:i A');
                    } catch (\Exception $e2) {
                        $pickupTime = $trip->pick_up_time;
                        $pickupTimeFormatted = $trip->pick_up_time;
                    }
                }
            }
            
            if ($trip->status === Trip::STATUS_IN_PROGRESS) {
                // Use updated_at when status changed to in_progress
                $startTime = $trip->updated_at;
                $startTimeFormatted = $startTime->format('g:i A');
            } elseif ($trip->status === Trip::STATUS_COMPLETED) {
                $startTime = $trip->updated_at;
                $startTimeFormatted = $startTime->format('g:i A');
            }

            $tripData = [
                'id' => $trip->id,
                'crew_name' => $trip->crew_name,
                'crew_phone' => $trip->crew_phone ?? null,
                'crew_address' => $trip->crew_address ?? null,
                'status' => [
                    'value' => $trip->status,
                    'label' => $statuses[$trip->status] ?? ucfirst(str_replace('_', ' ', $trip->status)),
                    'is_in_progress' => $trip->status === Trip::STATUS_IN_PROGRESS,
                    'is_completed' => $trip->status === Trip::STATUS_COMPLETED,
                    'is_upcoming' => $trip->status === Trip::STATUS_ASSIGNED,
                ],
                'pickup' => [
                    'address' => $trip->from_location,
                    'time' => $pickupTime,
                    'time_formatted' => $pickupTimeFormatted,
                ],
                'drop' => [
                    'address' => $trip->to_location,
                    'status' => $trip->status === Trip::STATUS_COMPLETED 
                        ? 'Completed' 
                        : ($trip->status === Trip::STATUS_IN_PROGRESS ? 'In Progress' : 'Scheduled'),
                ],
                'start_time' => $startTime ? [
                    'time' => $startTime->format('H:i'),
                    'time_formatted' => $startTimeFormatted,
                    'datetime' => $startTime->format('Y-m-d H:i:s'),
                ] : null,
                'scheduled_time' => $pickupTime ? [
                    'time' => $pickupTime,
                    'time_formatted' => $pickupTimeFormatted,
                ] : null,
                'vessel' => $trip->vessel ? [
                    'id' => $trip->vessel->id,
                    'name' => $trip->vessel->name,
                ] : null,
            ];

            if ($trip->status === Trip::STATUS_COMPLETED) {
                $completed[] = $tripData;
            } else {
                $pending[] = $tripData;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => [
                    'date' => $dateShort,
                    'formatted' => $dateFormatted,
                    'day' => $today->format('l'), // Sunday
                    'day_number' => $today->format('j'), // 23
                    'month' => $today->format('F'), // November
                    'year' => $today->format('Y'), // 2025
                ],
                'summary' => [
                    'total' => $totalTrips,
                    'completed' => $completedTrips,
                    'pending' => $pendingTrips,
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        // Validate the trip ID parameter
        $validator = Validator::make(['id' => $id], [
            'id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $driver = $request->user();

        // Get trip that belongs to the authenticated driver
        $trip = $driver->trips()
            ->with('vessel')
            ->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found or you do not have access to this trip.',
            ], 404);
        }

        // Calculate duration if trip is in progress
        $duration = null;
        $durationInMinutes = null;
        if ($trip->status === Trip::STATUS_IN_PROGRESS) {
            // Use updated_at as the start time (when status changed to in_progress)
            // Or use created_at if updated_at is not reliable
            $startTime = $trip->updated_at;
            $durationInMinutes = Carbon::now()->diffInMinutes($startTime);
            $hours = floor($durationInMinutes / 60);
            $minutes = $durationInMinutes % 60;
            
            if ($hours > 0) {
                $duration = "{$hours}h {$minutes}m";
            } else {
                $duration = "{$minutes}m";
            }
        } elseif ($trip->status === Trip::STATUS_COMPLETED) {
            // Calculate duration from start to completion
            $startTime = $trip->created_at;
            $endTime = $trip->updated_at;
            $durationInMinutes = $endTime->diffInMinutes($startTime);
            $hours = floor($durationInMinutes / 60);
            $minutes = $durationInMinutes % 60;
            
            if ($hours > 0) {
                $duration = "{$hours}h {$minutes}m";
            } else {
                $duration = "{$minutes}m";
            }
        }

        // Format trip date
        $tripDate = $trip->trip_date instanceof \Carbon\Carbon 
            ? $trip->trip_date 
            : Carbon::parse($trip->trip_date);

        // Format pickup time
        $pickupTime = null;
        $pickupTimeFormatted = null;
        if ($trip->status === Trip::STATUS_IN_PROGRESS || $trip->status === Trip::STATUS_COMPLETED) {
            // Use updated_at when status changed to in_progress, or pick_up_time
            $pickupDateTime = $trip->status === Trip::STATUS_IN_PROGRESS 
                ? $trip->updated_at 
                : ($trip->created_at ?? Carbon::parse($trip->trip_date . ' ' . $trip->pick_up_time));
            
            $pickupTime = $pickupDateTime->format('H:i');
            $pickupTimeFormatted = $pickupDateTime->format('g:i A');
        }

        // Format drop time (only if completed)
        $dropTime = null;
        $dropTimeFormatted = null;
        if ($trip->status === Trip::STATUS_COMPLETED) {
            $dropTime = $trip->updated_at->format('H:i');
            $dropTimeFormatted = $trip->updated_at->format('g:i A');
        }

        // Build response
        $response = [
            'success' => true,
            'data' => [
                'id' => $trip->id,
                'status' => [
                    'value' => $trip->status,
                    'label' => ucfirst(str_replace('_', ' ', $trip->status)),
                    'is_ongoing' => $trip->status === Trip::STATUS_IN_PROGRESS,
                    'is_completed' => $trip->status === Trip::STATUS_COMPLETED,
                    'message' => $trip->status === Trip::STATUS_IN_PROGRESS 
                        ? 'Currently ongoing' 
                        : ($trip->status === Trip::STATUS_COMPLETED ? 'Completed' : 'Assigned'),
                ],
                'crew_information' => [
                    'name' => $trip->crew_name,
                    'phone' => $trip->crew_phone ?? null,
                    'address' => $trip->crew_address ?? null,
                ],
                'trip_date' => [
                    'date' => $tripDate->format('Y-m-d'),
                    'formatted' => $tripDate->format('l, F j, Y'), // Thursday, November 13, 2025
                    'day' => $tripDate->format('l'), // Thursday
                    'month' => $tripDate->format('F'), // November
                    'day_number' => $tripDate->format('j'), // 13
                    'year' => $tripDate->format('Y'), // 2025
                ],
                'locations' => [
                    'pickup' => [
                        'address' => $trip->from_location,
                        'status' => $trip->status === Trip::STATUS_IN_PROGRESS || $trip->status === Trip::STATUS_COMPLETED 
                            ? 'Started' 
                            : 'Scheduled',
                        'time' => $pickupTime,
                        'time_formatted' => $pickupTimeFormatted,
                        'date' => $tripDate->format('Y-m-d'),
                        'datetime' => $pickupTime ? $tripDate->format('Y-m-d') . ' ' . $pickupTime . ':00' : null,
                    ],
                    'drop' => [
                        'address' => $trip->to_location,
                        'status' => $trip->status === Trip::STATUS_COMPLETED 
                            ? 'Completed' 
                            : ($trip->status === Trip::STATUS_IN_PROGRESS ? 'In Progress' : 'Scheduled'),
                        'time' => $dropTime,
                        'time_formatted' => $dropTimeFormatted ?? 'N/A',
                        'date' => $tripDate->format('Y-m-d'),
                        'datetime' => $dropTime ? $tripDate->format('Y-m-d') . ' ' . $dropTime . ':00' : null,
                    ],
                ],
                'timeline' => [
                    'trip_started' => [
                        'date' => $tripDate->format('Y-m-d'),
                        'time' => $pickupTime,
                        'formatted' => $pickupTime 
                            ? $tripDate->format('M j, Y') . ' at ' . $pickupTimeFormatted 
                            : null,
                        'datetime' => $pickupTime ? $tripDate->format('Y-m-d') . ' ' . $pickupTime . ':00' : null,
                    ],
                    'duration_so_far' => $duration,
                    'duration_minutes' => $durationInMinutes,
                ],
                'vessel' => $trip->vessel ? [
                    'id' => $trip->vessel->id,
                    'name' => $trip->vessel->name,
                ] : null,
            ],
        ];

        return response()->json($response, 200);
    }

    /**
     * Update trip status (e.g., start trip, complete trip).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        // Validate the trip ID parameter
        $idValidator = Validator::make(['id' => $id], [
            'id' => ['required', 'integer', 'min:1'],
        ]);

        if ($idValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $idValidator->errors()->first(),
            ], 422);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:' . implode(',', [Trip::STATUS_ASSIGNED, Trip::STATUS_IN_PROGRESS, Trip::STATUS_COMPLETED])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $driver = $request->user();

        // Get trip that belongs to the authenticated driver
        $trip = $driver->trips()->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found or you do not have access to this trip.',
            ], 404);
        }

        $oldStatus = $trip->status;
        $trip->status = $request->status;
        // Disable automatic activity logging since we're manually creating the log
        $trip->saveQuietly();

        // Create manual activity log entry
        $statuses = Trip::getStatuses();
        $oldStatusLabel = $statuses[$oldStatus] ?? ucfirst(str_replace('_', ' ', $oldStatus));
        $newStatusLabel = $statuses[$trip->status] ?? ucfirst(str_replace('_', ' ', $trip->status));
        
        // Only log if status actually changed
        if ($oldStatus !== $trip->status) {
            $description = "Trip status changed from '{$oldStatusLabel}' to '{$newStatusLabel}' by driver '{$driver->name}'";
        } else {
            $description = "Trip status updated to '{$newStatusLabel}' by driver '{$driver->name}'";
        }

        ActivityLog::create([
            'loggable_type' => Trip::class,
            'loggable_id' => $trip->id,
            'action' => 'updated',
            'driver_id' => $driver->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $trip->status],
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip status updated successfully.',
            'data' => [
                'id' => $trip->id,
                'old_status' => $oldStatus,
                'new_status' => $trip->status,
                'status_label' => ucfirst(str_replace('_', ' ', $trip->status)),
            ],
        ], 200);
    }

    /**
     * Update crew details for a specific trip.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCrewDetails(Request $request, $id)
    {
        // Validate the trip ID parameter
        $idValidator = Validator::make(['id' => $id], [
            'id' => ['required', 'integer', 'min:1'],
        ]);

        if ($idValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $idValidator->errors()->first(),
            ], 422);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'crew_name' => ['sometimes', 'required', 'string', 'max:255'],
            'crew_phone' => ['nullable', 'integer', 'min:10'],
            'crew_address' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->first(),
            ], 422);
        }

        $driver = $request->user();

        // Get trip that belongs to the authenticated driver
        $trip = $driver->trips()->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found or you do not have access to this trip.',
            ], 404);
        }

        // Get old values before update
        $oldValues = [
            'crew_name' => $trip->crew_name,
            'crew_phone' => $trip->crew_phone,
            'crew_address' => $trip->crew_address,
        ];

        $validated = $validator->validated();

        // Update trip (disable automatic activity logging since we're manually creating the log)
        $trip->fill($validated);
        $trip->saveQuietly();

        // Refresh trip to get updated data
        $trip->refresh();

        // Create manual activity log entry
        $description = "Trip crew details updated by driver '{$driver->name}'";

        ActivityLog::create([
            'loggable_type' => Trip::class,
            'loggable_id' => $trip->id,
            'action' => 'updated',
            'driver_id' => $driver->id,
            'old_values' => $oldValues,
            'new_values' => $validated,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Crew details updated successfully.',
            'data' => [
                'id' => $trip->id,
                'crew_information' => [
                    'name' => $trip->crew_name,
                    'phone' => $trip->crew_phone ?? null,
                    'address' => $trip->crew_address ?? null,
                ],
            ],
        ], 200);
    }

    /**
     * Get list of trip issue types.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIssueTypes(Request $request)
    {
        $issueTypes = \App\Models\TripIssueType::orderBy('title')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'issue_types' => $issueTypes->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'title' => $type->title,
                    ];
                }),
            ],
        ], 200);
    }

    /**
     * Submit a trip issue.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitIssue(Request $request, $id)
    {
        // Validate the trip ID parameter
        $idValidator = Validator::make(['id' => $id], [
            'id' => ['required', 'integer', 'min:1'],
        ]);

        if ($idValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $idValidator->errors()->first(),
            ], 422);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'issue_type_id' => ['required', 'integer', 'exists:trip_issue_types,id'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->first(),
            ], 422);
        }

        $driver = $request->user();

        // Get trip that belongs to the authenticated driver
        $trip = $driver->trips()->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found or you do not have access to this trip.',
            ], 404);
        }

        // Get issue type for description
        $issueType = \App\Models\TripIssueType::find($request->issue_type_id);

        // Create trip issue
        $tripIssue = \App\Models\TripIssue::create([
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'issue_type_id' => $request->issue_type_id,
            'description' => $request->description,
        ]);

        // Load relationships
        $tripIssue->load('issueType');

        // Create manual activity log entry
        $description = "Trip issue '{$issueType->title}' reported by driver '{$driver->name}'" . 
            ($request->description ? ": {$request->description}" : '');

        ActivityLog::create([
            'loggable_type' => Trip::class,
            'loggable_id' => $trip->id,
            'action' => 'updated',
            'driver_id' => $driver->id,
            'old_values' => null,
            'new_values' => [
                'issue_type' => $issueType->title,
                'description' => $request->description,
            ],
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip issue submitted successfully.',
            'data' => [
                'id' => $tripIssue->id,
                'trip_id' => $tripIssue->trip_id,
                'issue_type' => [
                    'id' => $tripIssue->issueType->id,
                    'title' => $tripIssue->issueType->title,
                ],
                'description' => $tripIssue->description,
                'created_at' => $tripIssue->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Get all trip expense types.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getExpenseTypes(Request $request)
    {
        $expenseTypes = \App\Models\TripExpenseType::orderBy('title')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'expense_types' => $expenseTypes->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'title' => $type->title,
                    ];
                }),
            ],
        ], 200);
    }

    /**
     * Submit a trip expense.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitExpense(Request $request, $id)
    {
        // Validate the trip ID parameter
        $idValidator = Validator::make(['id' => $id], [
            'id' => ['required', 'integer', 'min:1'],
        ]);

        if ($idValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $idValidator->errors()->first(),
            ], 422);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'expense_type_id' => ['required', 'integer', 'exists:trip_expense_types,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'receipt' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'], // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->first(),
            ], 422);
        }

        $driver = $request->user();

        // Get trip that belongs to the authenticated driver
        $trip = $driver->trips()->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found or you do not have access to this trip.',
            ], 404);
        }

        // Get expense type for description
        $expenseType = \App\Models\TripExpenseType::find($request->expense_type_id);

        // Handle receipt upload if provided
        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        // Create trip expense
        $tripExpense = \App\Models\TripExpense::create([
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'expense_type_id' => $request->expense_type_id,
            'amount' => $request->amount,
            'receipt' => $receiptPath,
        ]);

        // Load relationships
        $tripExpense->load('expenseType');

        // Create manual activity log entry
        $description = "Trip expense '{$expenseType->title}' of {$request->amount} submitted by driver '{$driver->name}'";

        ActivityLog::create([
            'loggable_type' => Trip::class,
            'loggable_id' => $trip->id,
            'action' => 'updated',
            'driver_id' => $driver->id,
            'old_values' => null,
            'new_values' => [
                'expense_type' => $expenseType->title,
                'amount' => $request->amount,
                'receipt' => $receiptPath ? 'uploaded' : null,
            ],
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip expense submitted successfully.',
            'data' => [
                'id' => $tripExpense->id,
                'trip_id' => $tripExpense->trip_id,
                'expense_type' => [
                    'id' => $tripExpense->expenseType->id,
                    'title' => $tripExpense->expenseType->title,
                ],
                'amount' => (float) $tripExpense->amount,
                'receipt' => $tripExpense->receipt ? asset('storage/' . $tripExpense->receipt) : null,
                'created_at' => $tripExpense->created_at->toISOString(),
            ],
        ], 201);
    }
}

