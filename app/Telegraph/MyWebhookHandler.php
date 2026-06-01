<?php

namespace App\Telegraph;

use App\Models\CurrencyRate;
use App\Models\Shipment;
use App\Models\TelegraphChat;
use App\Services\IpostService;
use App\Telegraph\Traits\HandlesClientCreation;
use App\Telegraph\Traits\HandlesReports;
use App\Telegraph\Traits\HandlesSettings;
use App\Telegraph\Traits\HandlesShipmentCreation;
use App\Telegraph\Traits\HandlesShipmentSearch;
use Carbon\Carbon;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Stringable;

class MyWebhookHandler extends WebhookHandler
{
    use HandlesShipmentCreation;
    use HandlesClientCreation;
    use HandlesShipmentSearch;
    use HandlesSettings;
    use HandlesReports;

    const ADMIN_URL          = 'https://t.me/Forest_Gamb';
    const EXAMPLE_TRACK_CODE = 'YT7597474805854';
    const STATE_TIMEOUT_MIN  = 30;

    public function start(): void
    {
        $this->sendMainMenu();
    }

    public function new(): void
    {
        $this->startCreateShipment();
    }

    protected function sendMainMenu(): void
    {
        $botUser = $this->getOrCreateBotUser();
        if (!$botUser->is_admin) {
            $this->chat->html("⚠️ Siz hali foydalanuvchi emassiz. <a href=\"" . self::ADMIN_URL . "\">Bekjon</a> akaga murojaat qiling.\n\n")
                ->withoutPreview()
                ->send();
            return;
        }

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

        $this->chat->html("📦🇨🇳 <b>CHIBU bot</b>\nQuyidagilardan birini tanlang:")
            ->keyboard($keyboard)
            ->send();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $text    = (string) ($this->message?->text() ?? '');
        $botUser = $this->getOrCreateBotUser();

        if (!$botUser->is_admin) {
            $this->chat->html("⚠️ Siz hali foydalanuvchi emassiz. <a href=\"" . self::ADMIN_URL . "\">Bekjon</a> akaga murojaat qiling.\n\n")
                ->withoutPreview()
                ->send();
            return;
        }

        if (str_starts_with($text, '/new')) {
            $this->startCreateShipment();
            return;
        }

        if ($botUser->state === 'client.create.phone' && $this->message?->contact() !== null) {
            $this->finishClientCreation($botUser, $this->message->contact()->phoneNumber());
            return;
        }

        // Clear stale state after STATE_TIMEOUT_MIN minutes of inactivity
        $stateSetAt = isset($botUser->payload['_state_set_at'])
            ? Carbon::parse($botUser->payload['_state_set_at'])
            : null;

        if ($botUser->state && $stateSetAt && $stateSetAt->diffInMinutes(Carbon::now()) > self::STATE_TIMEOUT_MIN) {
            $this->clearState($botUser);
            $this->chat->html("⏰ Vaqt tugadi (" . self::STATE_TIMEOUT_MIN . " daqiqa). Boshqatdan boshlang.")->send();
            $this->sendMainMenu();
            return;
        }

        match ($botUser->state) {
            'shipment.create.track'          => $this->stepTrack($botUser, $text),
            'shipment.create.amount'         => $this->stepAmount($botUser, $text),
            'shipment.create.price_yuan'     => $this->stepPriceYuan($botUser, $text),
            'shipment.create.vendor_or_link' => $this->stepVendorOrLink($botUser, $text),
            'shipment.create.note'           => $this->stepNote($botUser, $text),
            'shipment.search.track'          => $this->stepSearchTrack($botUser, $text),
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
        $action = $this->data->get('a');
        $page   = (int) $this->data->get('p', 1);

        match ($action) {
            'new'      => $this->startCreateShipment(),
            'list'     => $this->menuListShipments($page),
            'search'   => $this->menuSearchShipment(),
            'payment'  => $this->menuAddPayment(),
            'expense'  => $this->menuAddExpense(),
            'report'   => $this->menuReport(),
            'settings' => $this->menuSettings(),
            'back'     => $this->sendMainMenu(),
            default    => $this->sendMainMenu(),
        };
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
        $text  = "📄 <b>Yuklar ro'yxati</b> ({$total} ta)\nSahifa: {$shipments->currentPage()}/{$shipments->lastPage()}\n━━━━━━━━━━━━━━━━━━\n\n";

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

            $text .= "📦 <b>#{$shipment->id}</b> · <code>{$shipment->track_code}</code>\n";
            $text .= "👤 {$client}  💴 {$yuan}{$link}\n";
            $text .= "⚖️ {$amountText}  {$delivery}\n";
            $text .= "📊 {$status}  ·  📅 {$shipment->created_at->format('d.m.Y H:i:s')}\n";

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
                $text .= "🌐 {$ipostLabel}: {$iStatus}\n";
                $text .= "   🚚 Yolkiro: {$iPay}  {$iPayLabel}{$imgLink}\n";

                $pieces   = (int) ($shipment->pieces ?? 0);
                $goodsUzs = $yuanRate > 0 && $shipment->price_yuan ? (float) $shipment->price_yuan * $yuanRate : 0;
                $totalUzs = $goodsUzs + $iPaySom;
                if ($pieces > 0 && $totalUzs > 0) {
                    $perPiece = $totalUzs / $pieces;
                    $text    .= "   💰 Jami: " . number_format((int) $totalUzs) . " so'm"
                        . "  |  1 dona: <b>" . number_format((int) $perPiece) . " so'm</b>\n";
                }
            } elseif ($shipment->ipost_id) {
                $text .= "🌐 IPOST: #<code>{$shipment->ipost_id}</code>\n";
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

        $keyboard->row([Button::make("⬅️ Orqaga")->action('menu')->param('a', 'back')]);

        $this->chat->html($text)->keyboard($keyboard)->send();
    }

    protected function menuAddPayment(): void
    {
        $this->chat->html("💳 <b>To'lov qo'shish</b>\n\nAvval trek kodni yuboring.")->send();
    }

    protected function menuAddExpense(): void
    {
        $this->chat->html("🧾 <b>Xarajat qo'shish</b>\n\nAvval trek kodni yuboring.")->send();
    }

    protected function backKeyboard(): Keyboard
    {
        return Keyboard::make()->row([
            Button::make("⬅️ Orqaga")->action('menu')->param('a', 'back'),
        ]);
    }

    // ====== HELPERS ======

    protected function getOrCreateBotUser(): TelegraphChat
    {
        $chatId  = (int) $this->chat->chat_id;
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

        // First user auto-becomes admin when no admin exists
        if (!$botUser->is_admin && !TelegraphChat::query()->where('is_admin', true)->exists()) {
            $botUser->is_admin = true;
            $botUser->save();
        }

        return $botUser;
    }

    protected function setState(TelegraphChat $botUser, string $state, array $payload = []): void
    {
        $payload['_state_set_at'] = Carbon::now()->toIso8601String();
        $botUser->state           = $state;
        $botUser->payload         = $payload;
        $botUser->last_seen_at    = Carbon::now();
        $botUser->save();
    }

    protected function clearState(TelegraphChat $botUser): void
    {
        $botUser->state        = null;
        $botUser->payload      = null;
        $botUser->last_seen_at = Carbon::now();
        $botUser->save();
    }

    protected function normalizeTrack(string $text): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', trim($text)));
    }

    protected function parseNumber(string $text): ?float
    {
        $t = str_replace([' ', ','], ['', '.'], trim($text));
        if (!preg_match('/^-?\d+(\.\d+)?$/', $t)) {
            return null;
        }
        return (float) $t;
    }
}
