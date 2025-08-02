<?php

namespace App\Http\Controllers;

use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Client;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ScriptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Script::with('creator');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $scripts = $query->withCount('executions')
                         ->orderBy('created_at', 'desc')
                         ->paginate(20);

        return Inertia::render('Scripts/Index', [
            'scripts' => $scripts,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Scripts/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:scripts',
            'description' => 'nullable|string',
            'tinker_code' => 'required|string',
        ]);

        $script = Script::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'tinker_code' => $validated['tinker_code'],
            'created_by' => auth()->id(),
        ]);

        ActivityLog::log('script_created', 'script', $script->id, [
            'script_name' => $script->name,
        ]);

        return redirect()->route('scripts.show', $script)
                       ->with('success', 'Script created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Script $script)
    {
        $script->load(['creator', 'executions' => function($query) {
            $query->with(['executor', 'client'])
                  ->latest('executed_at')
                  ->limit(10);
        }]);

        // Get clients for the execute dropdown
        $clients = auth()->user()->role === 'admin' 
            ? Client::all() 
            : auth()->user()->clients;

        return Inertia::render('Scripts/Show', [
            'script' => $script,
            'clients' => $clients,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Script $script)
    {
        return Inertia::render('Scripts/Edit', [
            'script' => $script,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Script $script)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:scripts,name,' . $script->id,
            'description' => 'nullable|string',
            'tinker_code' => 'required|string',
        ]);

        $script->update($validated);

        ActivityLog::log('script_updated', 'script', $script->id, [
            'script_name' => $script->name,
            'changes' => $request->only(['name', 'description']),
        ]);

        return redirect()->route('scripts.show', $script)
                       ->with('success', 'Script updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Script $script)
    {
        ActivityLog::log('script_deleted', 'script', $script->id, [
            'script_name' => $script->name,
        ]);

        $script->delete();

        return redirect()->route('scripts.index')
                       ->with('success', 'Script deleted successfully');
    }

    /**
     * Execute a script
     */
    public function execute(Request $request, Script $script)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
        ]);

        try {
            // Build the tinker command
            $code = $script->tinker_code;
            
            // If a client is specified, inject it as a variable
            if (isset($validated['client_id'])) {
                $clientId = $validated['client_id'];
                $code = "\$client = \\App\\Models\\Client::find({$clientId});\n" . $code;
            }

            // Create a temporary file with the code
            $tempFile = tempnam(sys_get_temp_dir(), 'tinker_script_');
            file_put_contents($tempFile, $code);

            // Execute the tinker command
            $process = new Process([
                'php',
                base_path('artisan'),
                'tinker',
                '--execute',
                "include '{$tempFile}'"
            ]);

            $process->setTimeout(30); // 30 second timeout
            $process->run();

            // Clean up temp file
            unlink($tempFile);

            // Get the output
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $fullOutput = $output . ($errorOutput ? "\n\nErrors:\n" . $errorOutput : '');

            // Record the execution
            $execution = ScriptExecution::create([
                'script_id' => $script->id,
                'executed_by' => auth()->id(),
                'client_id' => $validated['client_id'] ?? null,
                'output' => $fullOutput,
                'executed_at' => now(),
            ]);

            // Update last run time
            $script->update(['last_run_at' => now()]);

            ActivityLog::log('script_executed', 'script', $script->id, [
                'script_name' => $script->name,
                'client_id' => $validated['client_id'] ?? null,
                'success' => $process->isSuccessful(),
            ]);

            if (!$process->isSuccessful()) {
                return back()->with('error', 'Script execution failed: ' . $errorOutput);
            }

            return back()->with('success', 'Script executed successfully')
                        ->with('execution_output', $fullOutput);

        } catch (\Exception $e) {
            ActivityLog::log('script_execution_failed', 'script', $script->id, [
                'script_name' => $script->name,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Script execution failed: ' . $e->getMessage());
        }
    }
}
