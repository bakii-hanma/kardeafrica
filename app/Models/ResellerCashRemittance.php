<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerCashRemittance extends Model
{
    const STATUS_PENDING   = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reseller_id',
        'amount',
        'external_reference',
        'status',
        'bill_id',
        'portal_url',
        'payment_method',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }
}
