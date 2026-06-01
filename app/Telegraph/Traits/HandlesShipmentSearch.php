<?php

namespace App\Telegraph\Traits;

use App\Models\CurrencyRate;
use App\Models\Shipment;
use App\Models\TelegraphChat;
use App\Services\IpostService;

trait HandlesShipmentSearch
{
    protected function menuSearchShipment(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $this->setState($botUser, 'shipment.search.track', []);

        $this->chat->html("🔍 <b>Qidirish</b>\n\nTrek kodni yuboring")
            ->keyboard($this->backKeyboard())
            ->send();
    }

    protected function stepSearchTrack(TelegraphChat $botUser, string $text): void
    {
        $track = $this->normalizeTrack($text);

        if (mb_strlen($track) < 3) {
            $this->chat->html("❌ Trek kod juda qisqa. Qaytadan yuboring.")->send();
            return;
        }

        $shipments = Shipment::with('client')
            ->where('created_by_id', $botUser->id)
            ->where('track_code', 'like', '%' . $track . '%')
            ->latest()
            ->limit(5)
            ->get();

        if ($shipments->isEmpty()) {
            $this->chat->html("❌ <b>Topilmadi</b>\n\n<code>{$track}</code> bo'yicha yuk yo'q.\n\nBoshqa trek kodni yuboring:")
                ->keyboard($this->backKeyboard())
                ->send();
            return;
        }

        $statusLabel = [
            'CREATED'         => '🆕 Yaratildi',
            'CHINA_WAREHOUSE' => '🏭 Xitoy ombori',
            'ON_THE_WAY'      => '🚛 Yo\'lda',
            'CUSTOMS'         => '📋 Bojxona',
            'DELIVERED'       => '✅ Yetkazildi',
            'CANCELLED'       => '❌ Bekor',
        ];

        $deliveryLabel = [
            'avia'  => '✈️ Avia',
            'avto'  => '🚛 Avto',
            'sea'   => '🚢 Daryo',
            'other' => '📦 Boshqa',
        ];

        $ipostMap = (new IpostService())->fetchAllByTrack((string) $this->chat->chat_id);
        $yuanRate = (float) (CurrencyRate::latestYuan()?->rate ?? 0);

        $iStatusMap = [
            'Warehouse' => '🏭 Xitoy ombori',
            'Ulugchat'  => '🛂 Xitoy chegara punkti',
            'Osh'       => "🏔 O'zbekiston chegara punkti",
            'DropZone'  => '📍 Qabul qilish punkti',
            'Delivered' => '✅ Qabul qilindi',
            'CREATED'   => '🆕 Yangi yaratildi',
            'Yiwu'      => "🚀 Xitoydan yo'lga chiqdi",
        ];
        $iPayMap = [
            'PAID'      => "✅ To'landi",
            'UN_BILLED' => "⏳ To'lanmadi",
        ];

        $count      = $shipments->count();
        $resultText = "🔍 <b>Natija</b> ({$count} ta):\n━━━━━━━━━━━━━━━━━━\n\n";

        foreach ($shipments as $shipment) {
            $amountText = match ($shipment->tariff_type) {
                'kg'    => ($shipment->weight_kg ?? 0) . ' kg',
                'm3'    => ($shipment->volume_m3 ?? 0) . ' m³',
                'piece' => ($shipment->pieces ?? 0) . ' dona',
                default => '-',
            };

            $status   = $statusLabel[$shipment->status] ?? $shipment->status;
            $delivery = $deliveryLabel[$shipment->delivery_type] ?? $shipment->delivery_type;
            $client   = $shipment->client?->name ?? '—';
            $yuan     = $shipment->price_yuan ? number_format((float) $shipment->price_yuan, 2) . ' ¥' : '—';
            $link     = $shipment->order_url
                ? "\n🔗 <a href=\"{$shipment->order_url}\">" . mb_strimwidth($shipment->order_url, 0, 40, '…') . '</a>'
                : '';

            $resultText .= "📦 <b>#{$shipment->id}</b> · <code>{$shipment->track_code}</code>\n";
            $resultText .= "👤 {$client}  💴 {$yuan}{$link}\n";
            $resultText .= "⚖️ {$amountText}  {$delivery}\n";
            $resultText .= "📊 {$status}  ·  📅 {$shipment->created_at->format('d.m.Y H:i:s')}\n";

            $ii = $ipostMap[mb_strtoupper($shipment->track_code)] ?? null;
            if ($ii) {
                $rawStatus  = $ii['status'] ?? '';
                $iStatus    = $iStatusMap[$rawStatus] ?? ('🏭 ' . $rawStatus);
                $rawPay     = $ii['payStatus'] ?? '';
                $iPayLabel  = $iPayMap[$rawPay] ?? $rawPay;
                $iPaySom    = (int) ($ii['payAmountSom'] ?? 0);
                $iPay       = $iPaySom > 0 ? number_format($iPaySom) . " so'm" : "—";
                $iImg       = $ii['images'][1] ?? ($ii['images'][0] ?? null);
                $imgLink    = $iImg ? "  <a href=\"{$iImg}\">🖼 Rasm</a>" : '';
                $ipostLabel = $shipment->ipost_id ? "IPOST #{$shipment->ipost_id}" : "IPOST";
                $resultText .= "🌐 {$ipostLabel}: {$iStatus}\n";
                $resultText .= "   🚚 Yolkiro: {$iPay}  {$iPayLabel}{$imgLink}\n";

                $pieces   = (int) ($shipment->pieces ?? 0);
                $goodsUzs = $yuanRate > 0 && $shipment->price_yuan ? (float) $shipment->price_yuan * $yuanRate : 0;
                $totalUzs = $goodsUzs + $iPaySom;
                if ($pieces > 0 && $totalUzs > 0) {
                    $perPiece   = $totalUzs / $pieces;
                    $resultText .= "   💰 Jami: " . number_format((int) $totalUzs) . " so'm"
                        . "  |  1 dona: <b>" . number_format((int) $perPiece) . " so'm</b>\n";
                }
            } elseif ($shipment->ipost_id) {
                $resultText .= "🌐 IPOST: #<code>{$shipment->ipost_id}</code>\n";
            } else {
                $resultText .= "🌐 IPOST: ➖\n";
            }

            $resultText .= "\n";
        }

        $this->clearState($botUser);
        $this->chat->html($resultText)
            ->keyboard($this->backKeyboard())
            ->send();
    }
}
