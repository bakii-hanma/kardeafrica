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
        'admin_commission_rate', 'vendor_commission_rate',
    ];

    protected $casts = [
        'denominations'           => 'array',           // JSON ↔ array d'entiers
        'allow_custom_amount'     => 'boolean',
        'is_active'               => 'boolean',
        'min_amount'              => 'decimal:2',
        'max_amount'              => 'decimal:2',
        'total_revenue'           => 'decimal:2',
        'admin_commission_rate'   => 'decimal:2',
        'vendor_commission_rate'  => 'decimal:2',
        'activated_at'            => 'datetime',
    ];

    // ============================================================
    // Validation des montants (anti-fraude C2)
    // ============================================================

    /**
     * Un montant est-il ACHETABLE pour cette carte ? Un montant listé dans
     * `denominations` est toujours accepté ; sinon, seulement si la carte
     * autorise le montant libre et que le montant est dans [min_amount, max_amount].
     *
     * @param bool $requireActive true à la vente (vitrine), false à la livraison
     *        d'une commande déjà payée (la carte a pu être désactivée depuis).
     */
    public function isValidAmount($amount, bool $requireActive = true): bool
    {
        $amount = (float) $amount;
        if ($amount <= 0) return false;
        if ($requireActive && !$this->is_active) return false;

        $denoms = collect($this->denominations ?? [])->map(fn ($d) => (float) $d);
        if ($denoms->contains($amount)) return true;

        if ($this->allow_custom_amount) {
            $min = $this->min_amount !== null ? (float) $this->min_amount : 0.0;
            $max = $this->max_amount !== null ? (float) $this->max_amount : PHP_FLOAT_MAX;
            return $amount >= $min && $amount <= $max;
        }
        return false;
    }

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
