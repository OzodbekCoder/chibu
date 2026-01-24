<?php

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphChat as ModelsTelegraphChat;

class TelegraphChat extends ModelsTelegraphChat
{
    protected $table = 'telegraph_chats';

    protected $fillable = [
        'chat_id',
        'name',
        'telegraph_bot_id',
        'state',
        'payload',
        'is_admin',
        'last_seen_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'is_admin' => 'boolean',
        'last_seen_at' => 'datetime'
    ];
}
