<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerCard extends Model
{
    const STATUS_ACTIVE  = 'active';
    const STATUS_USED    = 'used';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'reseller_order_id',
        'reseller_order_item_id',
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

    // SÉCURITÉ (H13) : la valeur d'une carte, c'est son code. Masqué à la
    // sérialisation JSON, comme UserCard. Les vues Blade vendeur accèdent aux
    // propriétés directement (non affectées par $hidden) ; utiliser makeVisible()
    // sur tout endpoint JSON qui doit légitimement les exposer au propriétaire.
    protected $hidden = ['card_code', 'pin', 'serial_number'];

    protected $casts = [
        'expiration_date' => 'date',
        'face_value'      => 'decimal:2',
        'metadata'        => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(ResellerOrder::class, 'reseller_order_id');
    }

    public function item()
    {
        return $this->belongsTo(ResellerOrderItem::class, 'reseller_order_item_id');
    }
}
