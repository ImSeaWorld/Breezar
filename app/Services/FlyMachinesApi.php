<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FlyMachinesApi
{
    protected $apiToken;
    protected $baseUrl = 'https://api.machines.dev/v1';

    public function __construct($apiToken = null)
    {
        // Allow passing token for client-specific operations
        if ($apiToken) {
            $this->apiToken = $apiToken;
        } else {
            // Get from database settings first, fall back to env
            $this->apiToken = Setting::get('fly_api_token', config('services.fly.api_token'));
        }
        
        if (!$this->apiToken) {
            throw new Exception('Fly.io API token not configured. Please set it in Settings or provide client-specific token.');
        }
    }

    /**
     * Create FlyMachinesApi instance for a specific client
     */
    public static function forClient($client)
    {
        if ($client->fly_api_token && !$client->isFlyTokenExpired()) {
            // Use client-specific token if available and not expired
            return new self($client->decrypted_fly_api_token);
        }
        
        // Fall back to global settings
        return new self();
    }

    /**
     * Make a request to the Machines API
     */
    protected function request($method, $path, $data = null)
    {
        $url = $this->baseUrl . $path;
        
        $request = Http::withToken($this->apiToken)
            ->acceptJson()
            ->contentType('application/json');

        switch (strtoupper($method)) {
            case 'GET':
                $response = $request->get($url, $data);
                break;
            case 'POST':
                $response = $request->post($url, $data);
                break;
            case 'PUT':
                $response = $request->put($url, $data);
                break;
            case 'DELETE':
                $response = $request->delete($url);
                break;
            default:
                throw new Exception("Unsupported HTTP method: $method");
        }

        if ($response->failed()) {
            Log::error('Fly.io Machines API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
                'method' => $method,
            ]);
            
            $error = $response->json('error') ?? $response->body();
            throw new Exception("Fly.io Machines API request failed: $error");
        }

        return $response->json();
    }

    /**
     * List all machines for an app
     */
    public function listMachines($appName, $includeDeleted = false, $region = null, $state = null)
    {
        $params = [];
        
        if ($includeDeleted) {
            $params['include_deleted'] = true;
        }
        
        if ($region) {
            $params['region'] = $region;
        }
        
        if ($state) {
            $params['state'] = $state;
        }

        try {
            $machines = $this->request('GET', "/apps/{$appName}/machines", $params);
            
            Log::info('FlyMachinesApi listMachines response', [
                'app' => $appName,
                'count' => is_array($machines) ? count($machines) : 'not_array',
                'first_machine' => is_array($machines) && count($machines) > 0 ? array_keys($machines[0]) : 'no_machines',
            ]);
            
            return $machines;
        } catch (Exception $e) {
            Log::error('Failed to list machines', [
                'app' => $appName,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get a specific machine
     */
    public function getMachine($appName, $machineId)
    {
        return $this->request('GET', "/apps/{$appName}/machines/{$machineId}");
    }

    /**
     * Start a machine
     */
    public function startMachine($appName, $machineId)
    {
        try {
            $result = $this->request('POST', "/apps/{$appName}/machines/{$machineId}/start");
            
            Log::info('Machine started', [
                'app' => $appName,
                'machine' => $machineId
            ]);
            
            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            Log::error('Failed to start machine', [
                'app' => $appName,
                'machine' => $machineId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Stop a machine
     */
    public function stopMachine($appName, $machineId, $signal = null, $timeout = null)
    {
        $data = [];
        
        if ($signal) {
            $data['signal'] = $signal;
        }
        
        if ($timeout) {
            $data['timeout'] = $timeout;
        }

        try {
            $result = $this->request('POST', "/apps/{$appName}/machines/{$machineId}/stop", $data);
            
            Log::info('Machine stopped', [
                'app' => $appName,
                'machine' => $machineId
            ]);
            
            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            Log::error('Failed to stop machine', [
                'app' => $appName,
                'machine' => $machineId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Restart a machine
     */
    public function restartMachine($appName, $machineId, $timeout = null, $signal = null)
    {
        $params = [];
        
        if ($timeout) {
            $params['timeout'] = $timeout;
        }
        
        if ($signal) {
            $params['signal'] = $signal;
        }

        try {
            // Query params should be passed in the third parameter for GET requests
            // For POST with query params, we need to format the URL
            $url = "/apps/{$appName}/machines/{$machineId}/restart";
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            
            $result = $this->request('POST', $url);
            
            Log::info('Machine restarted', [
                'app' => $appName,
                'machine' => $machineId
            ]);
            
            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            Log::error('Failed to restart machine', [
                'app' => $appName,
                'machine' => $machineId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Execute a command on a machine
     */
    public function execCommand($appName, $machineId, $command, $timeout = 30)
    {
        $data = [
            'cmd' => is_array($command) ? $command : [$command],
            'timeout' => $timeout
        ];

        try {
            $result = $this->request('POST', "/apps/{$appName}/machines/{$machineId}/exec", $data);
            
            return [
                'success' => true,
                'stdout' => $result['stdout'] ?? '',
                'stderr' => $result['stderr'] ?? '',
                'exit_code' => $result['exit_code'] ?? null
            ];
        } catch (Exception $e) {
            Log::error('Failed to execute command', [
                'app' => $appName,
                'machine' => $machineId,
                'command' => $command,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Wait for a machine to reach a specific state
     */
    public function waitForState($appName, $machineId, $state = 'started', $timeout = 60)
    {
        $params = [
            'state' => $state,
            'timeout' => $timeout
        ];

        try {
            $this->request('GET', "/apps/{$appName}/machines/{$machineId}/wait", $params);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to wait for machine state', [
                'app' => $appName,
                'machine' => $machineId,
                'desired_state' => $state,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * List events for a machine
     */
    public function listEvents($appName, $machineId, $limit = 20)
    {
        try {
            return $this->request('GET', "/apps/{$appName}/machines/{$machineId}/events", ['limit' => $limit]);
        } catch (Exception $e) {
            Log::error('Failed to list machine events', [
                'app' => $appName,
                'machine' => $machineId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get processes running on a machine
     */
    public function listProcesses($appName, $machineId)
    {
        try {
            return $this->request('GET', "/apps/{$appName}/machines/{$machineId}/ps");
        } catch (Exception $e) {
            Log::error('Failed to list machine processes', [
                'app' => $appName,
                'machine' => $machineId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}