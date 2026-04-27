<?php

namespace App\Telegraph;

use App\Models\Client;
use App\Models\Shipment;
use App\Models\TelegraphChat;
use Carbon\Carbon;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\Button;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stringable;

class MyWebhookHandler extends WebhookHandler
{
    const ADMIN_URL = 'https://t.me/Forest_Gamb';
    const EXAMPLE_TRACK_CODE = 'YT7597474805854';
    public function start(): void
    {
        $this->sendMainMenu();
    }
    public function new(): void
    {
        $this->menuNewShipment();
    }

    protected function sendMainMenu(): void
    {
        $botUser = $this->getOrCreateBotUser();
        if ($botUser->is_admin == false) {
            $this->chat->html("⚠️ Siz hali foydalanuvchi emassiz. <a href=\"" . self::ADMIN_URL . "\">Bekjon</a> akaga murojaat qiling.\n\n")
                ->withoutPreview()
                ->send();
            return;
        }
        $text = "📦🇨🇳 <b>CHIBU bot</b>\n"
            . "Quyidagilardan birini tanlang:";

        $keyboard = Keyboard::make()
            ->row([
                Button::make("➕ Yangi yuk")->action('menu')->param('a', 'new'),
                Button::make("📄 Yuklar ro'yxati")->action('menu')->param('a', 'list'),
            ])
            ->row([
                Button::make("🔍 Trek bo'yicha qidirish")->action('menu')->param('a', 'search'),
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
        if ($botUser->is_admin == false) {
            $this->chat->html("⚠️ Siz hali foydalanuvchi emassiz. <a href=\"" . self::ADMIN_URL . "\">Bekjon</a> akaga murojaat qiling.\n\n")
                ->withoutPreview()
                ->send();
            return;
        }
        // /start, /new kabi commandlarni xohlasangiz shu yerda ham ushlang
        if (Str::startsWith($text, '/new')) {
            $this->startCreateShipment();
            return;
        }

        match ($state) {
            'shipment.create.track'       => $this->stepTrack($botUser, $text),
            'shipment.create.amount'      => $this->stepAmount($botUser, $text),
            'shipment.create.vendor_or_link' => $this->stepVendorOrLink($botUser, $text),
            default                       => $this->chat->html("Menu uchun /start yoki yangi yuk uchun /new yozing.")->send(),
        };
    }
    public function menu(): void
    {
        if ($this->messageId) {
            $this->chat->deleteMessage($this->messageId)->send();
        }
        $action = $this->data->get('a'); // new/list/search/payment/expense/report/settings
        $page = (int) $this->data->get('p', 1);
        match ($action) {
            'new'      => $this->menuNewShipment(),
            'list'     => $this->menuListShipments($page),
            'search'   => $this->menuSearchShipment(),
            'payment'  => $this->menuAddPayment(),
            'expense'  => $this->menuAddExpense(),
            'report'   => $this->menuReport(),
            'settings' => $this->menuSettings(),
            'back' => $this->sendMainMenu(),
            default    => $this->sendMainMenu(),
        };
    }

    // ====== MENU PAGES ======

    protected function menuNewShipment(): void
    {
        // Bu yerda state machine boshlaysiz:
        // state = shipment.create.track
        $this->startCreateShipment();
    }

    protected function menuListShipments(int $page = 1): void
    {
        $perPage = 5;

        $shipments = Shipment::where('created_by_id', $this->getOrCreateBotUser()->id)
            ->latest()->paginate($perPage, ['*'], 'page', $page);

        if ($shipments->isEmpty()) {
            $this->chat->html("📄 <b>Yuklar ro'yxati bo'sh</b>")
                ->keyboard($this->backKeyboard())
                ->send();
            return;
        }

        $text = "📄 <b>Yuklar ro'yxati</b>\n\n";

        foreach ($shipments as $shipment) {
            $text .= "📦 ID: <b>{$shipment->id}</b>\n";
            $text .= "🚚 Trek: <code>{$shipment->tracking}</code>\n";
            $text .= "📅 Sana: {$shipment->created_at->format('d.m.Y')}\n\n";
        }

        $keyboard = Keyboard::make();

        // pagination tugmalari
        $buttons = [];

        if ($shipments->currentPage() > 1) {
            $buttons[] = Button::make("⬅️ Oldingi")
                ->action('menu')
                ->param('a', 'list')
                ->param('p', $page - 1);
        }

        if ($shipments->hasMorePages()) {
            $buttons[] = Button::make("➡️ Keyingi")
                ->action('menu')
                ->param('a', 'list')
                ->param('p', $page + 1);
        }

        if (!empty($buttons)) {
            $keyboard->row($buttons);
        }

        $keyboard->row([
            Button::make("⬅️ Orqaga")->action('menu')->param('a', 'back')
        ]);

        $this->chat->html($text)
            ->keyboard($keyboard)
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

        $this->chat->html("➕ <b>Yangi yuk</b>\n\nTrek kodni yuboring.\nMasalan: <code>" . self::EXAMPLE_TRACK_CODE . "</code>")
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

        $this->chat->html("🚚 Yetkazish turini tanlang:")
            ->keyboard(
                Keyboard::make()->row([
                    Button::make("✈️ Avia")->action('createShipmentDeliveryType')->param('d', 'avia'),
                    Button::make("🚛 Avto")->action('createShipmentDeliveryType')->param('d', 'avto'),
                    Button::make("🚢 Daryo")->action('createShipmentDeliveryType')->param('d', 'sea'),
                    Button::make("🗿 Boshqa")->action('createShipmentDeliveryType')->param('d', 'other')
                ])->row([
                    Button::make("❌ Bekor qilish")->action('createShipmentCancel')
                ])
            )
            ->send();
    }
    protected function stepVendorOrLink(TelegraphChat $botUser, string $text): void
    {
        $payload = $botUser->payload ?? [];

        $t = trim($text);
        if ($t !== '0' && $t !== '') {
            // agar URL bo‘lsa order_url ga, bo‘lmasa vendor_name ga yozamiz
            if (filter_var($t, FILTER_VALIDATE_URL)) {
                $payload['shipment']['order_url'] = $t;
            } else {
                $payload['shipment']['vendor_name'] = $t;
            }
        }

        // endi client tanlash
        $this->setState($botUser, 'shipment.create.client', $payload);
        $this->sendClientPickMenu($botUser, $payload, 1);
    }
    protected function sendClientPickMenu(TelegraphChat $botUser, array $payload, int $page = 1): void
    {
        $perPage = 8;

        $query = Client::query()->orderBy('id', 'desc'); // xohlagan sort
        $p = $query->paginate($perPage, ['*'], 'page', $page);

        $kb = Keyboard::make();

        foreach ($p->items() as $client) {
            $label = ($client->client_id ?? 'Client') . " (" . $client->name . ")";
            $kb->row([
                Button::make($label)->action('createShipmentClientPick')->param('cid', $client->client_id),
            ]);
        }

        $navRow = [];
        if ($p->currentPage() > 1) {
            $navRow[] = Button::make("⬅️ Oldingi")->action('createShipmentClientPage')->param('p', $p->currentPage() - 1);
        }
        if ($p->hasMorePages()) {
            $navRow[] = Button::make("Keyingi ➡️")->action('createShipmentClientPage')->param('p', $p->currentPage() + 1);
        }
        if (!empty($navRow)) {
            $kb->row($navRow);
        }

        $kb->row([
            Button::make("❌ Bekor qilish")->action('createShipmentCancel'),
        ]);

        $this->chat->html("👤 Mijozni tanlang (Client ID):")
            ->keyboard($kb)
            ->send();
    }
    public function createShipmentClientPage(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $payload = $botUser->payload ?? [];
        $page = (int) ($this->data->get('p') ?? 1);

        $this->setState($botUser, 'shipment.create.client', $payload);
        $this->sendClientPickMenu($botUser, $payload, max(1, $page));
    }

    public function createShipmentClientPick(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $clientId = (string) $this->data->get('cid');

        if ($clientId === '') {
            $this->chat->html("❌ Client tanlanmadi.")->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['shipment']['client_id'] = $clientId;

        $this->showConfirm($botUser, $payload);
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
    public function createShipmentDeliveryType(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $type = $this->data->get('d'); // avia|avto

        if (!in_array($type, ['avia', 'avto'], true)) {
            $this->chat->html("❌ Noto‘g‘ri yetkazish turi.")->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['shipment']['delivery_type'] = $type;

        // keyingi bosqich: vendor_name yoki url (ixtiyoriy)
        $this->setState($botUser, 'shipment.create.vendor_or_link', $payload);

        $this->chat->html(
            "🔗 Buyurtma linki yoki vendor nomini yuboring (ixtiyoriy).\n" .
                "Masalan: <code>https://example.com/order/123</code> yoki <code>Vendor ABC</code>\n\n" .
                "O‘tkazib yuborish uchun <code>0</code> yuboring."
        )->send();
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

        $amountText = match ($type) {
            'kg' => ($s['weight_kg'] ?? 0) . " kg",
            'm3' => ($s['volume_m3'] ?? 0) . " m³",
            'piece' => ($s['pieces'] ?? 0) . " dona",
            default => '-',
        };

        $delivery = $s['delivery_type'] ?? '-';
        $clientId = $s['client_id'] ?? '-';

        $preview = "✅ <b>Tekshiring:</b>\n"
            . "• Trek: <code>{$track}</code>\n"
            . "• Tarif turi: <b>{$type}</b>\n"
            . "• Miqdor: <b>{$amountText}</b>\n"
            . "• Yetkazish: <b>{$delivery}</b>\n"
            . "• Client ID: <code>{$clientId}</code>\n";

        if (!empty($s['vendor_name'])) {
            $preview .= "• Vendor: <b>{$s['vendor_name']}</b>\n";
        }
        if (!empty($s['order_url'])) {
            $preview .= "• Link: <a href=\"{$s['order_url']}\">buyurtma</a>\n";
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
            'order_link' => $s['order_url'] ?? null,
            'client_id' => $s['client_id'] ?? null,
            'weight_kg' => $s['weight_kg'] ?? null,
            'volume_m3' => $s['volume_m3'] ?? null,
            'pieces' => $s['pieces'] ?? null,
            'tariff_type' => $s['tariff_type'] ?? 'kg',
            'status' => 'CREATED',
            'status_at' => Carbon::now(),
            'created_by_id' => $botUser->id
        ]);

        $this->clearState($botUser);
        if ($this->messageId) {
            $this->chat->deleteMessage($this->messageId)->send();
        }
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
                'name' => $this->message?->from()?->username() ?? $this->message?->from()?->firstName() ?? 'User' . $chatId,
                'state' => null,
                'payload' => null,
                'last_seen_at' => Carbon::now(),
                'is_admin' => false
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
