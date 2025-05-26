<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_status',
        'payment_type',
        'payment_gateway',
        'payment_gateway_reference_id',
        'payment_gateway_response',
        'gross_amount',
        'payment_proof',
        'payment_date',
        'expired_at',
        'payment_va_name',      // Kolom baru
        'payment_va_number',    // Kolom baru
        'payment_ewallet',    // Kolom baru
        'payment_qr_url',
        'payment_deeplink'
    ];

    protected $casts = [
        'payment_gateway_response' => 'array',
        'payment_date' => 'datetime',
        'expired_at' => 'datetime',
        
    ];

    // Relasi dengan tabel Order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orders()
{
    return $this->hasMany(Order::class, 'order_id', 'payment_gateway_reference_id');
}


    // Relasi dengan tabel PaymentRefunds
    public function paymentRefunds()
    {
        return $this->hasMany(PaymentRefund::class);
    }

    // Relasi dengan tabel PaymentLogs
    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class);
    }

    public function setPaymentDateAttribute($value)
    {
        $this->attributes['payment_date'] = Carbon::parse($value)->setTimezone('UTC');
    }

    public function setExpiredAtAttribute($value)
    {
        $this->attributes['expired_at'] = Carbon::parse($value)->setTimezone('UTC');
    }
}
