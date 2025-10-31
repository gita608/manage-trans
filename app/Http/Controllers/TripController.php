<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vessel;
use Illuminate\Http\Request;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trips = Trip::with(['driver', 'vessel'])->latest('trip_date')->latest('pick_up_time')->paginate(15);
        return view('trips.index', compact('trips'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();
        return view('trips.create', compact('drivers', 'vessels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'crew_name' => ['required', 'string', 'max:255'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'vessel_id' => ['required', 'exists:vessels,id'],
            'trip_date' => ['required', 'date'],
            'pick_up_time' => ['required'],
            'from_location' => ['required', 'string', 'max:255'],
            'to_location' => ['required', 'string', 'max:255'],
            'crew_phone' => ['nullable', 'string', 'max:255'],
            'crew_address' => ['nullable', 'string'],
        ]);

        // Set default status to assigned
        $validated['status'] = Trip::STATUS_ASSIGNED;

        Trip::create($validated);

        return redirect()->route('trips.index')->with('success', 'Trip created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip)
    {
        $trip->load(['driver', 'vessel', 'activityLogs.user']);
        return view('trips.show', compact('trip'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Trip $trip)
    {
        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();
        return view('trips.edit', compact('trip', 'drivers', 'vessels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'crew_name' => ['required', 'string', 'max:255'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'vessel_id' => ['required', 'exists:vessels,id'],
            'trip_date' => ['required', 'date'],
            'pick_up_time' => ['required'],
            'from_location' => ['required', 'string', 'max:255'],
            'to_location' => ['required', 'string', 'max:255'],
            'crew_phone' => ['nullable', 'string', 'max:255'],
            'crew_address' => ['nullable', 'string'],
        ]);

        // Keep existing status, don't update it through the form
        $trip->update($validated);

        return redirect()->route('trips.index')->with('success', 'Trip updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
        $trip->delete();

        return redirect()->route('trips.index')->with('success', 'Trip deleted successfully!');
    }
}
