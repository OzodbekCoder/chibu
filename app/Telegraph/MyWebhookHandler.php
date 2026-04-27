<?php

namespace App\Telegraph;

use App\Models\Client;
use App\Models\CurrencyRate;
use App\Models\Shipment;
use App\Models\TelegraphChat;
use Carbon\Carbon;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use Illuminate\Support\Facades\Http;
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

        if ($state === 'client.create.phone' && $this->message?->contact() !== null) {
            $this->finishClientCreation($botUser, $this->message->contact()->phoneNumber());
            return;
        }

        match ($state) {
            'shipment.create.track'          => $this->stepTrack($botUser, $text),
            'shipment.create.amount'         => $this->stepAmount($botUser, $text),
            'shipment.create.price_yuan'     => $this->stepPriceYuan($botUser, $text),
            'shipment.create.vendor_or_link' => $this->stepVendorOrLink($botUser, $text),
            'shipment.create.note'           => $this->stepNote($botUser, $text),
            'client.create.name'             => $this->stepClientName($botUser, $text),
            'client.create.phone'            => $this->stepClientPhone($botUser, $text),
            'settings.yuan_rate'             => $this->stepYuanRate($botUser, $text),
            default                          => $this->chat->html("Menu uchun /start yoki yangi yuk uchun /new yozing.")->send(),
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
        $botUser = $this->getOrCreateBotUser();

        $shipments = Shipment::with('client')
            ->where('created_by_id', $botUser->id)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        if ($shipments->isEmpty()) {
            $this->chat->html("📄 <b>Yuklar ro'yxati bo'sh</b>\n\n/new bilan yangi yuk qo'shing.")
                ->keyboard($this->backKeyboard())
                ->send();
            return;
        }

        $total = $shipments->total();
        $text  = "📄 <b>Yuklar ro'yxati</b> ({$total} ta)\n";
        $text .= "Sahifa: {$shipments->currentPage()}/{$shipments->lastPage()}\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n\n";

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

        // Fetch live IPOST data for shipments that have ipost_id
        $ipostMap  = $this->fetchIpostMap($shipments->getCollection()->pluck('ipost_id')->filter()->values()->all());
        $yuanRate  = (float) (CurrencyRate::latestYuan()?->rate ?? 0);

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
            $yuan     = $shipment->price_yuan ? number_format((float)$shipment->price_yuan, 2) . ' ¥' : '—';
            $link     = $shipment->order_url
                ? "\n🔗 <a href=\"{$shipment->order_url}\">" . mb_strimwidth($shipment->order_url, 0, 40, '…') . '</a>'
                : '';

            $text .= "📦 <b>#{$shipment->id}</b> · <code>{$shipment->track_code}</code>\n";
            $text .= "👤 {$client}  💴 {$yuan}{$link}\n";
            $text .= "⚖️ {$amountText}  {$delivery}\n";
            $text .= "📊 {$status}  ·  📅 {$shipment->created_at->format('d.m.Y H:i:s')}\n";

            if ($shipment->ipost_id) {
                $ii = $ipostMap[(string)$shipment->ipost_id] ?? null;
                if ($ii) {
                    $iStatusMap = [
                        'Ulugchat' => '🛂 Xitoy chegara punkti',
                        'Osh'      => '🏔 O\'zbekiston chegara punkti',
                        'DropZone' => '📍 Qabul qilish punkti',
                        'Delivered'=> '✅ Qabul qilindi',
                        'CREATED'  => '🆕 Yangi yaratildi',
                        'Yiwu'     => '🚀 Xitoydan yo\'lga chiqdi',
                    ];
                    $iPayMap = [
                        'PAID'     => "✅ To'landi",
                        'UN_BILLED'=> "⏳ To'lanmadi",
                    ];
                    $rawStatus = $ii['status'] ?? '';
                    $iStatus   = $iStatusMap[$rawStatus] ?? ('🏭 Omborda (' . $rawStatus . ')');
                    $rawPay    = $ii['payStatus'] ?? '';
                    $iPayLabel = $iPayMap[$rawPay] ?? $rawPay;
                    $iWeight   = isset($ii['weight']) ? $ii['weight'] . ' kg' : '—';
                    $iPay      = isset($ii['payAmountSom']) ? number_format((int)$ii['payAmountSom']) . " so'm" : '—';
                    $iImg      = $ii['images'][1] ?? ($ii['images'][0] ?? null);
                    $imgLink   = $iImg ? "  <a href=\"{$iImg}\">🖼 Rasm</a>" : '';
                    $text .= "🌐 IPOST #{$shipment->ipost_id}: {$iStatus}\n";
                    $text .= "   ⚖️ {$iWeight}  💳 {$iPay}  {$iPayLabel}{$imgLink}\n";

                    $pieces       = (int)($shipment->pieces ?? 0);
                    $goodsUzs     = $yuanRate > 0 && $shipment->price_yuan
                                    ? (float)$shipment->price_yuan * $yuanRate
                                    : 0;
                    $deliveryUzs  = (float)($ii['payAmountSom'] ?? 0);
                    $totalUzs     = $goodsUzs + $deliveryUzs;
                    if ($pieces > 0 && $totalUzs > 0) {
                        $perPiece = $totalUzs / $pieces;
                        $text .= "   💰 Jami: " . number_format((int)$totalUzs) . " so'm"
                               . "  |  1 dona: <b>" . number_format((int)$perPiece) . " so'm</b>\n";
                    }
                } else {
                    $text .= "🌐 IPOST: #<code>{$shipment->ipost_id}</code>\n";
                }
            } else {
                $text .= "🌐 IPOST: ➖\n";
            }

            $text .= "\n";
        }

        $keyboard = Keyboard::make();
        $navRow   = [];

        if ($shipments->currentPage() > 1) {
            $navRow[] = Button::make("⬅️ Oldingi")->action('menu')->param('a', 'list')->param('p', $page - 1);
        }
        if ($shipments->hasMorePages()) {
            $navRow[] = Button::make("Keyingi ➡️")->action('menu')->param('a', 'list')->param('p', $page + 1);
        }
        if (!empty($navRow)) {
            $keyboard->row($navRow);
        }

        $keyboard->row([
            Button::make("⬅️ Orqaga")->action('menu')->param('a', 'back'),
        ]);

        $this->chat->html($text)->keyboard($keyboard)->send();
    }

    protected function menuSearchShipment(): void
    {
        $this->chat->html("🔍 <b>Qidirish</b>\n\nTrek kodni yuboring.")
            ->send();
    }

    protected function menuAddPayment(): void
    {
        $this->chat->html("💳 <b>To'lov qo'shish</b>\n\nAvval trek kodni yuboring.")
            ->send();
    }

    protected function menuAddExpense(): void
    {
        $this->chat->html("🧾 <b>Xarajat qo'shish</b>\n\nAvval trek kodni yuboring.")
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
        $rate = CurrencyRate::latestYuan();
        $rateText = $rate
            ? number_format((float) $rate->rate, 2) . " so'm  ({$rate->rate_date})"
            : "kiritilmagan";

        $text = "⚙️ <b>Sozlamalar</b>\n\n"
            . "💴 Yuan kursi (CNY → UZS): <b>{$rateText}</b>";

        $this->chat->html($text)
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
            $this->chat->html("⚠️ Bu trek kod avval kiritilgan: <code>{$track}</code>\n\nBoshqasini yuboring yoki oxiriga farqlovchi qo'shing.")
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
            $this->chat->html("❌ Noto'g'ri tarif turi.")->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['shipment']['tariff_type'] = $type;

        $this->setState($botUser, 'shipment.create.amount', $payload);

        $label = match ($type) {
            'kg' => "og'irlik (kg)",
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
            $this->chat->html("❌ Miqdor noto'g'ri. Masalan: <code>12.5</code> yoki <code>3</code>")->send();
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
            // agar URL bo'lsa order_url ga, bo'lmasa vendor_name ga yozamiz
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

        $p = Client::query()
            ->where('created_by_id', $botUser->id)
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $kb = Keyboard::make();

        foreach ($p->items() as $client) {
            $kb->row([
                Button::make($client->name)->action('createShipmentClientPick')->param('cid', $client->id),
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
            Button::make("➕ Yangi mijoz qo'shish")->action('createShipmentNewClient'),
        ]);
        $kb->row([
            Button::make("❌ Bekor qilish")->action('createShipmentCancel'),
        ]);

        $text = $p->isEmpty()
            ? "👤 Hali mijoz yo'q. Yangi mijoz qo'shing:"
            : "👤 Mijozni tanlang yoki yangi qo'shing:";

        $this->chat->html($text)
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
        $clientId = (int) data_get($this->data, 'cid', 0);

        if (!$clientId) {
            $this->chat->html("❌ Mijoz tanlanmadi.")->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['shipment']['client_id'] = $clientId;

        $this->askForNote($botUser, $payload);
    }

    protected function stepPriceYuan(TelegraphChat $botUser, string $text): void
    {
        $payload = $botUser->payload ?? [];

        $price = $this->parseNumber($text);
        if ($price === null) {
            $this->chat->html("❌ Narx noto'g'ri. Masalan: <code>350.50</code> yoki <code>0</code>")->send();
            return;
        }

        if ($price > 0) {
            $payload['shipment']['price_yuan'] = $price;
        }

        $this->setState($botUser, 'shipment.create.vendor_or_link', $payload);

        $this->chat->html(
            "🔗 Buyurtma linki yoki vendor nomini yuboring (ixtiyoriy).\n" .
                "Masalan: <code>https://1688.com/order/123</code> yoki <code>Vendor ABC</code>"
        )
            ->keyboard(
                Keyboard::make()->row([
                    Button::make("⏭ O'tkazib yuborish")->action('skipVendorOrLink'),
                    Button::make("❌ Bekor qilish")->action('createShipmentCancel'),
                ])
            )
            ->send();
    }

    protected function askForNote(TelegraphChat $botUser, array $payload): void
    {
        $this->setState($botUser, 'shipment.create.note', $payload);

        $this->chat->html("📝 Qisqacha izoh yuboring (ixtiyoriy).\nMasalan: <code>Qizil rangli, katta o'lcham</code>")
            ->keyboard(
                Keyboard::make()->row([
                    Button::make("⏭ O'tkazib yuborish")->action('skipNote'),
                    Button::make("❌ Bekor qilish")->action('createShipmentCancel'),
                ])
            )
            ->send();
    }

    protected function stepNote(TelegraphChat $botUser, string $text): void
    {
        $payload = $botUser->payload ?? [];

        $note = trim($text);
        if ($note !== '') {
            $payload['shipment']['note'] = $note;
        }

        $this->showConfirm($botUser, $payload);
    }
    protected function stepTariffValue(TelegraphChat $botUser, string $text): void
    {
        $tariff = $this->parseNumber($text);
        if ($tariff === null || $tariff <= 0) {
            $this->chat->html("❌ Tarif noto'g'ri. Masalan: <code>3.2</code>")->send();
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
            $this->chat->html("❌ Noto'g'ri valyuta.")->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['shipment']['tariff_currency'] = $currency;

        // Agar UZS tanlansa usd_rate so'rash mumkin (ixtiyoriy)
        // Men default: USD bo'lsa rate shart emas; UZS bo'lsa rate so'raymiz (skip ham mumkin)
        if ($currency === 'UZS') {
            $this->setState($botUser, 'shipment.create.currency', $payload);

            $this->chat->html("1 USD kursini yuboring (ixtiyoriy).\nMasalan: <code>12650</code>")
                ->keyboard(
                    Keyboard::make()->row([
                        Button::make("⏭ O'tkazib yuborish")->action('skipUsdRate'),
                        Button::make("❌ Bekor qilish")->action('createShipmentCancel'),
                    ])
                )
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

        if (!in_array($type, ['avia', 'avto', 'sea', 'other'], true)) {
            $this->chat->html("❌ Noto'g'ri yetkazish turi.")->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['shipment']['delivery_type'] = $type;

        $this->setState($botUser, 'shipment.create.price_yuan', $payload);

        $this->chat->html("💴 Tovar narxini <b>yuanda (¥)</b> yuboring.\nMasalan: <code>350.50</code>")
            ->keyboard(
                Keyboard::make()->row([
                    Button::make("⏭ O'tkazib yuborish")->action("skipPriceYuan"),
                    Button::make("❌ Bekor qilish")->action("createShipmentCancel"),
                ])
            )
            ->send();
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
            $this->chat->html("❌ Kurs noto'g'ri. Masalan: <code>12650</code> yoki <code>0</code>")->send();
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
        $clientId    = $s['client_id'] ?? null;
        $client      = $clientId ? Client::query()->find($clientId) : null;
        $clientLabel = $client?->name ?? '—';

        $preview = "✅ <b>Tekshiring:</b>\n"
            . "• Trek: <code>{$track}</code>\n"
            . "• Tarif turi: <b>{$type}</b>\n"
            . "• Miqdor: <b>{$amountText}</b>\n"
            . "• Yetkazish: <b>{$delivery}</b>\n"
            . "• Mijoz: <b>{$clientLabel}</b>\n";

        if (!empty($s['price_yuan'])) {
            $preview .= "• Narx: <b>¥ {$s['price_yuan']}</b>\n";
        }
        if (!empty($s['vendor_name'])) {
            $preview .= "• Vendor: <b>{$s['vendor_name']}</b>\n";
        }
        if (!empty($s['order_url'])) {
            $preview .= "• Link: <a href=\"{$s['order_url']}\">buyurtma</a>\n";
        }
        if (!empty($s['note'])) {
            $preview .= "• Izoh: <i>{$s['note']}</i>\n";
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
            $this->chat->html("⚠️ Ma'lumot topilmadi. Qaytadan /new qiling.")->send();
            $this->clearState($botUser);
            return;
        }

        $shipment = Shipment::query()->create([
            'track_code'      => $s['track_code'],
            'client_id'       => $s['client_id'] ?? null,
            'vendor_name'     => $s['vendor_name'] ?? null,
            'order_url'       => $s['order_url'] ?? null,
            'tariff_type'     => $s['tariff_type'] ?? 'kg',
            'weight_kg'       => $s['weight_kg'] ?? null,
            'volume_m3'       => $s['volume_m3'] ?? null,
            'pieces'          => $s['pieces'] ?? null,
            'tariff_value'    => $s['tariff_value'] ?? null,
            'tariff_currency' => $s['tariff_currency'] ?? null,
            'usd_rate'        => $s['usd_rate'] ?? null,
            'price_yuan'      => $s['price_yuan'] ?? null,
            'delivery_type'   => $s['delivery_type'] ?? 'avia',
            'note'            => $s['note'] ?? null,
            'status'          => 'CREATED',
            'status_at'       => Carbon::now(),
            'created_by_id'   => $botUser->id,
        ]);

        $this->clearState($botUser);
        if ($this->messageId) {
            $this->chat->deleteMessage($this->messageId)->send();
        }

        $shipment->load('client');
        if (Str::contains($shipment->client?->notes ?? '', 'IPOST')) {
            $this->callIpostApi($shipment);
        }

        $this->chat->html("🎉 Saqlandi!\n\n📦 Yuk ID: <b>{$shipment->id}</b>\nTrek: <code>{$shipment->track_code}</code>")
            ->send();

        $this->sendMainMenu();
    }

    // ====== CREATE CLIENT FLOW (shipment ichidan) ======

    public function createShipmentNewClient(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $payload = $botUser->payload ?? [];

        $this->setState($botUser, 'client.create.name', $payload);

        $this->chat->html("👤 <b>Yangi mijoz</b>\n\nMijoz ismini yuboring:")
            ->send();
    }

    protected function stepClientName(TelegraphChat $botUser, string $text): void
    {
        $name = trim($text);
        if (mb_strlen($name) < 2) {
            $this->chat->html("❌ Ism juda qisqa. Qaytadan yuboring.")->send();
            return;
        }

        $payload = $botUser->payload ?? [];
        $payload['new_client']['name'] = $name;

        $this->setState($botUser, 'client.create.phone', $payload);

        $this->chat->html("📞 Telefon raqamini ulashing yoki o'tkazib yuboring:")
            ->replyKeyboard(
                ReplyKeyboard::make()
                    ->oneTime()
                    ->resize()
                    ->row([
                        ReplyButton::make("📱 Raqamni ulashish")->requestContact(),
                    ])
                    ->row([
                        ReplyButton::make("O'tkazib yuborish"),
                    ])
            )
            ->send();
    }

    protected function handleContact(): void
    {
        $botUser = $this->getOrCreateBotUser();

        if ($botUser->state !== 'client.create.phone') {
            return;
        }

        $phone = $this->message?->contact()?->phoneNumber();

        $this->finishClientCreation($botUser, $phone);
    }

    protected function stepClientPhone(TelegraphChat $botUser, string $text): void
    {
        // Foydalanuvchi "O'tkazib yuborish" tugmasini yoki ixtiyoriy matn yubordi
        $phone = trim($text) === "O'tkazib yuborish" ? null : trim($text);

        $this->finishClientCreation($botUser, $phone ?: null);
    }

    protected function finishClientCreation(TelegraphChat $botUser, ?string $phone): void
    {
        $payload = $botUser->payload ?? [];
        $clientData = $payload['new_client'] ?? [];

        if (empty($clientData['name'])) {
            $this->startCreateShipment();
            return;
        }

        $client = Client::query()->create([
            'name'          => $clientData['name'],
            'phone'         => $phone,
            'created_by_id' => $botUser->id,
        ]);

        unset($payload['new_client']);
        $payload['shipment']['client_id'] = $client->id;

        $this->chat->html("✅ Mijoz saqlandi: <b>{$client->name}</b>")
            ->removeReplyKeyboard()
            ->send();

        $this->askForNote($botUser, $payload);
    }

    // ====== SKIP ACTIONS ======

    public function skipPriceYuan(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $payload = $botUser->payload ?? [];

        $this->setState($botUser, 'shipment.create.vendor_or_link', $payload);

        $this->chat->html(
            "🔗 Buyurtma linki yoki vendor nomini yuboring (ixtiyoriy).\n" .
                "Masalan: <code>https://1688.com/order/123</code> yoki <code>Vendor ABC</code>"
        )
            ->keyboard(
                Keyboard::make()->row([
                    Button::make("⏭ O'tkazib yuborish")->action('skipVendorOrLink'),
                    Button::make("❌ Bekor qilish")->action('createShipmentCancel'),
                ])
            )
            ->send();
    }

    public function skipVendorOrLink(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $payload = $botUser->payload ?? [];

        $this->setState($botUser, 'shipment.create.client', $payload);
        $this->sendClientPickMenu($botUser, $payload, 1);
    }

    public function skipNote(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $payload = $botUser->payload ?? [];

        $this->showConfirm($botUser, $payload);
    }

    public function skipUsdRate(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $payload = $botUser->payload ?? [];

        $this->showConfirm($botUser, $payload);
    }

    public function createShipmentCancel(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $this->clearState($botUser);

        if ($this->messageId) {
            $this->chat->deleteMessage($this->messageId)->send();
        }

        $this->chat->html("❌ Bekor qilindi. /new bilan qayta boshlang.")
            ->send();
    }

    // ====== IPOST API ======

    /**
     * @param  string[] $ipostIds
     * @return array<string, array>
     */
    protected function fetchIpostMap(array $ipostIds): array
    {
        if (empty($ipostIds)) return [];

        $endpoint = rtrim(env('IPOST_ADD_ENDPOINT', ''), '/');
        $apiKey   = env('IPOST_API_KEY', '');
        if (!$endpoint || !$apiKey) return [];

        $headers = [
            'x-apikey'    => $apiKey,
            'x-timestamp' => (string) time(),
            'x-chat-id'   => (string) $this->chat->chat_id,
            'source'      => 'TELEGRAM',
        ];

        $result = [];
        foreach ($ipostIds as $ipostId) {
            try {
                /** @var \Illuminate\Http\Client\Response $res */
                $res = Http::withHeaders($headers)->timeout(8)->get("{$endpoint}/{$ipostId}");
                if (!$res->successful()) continue;

                $data = $res->json();
                $item = \is_array($data) && array_is_list($data) ? ($data[0] ?? null) : $data;
                if (\is_array($item) && isset($item['id'])) {
                    $result[(string)$item['id']] = $item;
                }
            } catch (\Throwable) {
                // skip failed fetches silently
            }
        }
        return $result;
    }

    protected function callIpostApi(Shipment $shipment): void
    {
        $endpoint = rtrim(env('IPOST_ADD_ENDPOINT', ''), '/');
        $apiKey   = env('IPOST_API_KEY', '');

        if (!$endpoint || !$apiKey) {
            $this->chat->html("⚠️ <b>IPOST:</b> IPOST_ADD_ENDPOINT yoki IPOST_API_KEY sozlanmagan.")->send();
            return;
        }

        $headers = [
            'x-apikey'    => $apiKey,
            'x-timestamp' => 1777288697,
            'x-chat-id'   => (string) $this->chat->chat_id,
            'source'      => 'TELEGRAM',
        ];
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->post($endpoint, ['trackingNumber' => $shipment->track_code]);

            if (!$response->successful()) {
                $this->chat->html(
                    "⚠️ <b>IPOST xatosi</b>\n" .
                        "Trek: <code>{$shipment->track_code}</code>\n" .
                        "Status: <b>{$response->status()}</b>\n" .
                        "<code>" . mb_strimwidth($response->body(), 0, 300, '...') . "</code>"
                )->send();
                Log::warning('IPOST add failed', ['status' => $response->status(), 'body' => $response->body()]);
                return;
            }

            $data    = $response->json();
            $ipostId = is_array($data) ? ($data[0]['id'] ?? null) : ($data['id'] ?? null);

            if (!$ipostId) {
                $this->chat->html(
                    "⚠️ <b>IPOST:</b> ID olinmadi.\n" .
                        "<code>" . mb_strimwidth($response->body(), 0, 300, '...') . "</code>"
                )->send();
                return;
            }

            $shipment->update(['ipost_id' => (string) $ipostId]);

            if ($shipment->note) {
                /** @var \Illuminate\Http\Client\Response $remark */
                $remark = Http::withHeaders($headers)
                    ->timeout(15)
                    ->post("{$endpoint}/{$ipostId}/remark", ['remark' => $shipment->note]);

                if (!$remark->successful()) {
                    $this->chat->html(
                        "⚠️ <b>IPOST remark xatosi</b>\n" .
                            "IPOST ID: <code>{$ipostId}</code>\n" .
                            "Status: <b>{$remark->status()}</b>"
                    )->send();
                    Log::warning('IPOST remark failed', ['ipost_id' => $ipostId, 'status' => $remark->status()]);
                }
            }
        } catch (\Throwable $e) {
            $this->chat->html(
                "⚠️ <b>IPOST ulanish xatosi</b>\n" .
                    "Trek: <code>{$shipment->track_code}</code>\n" .
                    "<code>{$e->getMessage()}</code>"
            )->send();
            Log::error('IPOST API xatosi', ['error' => $e->getMessage(), 'track' => $shipment->track_code]);
        }
    }

    // ====== HELPERS ======

    protected function getOrCreateBotUser(): TelegraphChat
    {
        $chatId = (int) $this->chat->chat_id; // ko'pincha user chat_id chat_id bo'ladi
        // Agar group bo'lsa, user id boshqa bo'lishi mumkin. Hozir personal bot deb qabul qilamiz.

        $botUser = TelegraphChat::query()->firstOrCreate(
            ['chat_id' => $chatId],
            [
                'name'         => $this->message?->from()?->username() ?? $this->message?->from()?->firstName() ?? 'User' . $chatId,
                'state'        => null,
                'payload'      => null,
                'last_seen_at' => Carbon::now(),
                'is_admin'     => false,
            ]
        );

        // Agar tizimda hech qanday admin bo'lmasa, birinchi kelgan odam admin bo'ladi
        if (!$botUser->is_admin && !TelegraphChat::query()->where('is_admin', true)->exists()) {
            $botUser->is_admin = true;
            $botUser->save();
        }

        return $botUser;
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
