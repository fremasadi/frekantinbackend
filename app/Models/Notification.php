<?php

// app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    // Menentukan kolom yang bisa diisi (fillable)
    protected $fillable = [
        'user_id',
        'type',
        'message',
        'is_read',
    ];

    // Relasi dengan tabel User (user_id)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Mendapatkan status notifikasi
    public function getIsReadAttribute($value)
    {
        return $value ? 'Read' : 'Unread';
    }

    // Menandai notifikasi sebagai dibaca
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    // Menandai notifikasi sebagai belum dibaca
    public function markAsUnread()
    {
        $this->update(['is_read' => false]);
    }
}
