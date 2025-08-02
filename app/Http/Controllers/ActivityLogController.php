<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by resource type
        if ($request->filled('resource_type')) {
            $query->where('resource_type', $request->resource_type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in metadata
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('resource_type', 'like', "%{$search}%")
                  ->orWhere('metadata', 'like', "%{$search}%");
            });
        }

        $activities = $query->orderBy('created_at', 'desc')
                           ->paginate(50);

        // Get unique actions and resource types for filters
        $actions = ActivityLog::distinct()->pluck('action')->sort()->values()->toArray();
        $resourceTypes = ActivityLog::distinct()->pluck('resource_type')->sort()->values()->toArray();
        $users = User::orderBy('name')->get();

        return Inertia::render('Activity/Index', [
            'activities' => $activities,
            'actions' => $actions,
            'resourceTypes' => $resourceTypes,
            'users' => $users,
            'filters' => $request->only(['user_id', 'action', 'resource_type', 'date_from', 'date_to', 'search']),
        ]);
    }
}
