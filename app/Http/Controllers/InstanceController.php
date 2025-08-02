<?php

namespace App\Http\Controllers;

use App\Models\ClientInstance;
use App\Models\ActivityLog;
use App\Services\FlyApi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstanceController extends Controller
{
    protected function getFlyApiForInstance($instance)
    {
        return FlyApi::forClient($instance->client);
    }

    public function index(Request $request)
    {
        $query = ClientInstance::with('client');

        // Filter by client if not admin
        if (auth()->user()->role === 'manager') {
            $clientIds = auth()->user()->clients->pluck('id');
            $query->whereIn('client_id', $clientIds);
        }

        // Apply filters
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('fly_app_id', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $instances = $query->orderBy('created_at', 'desc')
                          ->paginate(20);

        return Inertia::render('Instances/Index', [
            'instances' => $instances,
            'filters' => $request->only(['client_id', 'type', 'status', 'search']),
        ]);
    }

    public function show(ClientInstance $instance)
    {
        $instance->load('client');
        
        // Check access - admins can see all, managers only their assigned clients
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($instance->client_id)) {
            abort(403, 'Unauthorized');
        }

        try {
            // Create FlyApi instance for this client
            $flyApi = $this->getFlyApiForInstance($instance);
            
            // Fetch real-time data from Fly.io
            $flyData = $flyApi->getApp($instance->fly_app_id);
            $machines = $flyData['machines']['nodes'] ?? [];
            
            // Debug logging for machines data
            Log::info('InstanceController machines debug', [
                'app' => $instance->fly_app_id,
                'flyData_structure' => array_keys($flyData ?? []),
                'has_machines' => isset($flyData['machines']),
                'machines_structure' => $flyData['machines'] ?? 'not_found',
                'machines_count' => count($machines),
            ]);
            
            // Get recent logs from allocations
            $logs = $flyApi->getLogs($instance->fly_app_id, null, 50);
            
            // Get metrics if available
            $metrics = $flyApi->getMetrics($instance->fly_app_id);
        } catch (\Exception $e) {
            $machines = [];
            $logs = null;
            $metrics = null;
        }

        ActivityLog::log('instance_viewed', 'instance', $instance->id, [
            'instance_name' => $instance->fly_app_id
        ]);

        return Inertia::render('Instances/Show', [
            'instance' => $instance,
            'machines' => $machines,
            'logs' => $logs,
            'metrics' => $metrics
        ]);
    }

    public function restart(ClientInstance $instance)
    {
        // Check access
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($instance->client_id)) {
            abort(403, 'Unauthorized');
        }

        try {
            // Create FlyApi instance for this client
            $flyApi = $this->getFlyApiForInstance($instance);
            
            // Get machines for this app
            $flyData = $flyApi->getApp($instance->fly_app_id);
            $machines = $flyData['data']['app']['machines']['nodes'] ?? [];
            
            $restarted = [];
            $failed = [];
            
            foreach ($machines as $machine) {
                if ($machine['state'] === 'started') {
                    $result = $flyApi->restartMachine($instance->fly_app_id, $machine['id']);
                    
                    if (isset($result['data']['restartMachine'])) {
                        $restarted[] = $machine['id'];
                    } else {
                        $failed[] = $machine['id'];
                    }
                }
            }

            ActivityLog::log('instance_restarted', 'instance', $instance->id, [
                'instance_name' => $instance->fly_app_id,
                'machines_restarted' => $restarted,
                'machines_failed' => $failed
            ]);

            return back()->with('success', 'Instance restart initiated. ' . count($restarted) . ' machines restarted.');
        } catch (\Exception $e) {
            ActivityLog::log('instance_restart_failed', 'instance', $instance->id, [
                'instance_name' => $instance->fly_app_id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to restart instance: ' . $e->getMessage());
        }
    }

    public function stop(ClientInstance $instance)
    {
        // Check access
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($instance->client_id)) {
            abort(403, 'Unauthorized');
        }

        try {
            // Create FlyApi instance for this client
            $flyApi = $this->getFlyApiForInstance($instance);
            
            // Get machines for this app
            $flyData = $flyApi->getApp($instance->fly_app_id);
            $machines = $flyData['data']['app']['machines']['nodes'] ?? [];
            
            $stopped = [];
            $failed = [];
            
            foreach ($machines as $machine) {
                if ($machine['state'] === 'started') {
                    $result = $flyApi->stopMachine($instance->fly_app_id, $machine['id']);
                    
                    if (isset($result['data']['stopMachine'])) {
                        $stopped[] = $machine['id'];
                    } else {
                        $failed[] = $machine['id'];
                    }
                }
            }

            // Update instance status
            $instance->update(['status' => 'stopped']);

            ActivityLog::log('instance_stopped', 'instance', $instance->id, [
                'instance_name' => $instance->fly_app_id,
                'machines_stopped' => $stopped,
                'machines_failed' => $failed
            ]);

            return back()->with('success', 'Instance stopped. ' . count($stopped) . ' machines stopped.');
        } catch (\Exception $e) {
            ActivityLog::log('instance_stop_failed', 'instance', $instance->id, [
                'instance_name' => $instance->fly_app_id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to stop instance: ' . $e->getMessage());
        }
    }

    public function start(ClientInstance $instance)
    {
        // Check access
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($instance->client_id)) {
            abort(403, 'Unauthorized');
        }

        try {
            // Create FlyApi instance for this client
            $flyApi = $this->getFlyApiForInstance($instance);
            
            // Get machines for this app
            $flyData = $flyApi->getApp($instance->fly_app_id);
            $machines = $flyData['data']['app']['machines']['nodes'] ?? [];
            
            $started = [];
            $failed = [];
            
            foreach ($machines as $machine) {
                if ($machine['state'] === 'stopped') {
                    $result = $flyApi->startMachine($instance->fly_app_id, $machine['id']);
                    
                    if (isset($result['data']['startMachine'])) {
                        $started[] = $machine['id'];
                    } else {
                        $failed[] = $machine['id'];
                    }
                }
            }

            // Update instance status
            $instance->update(['status' => 'running']);

            ActivityLog::log('instance_started', 'instance', $instance->id, [
                'instance_name' => $instance->fly_app_id,
                'machines_started' => $started,
                'machines_failed' => $failed
            ]);

            return back()->with('success', 'Instance started. ' . count($started) . ' machines started.');
        } catch (\Exception $e) {
            ActivityLog::log('instance_start_failed', 'instance', $instance->id, [
                'instance_name' => $instance->fly_app_id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to start instance: ' . $e->getMessage());
        }
    }

    public function console(ClientInstance $instance)
    {
        // Check access
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($instance->client_id)) {
            abort(403, 'Unauthorized');
        }

        try {
            // Create FlyApi instance for this client
            $flyApi = $this->getFlyApiForInstance($instance);
            
            // Get first available machine
            $flyData = $flyApi->getApp($instance->fly_app_id);
            $machines = $flyData['data']['app']['machines']['nodes'] ?? [];
            
            if (empty($machines)) {
                throw new \Exception('No machines available for console access');
            }

            $machineId = $machines[0]['id'];
            $consoleSession = $flyApi->createConsoleSession($instance->fly_app_id, $machineId);
            
            ActivityLog::log('instance_console_accessed', 'instance', $instance->id, [
                'instance_name' => $instance->fly_app_id,
                'machine_id' => $machineId
            ]);

            return Inertia::render('Instances/Console', [
                'instance' => $instance,
                'consoleUrl' => $consoleSession['data']['createConsoleSession']['url'] ?? null
            ]);
        } catch (\Exception $e) {
            ActivityLog::log('instance_console_failed', 'instance', $instance->id, [
                'instance_name' => $instance->fly_app_id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to create console session: ' . $e->getMessage());
        }
    }
}
