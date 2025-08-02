<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\ActivityLog;
use App\Services\FlyApi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index()
    {
        // Get all settings grouped by group
        $settings = Setting::getAllGrouped();

        ActivityLog::log('settings_viewed', null);

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => 'required|in:string,boolean,json,encrypted',
        ]);

        $updatedSettings = [];

        foreach ($validated['settings'] as $settingData) {
            $setting = Setting::where('key', $settingData['key'])->first();
            
            if ($setting) {
                // Only update if value has changed
                $oldValue = $setting->decrypted_value;
                $newValue = $settingData['value'];
                
                if ($oldValue !== $newValue) {
                    $setting->value = $newValue;
                    $setting->save();
                    
                    $updatedSettings[] = $settingData['key'];
                }
            }
        }

        if (count($updatedSettings) > 0) {
            ActivityLog::log('settings_updated', null, [
                'updated_keys' => $updatedSettings
            ]);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'api_token' => 'required|string',
            'org_id' => 'required|string',
        ]);

        try {
            // Create a temporary FlyApi instance with the provided credentials
            $flyApi = new FlyApi($validated['api_token']);
            
            // Try to fetch organizations to test the connection
            $result = $flyApi->testConnection();
            
            if ($result['success']) {
                // Check if the provided org_id exists
                $orgExists = false;
                foreach ($result['organizations'] as $org) {
                    if ($org['id'] === $validated['org_id']) {
                        $orgExists = true;
                        break;
                    }
                }
                
                if (!$orgExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Organization ID not found. Available organizations: ' . 
                                   implode(', ', array_column($result['organizations'], 'id'))
                    ]);
                }
                
                ActivityLog::log('fly_connection_tested', null, [
                    'success' => true,
                    'org_id' => $validated['org_id']
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful! API token is valid and organization found.',
                    'organizations' => $result['organizations']
                ]);
            } else {
                throw new \Exception($result['error']);
            }
        } catch (\Exception $e) {
            ActivityLog::log('fly_connection_test_failed', null, [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ], 422);
        }
    }
}
