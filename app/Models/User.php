<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'image',
        'role',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    const ROLE_ADMIN = 'admin';
    const ROLE_SELLER = 'seller';
    const ROLE_CUSTOMER = 'customer';

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSeller()
    {
        return $this->role === self::ROLE_SELLER;
    }

    public function isCustomer()
    {
        return $this->role === self::ROLE_CUSTOMER;
    }
    
}