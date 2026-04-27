<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'weight_kg'      => 'decimal:3',
        'volume_m3'      => 'decimal:4',
        'tariff_value'   => 'decimal:2',
        'usd_rate'       => 'decimal:2',
        'status_at'      => 'datetime',
        'arrived_at'     => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(TelegraphChat::class, 'created_by_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ShipmentPayment::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ShipmentStatusLog::class);
    }

    public function totalPaidUsd(): float
    {
        return $this->payments->sum(function ($p) {
            if ($p->currency === 'USD') {
                return (float) $p->amount;
            }
            return $p->usd_rate > 0 ? (float) $p->amount / (float) $p->usd_rate : 0;
        });
    }

    public function getAmountLabelAttribute(): string
    {
        return match ($this->tariff_type) {
            'kg'    => $this->weight_kg . ' kg',
            'm3'    => $this->volume_m3 . ' m3',
            'piece' => $this->pieces . ' dona',
            default => '-',
        };
    }
}
