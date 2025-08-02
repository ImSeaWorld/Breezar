<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FlyWebsocketService
{
    protected $apiToken;
    protected $websocketUrl = 'wss://fly.io/phx/live/websocket';
    
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
            throw new Exception('Fly.io API token not configured.');
        }
    }

    /**
     * Get websocket connection details for logs
     * This generates the necessary authentication info for connecting to Fly's websocket
     */
    public function getWebsocketConnectionDetails($appName, $instanceId = null)
    {
        try {
            // Get session/CSRF token if needed
            $sessionData = $this->getSessionToken();
            
            // Note: Fly.io's websocket authentication is not well documented
            // This attempts to match their web interface behavior
            return [
                'url' => $this->websocketUrl,
                'params' => [
                    'vsn' => '2.0.0',
                    '_csrf_token' => $sessionData['token'] ?? null,
                    'token' => $this->apiToken, // Try API token directly
                ],
                'channels' => [
                    // Phoenix channel format for Fly.io logs
                    "app_logs:{$appName}" => [
                        'topic' => "app_logs:{$appName}",
                        'params' => [
                            'region' => null,
                            'instance_id' => $instanceId,
                            'follow' => true,
                            'tail' => 100
                        ]
                    ]
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Origin' => 'https://fly.io',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ];
        } catch (Exception $e) {
            Log::error('Failed to get websocket connection details', [
                'error' => $e->getMessage(),
                'app' => $appName
            ]);
            
            // Return minimal config to allow client-side attempts
            return [
                'url' => $this->websocketUrl,
                'params' => [
                    'vsn' => '2.0.0',
                ],
                'apiToken' => $this->apiToken,
                'appName' => $appName
            ];
        }
    }

    /**
     * Get session token for websocket authentication
     * Note: This is a placeholder - Fly.io's exact auth flow may differ
     */
    protected function getSessionToken()
    {
        try {
            // Fly.io uses Phoenix LiveView which requires a CSRF token
            // This token is typically obtained from their web interface
            
            // First, get the main page to extract CSRF token
            $response = Http::withToken($this->apiToken)
                ->withHeaders([
                    'Accept' => 'text/html,application/xhtml+xml',
                    'User-Agent' => 'LMS-Manager/1.0'
                ])
                ->get('https://fly.io/apps');
            
            if ($response->successful()) {
                // Extract CSRF token from the response
                // Phoenix typically includes it in a meta tag or as part of the LiveView connection
                $html = $response->body();
                
                // Look for Phoenix LiveView session data
                preg_match('/<div[^>]*data-phx-session="([^"]+)"/', $html, $matches);
                $phxSession = $matches[1] ?? null;
                
                preg_match('/<meta[^>]*name="csrf-token"[^>]*content="([^"]+)"/', $html, $matches);
                $csrfToken = $matches[1] ?? null;
                
                return [
                    'token' => $csrfToken,
                    'session' => $phxSession,
                    'cookie' => $response->header('Set-Cookie')
                ];
            }
            
            throw new Exception('Failed to get session token from Fly.io');
        } catch (Exception $e) {
            Log::error('Failed to get Fly.io session token', [
                'error' => $e->getMessage()
            ]);
            
            // Return null token - connection might still work with just Bearer auth
            return ['token' => null];
        }
    }

    /**
     * Generate Phoenix channel join message
     */
    public function generateJoinMessage($topic, $params = [])
    {
        return [
            'topic' => $topic,
            'event' => 'phx_join',
            'payload' => $params,
            'ref' => uniqid()
        ];
    }

    /**
     * Generate Phoenix heartbeat message
     */
    public function generateHeartbeatMessage()
    {
        return [
            'topic' => 'phoenix',
            'event' => 'heartbeat',
            'payload' => new \stdClass(),
            'ref' => uniqid()
        ];
    }
}