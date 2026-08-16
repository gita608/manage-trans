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
use Illuminate\Validation\ValidationException;

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
        $dutyDate = DriverCheckIn::normalizeDutyDate($validated['date']);
        $limitHours = DriverCheckIn::autoCheckoutHours();

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

        try {
            $result = DB::transaction(function () use ($driver, $validated, $checkInAt, $now, $dutyDate, $limitHours) {
                // Lock this driver's duty-day rows (and any active session) to avoid races
                DriverCheckIn::query()
                    ->where('driver_id', $driver->id)
                    ->where(function ($q) use ($dutyDate) {
                        $q->whereDate('check_in_date', $dutyDate)
                            ->orWhere('status', DriverCheckIn::STATUS_CHECKED_IN);
                    })
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

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
                    throw ValidationException::withMessages([
                        'vehicle_id' => ['You are already checked in with this vehicle.'],
                    ]);
                }

                $vehicleInUse = DriverCheckIn::query()
                    ->active()
                    ->where('vehicle_id', $validated['vehicle_id'])
                    ->when($active, fn ($q) => $q->where('id', '!=', $active->id))
                    ->exists();

                if ($vehicleInUse) {
                    throw ValidationException::withMessages([
                        'vehicle_id' => ['This vehicle is already checked in by another driver.'],
                    ]);
                }

                $sessionStart = $checkInAt->copy();
                $sessionDutyDate = $dutyDate;
                $switched = false;

                if ($active) {
                    // Close previous session at switch time (capped at its daily allowance due time)
                    $active->closeNow($now);
                    $switched = true;
                    $active = null;

                    // Use the real switch timestamp so seconds are preserved and sessions do not overlap
                    $sessionStart = $now->copy();
                    $sessionDutyDate = DriverCheckIn::normalizeDutyDate($sessionStart);
                }

                $remainingSeconds = DriverCheckIn::remainingSecondsForDriverDay(
                    (int) $driver->id,
                    $sessionDutyDate,
                    asOf: $now
                );

                if ($remainingSeconds <= 0) {
                    throw ValidationException::withMessages([
                        'date' => [
                            "You have reached the maximum {$limitHours} hours of check-in time for this duty day.",
                        ],
                    ]);
                }

                $checkIn = DriverCheckIn::create([
                    'driver_id' => $driver->id,
                    'vehicle_id' => $validated['vehicle_id'],
                    'check_in_date' => $sessionDutyDate,
                    'check_in_time' => $sessionStart->format('H:i:s'),
                    'check_in_at' => $sessionStart,
                    'start_km' => $validated['start_km'],
                    'status' => DriverCheckIn::STATUS_CHECKED_IN,
                ]);

                return [
                    'check_in' => $checkIn,
                    'switched' => $switched,
                ];
            });
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'Validation failed';

            $payload = [
                'success' => false,
                'message' => $message,
                'errors' => $message,
            ];

            if (str_contains($message, 'already checked in with this vehicle')) {
                $active = DriverCheckIn::query()
                    ->with('vehicle')
                    ->active()
                    ->where('driver_id', $driver->id)
                    ->latest('check_in_at')
                    ->first();

                if ($active) {
                    $payload['data'] = $this->formatCheckIn($active);
                }
            }

            return response()->json($payload, 422);
        }

        $checkIn = $result['check_in']->load('vehicle');

        return response()->json([
            'success' => true,
            'message' => $result['switched']
                ? 'Vehicle switched successfully.'
                : 'Checked in successfully.',
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
        $dutyDate = DriverCheckIn::normalizeDutyDate($checkIn->check_in_date);
        $usedSeconds = DriverCheckIn::usedSecondsForDriverDay(
            (int) $checkIn->driver_id,
            $dutyDate
        );
        $remainingSeconds = max(0, DriverCheckIn::dailyLimitSeconds() - $usedSeconds);

        return [
            'id' => $checkIn->id,
            'status' => $checkIn->status,
            'check_in_date' => $checkIn->check_in_date?->format('Y-m-d'),
            'check_in_time' => Carbon::parse($checkIn->check_in_time)->format('H:i'),
            'check_in_at' => $checkIn->check_in_at?->toIso8601String(),
            'auto_checkout_at' => $checkIn->autoCheckoutDueAt()->toIso8601String(),
            'start_km' => (float) $checkIn->start_km,
            'checked_out_at' => $checkIn->checked_out_at?->toIso8601String(),
            'daily_limit_seconds' => DriverCheckIn::dailyLimitSeconds(),
            'daily_used_seconds' => $usedSeconds,
            'daily_remaining_seconds' => $remainingSeconds,
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
