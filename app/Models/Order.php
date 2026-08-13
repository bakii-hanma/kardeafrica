<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'external_reference',
        'user_id',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency',
        'payment_status',
        'payment_method',
        'cash_reseller_id',
        'cash_lock_expires_at',
        'cash_confirmation_code',
        'billing_details',
        'notes',
        'completed_at',
        // H4 — marqueur posé sous verrou AVANT l'appel afrikard /orders/checkout
        // (voir ProcessCheckoutJob) : un rejeu ne doit jamais re-facturer la carte.
        'delivery_requested_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billing_details' => 'array',
        'completed_at' => 'datetime',
        'cash_lock_expires_at' => 'datetime',
        'delivery_requested_at' => 'datetime',
    ];

    const PAYMENT_METHOD_CASH_RESELLER = 'cash_at_reseller';

    /**
     * Statuts de commande possibles
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    // État transitoire : virement de remboursement en cours auprès du PSP.
    // Marqué DANS un verrou avant l'appel transfer.php pour empêcher tout
    // double remboursement (voir OrderController::refund).
    const STATUS_REFUNDING = 'refunding';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Statuts de paiement possibles
     */
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PROCESSING = 'processing';
    const PAYMENT_STATUS_COMPLETED = 'completed';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_CANCELLED = 'cancelled';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

    /**
     * Génère automatiquement le numéro de commande
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les éléments de commande
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relation avec les paiements
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relation avec les cartes utilisateur recues
     */
    public function userCards()
    {
        return $this->hasMany(UserCard::class);
    }

    /**
     * Vendeur Kardafrica chargé d'encaisser le client (paiement cash physique).
     */
    public function cashReseller()
    {
        return $this->belongsTo(Reseller::class, 'cash_reseller_id');
    }

    /**
     * Génère un numéro de commande unique
     */
    public static function generateOrderNumber()
    {
        do {
            $number = 'KA' . date('Ymd') . Str::upper(Str::random(6));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Commandes par statut
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Commandes terminées
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Commandes en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Vérifie si la commande est payée
     */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Vérifie si la commande est terminée
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Calcule le total avec taxes
     */
    public function calculateTotal()
    {
        $this->subtotal = $this->orderItems->sum('total_price');
        $this->tax_amount = $this->subtotal * 0.18; // TVA 18%
        $this->total_amount = $this->subtotal + $this->tax_amount;
        $this->save();
    }

    /**
     * Vérifie si la commande peut être annulée
     */
    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Relation avec le paiement principal
     */
    public function payment()
    {
        return $this->hasOne(Payment::class)->latest();
    }

    /**
     * Obtenir le statut formaté
     */
    public function getStatusLabelAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours de traitement',
            self::STATUS_SHIPPED => 'Expédiée',
            self::STATUS_DELIVERED => 'Livrée',
            self::STATUS_COMPLETED => 'Terminée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_REFUNDING => 'Remboursement en cours',
            self::STATUS_REFUNDED => 'Remboursée'
        ];
        
        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Obtenir le statut de paiement formaté
     */
    public function getPaymentStatusLabelAttribute()
    {
        $statuses = [
            self::PAYMENT_STATUS_PENDING => 'En attente',
            self::PAYMENT_STATUS_PROCESSING => 'En cours',
            self::PAYMENT_STATUS_COMPLETED => 'Payé',
            self::PAYMENT_STATUS_FAILED => 'Échoué',
            self::PAYMENT_STATUS_CANCELLED => 'Annulé',
            self::PAYMENT_STATUS_REFUNDED => 'Remboursé'
        ];
        
        return $statuses[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * Obtenir le total formaté
     */
    public function getFormattedTotalAttribute()
    {
        return number_format($this->total_amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Marquer la commande comme complétée
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'payment_status' => self::PAYMENT_STATUS_COMPLETED,
            'completed_at' => now()
        ]);
    }

    /**
     * Marquer la commande comme annulée
     */
    public function markAsCancelled()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'payment_status' => self::PAYMENT_STATUS_CANCELLED
        ]);
    }
}
