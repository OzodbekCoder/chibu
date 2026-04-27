<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    protected $guarded = [];

    public static function latestYuan(): ?self
    {
        return self::query()
            ->where('base', 'CNY')
            ->where('quote', 'UZS')
            ->orderByDesc('rate_date')
            ->first();
    }
}
