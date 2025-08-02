<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class FlyMetricsApi
{
    protected $apiToken;
    protected $baseUrl = 'https://api.fly.io/prometheus';
    protected $orgSlug;

    public function __construct($apiToken = null, $orgSlug = null)
    {
        // Allow passing token for client-specific operations
        if ($apiToken) {
            $this->apiToken = $apiToken;
            $this->orgSlug = $orgSlug;
        } else {
            // Get from database settings first, fall back to env
            $this->apiToken = Setting::get('fly_api_token', config('services.fly.api_token'));
            $this->orgSlug = Setting::get('fly_org_id', config('services.fly.org_id'));
        }
        
        if (!$this->apiToken) {
            throw new Exception('Fly.io API token not configured.');
        }
        
        if (!$this->orgSlug) {
            throw new Exception('Fly.io organization slug not configured.');
        }
    }

    /**
     * Create FlyMetricsApi instance for a specific client
     */
    public static function forClient($client)
    {
        if ($client->fly_api_token && !$client->isFlyTokenExpired()) {
            // Use client-specific token if available
            return new self($client->decrypted_fly_api_token, $client->fly_org_id);
        }
        
        // Fall back to global settings
        return new self();
    }

    /**
     * Make a request to the Prometheus API
     */
    protected function request($endpoint, $params = [])
    {
        $url = "{$this->baseUrl}/{$this->orgSlug}{$endpoint}";
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'FlyV1 ' . $this->apiToken,
                'Accept' => 'application/json',
            ])->get($url, $params);

            if ($response->failed()) {
                Log::error('Fly.io Metrics API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $url,
                ]);
                
                throw new Exception('Fly.io Metrics API request failed: ' . $response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Failed to fetch metrics', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint
            ]);
            throw $e;
        }
    }

    /**
     * Execute an instant query
     */
    public function query($query, $time = null)
    {
        $params = ['query' => $query];
        
        if ($time) {
            $params['time'] = $time;
        }

        $result = $this->request('/api/v1/query', $params);
        
        return $this->parseQueryResult($result);
    }

    /**
     * Execute a range query
     */
    public function queryRange($query, $start, $end, $step = '15s')
    {
        $params = [
            'query' => $query,
            'start' => $start,
            'end' => $end,
            'step' => $step,
        ];

        $result = $this->request('/api/v1/query_range', $params);
        
        return $this->parseRangeResult($result);
    }

    /**
     * Get instance CPU usage
     */
    public function getInstanceCPU($appName, $instanceId = null, $duration = '5m')
    {
        $query = "avg(rate(fly_instance_cpu{app=\"{$appName}\"";
        
        if ($instanceId) {
            $query .= ",instance=\"{$instanceId}\"";
        }
        
        $query .= "}[{$duration}])) * 100";

        return $this->query($query);
    }

    /**
     * Get instance memory usage
     */
    public function getInstanceMemory($appName, $instanceId = null)
    {
        $query = "fly_instance_memory_mem_total{app=\"{$appName}\"";
        
        if ($instanceId) {
            $query .= ",instance=\"{$instanceId}\"";
        }
        
        $query .= "} - fly_instance_memory_mem_available{app=\"{$appName}\"";
        
        if ($instanceId) {
            $query .= ",instance=\"{$instanceId}\"";
        }
        
        $query .= "}";

        $used = $this->query($query);
        
        // Also get total memory for percentage calculation
        $totalQuery = "fly_instance_memory_mem_total{app=\"{$appName}\"";
        if ($instanceId) {
            $totalQuery .= ",instance=\"{$instanceId}\"";
        }
        $totalQuery .= "}";
        
        $total = $this->query($totalQuery);
        
        return [
            'used' => $used,
            'total' => $total,
            'percentage' => $this->calculatePercentage($used, $total)
        ];
    }

    /**
     * Get network traffic metrics
     */
    public function getNetworkTraffic($appName, $duration = '5m')
    {
        $rxQuery = "rate(fly_instance_net_recv_bytes{app=\"{$appName}\"}[{$duration}])";
        $txQuery = "rate(fly_instance_net_sent_bytes{app=\"{$appName}\"}[{$duration}])";
        
        return [
            'rx' => $this->query($rxQuery),
            'tx' => $this->query($txQuery),
        ];
    }

    /**
     * Get HTTP response metrics
     */
    public function getHttpMetrics($appName, $duration = '5m')
    {
        // Response count by status
        $responseQuery = "sum(rate(fly_edge_http_responses_count{app=\"{$appName}\"}[{$duration}])) by (status)";
        
        // Response time percentiles
        $latencyQuery = "histogram_quantile(0.95, sum(rate(fly_edge_http_response_time_seconds_bucket{app=\"{$appName}\"}[{$duration}])) by (le))";
        
        return [
            'responses_by_status' => $this->query($responseQuery),
            'p95_latency' => $this->query($latencyQuery),
        ];
    }

    /**
     * Get volume metrics
     */
    public function getVolumeMetrics($appName)
    {
        $query = "fly_volume_used_pct{app=\"{$appName}\"}";
        
        return $this->query($query);
    }

    /**
     * Get comprehensive app metrics
     */
    public function getAppMetrics($appName, $duration = '5m')
    {
        try {
            // Cache metrics for 30 seconds to reduce API calls
            return Cache::remember("fly_metrics_{$appName}_{$duration}", 30, function () use ($appName, $duration) {
                return [
                    'cpu' => $this->getInstanceCPU($appName, null, $duration),
                    'memory' => $this->getInstanceMemory($appName),
                    'network' => $this->getNetworkTraffic($appName, $duration),
                    'http' => $this->getHttpMetrics($appName, $duration),
                    'volumes' => $this->getVolumeMetrics($appName),
                    'timestamp' => now()->toIso8601String(),
                ];
            });
        } catch (Exception $e) {
            Log::error('Failed to fetch app metrics', [
                'app' => $appName,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Failed to fetch metrics',
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Get time series data for a metric
     */
    public function getMetricTimeSeries($query, $duration = '1h', $step = '1m')
    {
        $end = time();
        $start = $end - $this->parseDuration($duration);
        
        return $this->queryRange($query, $start, $end, $step);
    }

    /**
     * Parse query result
     */
    protected function parseQueryResult($result)
    {
        if ($result['status'] !== 'success') {
            throw new Exception('Query failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        $data = $result['data'] ?? [];
        
        if ($data['resultType'] === 'vector') {
            return $this->parseVectorResult($data['result']);
        } elseif ($data['resultType'] === 'matrix') {
            return $this->parseMatrixResult($data['result']);
        }

        return $data;
    }

    /**
     * Parse range query result
     */
    protected function parseRangeResult($result)
    {
        if ($result['status'] !== 'success') {
            throw new Exception('Range query failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        $data = $result['data'] ?? [];
        
        if ($data['resultType'] === 'matrix') {
            return $this->parseMatrixResult($data['result']);
        }

        return $data;
    }

    /**
     * Parse vector result
     */
    protected function parseVectorResult($results)
    {
        $parsed = [];
        
        foreach ($results as $result) {
            $metric = $result['metric'] ?? [];
            $value = $result['value'] ?? [];
            
            $parsed[] = [
                'labels' => $metric,
                'value' => floatval($value[1] ?? 0),
                'timestamp' => $value[0] ?? null,
            ];
        }

        return $parsed;
    }

    /**
     * Parse matrix result
     */
    protected function parseMatrixResult($results)
    {
        $parsed = [];
        
        foreach ($results as $result) {
            $metric = $result['metric'] ?? [];
            $values = $result['values'] ?? [];
            
            $series = [
                'labels' => $metric,
                'values' => [],
            ];
            
            foreach ($values as $value) {
                $series['values'][] = [
                    'timestamp' => $value[0],
                    'value' => floatval($value[1]),
                ];
            }
            
            $parsed[] = $series;
        }

        return $parsed;
    }

    /**
     * Calculate percentage from used and total metrics
     */
    protected function calculatePercentage($used, $total)
    {
        if (empty($used) || empty($total)) {
            return 0;
        }

        $usedValue = $used[0]['value'] ?? 0;
        $totalValue = $total[0]['value'] ?? 0;
        
        if ($totalValue == 0) {
            return 0;
        }

        return round(($usedValue / $totalValue) * 100, 2);
    }

    /**
     * Parse duration string to seconds
     */
    protected function parseDuration($duration)
    {
        $units = [
            's' => 1,
            'm' => 60,
            'h' => 3600,
            'd' => 86400,
        ];

        if (preg_match('/(\d+)([smhd])/', $duration, $matches)) {
            $value = intval($matches[1]);
            $unit = $matches[2];
            
            return $value * ($units[$unit] ?? 60);
        }

        return 300; // Default 5 minutes
    }

    /**
     * Get Grafana dashboard URL for the app
     */
    public function getGrafanaDashboardUrl($appName)
    {
        return "https://fly-metrics.net/d/fly-app/fly-app?var-app={$appName}&var-region=All";
    }

    /**
     * Get Grafana logs search URL
     */
    public function getGrafanaLogsUrl($appName)
    {
        return "https://fly-metrics.net/d/fly-logs/fly-logs?var-app={$appName}";
    }
}