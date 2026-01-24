<?php

namespace App\Telegraph;

use App\Models\Shipment;
use App\Models\TelegraphChat;
use Carbon\Carbon;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\Button;
use Illuminate\Support\Str;
use Stringable;

class MyWebhookHandler extends WebhookHandler
{
    public function start(): void
    {
        $this->sendMainMenu();
    }

    protected function sendMainMenu(): void
    {
        $text = "📦🇨🇳 <b>CHIBU bot</b>\n"
            . "Quyidagilardan birini tanlang:";

        $keyboard = Keyboard::make()
            ->row([
                Button::make("➕ Yangi yuk")->action('menu')->param('a', 'new'),
                Button::make("📄 Yuklar ro‘yxati")->action('menu')->param('a', 'list'),
            ])
            ->row([
                Button::make("🔍 Trek bo‘yicha qidirish")->action('menu')->param('a', 'search'),
            ])
            ->row([
                Button::make("💳 To‘lov qo‘shish")->action('menu')->param('a', 'payment'),
                Button::make("🧾 Xarajat qo‘shish")->action('menu')->param('a', 'expense'),
            ])
            ->row([
                Button::make("📊 Hisobot")->action('menu')->param('a', 'report'),
                Button::make("⚙️ Sozlamalar")->action('menu')->param('a', 'settings'),
            ]);

        $this->chat->html($text)->keyboard($keyboard)->send();
    }
    protected function handleChatMessage(Stringable $text): void
    {
        $text = (string) ($this->message?->text() ?? '');

        $botUser = $this->getOrCreateBotUser();
        $state = $botUser->state;

        // /start, /new kabi commandlarni xohlasangiz shu yerda ham ushlang
        if (Str::startsWith($text, '/new')) {
            $this->startCreateShipment();
            return;
        }

        match ($state) {
            'shipment.create.track'       => $this->stepTrack($botUser, $text),
            'shipment.create.amount'      => $this->stepAmount($botUser, $text),
            'shipment.create.tariff_value' => $this->stepTariffValue($botUser, $text),
            'shipment.create.currency'    => $this->stepCurrencyManualOrSkip($botUser, $text),
            default                       => $this->chat->html("Menu uchun /start yoki yangi yuk uchun /new yozing.")->send(),
        };
    }
    public function menu(): void
    {
        $action = $this->data->get('a'); // new/list/search/payment/expense/report/settings

        match ($action) {
            'new'      => $this->menuNewShipment(),
            'list'     => $this->menuListShipments(),
            'search'   => $this->menuSearchShipment(),
            'payment'  => $this->menuAddPayment(),
            'expense'  => $this->menuAddExpense(),
            'report'   => $this->menuReport(),
            'settings' => $this->menuSettings(),
            default    => $this->sendMainMenu(),
        };
    }

    // ====== MENU PAGES ======

    protected function menuNewShipment(): void
    {
        // Bu yerda state machine boshlaysiz:
        // state = shipment.create.track
        $this->chat->html("➕ <b>Yangi yuk</b>\n\nTrek kodni yuboring (masalan: ABC123).")
            ->send();
        $this->startCreateShipment();
    }

    protected function menuListShipments(): void
    {
        // Keyin DB’dan oxirgi 10 ta yukni chiqaramiz (keyingi bosqich)
        $this->chat->html("📄 <b>Yuklar ro‘yxati</b>\n\nHozircha demo. Keyin oxirgi yuklarni chiqaramiz.")
            ->keyboard($this->backKeyboard())
            ->send();
    }

    protected function menuSearchShipment(): void
    {
        $this->chat->html("🔍 <b>Qidirish</b>\n\nTrek kodni yuboring.")
            ->send();
    }

    protected function menuAddPayment(): void
    {
        $this->chat->html("💳 <b>To‘lov qo‘shish</b>\n\nAvval trek kodni yuboring.")
            ->send();
    }

    protected function menuAddExpense(): void
    {
        $this->chat->html("🧾 <b>Xarajat qo‘shish</b>\n\nAvval trek kodni yuboring.")
            ->send();
    }

    protected function menuReport(): void
    {
        $this->chat->html("📊 <b>Hisobot</b>\n\nTanlang: Kunlik / Haftalik / Oylik (keyingi bosqichda qilamiz).")
            ->keyboard($this->backKeyboard())
            ->send();
    }

    protected function menuSettings(): void
    {
        $this->chat->html("⚙️ <b>Sozlamalar</b>\n\nAdmin bo‘limi (keyin qo‘shamiz).")
            ->keyboard($this->backKeyboard())
            ->send();
    }

    protected function backKeyboard(): Keyboard
    {
        return Keyboard::make()->row([
            Button::make("⬅️ Orqaga")->action('menu')->param('a', 'back'),
        ]);
    }

    // ====== CREATE SHIPMENT FLOW ======

    protected function startCreateShipment(): void
    {
        $botUser = $this->getOrCreateBotUser();

        $this->setState($botUser, 'shipment.create.track', [
            'shipment' => []
        ]);

        $this->chat->html("➕ <b>Yangi yuk</b>\n\nTrek kodni yuboring.\nMasalan: <code>ABC123</code>")
            ->send();
    }

    protected function stepTrack(TelegraphChat $botUser, string $text): void
    {
        $track = $this->normalizeTrack($text);

        if (mb_strlen($track) < 3) {
            $this->chat->html("❌ Trek kod juda qisqa. Qaytadan yuboring.")
                ->send();
            return;
        }

        // Unique tekshiruv (xohlovchiga)
        $exists = Shipment::query()->where('track_code', $track)->exists();
        if ($exists) {
            $this->chat->html("⚠️ Bu trek kod avval kiritilgan: <code>{$track}</code>\n\nBoshqasini yuboring yoki oxiriga farqlovchi qo‘shing.")
                ->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['shipment']['track_code'] = $track;

        $this->setState($botUser, 'shipment.create.tariff_type', $payload);

        $this->chat->html("✅ Trek qabul qilindi: <code>{$track}</code>\n\nTarif turini tanlang:")
            ->keyboard(
                Keyboard::make()
                    ->row([
                        Button::make("⚖️ Kg")->action('createShipmentTariffType')->param('t', 'kg'),
                        Button::make("📦 Dona")->action('createShipmentTariffType')->param('t', 'piece'),
                    ])
                    ->row([
                        Button::make("📐 m³")->action('createShipmentTariffType')->param('t', 'm3'),
                        Button::make("❌ Bekor qilish")->action('createShipmentCancel'),
                    ])
            )
            ->send();
    }

    public function createShipmentTariffType(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $type = $this->data->get('t'); // kg|piece|m3

        if (!in_array($type, ['kg', 'piece', 'm3'], true)) {
            $this->chat->html("❌ Noto‘g‘ri tarif turi.")->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['shipment']['tariff_type'] = $type;

        $this->setState($botUser, 'shipment.create.amount', $payload);

        $label = match ($type) {
            'kg' => 'og‘irlik (kg)',
            'm3' => 'hajm (m³)',
            'piece' => 'soni (dona)',
        };

        $this->chat->html("🔢 Miqdorni yuboring: <b>{$label}</b>\nMasalan: <code>12.5</code>")
            ->send();
    }

    protected function stepAmount(TelegraphChat $botUser, string $text): void
    {
        $payload = $botUser->payload ?? [];
        $type = $payload['shipment']['tariff_type'] ?? null;

        if (!$type) {
            $this->startCreateShipment();
            return;
        }

        $amount = $this->parseNumber($text);
        if ($amount === null || $amount <= 0) {
            $this->chat->html("❌ Miqdor noto‘g‘ri. Masalan: <code>12.5</code> yoki <code>3</code>")->send();
            return;
        }

        // payloadga tegishli field
        if ($type === 'kg') {
            $payload['shipment']['weight_kg'] = $amount;
        } elseif ($type === 'm3') {
            $payload['shipment']['volume_m3'] = $amount;
        } else {
            $payload['shipment']['pieces'] = (int) round($amount);
        }

        $this->setState($botUser, 'shipment.create.tariff_value', $payload);

        $this->chat->html("💰 Tarif qiymatini yuboring.\nMasalan: <code>3.2</code> (USD yoki UZS keyin tanlaysiz)")
            ->send();
    }

    protected function stepTariffValue(TelegraphChat $botUser, string $text): void
    {
        $tariff = $this->parseNumber($text);
        if ($tariff === null || $tariff <= 0) {
            $this->chat->html("❌ Tarif noto‘g‘ri. Masalan: <code>3.2</code>")->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['shipment']['tariff_value'] = $tariff;

        $this->setState($botUser, 'shipment.create.currency', $payload);

        $this->chat->html("💱 Valyutani tanlang:")
            ->keyboard(
                Keyboard::make()
                    ->row([
                        Button::make("💵 USD")->action('createShipmentCurrency')->param('c', 'USD'),
                        Button::make("💴 UZS")->action('createShipmentCurrency')->param('c', 'UZS'),
                    ])
                    ->row([
                        Button::make("❌ Bekor qilish")->action('createShipmentCancel'),
                    ])
            )
            ->send();
    }

    public function createShipmentCurrency(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $currency = $this->data->get('c'); // USD|UZS

        if (!in_array($currency, ['USD', 'UZS'], true)) {
            $this->chat->html("❌ Noto‘g‘ri valyuta.")->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['shipment']['tariff_currency'] = $currency;

        // Agar UZS tanlansa usd_rate so'rash mumkin (ixtiyoriy)
        // Men default: USD bo'lsa rate shart emas; UZS bo'lsa rate so'raymiz (skip ham mumkin)
        if ($currency === 'UZS') {
            $this->setState($botUser, 'shipment.create.currency', $payload);

            $this->chat->html("1 USD kursini yuboring (ixtiyoriy).\nMasalan: <code>12650</code>\n\nYoki <code>0</code> yuboring (kurs kiritilmaydi).")
                ->send();
            return;
        }

        // USD bo'lsa confirm
        $this->showConfirm($botUser, $payload);
    }

    // UZS kursini qo'lda kiritish yoki skip
    protected function stepCurrencyManualOrSkip(TelegraphChat $botUser, string $text): void
    {
        $payload = $botUser->payload ?? [];
        $currency = $payload['shipment']['tariff_currency'] ?? null;

        if ($currency !== 'UZS') {
            $this->showConfirm($botUser, $payload);
            return;
        }

        $rate = $this->parseNumber($text);
        if ($rate === null) {
            $this->chat->html("❌ Kurs noto‘g‘ri. Masalan: <code>12650</code> yoki <code>0</code>")->send();
            return;
        }

        if ($rate > 0) {
            $payload['shipment']['usd_rate'] = $rate;
        }

        $this->showConfirm($botUser, $payload);
    }

    protected function showConfirm(TelegraphChat $botUser, array $payload): void
    {
        $this->setState($botUser, 'shipment.create.confirm', $payload);

        $s = $payload['shipment'] ?? [];
        $track = $s['track_code'] ?? '-';
        $type  = $s['tariff_type'] ?? '-';
        $tariff = $s['tariff_value'] ?? 0;
        $cur   = $s['tariff_currency'] ?? 'USD';

        $amountText = match ($type) {
            'kg' => ($s['weight_kg'] ?? 0) . " kg",
            'm3' => ($s['volume_m3'] ?? 0) . " m³",
            'piece' => ($s['pieces'] ?? 0) . " dona",
            default => '-',
        };

        $preview = "✅ <b>Tekshiring:</b>\n"
            . "• Trek: <code>{$track}</code>\n"
            . "• Tarif turi: <b>{$type}</b>\n"
            . "• Miqdor: <b>{$amountText}</b>\n"
            . "• Tarif: <b>{$tariff} {$cur}</b>\n";

        if (!empty($s['usd_rate'])) {
            $preview .= "• Kurs: <b>{$s['usd_rate']}</b>\n";
        }

        $preview .= "\nTasdiqlaysizmi?";

        $this->chat->html($preview)
            ->keyboard(
                Keyboard::make()->row([
                    Button::make("✅ Tasdiqlash")->action('createShipmentConfirm'),
                    Button::make("❌ Bekor qilish")->action('createShipmentCancel'),
                ])
            )
            ->send();
    }

    public function createShipmentConfirm(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $payload = $botUser->payload ?? [];
        $s = $payload['shipment'] ?? null;

        if (!$s || empty($s['track_code'])) {
            $this->chat->html("⚠️ Ma’lumot topilmadi. Qaytadan /new qiling.")->send();
            $this->clearState($botUser);
            return;
        }

        $shipment = Shipment::query()->create([
            'track_code' => $s['track_code'],
            'vendor_name' => $s['vendor_name'] ?? null,
            'weight_kg' => $s['weight_kg'] ?? null,
            'volume_m3' => $s['volume_m3'] ?? null,
            'pieces' => $s['pieces'] ?? null,
            'tariff_type' => $s['tariff_type'] ?? 'kg',
            'tariff_value' => $s['tariff_value'] ?? 0,
            'tariff_currency' => $s['tariff_currency'] ?? 'USD',
            'usd_rate' => $s['usd_rate'] ?? null,
            'status' => 'CREATED',
            'status_at' => Carbon::now(),
            'created_by_chat_id' => $botUser->chat_id
        ]);

        $this->clearState($botUser);

        $this->chat->html("🎉 Saqlandi!\n\n📦 Yuk ID: <b>{$shipment->id}</b>\nTrek: <code>{$shipment->track_code}</code>")
            ->send();

        // xohlasangiz menu qaytaring:
        // $this->sendMainMenu();
    }

    public function createShipmentCancel(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $this->clearState($botUser);

        $this->chat->html("❌ Bekor qilindi. /new bilan qayta boshlang.")
            ->send();
    }

    // ====== HELPERS ======

    protected function getOrCreateBotUser(): TelegraphChat
    {
        $chatId = (int) $this->chat->chat_id; // ko'pincha user chat_id chat_id bo'ladi
        // Agar group bo'lsa, user id boshqa bo'lishi mumkin. Hozir personal bot deb qabul qilamiz.

        return TelegraphChat::query()->firstOrCreate(
            ['chat_id' => $chatId],
            [
                'username' => $this->message?->from()?->username(),
                'first_name' => $this->message?->from()?->firstName(),
                'last_name' => $this->message?->from()?->lastName(),
                'state' => null,
                'payload' => null,
            ]
        );
    }

    protected function setState(TelegraphChat $botUser, string $state, array $payload = []): void
    {
        $botUser->state = $state;
        $botUser->payload = $payload;
        $botUser->last_seen_at = Carbon::now();
        $botUser->save();
    }

    protected function clearState(TelegraphChat $botUser): void
    {
        $botUser->state = null;
        $botUser->payload = null;
        $botUser->last_seen_at = Carbon::now();
        $botUser->save();
    }

    protected function normalizeTrack(string $text): string
    {
        $track = trim($text);
        $track = preg_replace('/\s+/', '', $track);
        return mb_strtoupper($track);
    }

    protected function parseNumber(string $text): ?float
    {
        $t = trim($text);
        $t = str_replace([' ', ','], ['', '.'], $t);
        if (!preg_match('/^-?\d+(\.\d+)?$/', $t)) {
            return null;
        }
        return (float) $t;
    }
}
