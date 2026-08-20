<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DailyActivity;
use App\Models\Notification;
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
        
        // Get all trips for today assigned to this driver with their crews (eager loaded to avoid N+1)
        $trips = Trip::where('driver_id', $driver->id)
            ->whereDate('trip_date', $today)
            ->with(['partner', 'crews' => function($q) {
                $q->orderBy('pick_up_time', 'asc');
            }])
            ->get();

        // Format date once (reused multiple times)
        $dateFormatted = $today->format('l, j F Y');
        $dateShort = $today->format('Y-m-d');
        $dayName = $today->format('l');
        $dayNumber = $today->format('j');
        $month = $today->format('F');
        $year = $today->format('Y');

        // Helper to format a trip
        $mapTrip = function($trip) {
            $tripDate = $trip->trip_date instanceof Carbon ? $trip->trip_date : Carbon::parse($trip->trip_date);
            return [
                'trip_id' => $trip->id,
                'trip_reference' => $trip->trip_reference,
                'trip_title' => $trip->title,
                'partner_name' => $trip->partner?->title,
                'trip_date' => $tripDate->format('Y-m-d'),
                'trip_date_formatted' => $tripDate->format('l, F j, Y'),
                'crews' => $trip->crews->map(function($crew) {
                    return [
                        'id' => $crew->id,
                        'name' => $crew->name,
                        'phone' => $crew->phone,
                        'phone_2' => $crew->phone_2,
                        'address' => $crew->address,
                    ];
                })->values(),
            ];
        };

        // Partition trips by status into completed, ongoing, pending, cancelled
        $completedTrips = collect();
        $ongoingTrips = collect();
        $pendingTrips = collect();
        $cancelledTrips = collect();

        foreach ($trips as $trip) {
            if ($trip->status === TripCrew::STATUS_COMPLETED) {
                $completedTrips->push($mapTrip($trip));
            } elseif ($trip->status === TripCrew::STATUS_IN_PROGRESS) {
                $ongoingTrips->push($mapTrip($trip));
            } elseif ($trip->status === TripCrew::STATUS_CANCELLED) {
                $cancelledTrips->push($mapTrip($trip));
            } else {
                $pendingTrips->push($mapTrip($trip));
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => [
                    'date' => $dateShort,
                    'formatted' => $dateFormatted,
                    'day' => $dayName,
                    'day_number' => $dayNumber,
                    'month' => $month,
                    'year' => $year,
                ],
                'summary' => [
                    'total' => $trips->count(),
                    'completed' => $completedTrips->count(),
                    'ongoing' => $ongoingTrips->count(),
                    'pending' => $pendingTrips->count(),
                    'cancelled' => $cancelledTrips->count(),
                ],
                'tasks' => [
                    'pending' => $pendingTrips->values()->all(),
                    'ongoing' => $ongoingTrips->values()->all(),
                    'completed' => $completedTrips->values()->all(),
                    'cancelled' => $cancelledTrips->values()->all(),
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
            ->whereKey($id)
            ->with(['partner', 'crews.vessel', 'tripIssues.issueType', 'tripIssues.driver', 'tripExpenses.expenseType', 'tripExpenses.driver'])
            ->first();

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
                'crew_information' => [
                    'name' => $crew->name,
                    'phone' => $crew->phone,
                    'phone_2' => $crew->phone_2,
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
                'sub_remark' => $crew->sub_remark,
                'flight_number' => $crew->flight_number,
            ];
        }

        // Format trip issues
        $issues = [];
        $tripIssues = $trip->tripIssues()->with(['issueType', 'driver'])->get();
        foreach ($tripIssues as $issue) {
            $createdAt = $issue->created_at ? Carbon::parse($issue->created_at) : null;
            $issues[] = [
                'id' => $issue->id,
                'issue_type' => [
                    'id' => $issue->issueType->id ?? null,
                    'title' => $issue->issueType->title ?? 'Unknown',
                ],
                'description' => $issue->description,
                'created_at' => $createdAt ? [
                    'date' => $createdAt->format('Y-m-d'),
                    'formatted' => $createdAt->format('M d, Y h:i A'),
                    'timestamp' => $createdAt->timestamp,
                ] : null,
            ];
        }

        // Format trip expenses
        $expenses = [];
        $totalExpenseAmount = 0;
        $totalExpenseHours = 0;
        $tripExpenses = $trip->tripExpenses()->with(['expenseType', 'driver'])->get();
        foreach ($tripExpenses as $expense) {
            $totalExpenseAmount += (float) $expense->amount;
            $totalExpenseHours += (float) ($expense->hours ?? 0);
            $createdAt = $expense->created_at ? Carbon::parse($expense->created_at) : null;
            $expenses[] = [
                'id' => $expense->id,
                'expense_type' => [
                    'id' => $expense->expenseType->id ?? null,
                    'title' => $expense->expenseType->title ?? 'Unknown',
                    'input_types' => $expense->expenseType->input_types ?? ['amount', 'image'],
                ],
                'amount' => (float) $expense->amount,
                'hours' => $expense->hours !== null ? (float) $expense->hours : null,
                'description' => $expense->description,
                'receipt' => $expense->receipt ? asset('storage/' . $expense->receipt) : null,
                'created_at' => $createdAt ? [
                    'date' => $createdAt->format('Y-m-d'),
                    'formatted' => $createdAt->format('M d, Y h:i A'),
                    'timestamp' => $createdAt->timestamp,
                ] : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $trip->id,
                'trip_reference' => $trip->trip_reference,
                'trip_title' => $trip->title,
                'partner_name' => $trip->partner?->title,
                'trip_date' => [
                    'date' => $tripDate->format('Y-m-d'),
                    'formatted' => $tripDate->format('l, F j, Y'),
                ],
                'status' => [
                    'value' => $trip->status,
                    'label' => ucfirst(str_replace('_', ' ', $trip->status)),
                    'is_ongoing' => $trip->status === 'in_progress',
                    'is_completed' => $trip->status === 'completed',
                    'is_cancelled' => $trip->status === 'cancelled',
                ],
                'crews' => $crews,
                'issues' => [
                    'data' => $issues,
                    'total' => count($issues),
                ],
                'expenses' => [
                    'data' => $expenses,
                    'total' => count($expenses),
                    'total_amount' => (float) $totalExpenseAmount,
                    'total_hours' => (float) $totalExpenseHours,
                ],
            ],
        ], 200);
    }

    /**
     * Update trip status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id Trip ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $driver = $request->user();

        // Verify trip belongs to the driver
        $trip = Trip::where('driver_id', $driver->id)
            ->whereKey($id)
            ->first();

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found or you do not have access to it.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:assigned,in_progress,completed,cancelled'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $oldStatus = $trip->status;
        $trip->status = $request->status;
        // Use saveQuietly() to prevent automatic logging from LogsActivity trait
        // We'll log manually with driver_id instead
        $trip->saveQuietly();

        // Log activity on the Trip with driver_id (not user_id)
        if ($oldStatus !== $trip->status) {
            ActivityLog::create([
                'loggable_type' => Trip::class,
                'loggable_id' => $trip->id,
                'action' => 'updated',
                'driver_id' => $driver->id,
                'user_id' => null,
                'old_values' => [
                    'status' => $oldStatus,
                ],
                'new_values' => [
                    'status' => $trip->status,
                ],
                'description' => 'Trip status changed',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Trip status updated successfully.',
            'data' => [
                'trip_id' => $trip->id,
                'status' => $trip->status,
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
        
        $trip = Trip::where('driver_id', $driver->id)->find($id);

        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Trip not found.'], 404);
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
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'issue_type_id' => $request->issue_type_id,
            'description' => "Trip #{$trip->id}: " . $request->description,
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
        
        // $id is Trip ID or TripCrew ID
        $trip = Trip::where('driver_id', $driver->id)->find($id);   

        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Trip not found.'], 404);
        }

        // Validate expense type first
        $typeValidator = Validator::make($request->all(), [
            'expense_type_id' => ['required', 'exists:trip_expense_types,id'],
        ]);

        if ($typeValidator->fails()) {
            return response()->json(['success' => false, 'message' => $typeValidator->errors()->first()], 422);
        }

        $expenseType = \App\Models\TripExpenseType::find($request->expense_type_id);
        $inputTypes = $expenseType->input_types ?? ['number', 'image'];

        // Build dynamic validation rules based on configured input_types
        $rules = [
            'expense_type_id' => ['required', 'exists:trip_expense_types,id'],
        ];

        if (in_array('amount', $inputTypes) || in_array('number', $inputTypes)) {
            $rules['amount'] = ['required', 'numeric', 'min:0'];
        } else {
            $rules['amount'] = ['nullable', 'numeric', 'min:0'];
        }

        if (in_array('hours', $inputTypes)) {
            $rules['hours'] = ['required', 'numeric', 'min:0'];
        } else {
            $rules['hours'] = ['nullable', 'numeric', 'min:0'];
        }

        if (in_array('text', $inputTypes)) {
            $rules['description'] = ['required', 'string', 'max:1000'];
        } else {
            $rules['description'] = ['nullable', 'string', 'max:1000'];
        }

        if (in_array('image', $inputTypes)) {
            $rules['receipt'] = ['required', 'image', 'max:5120'];
        } else {
            $rules['receipt'] = ['nullable', 'image', 'max:5120'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        \App\Models\TripExpense::create([
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'expense_type_id' => $request->expense_type_id,
            'amount' => $request->amount ?? 0,
            'hours' => $request->hours,
            'description' => $request->description,
            'receipt' => $receiptPath,
        ]);

        return response()->json(['success' => true, 'message' => 'Expense submitted.'], 201);
    }

    /**
     * Update crew details for a specific trip crew.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id Trip ID
     * @param  int  $crew_id TripCrew ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCrewDetails(Request $request, $id, $crew_id)
    {
        $driver = $request->user();

        // Verify trip belongs to the driver
        $trip = Trip::where('driver_id', $driver->id)->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found or you do not have access to it.',
            ], 404);
        }

        // Find the crew member and verify it belongs to this trip
        $crew = TripCrew::where('trip_id', $trip->id)
            ->with('vessel')
            ->find($crew_id);

        if (!$crew) {
            return response()->json([
                'success' => false,
                'message' => 'Crew member not found or does not belong to this trip.',
            ], 404);
        }

        // Map request fields (handle both crew_* and direct field names)
        $requestData = $request->all();
        
        // Map crew_* prefixed fields to database fields
        if (isset($requestData['crew_name'])) {
            $requestData['name'] = $requestData['crew_name'];
        }
        if (isset($requestData['crew_phone'])) {
            $requestData['phone'] = $requestData['crew_phone'];
        }
        if (isset($requestData['crew_address'])) {
            $requestData['address'] = $requestData['crew_address'];
        }

        // Validate the request
        $validator = Validator::make($requestData, [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'vessel_id' => ['sometimes', 'nullable', 'exists:vessels,id'],
            'pick_up_time' => ['sometimes', 'nullable'],
            'from_location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'to_location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'flight_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'sub_remark' => ['nullable', 'string'],
            // Note: status is now on trips table, not trip_crews
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->first(),
            ], 422);
        }

        $oldValues = $crew->getOriginal();
        $validated = $validator->validated();

        // Remove crew_* prefixed fields if they exist (we've already mapped them)
        unset($validated['crew_name'], $validated['crew_phone'], $validated['crew_address']);

        // Update crew details
        $crew->fill($validated);
        $crew->save();

        // Log activity if there were changes
        $changes = array_diff_assoc($crew->getAttributes(), $oldValues);
        if (!empty($changes)) {
            ActivityLog::create([
                'loggable_type' => Trip::class,
                'loggable_id' => $trip->id,
                'action' => 'updated',
                'driver_id' => $driver->id,
                'description' => "Crew #{$crew->id} details updated by driver {$driver->name}",
            ]);
        }

        // Format pickup time for response
        $pickupTime = null;
        $pickupTimeFormatted = null;
        if ($crew->pick_up_time) {
            try {
                $pickupTimeCarbon = Carbon::parse($crew->pick_up_time);
                $pickupTime = $pickupTimeCarbon->format('H:i');
                $pickupTimeFormatted = $pickupTimeCarbon->format('g:i A');
            } catch (\Exception $e) {
                $pickupTime = $crew->pick_up_time;
                $pickupTimeFormatted = $crew->pick_up_time;
            }
        }

        // Format trip date
        $tripDate = $trip->trip_date instanceof \Carbon\Carbon 
            ? $trip->trip_date 
            : Carbon::parse($trip->trip_date);

        return response()->json([
            'success' => true,
            'message' => 'Crew details updated successfully.',
            'data' => [
                'id' => $crew->id,
                'trip_id' => $crew->trip_id,
                'crew_information' => [
                    'name' => $crew->name,
                    'phone' => $crew->phone,
                    'phone_2' => $crew->phone_2,
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
                'sub_remark' => $crew->sub_remark,
                'flight_number' => $crew->flight_number,
            ],
        ], 200);
    }

    /**
     * Get today's daily activities for the authenticated driver.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dailyActivity(Request $request)
    {
        $driver = $request->user();
        $today = Carbon::today();
        
        // Get all daily activities for today where this driver is the owner
        $activities = DailyActivity::where('driver_id', $driver->id)
            ->whereDate('activity_date', $today)
            ->with('driver')
            ->orderBy('created_at', 'desc')
            ->get();

        // Format activities for response
        $formattedActivities = $activities->map(function($activity) {
            $createdAt = $activity->created_at ? Carbon::parse($activity->created_at) : null;
            $activityDate = $activity->activity_date ? Carbon::parse($activity->activity_date) : null;
            
            return [
                'id' => $activity->id,
                'image' => $activity->image ? asset('storage/' . $activity->image) : null,
                'note' => $activity->note,
                'kilometers_driven' => $activity->kilometers_driven,
                'activity_date' => $activityDate ? [
                    'date' => $activityDate->format('Y-m-d'),
                    'formatted' => $activityDate->format('l, F j, Y'),
                ] : null,
                'created_at' => $createdAt ? [
                    'date' => $createdAt->format('Y-m-d'),
                    'time' => $createdAt->format('H:i:s'),
                    'formatted' => $createdAt->format('M d, Y h:i A'),
                    'timestamp' => $createdAt->timestamp,
                ] : null,
            ];
        })->values();

        // Format date information
        $dateFormatted = $today->format('l, j F Y');
        $dateShort = $today->format('Y-m-d');
        $dayName = $today->format('l');
        $dayNumber = $today->format('j');
        $month = $today->format('F');
        $year = $today->format('Y');

        return response()->json([
            'success' => true,
            'data' => [
                'date' => [
                    'date' => $dateShort,
                    'formatted' => $dateFormatted,
                    'day' => $dayName,
                    'day_number' => $dayNumber,
                    'month' => $month,
                    'year' => $year,
                ],
                'summary' => [
                    'total' => $activities->count(),
                    'total_kilometers' => $driver->total_kilometers,
                ],
                'activities' => $formattedActivities->all(),
            ],
        ], 200);
    }

    /**
     * Store a new daily activity for the authenticated driver.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeDailyActivity(Request $request)
    {
        $driver = $request->user();
        $today = Carbon::today();

        // $validator = Validator::make($request->all(), [
            // 'image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'], // Max 5MB
            // 'note' => ['nullable', 'string', 'max:1000'],
            // 'kilometers_driven' => ['nullable', 'numeric', 'min:0', 'max:2000'],
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Validation failed',
        //         'errors' => $validator->errors()->first(),
        //     ], 422);
        // }

        // Store the image
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('daily-activities', 'public');
        }

        // Create daily activity
        $activity = DailyActivity::create([
            'driver_id' => $driver->id,
            'image' => $imagePath,
            'note' => $request->note,
            'activity_date' => $today,
            'kilometers_driven' => $request->kilometers_driven,
        ]);

        // Update kilometers if provided
        $newTotal = $driver->total_kilometers;
        if ($request->filled('kilometers_driven') && $request->kilometers_driven > 0) {
            // Calculate old total before adding new km
            $oldTotal = $driver->total_kilometers;
            $newTotal = $oldTotal + $request->kilometers_driven;
            
            // Check if crossed a 10,000 km milestone
            $oldMilestone = floor($oldTotal / 10000);
            $newMilestone = floor($newTotal / 10000);
            
            if ($newMilestone > $oldMilestone) {
                // Crossed a milestone! Send notification
                $milestoneKm = $newMilestone * 10000;
                
                Notification::create([
                    'driver_id' => $driver->id,
                    'title' => '🔧 Vehicle Service Required',
                    'message' => "Your vehicle has reached {$milestoneKm} km! Please schedule a service soon.",
                    'type' => 'service_reminder',
                    'is_read' => false,
                ]);

                // Send push notification if token exists
                if ($driver->notification_token) {
                    $firebaseService = app(\App\Services\FirebaseNotificationService::class);
                    $firebaseService->sendPushNotification(
                        $driver->notification_token,
                        '🔧 Vehicle Service Required',
                        "Your vehicle has reached {$milestoneKm} km! Time for service.",
                        null,
                        ['type' => 'service_reminder', 'kilometers' => $newTotal]
                    );
                }
            }
            
            // Update driver's total kilometers
            $driver->total_kilometers = $newTotal;
            $driver->save();
        }

        // Format the response
        $createdAt = $activity->created_at ? Carbon::parse($activity->created_at) : null;
        $activityDate = $activity->activity_date ? Carbon::parse($activity->activity_date) : null;

        return response()->json([
            'success' => true,
            'message' => 'Daily activity created successfully.',
            'data' => [
                'id' => $activity->id,
                'image' => $activity->image ? asset('storage/' . $activity->image) : null,
                'note' => $activity->note,
                'kilometers_driven' => $activity->kilometers_driven,
                'activity_date' => $activityDate ? [
                    'date' => $activityDate->format('Y-m-d'),
                    'formatted' => $activityDate->format('l, F j, Y'),
                ] : null,
                'created_at' => $createdAt ? [
                    'date' => $createdAt->format('Y-m-d'),
                    'time' => $createdAt->format('H:i:s'),
                    'formatted' => $createdAt->format('M d, Y h:i A'),
                    'timestamp' => $createdAt->timestamp,
                ] : null,
            ],
            'total_kilometers' => $newTotal,
        ], 201);
    }
}

