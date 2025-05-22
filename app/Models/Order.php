<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'seller_id',
        'cart_id',
        'order_status',
        'total_amount',
        'table_number',
        'estimated_delivery_time',
        'expired_at',

    ];

    protected $casts = [
        'estimated_delivery_time' => 'datetime',
        'expired_at' => 'datetime',

        
    ];

    public function setExpiredAtAttribute($value)
    {
        $this->attributes['expired_at'] = Carbon::now()->addHours(1);
    }

    // Relasi dengan tabel User (customer)
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // Relasi dengan tabel User (seller)
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Relasi dengan tabel Cart
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function orderItems()
{
    return $this->hasMany(OrderItem::class, 'order_id', 'id');
}

public function payment()
{
    return $this->hasOne(Payment::class, 'payment_gateway_reference_id', 'order_id');
}


    // // Relasi dengan tabel Payment
    // public function payment()
    // {
    //     return $this->hasOne(Payment::class);
    // }

    public function setEstimatedDeliveryTimeAttribute($value)
    {
        $this->attributes['estimated_delivery_time'] = Carbon::parse($value)->setTimezone('UTC');
    }

    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->setTimezone('UTC');
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = Carbon::parse($value)->setTimezone('UTC');
    }
}