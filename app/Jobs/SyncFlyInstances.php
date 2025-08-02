<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FlyApi;
use App\Models\Client;
use App\Models\ClientInstance;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class SyncFlyInstances implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes

    protected $clientId;

    /**
     * Create a new job instance.
     */
    public function __construct($clientId = null)
    {
        $this->clientId = $clientId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $clients = $this->clientId 
            ? Client::where('id', $this->clientId)->get()
            : Client::where('status', 'active')->get();

        foreach ($clients as $client) {
            try {
                Log::info("Syncing Fly.io instances for client: {$client->name}");

                // Create FlyApi instance for this client
                $flyApi = FlyApi::forClient($client);

                // Get all apps for this organization
                $apps = $flyApi->listApps($client->fly_org_id);

                $syncedAppIds = [];

                foreach ($apps as $app) {
                    $instance = ClientInstance::updateOrCreate(
                        [
                            'fly_app_id' => $app['id'],
                        ],
                        [
                            'client_id' => $client->id,
                            'type' => $this->detectInstanceType($app['name']),
                            'region' => $app['machines']['nodes'][0]['region'] ?? null,
                            'size' => $app['machines']['nodes'][0]['config']['guest']['cpus'] ?? null,
                            'status' => $this->determineStatus($app),
                            'metadata' => [
                                'name' => $app['name'],
                                'hostname' => $app['hostname'],
                                'app_url' => $app['appUrl'],
                                'deployed' => $app['deployed'],
                                'current_release' => $app['currentRelease'] ?? null,
                                'machine_count' => count($app['machines']['nodes'] ?? []),
                            ],
                        ]
                    );

                    $syncedAppIds[] = $app['id'];

                    Log::info("Synced instance: {$app['name']} for client: {$client->name}");
                }

                // Mark any instances not found in the sync as stopped
                ClientInstance::where('client_id', $client->id)
                    ->whereNotIn('fly_app_id', $syncedAppIds)
                    ->update(['status' => 'stopped']);

                ActivityLog::log('fly_sync_completed', $client, [
                    'synced_count' => count($syncedAppIds),
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to sync Fly.io instances for client: {$client->name}", [
                    'error' => $e->getMessage(),
                ]);

                ActivityLog::log('fly_sync_failed', $client, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Detect the instance type based on app name
     */
    protected function detectInstanceType($appName): string
    {
        if (str_contains(strtolower($appName), 'sql') || str_contains(strtolower($appName), 'database')) {
            return 'sql';
        }

        if (str_contains(strtolower($appName), 'sas') || str_contains(strtolower($appName), 'app')) {
            return 'sas';
        }

        return 'sas'; // Default to SAS
    }

    /**
     * Determine the status of the app based on machine states
     */
    protected function determineStatus($app): string
    {
        if (!$app['deployed']) {
            return 'not_deployed';
        }

        $machines = $app['machines']['nodes'] ?? [];
        
        if (empty($machines)) {
            return 'no_machines';
        }

        $runningCount = 0;
        foreach ($machines as $machine) {
            if ($machine['state'] === 'started') {
                $runningCount++;
            }
        }

        if ($runningCount === count($machines)) {
            return 'running';
        } elseif ($runningCount > 0) {
            return 'partial';
        } else {
            return 'stopped';
        }
    }
}
