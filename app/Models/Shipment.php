<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'track_code',
        'vendor_name',
        'weight_kg',
        'volume_m3',
        'pieces',
        'tariff_type',
        'tariff_value',
        'tariff_currency',
        'usd_rate',
        'status',
        'status_at',
        'note',
        'created_by_telegram_id'
    ];

    protected $casts = [
        'weight_kg' => 'decimal:3',
        'volume_m3' => 'decimal:4',
        'tariff_value' => 'decimal:4',
        'usd_rate' => 'decimal:4',
        'status_at' => 'datetime',
    ];
}
