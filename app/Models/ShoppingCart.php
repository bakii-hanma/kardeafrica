<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends Model
{
    protected $table = 'shopping_cart';

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'name',
        'price',
        'image_url',
        'quantity',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
