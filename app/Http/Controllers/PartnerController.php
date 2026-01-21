<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $partners = Partner::latest()->get();
        return view('partners.index', compact('partners'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('partners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', 'unique:partners'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        // If this partner is set as default, unset all other defaults
        if (isset($validated['is_default']) && $validated['is_default']) {
            Partner::where('is_default', true)->update(['is_default' => false]);
        }

        Partner::create($validated);

        return redirect()->route('partners.index')->with('success', 'Partner created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Partner $partner)
    {
        return view('partners.edit', compact('partner'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('partners')->ignore($partner->id)],
            'is_default' => ['nullable', 'boolean'],
        ]);

        // If this partner is set as default, unset all other defaults
        if (isset($validated['is_default']) && $validated['is_default']) {
            Partner::where('is_default', true)->where('id', '!=', $partner->id)->update(['is_default' => false]);
        } else {
            // If unsetting default, ensure at least one partner remains default
            if ($partner->is_default && !($validated['is_default'] ?? false)) {
                $otherPartner = Partner::where('id', '!=', $partner->id)->first();
                if ($otherPartner) {
                    $otherPartner->update(['is_default' => true]);
                }
            }
        }

        $partner->update($validated);

        return redirect()->route('partners.index')->with('success', 'Partner updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partner $partner)
    {
        // If deleting the default partner, set another one as default
        if ($partner->is_default) {
            $otherPartner = Partner::where('id', '!=', $partner->id)->first();
            if ($otherPartner) {
                $otherPartner->update(['is_default' => true]);
            }
        }

        $partner->delete();

        return redirect()->route('partners.index')->with('success', 'Partner deleted successfully!');
    }
}
