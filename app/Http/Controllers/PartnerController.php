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

        // Handle checkbox fields that may not be present when unchecked
        $validated['is_default'] = $request->boolean('is_default');
        $validated['allow_manual_submission'] = $request->boolean('allow_manual_submission');
        $validated['allow_image_submission'] = $request->boolean('allow_image_submission');

        // If this partner is set as default, unset all other defaults
        if ($validated['is_default']) {
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

        // Handle checkbox fields that may not be present when unchecked
        $validated['is_default'] = $request->boolean('is_default');
        $validated['allow_manual_submission'] = $request->boolean('allow_manual_submission');
        $validated['allow_image_submission'] = $request->boolean('allow_image_submission');

        // If this partner is set as default, unset all other defaults
        if ($validated['is_default']) {
            Partner::where('is_default', true)->where('id', '!=', $partner->id)->update(['is_default' => false]);
        } else {
            // If unsetting default, ensure at least one partner remains default
            if ($partner->is_default) {
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
        try {
            // If deleting the default partner, set another one as default first
            if ($partner->is_default) {
                $otherPartner = Partner::where('id', '!=', $partner->id)->first();
                if ($otherPartner) {
                    $otherPartner->update(['is_default' => true]);
                }
            }

            $partner->delete();

            return redirect()->route('partners.index')->with('success', 'Partner deleted successfully!');
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if deletion failed due to RESTRICT constraint (existing requests)
            if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'RESTRICT') || str_contains($e->getMessage(), 'foreign key constraint')) {
                return redirect()->route('partners.index')
                    ->with('error', 'Cannot delete this partner because it has historical requests or users. Please contact support if you need assistance.');
            }
            
            // Re-throw other exceptions
            throw $e;
        }
    }
}
