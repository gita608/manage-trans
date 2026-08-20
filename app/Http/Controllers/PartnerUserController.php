<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class PartnerUserController extends Controller
{
    /**
     * Display a listing of partner users for a specific partner.
     */
    public function index(Partner $partner)
    {
        $partnerUsers = $partner->partnerUsers()->latest()->get();
        return view('partners.users.index', compact('partner', 'partnerUsers'));
    }

    /**
     * Show the form for creating a new partner user.
     */
    public function create(Partner $partner)
    {
        return view('partners.users.create', compact('partner'));
    }

    /**
     * Store a newly created partner user.
     */
    public function store(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:partner_users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:255'],
        ]);

        // Ensure partner_id comes from route, not from request
        $validated['partner_id'] = $partner->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        PartnerUser::create($validated);

        return redirect()->route('partners.users.index', $partner)
            ->with('success', 'Partner user created successfully!');
    }

    /**
     * Show the form for editing a partner user.
     */
    public function edit(Partner $partner, PartnerUser $partnerUser)
    {
        // Security: ensure this user belongs to this partner
        if ($partnerUser->partner_id !== $partner->id) {
            abort(403, 'Unauthorized access to partner user.');
        }

        return view('partners.users.edit', compact('partner', 'partnerUser'));
    }

    /**
     * Update the specified partner user.
     */
    public function update(Request $request, Partner $partner, PartnerUser $partnerUser)
    {
        // Security: ensure this user belongs to this partner
        if ($partnerUser->partner_id !== $partner->id) {
            abort(403, 'Unauthorized access to partner user.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('partner_users')->ignore($partnerUser->id)],
            'phone' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $partnerUser->update($validated);

        return redirect()->route('partners.users.index', $partner)
            ->with('success', 'Partner user updated successfully!');
    }

    /**
     * Update the partner user's password.
     */
    public function updatePassword(Request $request, Partner $partner, PartnerUser $partnerUser)
    {
        // Security: ensure this user belongs to this partner
        if ($partnerUser->partner_id !== $partner->id) {
            abort(403, 'Unauthorized access to partner user.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $partnerUser->update([
            'password' => $validated['password'], // Will be hashed automatically by model cast
        ]);

        return redirect()->route('partners.users.index', $partner)
            ->with('success', 'Password updated successfully!');
    }

    /**
     * Toggle the partner user's active status.
     */
    public function toggleStatus(Request $request, Partner $partner, PartnerUser $partnerUser)
    {
        // Security: ensure this user belongs to this partner
        if ($partnerUser->partner_id !== $partner->id) {
            abort(403, 'Unauthorized access to partner user.');
        }

        $partnerUser->update([
            'is_active' => !$partnerUser->is_active,
        ]);

        $status = $partnerUser->is_active ? 'activated' : 'deactivated';

        return redirect()->route('partners.users.index', $partner)
            ->with('success', "Partner user {$status} successfully!");
    }
}
