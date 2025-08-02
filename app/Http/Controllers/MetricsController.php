<?php

namespace App\Http\Controllers;

use App\Models\ClientInstance;
use App\Services\FlyMetricsApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetricsController extends Controller
{
    /**
     * Get metrics for an instance
     */
    public function getInstanceMetrics(Request $request, ClientInstance $instance)
    {
        // Check access
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($instance->client_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $duration = $request->get('duration', '5m');

        try {
            // Get client-specific metrics API if available
            $metricsApi = FlyMetricsApi::forClient($instance->client);
            
            // Get comprehensive metrics
            $metrics = $metricsApi->getAppMetrics($instance->fly_app_id, $duration);
            
            return response()->json($metrics);
        } catch (\Exception $e) {
            Log::error('Failed to fetch instance metrics', [
                'instance' => $instance->fly_app_id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get time series data for a specific metric
     */
    public function getMetricTimeSeries(Request $request, ClientInstance $instance)
    {
        // Check access
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($instance->client_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $metric = $request->get('metric');
        $duration = $request->get('duration', '1h');
        $step = $request->get('step', '1m');

        if (!$metric) {
            return response()->json(['error' => 'Metric parameter is required'], 400);
        }

        try {
            $metricsApi = FlyMetricsApi::forClient($instance->client);
            
            // Build query based on metric type
            $query = $this->buildMetricQuery($metric, $instance->fly_app_id);
            
            // Get time series data
            $data = $metricsApi->getMetricTimeSeries($query, $duration, $step);
            
            return response()->json([
                'metric' => $metric,
                'data' => $data,
                'query' => $query,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch metric time series', [
                'instance' => $instance->fly_app_id,
                'metric' => $metric,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch time series data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build metric query based on type
     */
    protected function buildMetricQuery($metric, $appName)
    {
        $queries = [
            'cpu' => "avg(rate(fly_instance_cpu{app=\"{$appName}\"}[1m])) * 100",
            'memory' => "(fly_instance_memory_mem_total{app=\"{$appName}\"} - fly_instance_memory_mem_available{app=\"{$appName}\"}) / fly_instance_memory_mem_total{app=\"{$appName}\"} * 100",
            'network_rx' => "rate(fly_instance_net_recv_bytes{app=\"{$appName}\"}[1m])",
            'network_tx' => "rate(fly_instance_net_sent_bytes{app=\"{$appName}\"}[1m])",
            'http_requests' => "sum(rate(fly_edge_http_responses_count{app=\"{$appName}\"}[1m]))",
            'http_errors' => "sum(rate(fly_edge_http_responses_count{app=\"{$appName}\",status=~\"5..\"}[1m]))",
            'load_average' => "fly_instance_load_average{app=\"{$appName}\"}",
            'disk_usage' => "fly_instance_disk_used_pct{app=\"{$appName}\"}",
        ];

        return $queries[$metric] ?? "fly_instance_cpu{app=\"{$appName}\"}";
    }

    /**
     * Get Grafana URLs for the instance
     */
    public function getGrafanaUrls(ClientInstance $instance)
    {
        // Check access
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($instance->client_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $metricsApi = FlyMetricsApi::forClient($instance->client);
            
            return response()->json([
                'dashboard' => $metricsApi->getGrafanaDashboardUrl($instance->fly_app_id),
                'logs' => $metricsApi->getGrafanaLogsUrl($instance->fly_app_id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate Grafana URLs',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}