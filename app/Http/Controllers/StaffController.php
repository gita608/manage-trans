<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::staff()->latest()->paginate(10);
        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        return view('staff.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_STAFF,
        ];

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('users', 'public');
        }

        User::create($data);

        return redirect()->route('staff.index')->with('success', 'Staff created successfully!');
    }

    public function edit(User $staff)
    {
        abort_unless((int) $staff->role === User::ROLE_STAFF, 404);
        return view('staff.edit', compact('staff'));
    }

    public function update(Request $request, User $staff)
    {
        abort_unless((int) $staff->role === User::ROLE_STAFF, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $staff->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->phone = $validated['phone'] ?? null;
        if (!empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
        }
        if ($request->hasFile('photo')) {
            if ($staff->photo) {
                Storage::disk('public')->delete($staff->photo);
            }
            $staff->photo = $request->file('photo')->store('users', 'public');
        }
        $staff->save();

        return redirect()->route('staff.index')->with('success', 'Staff updated successfully!');
    }

    public function destroy(User $staff)
    {
        abort_unless((int) $staff->role === User::ROLE_STAFF, 404);
        if ($staff->photo) {
            Storage::disk('public')->delete($staff->photo);
        }
        $staff->delete();
        return redirect()->route('staff.index')->with('success', 'Staff deleted successfully!');
    }
}


