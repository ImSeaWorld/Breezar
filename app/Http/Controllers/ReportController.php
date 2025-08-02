<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientReport;
use App\Models\ClientInstance;
use App\Models\ActivityLog;
use App\Services\FlyApi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $flyApi;

    public function __construct(FlyApi $flyApi)
    {
        $this->flyApi = $flyApi;
    }

    public function index(Request $request)
    {
        $query = ClientReport::with('client');

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

        if ($request->filled('month')) {
            $query->whereYear('report_month', Carbon::parse($request->month)->year)
                  ->whereMonth('report_month', Carbon::parse($request->month)->month);
        }

        $reports = $query->orderBy('report_month', 'desc')
                         ->orderBy('generated_at', 'desc')
                         ->paginate(20);

        // Get clients for filter dropdown
        $clients = auth()->user()->role === 'admin' 
            ? Client::all() 
            : auth()->user()->clients;

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'clients' => $clients,
            'filters' => $request->only(['client_id', 'type', 'month']),
        ]);
    }

    public function show(ClientReport $report)
    {
        // Check access
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($report->client_id)) {
            abort(403, 'Unauthorized');
        }

        $report->load('client');

        ActivityLog::log('report_viewed', 'report', $report->id, [
            'report_type' => $report->type,
            'client_name' => $report->client->name
        ]);

        return Inertia::render('Reports/Show', [
            'report' => $report,
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:work_items,custom,usage,performance',
            'month' => 'required|date_format:Y-m',
        ]);

        // Check access
        $client = Client::findOrFail($request->client_id);
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($client->id)) {
            abort(403, 'Unauthorized');
        }

        try {
            $reportData = $this->generateReportData($client, $request->type, $request->month);

            $report = ClientReport::create([
                'client_id' => $client->id,
                'type' => $request->type,
                'report_month' => Carbon::parse($request->month . '-01'),
                'data' => $reportData,
                'generated_at' => now(),
            ]);

            ActivityLog::log('report_generated', 'report', $report->id, [
                'report_type' => $request->type,
                'client_name' => $client->name,
                'month' => $request->month
            ]);

            return redirect()->route('reports.show', $report)
                           ->with('success', 'Report generated successfully');
        } catch (\Exception $e) {
            ActivityLog::log('report_generation_failed', 'client', $client->id, [
                'report_type' => $request->type,
                'month' => $request->month,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    protected function generateReportData(Client $client, $type, $month)
    {
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        switch ($type) {
            case 'usage':
                return $this->generateUsageReport($client, $startDate, $endDate);
            
            case 'performance':
                return $this->generatePerformanceReport($client, $startDate, $endDate);
            
            case 'work_items':
                return $this->generateWorkItemsReport($client, $startDate, $endDate);
            
            case 'custom':
                return $this->generateCustomReport($client, $startDate, $endDate);
            
            default:
                throw new \Exception('Invalid report type');
        }
    }

    protected function generateUsageReport(Client $client, $startDate, $endDate)
    {
        $instances = $client->instances;
        $usageData = [];

        foreach ($instances as $instance) {
            try {
                // Get metrics from Fly.io
                $metrics = $this->flyApi->getMetrics($instance->fly_app_id, $startDate, $endDate);
                
                $usageData['instances'][] = [
                    'app_id' => $instance->fly_app_id,
                    'type' => $instance->type,
                    'region' => $instance->region,
                    'cpu_usage' => $metrics['cpu_usage'] ?? [],
                    'memory_usage' => $metrics['memory_usage'] ?? [],
                    'network_io' => $metrics['network_io'] ?? [],
                    'uptime' => $metrics['uptime'] ?? 0,
                ];
            } catch (\Exception $e) {
                $usageData['instances'][] = [
                    'app_id' => $instance->fly_app_id,
                    'error' => 'Failed to fetch metrics: ' . $e->getMessage(),
                ];
            }
        }

        $usageData['summary'] = [
            'total_instances' => count($instances),
            'active_instances' => $instances->where('status', 'running')->count(),
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ];

        return $usageData;
    }

    protected function generatePerformanceReport(Client $client, $startDate, $endDate)
    {
        $instances = $client->instances;
        $performanceData = [];

        foreach ($instances as $instance) {
            try {
                // Get performance metrics
                $metrics = $this->flyApi->getMetrics($instance->fly_app_id, $startDate, $endDate);
                
                $performanceData['instances'][] = [
                    'app_id' => $instance->fly_app_id,
                    'type' => $instance->type,
                    'avg_response_time' => $metrics['avg_response_time'] ?? 0,
                    'error_rate' => $metrics['error_rate'] ?? 0,
                    'requests_per_minute' => $metrics['requests_per_minute'] ?? 0,
                    'availability' => $metrics['availability'] ?? 100,
                ];
            } catch (\Exception $e) {
                $performanceData['instances'][] = [
                    'app_id' => $instance->fly_app_id,
                    'error' => 'Failed to fetch performance data: ' . $e->getMessage(),
                ];
            }
        }

        $performanceData['summary'] = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'total_instances' => count($instances),
        ];

        return $performanceData;
    }

    protected function generateWorkItemsReport(Client $client, $startDate, $endDate)
    {
        // Get activity logs for this client during the period
        $activities = ActivityLog::where('created_at', '>=', $startDate)
                                ->where('created_at', '<=', $endDate)
                                ->where(function($query) use ($client) {
                                    $query->where('resource_type', 'client')
                                          ->where('resource_id', $client->id)
                                          ->orWhere(function($q) use ($client) {
                                              $instanceIds = $client->instances->pluck('id');
                                              $q->where('resource_type', 'instance')
                                                ->whereIn('resource_id', $instanceIds);
                                          });
                                })
                                ->with('user')
                                ->get();

        $workItems = [];
        $summary = [
            'total_activities' => $activities->count(),
            'by_action' => [],
            'by_user' => [],
        ];

        foreach ($activities as $activity) {
            $workItems[] = [
                'date' => $activity->created_at->format('Y-m-d H:i:s'),
                'user' => $activity->user->name,
                'action' => $activity->action,
                'description' => $this->formatActivityDescription($activity),
            ];

            // Count by action
            $summary['by_action'][$activity->action] = ($summary['by_action'][$activity->action] ?? 0) + 1;
            
            // Count by user
            $summary['by_user'][$activity->user->name] = ($summary['by_user'][$activity->user->name] ?? 0) + 1;
        }

        return [
            'work_items' => $workItems,
            'summary' => $summary,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ];
    }

    protected function generateCustomReport(Client $client, $startDate, $endDate)
    {
        // Custom report can be tailored to specific client needs
        return [
            'client_name' => $client->name,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'instances' => $client->instances->map(function($instance) {
                return [
                    'app_id' => $instance->fly_app_id,
                    'type' => $instance->type,
                    'status' => $instance->status,
                    'region' => $instance->region,
                ];
            }),
            'billing_info' => [
                'billing_start_date' => $client->billing_start_date,
                'months_active' => $client->billing_start_date 
                    ? Carbon::parse($client->billing_start_date)->diffInMonths(now())
                    : 0,
            ],
            'contact_info' => $client->contact_info,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    protected function formatActivityDescription($activity)
    {
        $descriptions = [
            'client_created' => 'Created client',
            'client_updated' => 'Updated client details',
            'instance_restarted' => 'Restarted instance',
            'instance_stopped' => 'Stopped instance',
            'instance_started' => 'Started instance',
            'fly_sync_completed' => 'Synchronized Fly.io instances',
        ];

        return $descriptions[$activity->action] ?? $activity->action;
    }
}
