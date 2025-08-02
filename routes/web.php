<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MetricsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // If authenticated, redirect to dashboard
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    
    // Otherwise redirect to login
    return redirect()->route('login');
});

Route::middleware(['auth', '2fa'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/refresh', [App\Http\Controllers\DashboardController::class, 'refreshStats'])->name('dashboard.refresh');

    // Client Management
    Route::resource('clients', App\Http\Controllers\ClientController::class);
    
    // Instance Management
    Route::get('/instances', [App\Http\Controllers\InstanceController::class, 'index'])->name('instances.index');
    Route::get('/instances/{instance}', [App\Http\Controllers\InstanceController::class, 'show'])->name('instances.show');
    Route::post('/instances/{instance}/restart', [App\Http\Controllers\InstanceController::class, 'restart'])->name('instances.restart');
    Route::post('/instances/{instance}/stop', [App\Http\Controllers\InstanceController::class, 'stop'])->name('instances.stop');
    Route::post('/instances/{instance}/start', [App\Http\Controllers\InstanceController::class, 'start'])->name('instances.start');
    Route::post('/instances/{instance}/console', [App\Http\Controllers\InstanceController::class, 'console'])->name('instances.console');
    
    // Metrics
    Route::get('/instances/{instance}/metrics', [MetricsController::class, 'getInstanceMetrics'])->name('instances.metrics');
    Route::get('/instances/{instance}/metrics/timeseries', [MetricsController::class, 'getMetricTimeSeries'])->name('instances.metrics.timeseries');
    Route::get('/instances/{instance}/grafana-urls', [MetricsController::class, 'getGrafanaUrls'])->name('instances.grafana-urls');
    
    // Reports
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [App\Http\Controllers\ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/generate', [App\Http\Controllers\ReportController::class, 'generate'])->name('reports.generate');
    
    // Admin-only routes
    Route::middleware(['role:admin'])->group(function () {
        // User Management
        Route::resource('users', App\Http\Controllers\UserController::class);
        
        // Scripts
        Route::resource('scripts', App\Http\Controllers\ScriptController::class);
        Route::post('/scripts/{script}/execute', [App\Http\Controllers\ScriptController::class, 'execute'])->name('scripts.execute');
        
        // Activity Logs
        Route::get('/activity', [App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity.index');
        
        // Fly.io Sync
        Route::get('/fly-sync', [App\Http\Controllers\FlySyncController::class, 'index'])->name('fly-sync');
        Route::post('/fly-sync/run', [App\Http\Controllers\FlySyncController::class, 'run'])->name('fly-sync.run');
        
        // Settings
        Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-connection', [App\Http\Controllers\SettingsController::class, 'testConnection'])->name('settings.test-connection');
    });
});

require __DIR__ . '/auth.php';
