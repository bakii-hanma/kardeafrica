<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'value',
        'balance',
        'currency',
        'code',
        'status',
        'description',
        'image',
        'brand',
        'expiry_date',
        'user_id',
        'metadata'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'balance' => 'decimal:2',
        'expiry_date' => 'date',
        'metadata' => 'array'
    ];

    /**
     * Relation avec l'utilisateur propriétaire de la carte
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les codes de cartes
     */
    public function cardCodes()
    {
        return $this->hasMany(CardCode::class);
    }

    /**
     * Relation avec les éléments de commande
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relation avec les éléments du panier
     */
    public function cartItems()
    {
        return $this->hasMany(ShoppingCart::class);
    }

    /**
     * Vérifier si la carte est active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->expiry_date === null || $this->expiry_date->isFuture());
    }

    /**
     * Vérifier si la carte est expirée
     */
    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    /**
     * Obtenir le solde formaté
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Obtenir la valeur formatée
     */
    public function getFormattedValueAttribute(): string
    {
        return number_format($this->value, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Générer un code unique pour la carte
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = 'KA' . strtoupper(bin2hex(random_bytes(8)));
        } while (self::where('code', $code)->exists());
        
        return $code;
    }
}
