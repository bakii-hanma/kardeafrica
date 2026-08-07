<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MerchantCardPurchase
 * ===
 * Achat d'un MerchantCard par un client. Contient :
 *  - le code 8 chiffres + le QR payload signé pour vérification
 *  - les coordonnées acheteur + destinataire (offrir à quelqu'un)
 *  - le solde restant (utilisations partielles autorisées)
 *  - le cycle de vie (inactive → active → partially_used → fully_used / expired)
 */
class MerchantCardPurchase extends Model
{
    use HasFactory;

    // Cycle de vie
    public const STATUS_INACTIVE        = 'inactive';        // créée mais pas encore payée
    public const STATUS_ACTIVE          = 'active';          // payée, utilisable
    public const STATUS_PARTIALLY_USED  = 'partially_used';  // débitée mais solde > 0
    public const STATUS_FULLY_USED      = 'fully_used';      // solde = 0
    public const STATUS_EXPIRED         = 'expired';
    public const STATUS_CANCELLED       = 'cancelled';

    public const PAYMENT_PENDING   = 'pending';
    public const PAYMENT_PAID      = 'paid';
    public const PAYMENT_FAILED    = 'failed';
    public const PAYMENT_REFUNDED  = 'refunded';

    protected $fillable = [
        'merchant_card_id',
        'order_id', 'order_item_id',
        'unique_code', 'pin_code', 'qr_payload',
        'buyer_name', 'buyer_phone', 'buyer_email',
        'recipient_name', 'recipient_phone', 'recipient_message',
        'amount', 'remaining_balance', 'currency',
        'admin_commission_amount', 'vendor_commission_amount', 'owner_net_amount',
        'payment_method', 'payment_status', 'payment_ref', 'ebilling_transaction_id',
        'status', 'delivery_channel', 'delivered_at',
        'expires_at', 'paid_at',
    ];

    protected $casts = [
        'amount'                    => 'decimal:2',
        'remaining_balance'         => 'decimal:2',
        'admin_commission_amount'   => 'decimal:2',
        'vendor_commission_amount'  => 'decimal:2',
        'owner_net_amount'          => 'decimal:2',
        'delivery_channel'          => 'array',
        'expires_at'                => 'datetime',
        'paid_at'                   => 'datetime',
        'delivered_at'              => 'datetime',
    ];

    /**
     * Le QR payload n'est JAMAIS exposé en API publique (cf spec §SÉCURITÉ #1)
     * — il sert uniquement lors du scan, dans une route authentifiée vendor.
     */
    // SÉCURITÉ (H14) : le couple unique_code + pin_code EST le secret
    // d'authentification au comptoir — masquer le QR seul ne suffisait pas.
    // Masqué en JSON ; l'acheteur voit sa carte via le miroir UserCard, et le
    // scan marchand (Owner/ScanController) lit pin_code par accès propriété.
    protected $hidden = ['qr_payload', 'unique_code', 'pin_code'];

    // ============================================================
    // Relations
    // ============================================================

    public function merchantCard(): BelongsTo
    {
        return $this->belongsTo(MerchantCard::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(MerchantCardRedemption::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    // ============================================================
    // Helpers
    // ============================================================

    /** Est-elle encore utilisable maintenant ? */
    public function isRedeemable(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID
            && in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PARTIALLY_USED], true)
            && $this->expires_at?->isFuture()
            && (float) $this->remaining_balance > 0;
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }
}
