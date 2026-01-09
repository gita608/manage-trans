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
        $drivers = Driver::query()->latest()->get();
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
            'type' => ['required', 'integer', 'in:1,2'],
            'email' => ['nullable', 'email', 'max:255', 'unique:drivers'],
            'password' => ['nullable', 'string', 'min:8'],
            'license_number' => ['nullable', 'string', 'max:255', 'unique:drivers'],
            'contact' => ['nullable', 'string', 'max:255'],
            'vehicle_info' => ['nullable', 'string'],
            'vehicle_name' => ['nullable', 'string', 'max:255'],
            'vehicle_brand' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        // Set default type if not provided
        if (!isset($validated['type'])) {
            $validated['type'] = Driver::TYPE_OUTSOURCING;
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('drivers', 'public');
            $validated['photo'] = $photoPath;
        }

        // Create driver first to get ID
        $driver = Driver::create($validated);

        // Handle document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('driver-documents', 'public');
                
                $driver->documents()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('drivers.index')->with('success', 'Driver created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Driver $driver)
    {
        $driver->load('latestLocation');
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
            'type' => ['required', 'integer', 'in:1,2'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('drivers')->ignore($driver->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'license_number' => ['nullable', 'string', 'max:255', Rule::unique('drivers')->ignore($driver->id)],
            'contact' => ['nullable', 'string', 'max:255'],
            'vehicle_info' => ['nullable', 'string'],
            'vehicle_name' => ['nullable', 'string', 'max:255'],
            'vehicle_brand' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:18', 'max:100'],
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

        // Handle document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('driver-documents', 'public');
                
                $driver->documents()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Password will be automatically hashed by the Driver model
        // Remove password from validated if empty (keep current password)
        if (empty($validated['password'])) {
            unset($validated['password']);
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

        // Delete documents
        foreach ($driver->documents as $document) {
            Storage::disk('public')->delete($document->file_path);
            $document->delete();
        }

        $driver->delete();

        return redirect()->route('drivers.index')->with('success', 'Driver deleted successfully!');
    }

    /**
     * Delete a specific document
     */
    public function deleteDocument(\App\Models\DriverDocument $document)
    {
        // Check if file exists and delete it
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
        
        $document->delete();
        
        return back()->with('success', 'Document deleted successfully!');
    }

    /**
     * Display the driver locations map.
     *
     * @return \Illuminate\View\View
     */
    public function map()
    {
        return view('drivers.map');
    }

    /**
     * Get all driver locations for the map (API endpoint).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function locations(Request $request)
    {
        $drivers = Driver::with('latestLocation')
            ->whereHas('latestLocation')
            ->get()
            ->map(function($driver) {
                $location = $driver->latestLocation;
                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'type' => $driver->type,
                    'type_label' => $driver->getTypeLabel(),
                    'contact' => $driver->contact,
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'updated_at' => $location->updated_at->format('Y-m-d H:i:s'),
                    'updated_at_human' => $location->updated_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'drivers' => $drivers,
        ]);
    }
}
