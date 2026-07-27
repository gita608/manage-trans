<?php

namespace App\Http\Controllers;

use App\Models\TripExpenseType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TripExpenseTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenseTypes = TripExpenseType::latest()->get();
        return view('trip-expense-types.index', compact('expenseTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('trip-expense-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', 'unique:trip_expense_types'],
            'input_types' => ['required', 'array', 'min:1'],
            'input_types.*' => ['string', Rule::in(['amount', 'number', 'hours', 'text', 'image'])],
        ]);

        TripExpenseType::create($validated);

        return redirect()->route('trip-expense-types.index')->with('success', 'Trip expense type created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TripExpenseType $tripExpenseType)
    {
        return view('trip-expense-types.edit', compact('tripExpenseType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TripExpenseType $tripExpenseType)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('trip_expense_types')->ignore($tripExpenseType->id)],
            'input_types' => ['required', 'array', 'min:1'],
            'input_types.*' => ['string', Rule::in(['amount', 'number', 'hours', 'text', 'image'])],
        ]);

        $tripExpenseType->update($validated);

        return redirect()->route('trip-expense-types.index')->with('success', 'Trip expense type updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripExpenseType $tripExpenseType)
    {
        $tripExpenseType->delete();

        return redirect()->route('trip-expense-types.index')->with('success', 'Trip expense type deleted successfully!');
    }
}
