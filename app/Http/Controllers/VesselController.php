<?php

namespace App\Http\Controllers;

use App\Models\Vessel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VesselController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vessels = Vessel::latest()->paginate(10);
        return view('vessels.index', compact('vessels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('vessels.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vessels'],
        ]);

        Vessel::create($validated);

        return redirect()->route('vessels.index')->with('success', 'Vessel created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vessel $vessel)
    {
        return view('vessels.show', compact('vessel'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vessel $vessel)
    {
        return view('vessels.edit', compact('vessel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vessel $vessel)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('vessels')->ignore($vessel->id)],
        ]);

        $vessel->update($validated);

        return redirect()->route('vessels.index')->with('success', 'Vessel updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vessel $vessel)
    {
        $vessel->delete();

        return redirect()->route('vessels.index')->with('success', 'Vessel deleted successfully!');
    }
}
