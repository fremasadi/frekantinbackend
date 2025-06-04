<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'month',
        'total_income',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'month' => 'date',
        'paid_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function scopeForMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // App\Models\SellerEarning.php
public function orders()
{
    return $this->hasMany(\App\Models\Order::class, 'seller_id', 'seller_id')
        ->whereMonth('created_at', '=', $this->month->format('m'))
        ->whereYear('created_at', '=', $this->month->format('Y'));
}

}
