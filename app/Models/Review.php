<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id', // Ganti order_id menjadi order_item_id
        'product_id',
        'customer_id',
        'rating',
        'comment',
        'image',
        'review_date',
    ];

    // Relasi dengan Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relasi dengan User (Customer)
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // Relasi dengan OrderItem
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}