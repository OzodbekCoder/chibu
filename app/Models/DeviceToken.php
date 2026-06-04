<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $guarded = [];

    public static function forUser(int $userId): ?string
    {
        return self::where('user_id', $userId)->value('token');
    }
}
