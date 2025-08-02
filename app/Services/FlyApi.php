<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FlyApi
{
    protected $apiToken;
    protected $baseUrl = 'https://api.fly.io/graphql';
    protected $orgId;

    public function __construct($apiToken = null, $orgId = null)
    {
        // Allow passing token and org for testing or client-specific operations
        if ($apiToken) {
            $this->apiToken = $apiToken;
            $this->orgId = $orgId ?: Setting::get('fly_org_id', config('services.fly.org_id'));
        } else {
            // Get from database settings first, fall back to env
            $this->apiToken = Setting::get('fly_api_token', config('services.fly.api_token'));
            $this->orgId = Setting::get('fly_org_id', config('services.fly.org_id'));
        }
        
        // Get API endpoint from settings or use default
        $this->baseUrl = Setting::get('fly_api_endpoint', 'https://api.fly.io/graphql');
        
        if (!$this->apiToken) {
            throw new Exception('Fly.io API token not configured. Please set it in Settings or provide client-specific token.');
        }
    }

    /**
     * Create FlyApi instance for a specific client
     */
    public static function forClient($client)
    {
        if ($client->fly_api_token && !$client->isFlyTokenExpired()) {
            // Use client-specific token if available and not expired
            return new self($client->decrypted_fly_api_token, $client->fly_org_id);
        }
        
        // Fall back to global settings
        return new self();
    }

    protected function query($query, $variables = [])
    {
        $response = Http::withToken($this->apiToken)
            ->post($this->baseUrl, [
                'query' => $query,
                'variables' => $variables,
            ]);

        if ($response->failed()) {
            Log::error('Fly.io API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Fly.io API request failed');
        }

        $data = $response->json();

        if (isset($data['errors'])) {
            Log::error('Fly.io GraphQL errors', $data['errors']);
            throw new Exception('Fly.io GraphQL errors: ' . json_encode($data['errors']));
        }

        return $data['data'] ?? [];
    }

    public function getOrganization($orgSlug)
    {
        $query = '
            query GetOrganization($slug: String!) {
                organization(slug: $slug) {
                    id
                    slug
                    name
                    type
                    creditBalance
                    creditBalanceFormatted
                }
            }
        ';

        return $this->query($query, ['slug' => $orgSlug]);
    }

    public function listApps($orgSlug = null)
    {
        $orgSlug = $orgSlug ?: $this->orgId;
        
        $query = '
            query ListApps($orgSlug: String!) {
                organization(slug: $orgSlug) {
                    apps {
                        nodes {
                            id
                            name
                            hostname
                            status
                            deployed
                            appUrl
                            createdAt
                            currentRelease {
                                id
                                version
                                status
                                createdAt
                            }
                            machines {
                                nodes {
                                    id
                                    name
                                    state
                                    region
                                    config
                                }
                            }
                        }
                    }
                }
            }
        ';

        $result = $this->query($query, ['orgSlug' => $orgSlug]);
        return $result['organization']['apps']['nodes'] ?? [];
    }

    public function getApp($appName)
    {
        $query = '
            query GetApp($appName: String!) {
                app(name: $appName) {
                    id
                    name
                    hostname
                    status
                    deployed
                    appUrl
                    organization {
                        id
                        slug
                        name
                    }
                    currentRelease {
                        id
                        version
                        status
                        createdAt
                    }
                    machines {
                        nodes {
                            id
                            name
                            state
                            region
                            config
                            createdAt
                        }
                    }
                    volumes {
                        nodes {
                            id
                            name
                            sizeGb
                            region
                            state
                            attachedMachine {
                                id
                                name
                            }
                        }
                    }
                }
            }
        ';

        $result = $this->query($query, ['appName' => $appName]);
        return $result['app'] ?? null;
    }

    public function scaleMachine($appName, $machineId, $count = null, $size = null)
    {
        $mutation = '
            mutation ScaleMachine($input: ScaleMachineInput!) {
                scaleMachine(input: $input) {
                    app {
                        name
                        machines {
                            nodes {
                                id
                                name
                                state
                                region
                            }
                        }
                    }
                }
            }
        ';

        $input = [
            'appId' => $appName,
            'machineId' => $machineId,
        ];

        if ($count !== null) {
            $input['count'] = $count;
        }

        if ($size !== null) {
            $input['size'] = $size;
        }

        return $this->query($mutation, ['input' => $input]);
    }

    public function getLogs($appName, $machineId = null, $limit = 50)
    {
        $query = '
            query GetAppLogs($appName: String!, $limit: Int) {
                app(name: $appName) {
                    machines {
                        nodes {
                            id
                            recentLogs(limit: $limit) {
                                id
                                instanceId
                                level
                                message
                                region
                                timestamp
                            }
                        }
                    }
                }
            }
        ';

        try {
            $data = $this->query($query, [
                'appName' => $appName,
                'limit' => $limit
            ]);

            $machines = $data['app']['machines']['nodes'] ?? [];
            $allLogs = [];

            // Collect logs from all machines
            foreach ($machines as $machine) {
                if ($machineId && $machine['id'] !== $machineId) {
                    continue; // Skip if specific machine requested and this isn't it
                }
                
                $logs = $machine['recentLogs'] ?? [];
                foreach ($logs as $log) {
                    $log['machineId'] = $machine['id']; // Add machine ID to log entry
                    $allLogs[] = $log;
                }
            }

            // Sort logs by timestamp (newest first)
            usort($allLogs, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            // Limit to requested number if collecting from multiple machines
            if (count($allLogs) > $limit) {
                $allLogs = array_slice($allLogs, 0, $limit);
            }

            return $allLogs;
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch logs from Fly.io', [
                'app' => $appName,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function deployApp($appName, $image, $config = [])
    {
        $mutation = '
            mutation Deploy($input: DeployInput!) {
                deploy(input: $input) {
                    release {
                        id
                        version
                        status
                    }
                }
            }
        ';

        $input = [
            'appId' => $appName,
            'image' => $image,
            'config' => $config,
        ];

        return $this->query($mutation, ['input' => $input]);
    }

    public function createConsoleSession($appName, $machineId = null)
    {
        $mutation = '
            mutation CreateConsoleSession($input: CreateConsoleSessionInput!) {
                createConsoleSession(input: $input) {
                    consoleSession {
                        id
                        url
                        expiresAt
                    }
                }
            }
        ';

        $input = [
            'appId' => $appName,
        ];

        if ($machineId) {
            $input['machineId'] = $machineId;
        }

        return $this->query($mutation, ['input' => $input]);
    }

    public function getMetrics($appName, $metric = 'cpu', $period = '1h')
    {
        // Note: Fly.io GraphQL API doesn't have a metrics field on App type
        // This method is deprecated and returns empty array
        // Consider using Fly.io's REST API for metrics
        
        return [];
        
        // Original query kept for reference:
        // query GetMetrics($appName: String!, $metric: String!, $period: String!) {
        //     app(name: $appName) {
        //         metrics(metric: $metric, period: $period) {
        //             timestamp
        //             value
        //             machineId
        //         }
        //     }
        // }
    }

    public function restartMachine($appName, $machineId)
    {
        $mutation = '
            mutation RestartMachine($input: RestartMachineInput!) {
                restartMachine(input: $input) {
                    machine {
                        id
                        state
                    }
                }
            }
        ';

        return $this->query($mutation, [
            'input' => [
                'appId' => $appName,
                'machineId' => $machineId,
            ]
        ]);
    }

    public function stopMachine($appName, $machineId)
    {
        $mutation = '
            mutation StopMachine($input: StopMachineInput!) {
                stopMachine(input: $input) {
                    machine {
                        id
                        state
                    }
                }
            }
        ';

        return $this->query($mutation, [
            'input' => [
                'appId' => $appName,
                'machineId' => $machineId,
            ]
        ]);
    }

    public function startMachine($appName, $machineId)
    {
        $mutation = '
            mutation StartMachine($input: StartMachineInput!) {
                startMachine(input: $input) {
                    machine {
                        id
                        state
                    }
                }
            }
        ';

        return $this->query($mutation, [
            'input' => [
                'appId' => $appName,
                'machineId' => $machineId,
            ]
        ]);
    }

    public function testConnection()
    {
        try {
            // Query to get organizations - simplest way to test the token
            $query = '
                query GetOrganizations {
                    organizations {
                        nodes {
                            id
                            slug
                            name
                            type
                        }
                    }
                }
            ';

            $result = $this->query($query);
            
            if (isset($result['organizations']['nodes'])) {
                return [
                    'success' => true,
                    'organizations' => array_map(function($org) {
                        return [
                            'id' => $org['slug'],
                            'name' => $org['name'],
                            'type' => $org['type'],
                        ];
                    }, $result['organizations']['nodes'])
                ];
            }

            return [
                'success' => false,
                'error' => 'Unable to fetch organizations'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}