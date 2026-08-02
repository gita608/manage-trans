<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vehicles = Vehicle::query()->latest()->get();
        return view('vehicles.index', compact('vehicles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('vehicles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vehicles')->where(function ($query) use ($request) {
                    return $query->whereRaw('LOWER(number) = ?', [strtolower($request->number)]);
                }),
            ],
            'brand' => ['nullable', 'string', 'max:255'],
            'info' => ['nullable', 'string'],
        ], [
            'number.unique' => 'A vehicle with this number already exists.',
        ]);

        Vehicle::create($validated);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        return view('vehicles.show', compact('vehicle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle)
    {
        return view('vehicles.edit', compact('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vehicles')->where(function ($query) use ($request) {
                    return $query->whereRaw('LOWER(number) = ?', [strtolower($request->number)]);
                })->ignore($vehicle->id),
            ],
            'brand' => ['nullable', 'string', 'max:255'],
            'info' => ['nullable', 'string'],
        ], [
            'number.unique' => 'A vehicle with this number already exists.',
        ]);

        $vehicle->update($validated);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted successfully!');
    }
}
