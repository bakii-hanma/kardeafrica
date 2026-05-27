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

    /** Catégories proposées dans le formulaire admin (synchro avec /gabon). */
    public const CATEGORIES = [
        'restaurant'   => 'Restaurant / Café',
        'mode'         => 'Mode & Vêtements',
        'beaute'       => 'Beauté & Coiffure',
        'spa'          => 'Spa & Bien-être',
        'sport'        => 'Sport & Fitness',
        'supermarche'  => 'Supermarché / Alimentation',
        'electronique' => 'Électronique & High-tech',
        'maison'       => 'Maison & Déco',
        'loisirs'      => 'Loisirs & Divertissement',
        'sante'        => 'Santé & Pharmacie',
        'autre'        => 'Autre',
    ];

    protected $fillable = [
        'reseller_id',
        'name', 'description', 'category', 'visual_url',
        'unique_code', 'pin_code', 'expires_at',
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
        'expires_at'           => 'date',
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

    /**
     * Cartes affichables sur la vitrine publique.
     * Le catalogue est désormais 100 % admin → on filtre juste sur is_active.
     * Les cartes héritées d'un marchand (reseller_id non null) doivent toujours
     * avoir un marchand actif + KYC approuvé pour rester en ligne.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         // Cartes catalogue admin (pas de marchand attaché)
                         $q->whereNull('reseller_id')
                           // OU cartes héritées d'un marchand : marchand toujours valide
                           ->orWhereHas('reseller', fn ($r) => $r->where('kyc_status', 'approved')->where('is_active', true));
                     });
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
