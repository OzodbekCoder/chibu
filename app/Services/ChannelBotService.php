<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChannelBotService
{
    /** Mavzu uchun keng yo'nalishlar — har safar tasodifiy tanlanadi */
    private const TOPICS = [
        'texno' => [
            'sun\'iy intellekt va mashina o\'rganish',
            'dasturlash tillari va frameworklar',
            'kiberxavfsizlik va maxfiylik',
            'yangi gadjetlar va qurilmalar',
            'kosmik texnologiyalar',
            'web va mobil ishlab chiqish',
            'open source loyihalar',
            'kvant kompyuterlar',
            'blockchain va kripto texnologiya',
            'robototexnika va avtomatlashtirish',
            'ma\'lumotlar bazasi va backend',
            'DevOps va bulutli texnologiyalar',
        ],
        'hobby' => [
            'qiziqarli ilmiy faktlar',
            'tarixdan ajablanarli voqealar',
            'foydali hayotiy lifehacklar',
            'psixologiya va miya haqida',
            'sayohat va geografiya qiziqarliklari',
            'kitob va film tavsiyalari',
            'sog\'lom turmush va sport',
            'kosmos va astronomiya',
            'tabiat va hayvonot olami',
            'mashhur ixtirochilar va kashfiyotlar',
            'jahon futboli — yangiliklar, rekordlar, afsonaviy o\'yinchilar',
            'o\'zbek futboli va milliy terma jamoa',
            'jahon badiiy adabiyoti — yozuvchilar va asarlar',
            'o\'zbek badiiy adabiyoti — Qodiriy, Oybek va boshqalar',
            'mashhur kitoblardan iqtiboslar va g\'oyalar',
            'ilmiy-fantastik kinolar — tavsiya va qiziqarli faktlar',
            'detektiv kinolar va seriallar — eng zo\'rlari',
            'komediya kinolar — kulgili va mashhur asarlar',
            'kino olami — rejissyorlar, syujet sirlari, Oskar',
        ],
    ];

    /** Foydalanuvchi joylaydigan media papka */
    private const MEDIA_DIR = 'channel-media';

    /**
     * AI bilan post matni yaratadi.
     */
    public function generate(string $type): ?string
    {
        $key   = config('channelbot.anthropic_key');
        $model = config('channelbot.anthropic_model');
        if (!$key) return null;

        $topics = self::TOPICS[$type] ?? self::TOPICS['texno'];
        $topic  = $topics[array_rand($topics)];

        $isTech = $type === 'texno';
        $style  = $isTech
            ? "Texno post: 2-3 abzas, mavzuni tushuntir, qiziqarli detal yoki misol qo'sh. Batafsil lekin og'ir emas."
            : "Qiziqarli post: 2-4 jumla, yengil va jonli. Ajablanarli fakt yoki foydali maslahat.";

        $prompt = <<<TXT
Sen O'zbek tilidagi Telegram kanal uchun post yozasan. Kanal mavzusi: texnologiya va qiziqarli narsalar.

Bugungi post turi: {$type}
Tanlangan mavzu yo'nalishi: {$topic}

Talablar:
- {$style}
- Toza, tabiiy o'zbek tilida yoz (lotin alifbosi).
- Boshida mos emoji bilan qisqa sarlavha (qalin <b>...</b>).
- Telegram HTML formatdan foydalan: <b>, <i>, <code> mumkin. Markdown EMAS.
- Hashtag QO'SHMA. Reklama yoki "obuna bo'ling" YOZMA.
- Aniq, yangi, takrorlanmaydigan mavzuni o'zing tanlab chuqurroq yoz.
- Faqat post matnini qaytar, boshqa hech narsa yozma.
TXT;

        try {
            $res = Http::withHeaders([
                'x-api-key'         => $key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->connectTimeout(10)
                ->timeout(60)
                ->retry(2, 2000)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => $model,
                    'max_tokens' => 1024,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if (!$res->successful()) {
                Log::warning('ChannelBot AI failed', ['status' => $res->status()]);
                return null;
            }

            $text = $res->json('content.0.text');
            return $text ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::error('ChannelBot AI exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Matnni kanalga yuboradi.
     */
    public function send(string $text): bool
    {
        $token   = config('channelbot.token');
        $channel = config('channelbot.channel');
        if (!$token || !$channel) return false;

        try {
            $res = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(2, 2000)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'                  => $channel,
                    'text'                     => $text,
                    'parse_mode'               => 'HTML',
                    'disable_web_page_preview' => false,
                ]);

            if (!$res->successful()) {
                Log::warning('ChannelBot send failed', ['status' => $res->status(), 'body' => mb_substr($res->body(), 0, 200)]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('ChannelBot send exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function generateAndSend(string $type): bool
    {
        // ~40% ehtimol bilan media papkadagi fayldan post (agar fayl bo'lsa)
        $media = $this->pickMedia();
        if ($media && random_int(1, 100) <= 40) {
            $caption = $this->generateCaption($type, $media['hint']);
            if ($caption && $this->sendMedia($media['path'], $caption)) {
                $this->consumeMedia($media['path']);
                return true;
            }
        }

        $text = $this->generate($type);
        if (!$text) return false;
        return $this->send($text);
    }

    /**
     * Media papkadan tasodifiy fayl tanlaydi (yo'l + fayl nomidan mavzu maslahati).
     * @return array{path:string,hint:string}|null
     */
    private function pickMedia(): ?array
    {
        $dir = storage_path('app/' . self::MEDIA_DIR);
        if (!is_dir($dir)) return null;

        $maxBytes = 5 * 1024 * 1024; // 5 MB
        $files = array_values(array_filter(
            glob($dir . '/*') ?: [],
            fn($f) => is_file($f)
                && !str_starts_with(basename($f), '.')
                && filesize($f) > 0
                && filesize($f) <= $maxBytes
        ));
        if (empty($files)) return null;

        $path = $files[array_rand($files)];
        $hint = pathinfo($path, PATHINFO_FILENAME);
        $hint = trim(preg_replace('/[_\-]+/', ' ', $hint));

        return ['path' => $path, 'hint' => $hint];
    }

    /** Yuborilgan media faylni o'chiradi (server joyini band qilmasin). */
    private function consumeMedia(string $path): void
    {
        @unlink($path);
    }

    /** Fayl uchun qisqa caption yozadi (fayl nomi — mavzu maslahati). */
    private function generateCaption(string $type, string $hint): ?string
    {
        $key   = config('channelbot.anthropic_key');
        $model = config('channelbot.anthropic_model');
        if (!$key) return null;

        $prompt = <<<TXT
Telegram kanal uchun rasm/fayl ostiga qisqa, jonli izoh (caption) yoz.
Post turi: {$type}. Fayl mavzusi (nomidan): "{$hint}".

Talablar:
- 1-3 jumla, O'zbek tilida (lotin).
- Boshida mos emoji bilan qisqa qalin sarlavha (<b>...</b>).
- Telegram HTML: <b>, <i> mumkin. Markdown EMAS. Hashtag YO'Q.
- Faqat caption matnini qaytar.
TXT;

        try {
            $res = Http::withHeaders([
                'x-api-key'         => $key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->connectTimeout(10)->timeout(60)->retry(2, 2000)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => $model,
                    'max_tokens' => 512,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ]);

            $text = $res->successful() ? $res->json('content.0.text') : null;
            return $text ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::error('ChannelBot caption exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /** Rasm yoki fayl + caption yuboradi (kengaytmaga qarab photo/document). */
    private function sendMedia(string $path, string $caption): bool
    {
        $token   = config('channelbot.token');
        $channel = config('channelbot.channel');
        if (!$token || !$channel || !is_file($path)) return false;

        $ext      = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $isPhoto  = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
        $method   = $isPhoto ? 'sendPhoto' : 'sendDocument';
        $field    = $isPhoto ? 'photo' : 'document';

        try {
            $res = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                ->connectTimeout(10)->timeout(60)->retry(2, 2000)
                ->attach($field, file_get_contents($path), basename($path))
                ->post("https://api.telegram.org/bot{$token}/{$method}", [
                    'chat_id'    => $channel,
                    'caption'    => mb_substr($caption, 0, 1024),
                    'parse_mode' => 'HTML',
                ]);

            if (!$res->successful()) {
                Log::warning('ChannelBot media send failed', ['status' => $res->status(), 'body' => mb_substr($res->body(), 0, 200)]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('ChannelBot media exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
