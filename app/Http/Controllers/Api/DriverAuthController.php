<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

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
                'vehicle_info' => $driver->vehicle_info,
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
                    'crew_name' => $trip->crew_name,
                    'crew_phone' => $trip->crew_phone,
                    'crew_address' => $trip->crew_address,
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
}
