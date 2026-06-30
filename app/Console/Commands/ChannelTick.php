<?php

namespace App\Console\Commands;

use App\Services\ChannelBotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ChannelTick extends Command
{
    protected $signature   = 'channel:tick {--now= : Darhol post tashlash (texno|hobby), reja kutmasdan}';
    protected $description = 'Channel bot: kun boshida random vaqtlarni rejalaydi, vaqt kelganda post tashlaydi';

    public function handle(ChannelBotService $bot): int
    {
        // Qo'lda test: --now=texno
        if ($type = $this->option('now')) {
            $ok = $bot->generateAndSend($type);
            $this->line($ok ? "✅ {$type} post tashlandi" : "❌ Xato (config/log tekshiring)");
            return self::SUCCESS;
        }

        $start = (int) config('channelbot.window_start', 9);
        $end   = (int) config('channelbot.window_end', 18);
        $now   = Carbon::now();

        if ($now->hour < $start || $now->hour >= $end) {
            return self::SUCCESS; // oyna tashqarisida
        }

        $today    = $now->toDateString();
        $planKey  = "channelbot_plan_{$today}";
        $sentKey  = "channelbot_sent_{$today}";

        // Kunlik reja: har tur uchun random daqiqa (oyna ichida)
        $plan = Cache::get($planKey);
        if (!$plan) {
            $plan = [];
            foreach (config('channelbot.daily_posts', ['texno', 'hobby']) as $type) {
                $h = random_int($start, $end - 1);
                $m = random_int(0, 59);
                $plan[$type] = sprintf('%02d:%02d', $h, $m);
            }
            Cache::put($planKey, $plan, now()->endOfDay());
        }

        $sent = Cache::get($sentKey, []);

        foreach ($plan as $type => $time) {
            if (in_array($type, $sent, true)) continue;

            [$h, $m] = explode(':', $time);
            $target  = $now->copy()->setTime((int) $h, (int) $m);

            if ($now->greaterThanOrEqualTo($target)) {
                if ($bot->generateAndSend($type)) {
                    $sent[] = $type;
                    Cache::put($sentKey, $sent, now()->endOfDay());
                    $this->line("✅ {$type} post tashlandi ({$time})");
                }
            }
        }

        return self::SUCCESS;
    }
}
