<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MerchantCard
 * ===
 * Template de carte-cadeau créé par un marchand (≠ ResellerCard qui est une
 * carte du catalogue afrikard que le vendor revend).
 *
 * Un MerchantCard appartient à un Reseller (= le marchand, après validation
 * KYC) et donne lieu à 0..N MerchantCardPurchase (= achats clients).
 */
class MerchantCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'name', 'description', 'category', 'visual_url',
        'denominations', 'allow_custom_amount', 'min_amount', 'max_amount', 'currency',
        'validity_months', 'terms_conditions',
        'is_active', 'activated_at', 'rejection_reason',
        'total_sold', 'total_revenue',
    ];

    protected $casts = [
        'denominations'        => 'array',           // JSON ↔ array d'entiers
        'allow_custom_amount'  => 'boolean',
        'is_active'            => 'boolean',
        'min_amount'           => 'decimal:2',
        'max_amount'           => 'decimal:2',
        'total_revenue'        => 'decimal:2',
        'activated_at'         => 'datetime',
    ];

    // ============================================================
    // Relations
    // ============================================================

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(MerchantCardPurchase::class);
    }

    // ============================================================
    // Scopes
    // ============================================================

    /** Cartes affichables sur la vitrine publique */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->whereHas('reseller', fn ($q) => $q->where('kyc_status', 'approved')->where('is_active', true));
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->whereHas('reseller', fn ($q) => $q->where('business_type', $category));
    }
}
