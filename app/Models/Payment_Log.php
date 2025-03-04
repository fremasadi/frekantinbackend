<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'event_type',
        'event_data',
    ];

    // Relasi dengan tabel Payment
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}