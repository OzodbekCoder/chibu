<?php

namespace App\Telegraph\Traits;

use App\Models\CurrencyRate;
use App\Models\TelegraphChat;
use Carbon\Carbon;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

trait HandlesSettings
{
    protected function menuSettings(): void
    {
        $rate     = CurrencyRate::latestYuan();
        $rateText = $rate
            ? number_format((float) $rate->rate, 2) . " so'm  ({$rate->rate_date})"
            : "kiritilmagan";

        $this->chat->html("⚙️ <b>Sozlamalar</b>\n\n💴 Yuan kursi (CNY → UZS): <b>{$rateText}</b>")
            ->keyboard(
                Keyboard::make()
                    ->row([Button::make("✏️ Yuan kursini yangilash")->action('settingsSetYuanRate')])
                    ->row([Button::make("⬅️ Orqaga")->action('menu')->param('a', 'back')])
            )
            ->send();
    }

    public function settingsSetYuanRate(): void
    {
        $botUser = $this->getOrCreateBotUser();
        if ($this->messageId) {
            $this->chat->deleteMessage($this->messageId)->send();
        }

        $this->setState($botUser, 'settings.yuan_rate', $botUser->payload ?? []);
        $this->chat->html("💴 <b>Yuan kursi</b>\n\n1 CNY = ? so'm\nMasalan: <code>2150</code>")->send();
    }

    protected function stepYuanRate(TelegraphChat $botUser, string $text): void
    {
        $value = (float) str_replace([',', ' '], '.', trim($text));
        if ($value <= 0) {
            $this->chat->html("❌ Noto'g'ri qiymat. Raqam kiriting. Masalan: <code>2150</code>")->send();
            return;
        }

        CurrencyRate::query()->updateOrCreate(
            ['base' => 'CNY', 'quote' => 'UZS', 'rate_date' => Carbon::today()->toDateString()],
            ['rate' => $value, 'created_by_id' => $botUser->id]
        );

        $this->clearState($botUser);
        $this->chat->html("✅ Yuan kursi saqlandi: <b>1 CNY = " . number_format($value, 2) . " so'm</b>")->send();
        $this->menuSettings();
    }
}
