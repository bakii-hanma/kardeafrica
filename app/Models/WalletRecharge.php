<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recharge wallet via E-Billing (Airtel/Moov/carte).
 * Tracking des paiements pending / completed / failed.
 */
class WalletRecharge extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reseller_id',
        'amount',
        'external_reference',
        'payment_method',
        'status',
        'bill_id',
        'portal_url',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }
}
