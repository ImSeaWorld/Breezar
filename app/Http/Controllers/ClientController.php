<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Models\ActivityLog;
use App\Jobs\SyncFlyInstances;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $clients = $user->isAdmin() 
            ? Client::with(['users', 'instances'])
                ->withCount(['instances', 'reports'])
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('fly_org_id', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(15)
            : $user->clients()
                ->with(['instances'])
                ->withCount(['instances', 'reports'])
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('fly_org_id', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(15);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::where('role', 'manager')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Clients/Create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fly_org_id' => 'required|string|max:255|unique:clients,fly_org_id',
            'contact_info' => 'nullable|array',
            'contact_info.email' => 'nullable|email',
            'contact_info.phone' => 'nullable|string',
            'contact_info.address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'billing_start_date' => 'nullable|date',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $client = Client::create($validated);

        if (!empty($validated['user_ids'])) {
            $client->users()->attach($validated['user_ids']);
        }

        // Dispatch sync job for this new client
        SyncFlyInstances::dispatch($client->id);

        ActivityLog::log('client_created', $client);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Client $client)
    {
        // Check access
        if (!$request->user()->isAdmin() && !$request->user()->clients->contains($client)) {
            abort(403);
        }

        $client->load(['users', 'instances', 'reports' => function ($query) {
            $query->latest()->take(10);
        }]);

        $recentActivities = ActivityLog::where('resource_type', Client::class)
            ->where('resource_id', $client->id)
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        $client->load('users');
        
        $users = User::where('role', 'manager')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Clients/Edit', [
            'client' => $client,
            'users' => $users,
            'selectedUserIds' => $client->users->pluck('id'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fly_org_id' => 'required|string|max:255|unique:clients,fly_org_id,' . $client->id,
            'fly_api_token' => 'nullable|string',
            'fly_token_type' => 'required|in:org,readonly,app',
            'fly_token_expires_at' => 'nullable|date',
            'contact_info' => 'nullable|array',
            'contact_info.email' => 'nullable|email',
            'contact_info.phone' => 'nullable|string',
            'contact_info.address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'billing_start_date' => 'nullable|date',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Handle token separately if provided
        if (!empty($validated['fly_api_token'])) {
            $client->fly_api_token = $validated['fly_api_token']; // Will be encrypted by mutator
        }
        
        // Remove token from validated data to avoid double processing
        unset($validated['fly_api_token']);
        
        $client->update($validated);

        // Sync user relationships
        $client->users()->sync($validated['user_ids'] ?? []);

        ActivityLog::log('client_updated', $client, [
            'token_updated' => !empty($request->fly_api_token)
        ]);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $clientName = $client->name;
        
        ActivityLog::log('client_deleted', null, [
            'client_name' => $clientName,
            'fly_org_id' => $client->fly_org_id,
        ]);

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', "Client '{$clientName}' has been deleted");
    }
}