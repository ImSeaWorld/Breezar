<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->withCount('clients')
                       ->orderBy('created_at', 'desc')
                       ->paginate(20);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Client::all();

        return Inertia::render('Users/Create', [
            'clients' => $clients,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,manager',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        // Attach clients if user is a manager
        if ($validated['role'] === 'manager' && !empty($validated['client_ids'])) {
            $user->clients()->attach($validated['client_ids']);
        }

        ActivityLog::log('user_created', 'user', $user->id, [
            'user_name' => $user->name,
            'user_email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->route('users.index')
                       ->with('success', 'User created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load(['clients', 'loginLogs' => function($query) {
            $query->latest()->limit(10);
        }]);

        // Get recent activities by this user
        $recentActivities = ActivityLog::where('user_id', $user->id)
                                      ->latest()
                                      ->limit(20)
                                      ->get();

        return Inertia::render('Users/Show', [
            'user' => $user,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $user->load('clients');
        $clients = Client::all();
        $selectedClientIds = $user->clients->pluck('id')->toArray();

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'clients' => $clients,
            'selectedClientIds' => $selectedClientIds,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,manager',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
            'reset_2fa' => 'nullable|boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Reset 2FA if requested
        if ($request->boolean('reset_2fa')) {
            $user->update(['two_factor_secret' => null]);
        }

        // Update client assignments
        if ($validated['role'] === 'manager') {
            $user->clients()->sync($validated['client_ids'] ?? []);
        } else {
            // Remove all client assignments for admins
            $user->clients()->detach();
        }

        ActivityLog::log('user_updated', 'user', $user->id, [
            'user_name' => $user->name,
            'changes' => $request->only(['name', 'email', 'role']),
        ]);

        return redirect()->route('users.index')
                       ->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account');
        }

        // Prevent deletion of last admin
        if ($user->role === 'admin' && User::where('role', 'admin')->count() === 1) {
            return back()->with('error', 'Cannot delete the last admin user');
        }

        ActivityLog::log('user_deleted', 'user', $user->id, [
            'user_name' => $user->name,
            'user_email' => $user->email,
        ]);

        $user->delete();

        return redirect()->route('users.index')
                       ->with('success', 'User deleted successfully');
    }
}
