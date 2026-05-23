<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ResellerOrder extends Model
{
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_FAILED     = 'failed';
    const STATUS_REFUNDED   = 'refunded';

    const PAYMENT_PENDING   = 'pending';
    const PAYMENT_COMPLETED = 'completed';
    const PAYMENT_FAILED    = 'failed';
    const PAYMENT_REFUNDED  = 'refunded';

    protected $fillable = [
        'reseller_id',
        'order_number',
        'claim_token',
        'customer_name',
        'customer_phone',
        'subtotal',
        'commission_earned',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'payment_method',
        'external_reference',
        'claimed_at',
        'expires_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'subtotal'          => 'decimal:2',
        'commission_earned' => 'decimal:2',
        'total_amount'      => 'decimal:2',
        'claimed_at'        => 'datetime',
        'expires_at'        => 'datetime',
        'completed_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $o) {
            if (empty($o->order_number)) {
                $o->order_number = 'KV' . now()->format('YmdHis') . strtoupper(Str::random(3));
            }
            if (empty($o->claim_token)) {
                $o->claim_token = (string) Str::uuid();
            }
            if (empty($o->expires_at)) {
                $o->expires_at = now()->addDays(30);
            }
        });
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function items()
    {
        return $this->hasMany(ResellerOrderItem::class);
    }

    public function cards()
    {
        return $this->hasMany(ResellerCard::class);
    }

    public function getIsClaimableAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->expires_at?->isFuture();
    }
}
