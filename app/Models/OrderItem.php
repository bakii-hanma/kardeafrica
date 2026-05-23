<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'card_id',
        'name',
        'quantity',
        'unit_price',
        'native_value',
        'native_currency',
        'total_price',
        'image_url',
        'card_details',
    ];

    protected $casts = [
        'card_details' => 'array',
        'unit_price'   => 'decimal:2',
        'native_value' => 'decimal:4',
        'total_price'  => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
