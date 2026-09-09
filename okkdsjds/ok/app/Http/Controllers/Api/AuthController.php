<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ExpoNotificationService;

class AuthController extends Controller
{
    public function login(Request $request)
    {

        Log::info("Api");
        Log::info($request);
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }


    public function userInfo(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    public function saveExpoToken(Request $request)
    {
        $request->validate([
            'expo_token' => 'required|string',
        ]);
        $user = $request->user();
        $user->expo_token = $request->expo_token;
        $user->save();
        return response()->json(['message' => 'Expo token saved successfully', 'expo_token' => $user->expo_token]);
    }

    public function sendExpoNotification(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'title' => 'required|string',
            'description' => 'required|string',
        ]);
        $result = ExpoNotificationService::send($request->user_ids, $request->title, $request->description);
        return response()->json($result);
    }
}
