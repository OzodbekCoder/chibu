<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'weight_kg' => 'decimal:3',
        'volume_m3' => 'decimal:4',
        'tariff_value' => 'decimal:4',
        'usd_rate' => 'decimal:4',
        'status_at' => 'datetime',
    ];
}
