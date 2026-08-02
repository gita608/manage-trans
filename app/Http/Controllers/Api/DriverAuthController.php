<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Driver;
use App\Models\DriverLocation;
use App\Models\TripCrew;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DriverAuthController extends Controller
{
    /**
     * Handle driver login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $driver = Driver::where('email', $request->email)->first();

        if (!$driver || !Hash::check($request->password, $driver->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        // Delete old tokens (optional - for single device login)
        // $driver->tokens()->delete();

        // Create token
        $token = $driver->createToken('driver-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'email' => $driver->email,
                'type' => $driver->type,
                'type_label' => $driver->getTypeLabel(),
                'license_number' => $driver->license_number,
                'contact' => $driver->contact,
                'age' => $driver->age,
                'photo' => $driver->photo,
            ],
        ], 200);
    }

    /**
     * Handle driver logout request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Delete current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ], 200);
    }

    /**
     * Get authenticated driver's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $driver = $request->user();

        // Get trip statistics (status is now on trips table)
        $totalTrips = $driver->trips()->count();
        // Completed trips: trips with completed status
        $completedTrips = $driver->trips()
            ->where('status', TripCrew::STATUS_COMPLETED)
            ->count();

        // Pending trips: trips with assigned or in_progress status
        $pendingTrips = $driver->trips()
            ->whereIn('status', [TripCrew::STATUS_ASSIGNED, TripCrew::STATUS_IN_PROGRESS])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'email' => $driver->email,
                'type' => $driver->type,
                'type_label' => $driver->getTypeLabel(),
                'license_number' => $driver->license_number,
                'contact' => $driver->contact,
                'total_kilometers' => $driver->total_kilometers,
                'age' => $driver->age,
                'photo' => $driver->photo ? asset('storage/' . $driver->photo) : null,
                'statistics' => [
                    'total_trips' => $totalTrips,
                    'completed_trips' => $completedTrips,
                    'pending_trips' => $pendingTrips,
                ],
                'created_at' => $driver->created_at->toISOString(),
                'updated_at' => $driver->updated_at->toISOString(),
            ],
        ], 200);
    }

    /**
     * Update authenticated driver's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $driver = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('drivers')->ignore($driver->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'license_number' => ['nullable', 'string', 'max:255', Rule::unique('drivers')->ignore($driver->id)],
            'contact' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->first(),
            ], 422);
        }

        $validated = $validator->validated();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($driver->photo) {
                Storage::disk('public')->delete($driver->photo);
            }
            $photoPath = $request->file('photo')->store('drivers', 'public');
            $validated['photo'] = $photoPath;
        }

        // Remove password from validated if empty (keep current password)
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Get old values before update
        $oldValues = $driver->getOriginal();
        $oldValues = array_intersect_key($oldValues, array_flip(array_keys($validated)));

        // Update driver (disable automatic activity logging since driver is not a User)
        $driver->fill($validated);
        $driver->saveQuietly();

        // Refresh driver to get updated data
        $driver->refresh();

        // Create manual activity log entry
        $description = "Driver '{$driver->name}' profile has been updated";

        ActivityLog::create([
            'loggable_type' => Driver::class,
            'loggable_id' => $driver->id,
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
            'message' => 'Profile updated successfully.',
            'data' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'email' => $driver->email,
                'type' => $driver->type,
                'type_label' => $driver->getTypeLabel(),
                'license_number' => $driver->license_number,
                'contact' => $driver->contact,
                'age' => $driver->age,
                'photo' => $driver->photo ? asset('storage/' . $driver->photo) : null,
                'created_at' => $driver->created_at->toISOString(),
                'updated_at' => $driver->updated_at->toISOString(),
            ],
        ], 200);
    }

    /**
     * Get all trips assigned to the authenticated driver.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trips(Request $request)
    {
        $driver = $request->user();

        // Get trips with vessel relationship
        $trips = $driver->trips()
            ->with('vessel')
            ->orderBy('trip_date', 'desc')
            ->orderBy('pick_up_time', 'desc')
            ->get()
            ->map(function ($trip) {
                return [
                    'id' => $trip->id,
                    'crew_name' => $trip->crews->first()->name ?? null,
                    'crew_phone' => $trip->crews->first()->phone ?? null,
                    'crew_phone_2' => $trip->crews->first()->phone_2 ?? null,
                    'crew_address' => $trip->crews->first()->address ?? null,
                    'crews' => $trip->crews->map(function($crew) {
                        return [
                            'name' => $crew->name,
                            'phone' => $crew->phone,
                            'phone_2' => $crew->phone_2,
                            'address' => $crew->address,
                        ];
                    }),
                    'trip_date' => $trip->trip_date->format('d/m/Y'),
                    'pick_up_time' => $trip->pick_up_time,
                    'from_location' => $trip->from_location,
                    'to_location' => $trip->to_location,
                    'status' => $trip->status,
                    'status_label' => ucfirst(str_replace('_', ' ', $trip->status)),
                    'vessel' => $trip->vessel ? [
                        'id' => $trip->vessel->id,
                        'name' => $trip->vessel->name,
                    ] : null,
                    'created_at' => $trip->created_at->format('d/m/Y h:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'trips' => $trips,
            'total' => $trips->count(),
        ], 200);
    }

    /**
     * Get app version information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function app_version()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'android_version' => getSetting('android_version', '1.0.0'),
                'ios_version' => getSetting('ios_version', '1.0.0'),
                'force_android_version' => getSetting('force_android_version', '1.0.0'),
                'force_ios_version' => getSetting('force_ios_version', '1.0.0'),
                'location_sync_intervel' => (int) getSetting('location_sync_intervel', 30),
                'check_in_auto_checkout_hours' => (int) getSetting('check_in_auto_checkout_hours', 12),
            ],
        ], 200);
    }

    /**
     * Update driver's current location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $driver = $request->user();

        // Update or create location record (one row per driver)
        DriverLocation::updateOrCreate(
            ['driver_id' => $driver->id],
            [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully.',
        ], 200);
    }

    /**
     * Update driver's notification token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateNotificationToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $driver = $request->user();
        $driver->notification_token = $request->notification_token;
        $driver->saveQuietly(); // Use saveQuietly to avoid triggering activity logs

        return response()->json([
            'success' => true,
            'message' => 'Notification token updated successfully.',
        ], 200);
    }
}
