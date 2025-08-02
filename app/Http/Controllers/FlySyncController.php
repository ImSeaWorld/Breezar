<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Jobs\SyncFlyInstances;
use App\Models\ActivityLog;
use App\Models\Client;

class FlySyncController extends Controller
{
    public function index(Request $request)
    {
        $syncHistory = ActivityLog::whereIn('action', ['fly_sync_completed', 'fly_sync_failed'])
            ->with(['user', 'resource'])
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'client' => $log->resource ? $log->resource->name : 'All Clients',
                    'user' => $log->user ? $log->user->name : 'System',
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at,
                ];
            });

        $clients = Client::where('status', 'active')
            ->withCount('instances')
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'fly_org_id' => $client->fly_org_id,
                    'instances_count' => $client->instances_count,
                ];
            });

        return Inertia::render('FlySync', [
            'syncHistory' => $syncHistory,
            'clients' => $clients,
        ]);
    }

    public function run(Request $request)
    {
        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $clientId = $request->input('client_id');

        SyncFlyInstances::dispatch($clientId);

        $message = $clientId 
            ? 'Sync job dispatched for client'
            : 'Sync job dispatched for all active clients';

        ActivityLog::log('fly_sync_manual', null, [
            'client_id' => $clientId,
            'initiated_by' => $request->user()->name,
        ]);

        return back()->with('success', $message);
    }
}
