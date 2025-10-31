<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $drivers = Driver::latest()->paginate(10);
        return view('drivers.index', compact('drivers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('drivers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:drivers'],
            'license_number' => ['required', 'string', 'max:255', 'unique:drivers'],
            'contact' => ['required', 'string', 'max:255'],
            'vehicle_info' => ['required', 'string'],
            'age' => ['required', 'integer', 'min:18', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('drivers', 'public');
            $validated['photo'] = $photoPath;
        }

        Driver::create($validated);

        return redirect()->route('drivers.index')->with('success', 'Driver created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Driver $driver)
    {
        return view('drivers.show', compact('driver'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Driver $driver)
    {
        return view('drivers.edit', compact('driver'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('drivers')->ignore($driver->id)],
            'license_number' => ['required', 'string', 'max:255', Rule::unique('drivers')->ignore($driver->id)],
            'contact' => ['required', 'string', 'max:255'],
            'vehicle_info' => ['required', 'string'],
            'age' => ['required', 'integer', 'min:18', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($driver->photo) {
                Storage::disk('public')->delete($driver->photo);
            }
            $photoPath = $request->file('photo')->store('drivers', 'public');
            $validated['photo'] = $photoPath;
        } else {
            // Keep existing photo if no new photo is uploaded
            unset($validated['photo']);
        }

        $driver->update($validated);

        return redirect()->route('drivers.index')->with('success', 'Driver updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Driver $driver)
    {
        // Delete photo if exists
        if ($driver->photo) {
            Storage::disk('public')->delete($driver->photo);
        }

        $driver->delete();

        return redirect()->route('drivers.index')->with('success', 'Driver deleted successfully!');
    }
}
