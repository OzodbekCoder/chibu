<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    protected $guarded = [];

    public static function latestYuan(?int $userId = null): ?self
    {
        return self::query()
            ->where('base', 'CNY')
            ->where('quote', 'UZS')
            ->when($userId, fn ($q) => $q->where('created_by_id', $userId))
            ->orderByDesc('rate_date')
            ->first();
    }
}
