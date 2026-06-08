<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        if (!$deviceToken) return false;

        try {
            // Lazily resolve messaging so missing credentials don't crash callers
            $messaging = app(\Kreait\Firebase\Contract\Messaging::class);

            $message = CloudMessage::new()
                ->withToken($deviceToken)
                ->withNotification(Notification::create($title, $body))
                ->withData(array_map('strval', $data));

            $messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            Log::warning('FCM send skipped/failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
