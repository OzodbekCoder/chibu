<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpostService
{
    public const CACHE_TTL = 600; // 10 daqiqa

    public static function cacheKey(int $userId): string
    {
        return "ipost_map_{$userId}";
    }

    /**
     * Cached IPOST map. Reads from cache; fetches live only on miss.
     */
    public function cached(int $userId, string $chatIdHeader = ''): array
    {
        return Cache::remember(
            self::cacheKey($userId),
            self::CACHE_TTL,
            fn () => $this->fetchAllByTrack($chatIdHeader)
        );
    }

    /**
     * Store a freshly fetched map into cache (used by scheduler).
     */
    public function putCache(int $userId, array $map): void
    {
        Cache::put(self::cacheKey($userId), $map, self::CACHE_TTL);
    }

    public function forget(int $userId): void
    {
        Cache::forget(self::cacheKey($userId));
    }

    public const STATUS_LABELS = [
        'Warehouse'          => '🏭 Xitoy ombori',
        'Ulugchat'           => '🛂 Xitoy chegara punkti',
        'Osh'                => "🏔 O'zbekiston chegara punkti",
        'DistributionCenter' => '🏢 Hududiy omborda',
        'Approaching'        => '🚐 Yetkizib berish manziliga yuborildi',
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
        $endpoint = rtrim(config('ipost.endpoint', ''), '/');
        $apiKey   = config('ipost.api_key', '');
        if (!$endpoint || !$apiKey) return [];

        $headers = $this->headers($chatIdHeader);

        try {
            $res = Http::withHeaders($headers)
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->connectTimeout(10)
                ->timeout(20)
                ->retry(2, 500)
                ->get($endpoint);
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
        $endpoint = rtrim(config('ipost.endpoint', ''), '/');
        $apiKey   = config('ipost.api_key', '');
        if (!$endpoint || !$apiKey) return null;

        try {
            $response = Http::withHeaders($this->headers($chatIdHeader))
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->connectTimeout(10)
                ->timeout(20)
                ->retry(2, 1000)
                ->post($endpoint, ['trackingNumber' => $shipment->track_code]);

            if (!$response->successful()) {
                Log::warning('IPOST add failed', ['status' => $response->status(), 'track' => $shipment->track_code]);
                return null;
            }

            $data    = $response->json();
            $ipostId = is_array($data) ? ($data[0]['id'] ?? null) : ($data['id'] ?? null);
            if (!$ipostId) return null;

            $shipment->update(['ipost_id' => (string) $ipostId]);

            // Set remark (note) — separate call, must succeed for note to appear in IPOST
            if ($shipment->note) {
                $this->setRemark((int) $ipostId, $shipment->note, $chatIdHeader);
            }

            return (string) $ipostId;
        } catch (\Throwable $e) {
            Log::error('IPOST register exception', ['error' => $e->getMessage(), 'track' => $shipment->track_code]);
            return null;
        }
    }

    /**
     * Set/update remark (note) on an IPOST parcel.
     */
    public function setRemark(int $ipostId, string $remark, string $chatIdHeader = ''): bool
    {
        $endpoint = rtrim(config('ipost.endpoint', ''), '/');
        if (!$endpoint) return false;

        try {
            $res = Http::withHeaders($this->headers($chatIdHeader))
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->connectTimeout(10)
                ->timeout(20)
                ->retry(2, 1000)
                ->post("{$endpoint}/{$ipostId}/remark", ['remark' => $remark]);

            if (!$res->successful()) {
                Log::warning('IPOST remark failed', ['status' => $res->status(), 'ipost_id' => $ipostId]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('IPOST remark exception', ['error' => $e->getMessage(), 'ipost_id' => $ipostId]);
            return false;
        }
    }

    private function headers(string $chatIdHeader): array
    {
        // Single IPOST account: prefer fixed IPOST_CHAT_ID from config.
        // Web users have synthetic chat_id (web-xxxx) that IPOST rejects with 401.
        $chatId = config('ipost.chat_id') ?: $chatIdHeader;

        return [
            'x-apikey'    => config('ipost.api_key', ''),
            'x-timestamp' => 1777288697,
            'x-chat-id'   => (string) $chatId,
            'source'      => 'TELEGRAM',
            'Accept'      => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }
}
