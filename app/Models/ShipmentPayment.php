<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentPayment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount'   => 'decimal:2',
        'usd_rate' => 'decimal:2',
        'paid_at'  => 'date',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(TelegraphChat::class, 'created_by_id');
    }
}
