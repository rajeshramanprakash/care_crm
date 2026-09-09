<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class ExpoNotificationService
{
    /**
     * Send push notification to given user IDs via Expo
     * @param array $userIds
     * @param string $title
     * @param string $description
     * @return array
     */
    public static function send(array $userIds, string $title, string $description): array
    {
        $users = User::whereIn('id', $userIds)->whereNotNull('expo_token')->pluck('expo_token')->toArray();
        if (empty($users)) {
            return ['success' => false, 'message' => 'No users with expo tokens found.'];
        }
        $messages = [];
        foreach ($users as $token) {
            $messages[] = [
                'to' => $token,
                'sound' => 'default',
                'title' => $title,
                'body' => $description,
            ];
        }
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://exp.host/--/api/v2/push/send', $messages);
        return [
            'success' => $response->successful(),
            'response' => $response->json(),
        ];
    }
}
