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
        'cart_item_id',
        'quantity',
        'price',
        'notes',
    ];

    // Relasi dengan tabel Order
    public function order()
{
    return $this->belongsTo(Order::class, 'order_id', 'id');
}


    // Relasi dengan tabel Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Relasi dengan tabel CartItem
    public function cartItem()
    {
        return $this->belongsTo(CartItem::class, 'cart_item_id');
    }
}