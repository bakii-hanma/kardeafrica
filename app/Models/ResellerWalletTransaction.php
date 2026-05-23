<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerWalletTransaction extends Model
{
    protected $fillable = [
        'reseller_id',
        'admin_id',
        'wallet',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
