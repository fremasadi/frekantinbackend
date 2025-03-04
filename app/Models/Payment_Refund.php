<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'refund_status',
        'refund_amount',
        'refund_reason',
        'refund_proof',
        'refund_processed_at',
    ];

    // Relasi dengan tabel Payment
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}