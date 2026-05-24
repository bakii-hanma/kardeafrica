<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MerchantCardRedemption
 * ===
 * Journal d'utilisation des cartes au comptoir. Une entrée = un débit (total
 * ou partiel) avec snapshot balance_before/after pour l'audit trail.
 *
 * Toutes les insertions se font dans une DB::transaction() avec un lockForUpdate
 * sur le MerchantCardPurchase associé (cf MerchantRedeemService — Phase 5).
 */
class MerchantCardRedemption extends Model
{
    use HasFactory;

    public const METHOD_QR   = 'qr';
    public const METHOD_CODE = 'code';

    protected $fillable = [
        'merchant_card_purchase_id', 'reseller_id', 'merchant_user_id',
        'amount_used', 'balance_before', 'balance_after',
        'scan_method', 'location', 'notes',
        'redeemed_at',
    ];

    protected $casts = [
        'amount_used'    => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
        'redeemed_at'    => 'datetime',
    ];

    // ============================================================
    // Relations
    // ============================================================

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(MerchantCardPurchase::class, 'merchant_card_purchase_id');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /** Employé qui a fait le scan (nullable si owner direct) */
    public function merchantUser(): BelongsTo
    {
        return $this->belongsTo(MerchantUser::class);
    }
}
