<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    /**
     * Get current app version info
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkVersion(Request $request)
    {
        // Get client version from request
        $clientVersion = $request->input('version', '0.0.0');
        $platform = $request->input('platform', 'android'); // android or ios
        
        // SIMPLE: Only ONE version allowed at a time
        $config = [
            'android' => [
                'current_version' => '1.0.2',  // ← Change this when you release new version (MUST match app.json)
                'download_url' => 'https://play.google.com/store/apps/details?id=com.careourclaim.app',
                'update_message' => 'Please update to the latest version to continue using the app.',
            ],
            'ios' => [
                'current_version' => '1.0.2',  // ← Change this when you release new version (MUST match app.json)
                'download_url' => 'https://apps.apple.com/app/careourclaim/id123456789',
                'update_message' => 'Please update to the latest version to continue using the app.',
            ],
        ];
        
        $platformConfig = $config[$platform] ?? $config['android'];
        
        // Simple check: Client version MUST match backend version
        $needsUpdate = $clientVersion !== $platformConfig['current_version'];
        
        return response()->json([
            'success' => true,
            'app_version' => $clientVersion,
            'server_version' => $platformConfig['current_version'],
            'needs_update' => $needsUpdate,
            'force_update' => $needsUpdate, // Always force if version mismatch
            'download_url' => $platformConfig['download_url'],
            'update_message' => $platformConfig['update_message'],
            'features' => [
                'Latest chat features with WhatsApp-style UI',
                'Bug fixes and performance improvements',
                'Enhanced security and stability',
            ],
        ]);
    }
    
    /**
     * Admin: Update app version config (optional - for dynamic control)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateConfig(Request $request)
    {
        // Only admin can update
        $user = $request->user();
        if (!in_array('1', explode(',', $user->role_id))) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'platform' => 'required|in:android,ios',
            'latest_version' => 'required|string',
            'minimum_version' => 'required|string',
            'force_update' => 'boolean',
        ]);
        
        // Store in config file or database
        // For now, returning success - you can implement storage as needed
        
        return response()->json([
            'success' => true,
            'message' => 'Version config updated successfully',
        ]);
    }
}

