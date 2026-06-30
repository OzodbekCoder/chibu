<?php

return [
    // Telegram bot token (channel'ga admin qilingan bot)
    'token'   => env('CHANNEL_BOT_TOKEN', ''),
    // Channel: @username yoki -100xxxxxxxxxx
    'channel' => env('CHANNEL_ID', ''),

    // Claude API
    'anthropic_key'   => env('ANTHROPIC_API_KEY', ''),
    'anthropic_model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),

    // Posting oynasi (soat, mahalliy vaqt)
    'window_start' => (int) env('CHANNEL_WINDOW_START', 9),
    'window_end'   => (int) env('CHANNEL_WINDOW_END', 18),

    // Har kuni postlar turlari (random vaqtda tashlanadi)
    'daily_posts' => ['texno', 'hobby'],

    // Postlar shu shaxs nomidan, birinchi shaxsda yoziladi
    'author' => env('CHANNEL_AUTHOR', 'kanal egasi'),
];
