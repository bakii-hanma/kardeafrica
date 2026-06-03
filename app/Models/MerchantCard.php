<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MerchantCard
 * ===
 * Template de carte-cadeau locale (Carte Gabon), créé par l'admin dans
 * /admin/merchant-cards. Chaque carte appartient à un CardOwner (commerçant)
 * qui peut la valider au comptoir via son dashboard /proprietaire/*. Chaque
 * template donne lieu à 0..N MerchantCardPurchase (= achats clients, chacun
 * avec son propre code/PIN généré à la livraison).
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
        'card_owner_id',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(CardOwner::class, 'card_owner_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(MerchantCardPurchase::class);
    }

    // ============================================================
    // Scopes
    // ============================================================

    /** Cartes affichables sur la vitrine publique : catalogue admin actif. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
