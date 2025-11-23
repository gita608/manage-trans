<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TripController extends Controller
{
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
        $trip->save();

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
}

