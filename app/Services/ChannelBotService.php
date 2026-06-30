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
        ],
    ];

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
        $text = $this->generate($type);
        if (!$text) return false;
        return $this->send($text);
    }
}
