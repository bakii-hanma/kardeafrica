<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserCard extends Model
{
    use HasFactory;

    protected $table = 'user_cards';

    protected $fillable = [
        'user_id',
        'order_id',
        'order_item_id',
        'product_id',
        'checkout_card_id',
        'name',
        'brand',
        'serial_number',
        'card_code',
        'pin',
        'expiration_date',
        'status',
        'face_value',
        'currency',
        'image_url',
        'metadata',
    ];

    protected $casts = [
        'face_value' => 'decimal:2',
        'expiration_date' => 'date',
        'metadata' => 'array',
        // Le code et le PIN SONT la carte : les lire, c'est pouvoir la dépenser.
        // Chiffrés au repos, la clé vivant dans l'environnement et jamais en base.
        'card_code' => 'encrypted',
        'pin' => 'encrypted',
    ];

    // Hidden fields for API - code/pin only shown on detail view
    protected $hidden = [
        'card_code',
        'pin',
    ];

    // Accessors auto-injectes dans toArray()/toJson()
    protected $appends = [
        'price_paid_xaf', // prix reellement paye en FCFA (vs face_value qui est la valeur faciale)
    ];

    /**
     * Prix reellement paye en FCFA (XAF), depuis l'OrderItem associe.
     * Si la relation n'est pas chargee, fallback sur face_value.
     */
    public function getPricePaidXafAttribute(): float
    {
        $unitPrice = $this->orderItem?->unit_price;
        return (float) ($unitPrice ?? $this->face_value ?? 0);
    }

    const STATUS_ACTIVE = 'active';
    const STATUS_USED = 'used';
    const STATUS_EXPIRED = 'expired';

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec la commande
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation avec l'item de commande
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Vérifier si la carte est active
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expiration_date && $this->expiration_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si la carte est expirée
     */
    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    /**
     * Scope pour les cartes actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Format solde/valeur
     */
    public function getFormattedValueAttribute(): string
    {
        return number_format($this->face_value, 0, ',', ' ') . ' FCFA';
    }
}
