<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    public function __construct(private Messaging $messaging) {}

    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        if (!$deviceToken) return false;

        try {
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(Notification::create($title, $body))
                ->withData(array_map('strval', $data));

            $this->messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            Log::error('FCM send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
