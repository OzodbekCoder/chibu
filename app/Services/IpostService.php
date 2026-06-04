<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpostService
{
    public const STATUS_LABELS = [
        'Warehouse'          => '🏭 Xitoy ombori',
        'Ulugchat'           => '🛂 Xitoy chegara punkti',
        'Osh'                => "🏔 O'zbekiston chegara punkti",
        'DistributionCenter' => '🏢 Hududiy omborda',
        'DropZone'           => '📍 Qabul qilish punkti',
        'Delivered'          => '✅ Qabul qilindi',
        'CREATED'            => '🆕 Yangi yaratildi',
        'Yiwu'               => "🚀 Xitoydan yo'lga chiqdi",
    ];

    // IPOST statuses where user can accept/archive the shipment
    public const ACCEPT_STATUSES = ['DistributionCenter', 'DropZone', 'Delivered'];

    public const PAY_LABELS = [
        'PAID'      => "✅ To'landi",
        'UN_BILLED' => "⏳ To'lanmadi",
    ];

    /**
     * Fetch all parcels from IPOST keyed by uppercased trackingNumber.
     */
    public function fetchAllByTrack(string $chatIdHeader = ''): array
    {
        $endpoint = rtrim(env('IPOST_ADD_ENDPOINT', ''), '/');
        $apiKey   = env('IPOST_API_KEY', '');
        if (!$endpoint || !$apiKey) return [];

        $headers = $this->headers($chatIdHeader);

        try {
            $res = Http::withHeaders($headers)->timeout(15)->get($endpoint);
            if (!$res->successful()) {
                Log::warning('IPOST fetch failed', ['status' => $res->status()]);
                return [];
            }
            $data  = $res->json();
            $items = is_array($data) && array_is_list($data) ? $data : (isset($data['trackingNumber']) ? [$data] : []);
            $out   = [];
            foreach ($items as $item) {
                if (!empty($item['trackingNumber'])) {
                    $out[mb_strtoupper($item['trackingNumber'])] = $item;
                }
            }
            return $out;
        } catch (\Throwable $e) {
            Log::error('IPOST fetch exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function register(Shipment $shipment, string $chatIdHeader = ''): ?string
    {
        $endpoint = rtrim(env('IPOST_ADD_ENDPOINT', ''), '/');
        $apiKey   = env('IPOST_API_KEY', '');
        if (!$endpoint || !$apiKey) return null;

        try {
            $response = Http::withHeaders($this->headers($chatIdHeader))
                ->timeout(15)
                ->post($endpoint, ['trackingNumber' => $shipment->track_code]);

            if (!$response->successful()) {
                Log::warning('IPOST add failed', ['status' => $response->status()]);
                return null;
            }

            $data    = $response->json();
            $ipostId = is_array($data) ? ($data[0]['id'] ?? null) : ($data['id'] ?? null);
            if (!$ipostId) return null;

            $shipment->update(['ipost_id' => (string) $ipostId]);

            if ($shipment->note) {
                Http::withHeaders($this->headers($chatIdHeader))
                    ->timeout(15)
                    ->post("{$endpoint}/{$ipostId}/remark", ['remark' => $shipment->note]);
            }

            return (string) $ipostId;
        } catch (\Throwable $e) {
            Log::error('IPOST register exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function headers(string $chatIdHeader): array
    {
        // Single IPOST account: prefer fixed IPOST_CHAT_ID from env.
        // Web users have synthetic chat_id (web-xxxx) that IPOST rejects with 401.
        $chatId = env('IPOST_CHAT_ID') ?: $chatIdHeader;

        return [
            'x-apikey'    => env('IPOST_API_KEY', ''),
            'x-timestamp' => 1777288697,
            'x-chat-id'   => (string) $chatId,
            'source'      => 'TELEGRAM',
            'Accept'      => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
        ];
    }
}
