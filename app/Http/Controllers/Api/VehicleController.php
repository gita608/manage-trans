<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    /**
     * List all vehicles for the driver app.
     */
    public function index(): JsonResponse
    {
        $vehicles = Vehicle::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'brand' => $vehicle->brand,
                'number' => $vehicle->number,
                'info' => $vehicle->info,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
            'message' => 'Vehicles retrieved successfully.',
        ]);
    }
}
