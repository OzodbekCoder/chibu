<?php

namespace App\Telegraph\Traits;

use App\Services\ReportService;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

trait HandlesReports
{
    protected function menuReport(): void
    {
        $this->chat->html("📊 <b>Hisobot</b>\n\nHisobot turini tanlang:")
            ->keyboard(
                Keyboard::make()
                    ->row([
                        Button::make("📅 Kunlik")->action('reportExport')->param('period', 'day'),
                        Button::make("📆 Haftalik")->action('reportExport')->param('period', 'week'),
                        Button::make("🗓 Oylik")->action('reportExport')->param('period', 'month'),
                    ])
                    ->row([Button::make("⬅️ Orqaga")->action('menu')->param('a', 'back')])
            )
            ->send();
    }

    public function reportExport(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $period  = $this->data->get('period') ?? 'day';

        if ($this->messageId) {
            $this->chat->deleteMessage($this->messageId)->send();
        }

        $this->chat->html("⏳ Hisobot tayyorlanmoqda...")->send();

        $info = (new ReportService())->generate($botUser->id, $period, (string) $this->chat->chat_id);

        $periodLabel = match ($period) {
            'week'  => 'Haftalik',
            'month' => 'Oylik',
            default => 'Kunlik',
        };

        $this->chat->document($info['path'])
            ->html("📊 <b>{$periodLabel} hisobot</b>\n{$info['count']} ta yuk · {$info['from']->format('d.m.Y')} — {$info['to']->format('d.m.Y')}")
            ->send();

        @unlink($info['path']);
        $this->sendMainMenu();
    }
}
