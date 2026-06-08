<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\ShipmentNotification;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Console\Command;

class TestPush extends Command
{
    protected $signature   = 'notif:test {userId? : User id to test}';
    protected $description = 'Diagnose push: show device tokens, send a test FCM push';

    public function handle(FcmService $fcm): int
    {
        // Overview
        $this->info('=== Device tokens ===');
        $tokens = DeviceToken::all();
        if ($tokens->isEmpty()) {
            $this->warn('No device tokens registered! Push impossible.');
            $this->line('Fix: open the native app (push permission granted) so it POSTs /app/device-token');
        }
        foreach ($tokens as $t) {
            $this->line("user_id={$t->user_id}  token=" . substr($t->token, 0, 24) . '...');
        }

        $this->info('=== Firebase credentials ===');
        $credPath = config('firebase.projects.app.credentials');
        $this->line('path: ' . $credPath);
        $this->line('exists: ' . (is_string($credPath) && file_exists($credPath) ? 'YES' : 'NO'));

        $this->info('=== Recent notifications (5) ===');
        foreach (ShipmentNotification::latest()->limit(5)->get() as $n) {
            $this->line("#{$n->id} user={$n->user_id} {$n->track_code} {$n->new_status} read=" . ($n->is_read ? '1' : '0'));
        }

        // Send test
        $userId = $this->argument('userId');
        if (!$userId) {
            $userId = $tokens->first()?->user_id;
        }
        if (!$userId) {
            $this->error('No userId / token to test send.');
            return self::FAILURE;
        }

        $token = DeviceToken::forUser((int) $userId);
        if (!$token) {
            $this->error("User {$userId} has no device token.");
            return self::FAILURE;
        }

        $this->info("=== Sending test push to user {$userId} (raw, shows real error) ===");
        try {
            $messaging = app(\Kreait\Firebase\Contract\Messaging::class);
            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
                ->withNotification(\Kreait\Firebase\Messaging\Notification::create('CHIBU test', 'Sinov ✅'))
                ->withData(['type' => 'test']);
            $messaging->send($message);
            $this->info('✅ Push yuborildi');
        } catch (\Throwable $e) {
            $this->error('❌ ' . get_class($e));
            $this->line($e->getMessage());
        }

        return self::SUCCESS;
    }
}
