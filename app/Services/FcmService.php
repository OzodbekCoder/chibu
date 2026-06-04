<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $serverKey = env('FCM_SERVER_KEY', '');
        if (!$serverKey || !$deviceToken) return false;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                ],
                'data'    => $data,
                'priority' => 'high',
            ]);

            if (!$response->successful()) {
                Log::warning('FCM send failed', ['status' => $response->status(), 'body' => $response->body()]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FCM exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
