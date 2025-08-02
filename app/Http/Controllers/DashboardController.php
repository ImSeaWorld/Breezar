<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Client;
use App\Models\ClientInstance;
use App\Models\User;
use App\Models\LoginLog;
use App\Models\ActivityLog;
use App\Services\FlyApi;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $stats = Cache::remember('dashboard.stats.' . $user->id, 300, function () use ($user) {
            $query = $user->isAdmin() ? Client::query() : $user->clients();
            
            return [
                'total_clients' => $query->count(),
                'active_clients' => $query->where('status', 'active')->count(),
                'total_instances' => ClientInstance::whereIn('client_id', $query->pluck('id'))->count(),
                'running_instances' => ClientInstance::whereIn('client_id', $query->pluck('id'))
                    ->where('status', 'running')->count(),
            ];
        });

        if ($user->isAdmin()) {
            $stats['total_users'] = User::count();
            $stats['admin_users'] = User::where('role', 'admin')->count();
        }

        $recentActivities = ActivityLog::with(['user'])
            ->when(!$user->isAdmin(), function ($query) use ($user) {
                $query->whereIn('user_id', $user->clients()->pluck('users.id'));
            })
            ->latest()
            ->take(10)
            ->get();

        $recentLogins = LoginLog::with(['user'])
            ->when(!$user->isAdmin(), function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->successful()
            ->latest('timestamp')
            ->take(5)
            ->get();

        $clientsWithInstances = $user->isAdmin() 
            ? Client::with(['instances'])->where('status', 'active')->get()
            : $user->clients()->with(['instances'])->where('status', 'active')->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'recentLogins' => $recentLogins,
            'clients' => $clientsWithInstances->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'fly_org_id' => $client->fly_org_id,
                    'instances' => $client->instances->map(function ($instance) {
                        return [
                            'id' => $instance->id,
                            'type' => $instance->type,
                            'status' => $instance->status,
                            'region' => $instance->region,
                        ];
                    }),
                    'sql_instance' => $client->sql_instance,
                    'sas_instance' => $client->sas_instance,
                ];
            }),
        ]);
    }

    public function refreshStats(Request $request)
    {
        Cache::forget('dashboard.stats.' . $request->user()->id);
        
        return redirect()->route('dashboard');
    }
}
