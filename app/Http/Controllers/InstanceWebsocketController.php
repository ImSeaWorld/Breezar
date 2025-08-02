<?php

namespace App\Http\Controllers;

use App\Models\ClientInstance;
use App\Services\FlyWebsocketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstanceWebsocketController extends Controller
{
    /**
     * Get websocket connection details for an instance
     */
    public function getWebsocketDetails(Request $request)
    {
        $appName = $request->input('app_name');
        $instanceId = $request->input('instance_id');
        
        // Validate user has access to this app
        $instance = ClientInstance::where('fly_app_id', $appName)->first();
        
        if (!$instance) {
            return response()->json(['error' => 'Instance not found'], 404);
        }
        
        // Check access
        if (auth()->user()->role === 'manager' && !auth()->user()->clients->contains($instance->client_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        try {
            // Get client-specific API token if available
            $apiToken = null;
            if ($instance->client->fly_api_token && !$instance->client->isFlyTokenExpired()) {
                $apiToken = $instance->client->decrypted_fly_api_token;
            }
            
            $websocketService = new FlyWebsocketService($apiToken);
            $details = $websocketService->getWebsocketConnectionDetails($appName, $instanceId);
            
            return response()->json($details);
        } catch (\Exception $e) {
            Log::error('Failed to get websocket details', [
                'app' => $appName,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to get websocket connection details'
            ], 500);
        }
    }
}