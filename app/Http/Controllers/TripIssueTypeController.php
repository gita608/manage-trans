<?php

namespace App\Http\Controllers;

use App\Models\TripIssueType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TripIssueTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $issueTypes = TripIssueType::latest()->get();
        return view('trip-issue-types.index', compact('issueTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('trip-issue-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', 'unique:trip_issue_types'],
        ]);

        TripIssueType::create($validated);

        return redirect()->route('trip-issue-types.index')->with('success', 'Trip issue type created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TripIssueType $tripIssueType)
    {
        return view('trip-issue-types.edit', compact('tripIssueType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TripIssueType $tripIssueType)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('trip_issue_types')->ignore($tripIssueType->id)],
        ]);

        $tripIssueType->update($validated);

        return redirect()->route('trip-issue-types.index')->with('success', 'Trip issue type updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripIssueType $tripIssueType)
    {
        $tripIssueType->delete();

        return redirect()->route('trip-issue-types.index')->with('success', 'Trip issue type deleted successfully!');
    }
}
