<?php

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphChat as ModelsTelegraphChat;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class TelegraphChat extends ModelsTelegraphChat implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'telegraph_chats';

    protected $fillable = [
        'chat_id',
        'name',
        'email',
        'password',
        'telegraph_bot_id',
        'state',
        'payload',
        'is_admin',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'payload'      => 'array',
        'is_admin'     => 'boolean',
        'last_seen_at' => 'datetime',
        'password'     => 'hashed',
    ];
}
