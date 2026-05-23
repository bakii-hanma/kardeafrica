<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerOrderItem extends Model
{
    protected $fillable = [
        'reseller_order_id',
        'product_id',
        'name',
        'brand',
        'image_url',
        'color',
        'unit_price',
        'native_value',
        'native_currency',
        'quantity',
        'total_price',
    ];

    protected $casts = [
        'unit_price'   => 'decimal:2',
        'native_value' => 'decimal:4',
        'total_price'  => 'decimal:2',
        'quantity'     => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(ResellerOrder::class, 'reseller_order_id');
    }
}
