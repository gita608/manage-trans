<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverCheckIn;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckInController extends Controller
{
    /**
     * Create a check-in, or switch vehicle if the driver already has an active session.
     */
    public function store(Request $request): JsonResponse
    {
        $driver = $request->user();

        $validator = Validator::make($request->all(), [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'start_km' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->first(),
            ], 422);
        }

        $validated = $validator->validated();
        $timezone = getAppTimezone();
        $now = Carbon::now($timezone);

        // Close any expired session for this driver before proceeding
        $active = DriverCheckIn::query()
            ->active()
            ->where('driver_id', $driver->id)
            ->latest('check_in_at')
            ->first();

        if ($active && $active->isExpired($now)) {
            $active->closeForAutoExpiry();
            $active = null;
        }

        if ($active && (int) $active->vehicle_id === (int) $validated['vehicle_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You are already checked in with this vehicle.',
                'data' => $this->formatCheckIn($active->load('vehicle')),
            ], 422);
        }

        $vehicleInUse = DriverCheckIn::query()
            ->active()
            ->where('vehicle_id', $validated['vehicle_id'])
            ->when($active, fn ($q) => $q->where('id', '!=', $active->id))
            ->exists();

        if ($vehicleInUse) {
            return response()->json([
                'success' => false,
                'message' => 'This vehicle is already checked in by another driver.',
            ], 422);
        }

        if (!Vehicle::query()->whereKey($validated['vehicle_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found.',
            ], 404);
        }

        $checkInAt = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['date'] . ' ' . $validated['time'],
            $timezone
        );

        $checkIn = DB::transaction(function () use ($driver, $validated, $checkInAt, $active, $now) {
            if ($active) {
                $active->closeNow($now);
            }

            return DriverCheckIn::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $validated['vehicle_id'],
                'check_in_date' => $validated['date'],
                'check_in_time' => $validated['time'],
                'check_in_at' => $checkInAt,
                'start_km' => $validated['start_km'],
                'status' => DriverCheckIn::STATUS_CHECKED_IN,
            ]);
        });

        $checkIn->load('vehicle');

        $message = $active
            ? 'Vehicle switched successfully.'
            : 'Checked in successfully.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $this->formatCheckIn($checkIn),
        ], 201);
    }

    /**
     * Return the driver's current active check-in (lazy auto-checkout if expired).
     */
    public function current(Request $request): JsonResponse
    {
        $driver = $request->user();
        $now = Carbon::now(getAppTimezone());

        $active = DriverCheckIn::query()
            ->with('vehicle')
            ->active()
            ->where('driver_id', $driver->id)
            ->latest('check_in_at')
            ->first();

        if ($active && $active->isExpired($now)) {
            $active->closeForAutoExpiry();
            $active = null;
        }

        return response()->json([
            'success' => true,
            'message' => $active ? 'Current check-in retrieved.' : 'No active check-in.',
            'data' => $active ? $this->formatCheckIn($active) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCheckIn(DriverCheckIn $checkIn): array
    {
        $vehicle = $checkIn->vehicle;

        return [
            'id' => $checkIn->id,
            'status' => $checkIn->status,
            'check_in_date' => $checkIn->check_in_date?->format('Y-m-d'),
            'check_in_time' => Carbon::parse($checkIn->check_in_time)->format('H:i'),
            'check_in_at' => $checkIn->check_in_at?->toIso8601String(),
            'auto_checkout_at' => $checkIn->autoCheckoutDueAt()->toIso8601String(),
            'start_km' => (float) $checkIn->start_km,
            'checked_out_at' => $checkIn->checked_out_at?->toIso8601String(),
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'brand' => $vehicle->brand,
                'number' => $vehicle->number,
                'info' => $vehicle->info,
            ] : null,
        ];
    }
}
