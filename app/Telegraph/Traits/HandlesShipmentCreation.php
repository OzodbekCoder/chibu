<?php

namespace App\Telegraph\Traits;

use App\Models\Client;
use App\Models\Shipment;
use App\Models\TelegraphChat;
use App\Services\IpostService;
use Carbon\Carbon;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Str;

trait HandlesShipmentCreation
{
    protected function startCreateShipment(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $this->setState($botUser, 'shipment.create.track', ['shipment' => []]);

        $this->chat->html("➕ <b>Yangi yuk</b>\n\nTrek kodni yuboring.\nMasalan: <code>" . self::EXAMPLE_TRACK_CODE . "</code>")
            ->send();
    }

    protected function stepTrack(TelegraphChat $botUser, string $text): void
    {
        $track = $this->normalizeTrack($text);

        if (mb_strlen($track) < 3) {
            $this->chat->html("❌ Trek kod juda qisqa. Qaytadan yuboring.")->send();
            return;
        }

        if (Shipment::query()->where('track_code', $track)->exists()) {
            $this->chat->html("⚠️ Bu trek kod avval kiritilgan: <code>{$track}</code>\n\nBoshqasini yuboring yoki oxiriga farqlovchi qo'shing.")
                ->send();
            return;
        }

        $payload                           = $botUser->payload ?? [];
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
        $type    = $this->data->get('t');

        if (!in_array($type, ['kg', 'piece', 'm3'], true)) {
            $this->chat->html("❌ Noto'g'ri tarif turi.")->send();
            return;
        }

        $payload                              = $botUser->payload ?? [];
        $payload['shipment']['tariff_type']   = $type;

        $this->setState($botUser, 'shipment.create.amount', $payload);

        $label = match ($type) {
            'kg'    => "og'irlik (kg)",
            'm3'    => 'hajm (m³)',
            'piece' => 'soni (dona)',
        };

        $this->chat->html("🔢 Miqdorni yuboring: <b>{$label}</b>\nMasalan: <code>12.5</code>")->send();
    }

    protected function stepAmount(TelegraphChat $botUser, string $text): void
    {
        $payload = $botUser->payload ?? [];
        $type    = $payload['shipment']['tariff_type'] ?? null;

        if (!$type) {
            $this->startCreateShipment();
            return;
        }

        $amount = $this->parseNumber($text);
        if ($amount === null || $amount <= 0) {
            $this->chat->html("❌ Miqdor noto'g'ri. Masalan: <code>12.5</code> yoki <code>3</code>")->send();
            return;
        }

        if ($type === 'kg')        $payload['shipment']['weight_kg'] = $amount;
        elseif ($type === 'm3')    $payload['shipment']['volume_m3'] = $amount;
        else                       $payload['shipment']['pieces']    = (int) round($amount);

        $this->setState($botUser, 'shipment.create.tariff_value', $payload);

        $this->chat->html("🚚 Yetkazish turini tanlang:")
            ->keyboard(
                Keyboard::make()
                    ->row([
                        Button::make("✈️ Avia")->action('createShipmentDeliveryType')->param('d', 'avia'),
                        Button::make("🚛 Avto")->action('createShipmentDeliveryType')->param('d', 'avto'),
                        Button::make("🚢 Daryo")->action('createShipmentDeliveryType')->param('d', 'sea'),
                        Button::make("🗿 Boshqa")->action('createShipmentDeliveryType')->param('d', 'other'),
                    ])
                    ->row([Button::make("❌ Bekor qilish")->action('createShipmentCancel')])
            )
            ->send();
    }

    public function createShipmentDeliveryType(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $type    = $this->data->get('d');

        if (!in_array($type, ['avia', 'avto', 'sea', 'other'], true)) {
            $this->chat->html("❌ Noto'g'ri yetkazish turi.")->send();
            return;
        }

        $payload                               = $botUser->payload ?? [];
        $payload['shipment']['delivery_type']  = $type;

        $this->setState($botUser, 'shipment.create.price_yuan', $payload);

        $this->chat->html("💴 Tovar narxini <b>yuanda (¥)</b> yuboring.\nMasalan: <code>350.50</code>")
            ->keyboard(
                Keyboard::make()->row([
                    Button::make("⏭ O'tkazib yuborish")->action('skipPriceYuan'),
                    Button::make("❌ Bekor qilish")->action('createShipmentCancel'),
                ])
            )
            ->send();
    }

    protected function stepPriceYuan(TelegraphChat $botUser, string $text): void
    {
        $payload = $botUser->payload ?? [];
        $price   = $this->parseNumber($text);

        if ($price === null) {
            $this->chat->html("❌ Narx noto'g'ri. Masalan: <code>350.50</code> yoki <code>0</code>")->send();
            return;
        }

        if ($price > 0) {
            $payload['shipment']['price_yuan'] = $price;
        }

        $this->setState($botUser, 'shipment.create.vendor_or_link', $payload);
        $this->promptVendorOrLink();
    }

    public function skipPriceYuan(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $this->setState($botUser, 'shipment.create.vendor_or_link', $botUser->payload ?? []);
        $this->promptVendorOrLink();
    }

    private function promptVendorOrLink(): void
    {
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

    protected function stepVendorOrLink(TelegraphChat $botUser, string $text): void
    {
        $payload = $botUser->payload ?? [];
        $t       = trim($text);

        if ($t !== '0' && $t !== '') {
            if (filter_var($t, FILTER_VALIDATE_URL)) {
                $payload['shipment']['order_url']   = $t;
            } else {
                $payload['shipment']['vendor_name'] = $t;
            }
        }

        $this->setState($botUser, 'shipment.create.client', $payload);
        $this->sendClientPickMenu($botUser, $payload, 1);
    }

    public function skipVendorOrLink(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $payload = $botUser->payload ?? [];

        $this->setState($botUser, 'shipment.create.client', $payload);
        $this->sendClientPickMenu($botUser, $payload, 1);
    }

    protected function sendClientPickMenu(TelegraphChat $botUser, array $payload, int $page = 1): void
    {
        $perPage = 8;
        $p       = Client::query()
            ->where('created_by_id', $botUser->id)
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $kb = Keyboard::make();

        foreach ($p->items() as $client) {
            $kb->row([Button::make($client->name)->action('createShipmentClientPick')->param('cid', $client->id)]);
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

        $kb->row([Button::make("➕ Yangi mijoz qo'shish")->action('createShipmentNewClient')]);
        $kb->row([Button::make("❌ Bekor qilish")->action('createShipmentCancel')]);

        $text = $p->isEmpty() ? "👤 Hali mijoz yo'q. Yangi mijoz qo'shing:" : "👤 Mijozni tanlang yoki yangi qo'shing:";

        $this->chat->html($text)->keyboard($kb)->send();
    }

    public function createShipmentClientPage(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $payload = $botUser->payload ?? [];
        $page    = (int) ($this->data->get('p') ?? 1);

        $this->setState($botUser, 'shipment.create.client', $payload);
        $this->sendClientPickMenu($botUser, $payload, max(1, $page));
    }

    public function createShipmentClientPick(): void
    {
        $botUser  = $this->getOrCreateBotUser();
        $clientId = (int) data_get($this->data, 'cid', 0);

        if (!$clientId) {
            $this->chat->html("❌ Mijoz tanlanmadi.")->send();
            return;
        }

        $payload                          = $botUser->payload ?? [];
        $payload['shipment']['client_id'] = $clientId;

        $this->askForNote($botUser, $payload);
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
        $note    = trim($text);

        if ($note !== '') {
            $payload['shipment']['note'] = $note;
        }

        $this->showConfirm($botUser, $payload);
    }

    public function skipNote(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $this->showConfirm($botUser, $botUser->payload ?? []);
    }

    protected function stepTariffValue(TelegraphChat $botUser, string $text): void
    {
        $tariff = $this->parseNumber($text);
        if ($tariff === null || $tariff <= 0) {
            $this->chat->html("❌ Tarif noto'g'ri. Masalan: <code>3.2</code>")->send();
            return;
        }

        $payload                              = $botUser->payload ?? [];
        $payload['shipment']['tariff_value']  = $tariff;

        $this->setState($botUser, 'shipment.create.currency', $payload);

        $this->chat->html("💱 Valyutani tanlang:")
            ->keyboard(
                Keyboard::make()
                    ->row([
                        Button::make("💵 USD")->action('createShipmentCurrency')->param('c', 'USD'),
                        Button::make("💴 UZS")->action('createShipmentCurrency')->param('c', 'UZS'),
                    ])
                    ->row([Button::make("❌ Bekor qilish")->action('createShipmentCancel')])
            )
            ->send();
    }

    public function createShipmentCurrency(): void
    {
        $botUser  = $this->getOrCreateBotUser();
        $currency = $this->data->get('c');

        if (!in_array($currency, ['USD', 'UZS'], true)) {
            $this->chat->html("❌ Noto'g'ri valyuta.")->send();
            return;
        }

        $payload                                   = $botUser->payload ?? [];
        $payload['shipment']['tariff_currency']    = $currency;

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

        $this->showConfirm($botUser, $payload);
    }

    protected function stepCurrencyManualOrSkip(TelegraphChat $botUser, string $text): void
    {
        $payload  = $botUser->payload ?? [];
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

    public function skipUsdRate(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $this->showConfirm($botUser, $botUser->payload ?? []);
    }

    protected function showConfirm(TelegraphChat $botUser, array $payload): void
    {
        $this->setState($botUser, 'shipment.create.confirm', $payload);

        $s    = $payload['shipment'] ?? [];
        $type = $s['tariff_type'] ?? '-';

        $amountText = match ($type) {
            'kg'    => ($s['weight_kg'] ?? 0) . ' kg',
            'm3'    => ($s['volume_m3'] ?? 0) . ' m³',
            'piece' => ($s['pieces'] ?? 0) . ' dona',
            default => '-',
        };

        $clientId    = $s['client_id'] ?? null;
        $clientLabel = $clientId ? (Client::query()->find($clientId)?->name ?? '—') : '—';

        $preview = "✅ <b>Tekshiring:</b>\n"
            . "• Trek: <code>" . ($s['track_code'] ?? '-') . "</code>\n"
            . "• Tarif turi: <b>{$type}</b>\n"
            . "• Miqdor: <b>{$amountText}</b>\n"
            . "• Yetkazish: <b>" . ($s['delivery_type'] ?? '-') . "</b>\n"
            . "• Mijoz: <b>{$clientLabel}</b>\n";

        if (!empty($s['price_yuan']))  $preview .= "• Narx: <b>¥ {$s['price_yuan']}</b>\n";
        if (!empty($s['vendor_name'])) $preview .= "• Vendor: <b>{$s['vendor_name']}</b>\n";
        if (!empty($s['order_url']))   $preview .= "• Link: <a href=\"{$s['order_url']}\">buyurtma</a>\n";
        if (!empty($s['note']))        $preview .= "• Izoh: <i>{$s['note']}</i>\n";

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
        $s       = $payload['shipment'] ?? null;

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
            (new IpostService())->register($shipment, (string) $this->chat->chat_id);
        }

        $this->chat->html("🎉 Saqlandi!\n\n📦 Yuk ID: <b>{$shipment->id}</b>\nTrek: <code>{$shipment->track_code}</code>")
            ->send();

        $this->sendMainMenu();
    }

    public function createShipmentCancel(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $this->clearState($botUser);

        if ($this->messageId) {
            $this->chat->deleteMessage($this->messageId)->send();
        }

        $this->chat->html("❌ Bekor qilindi. /new bilan qayta boshlang.")->send();
    }
}
