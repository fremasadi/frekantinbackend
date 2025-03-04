<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'description',
        'price',
        'image',
        'stock',
        'is_active'
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id')
                    ->where('role', 'seller'); // Memastikan hanya user dengan role seller
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
{
    return $this->hasMany(Review::class, 'product_id');
}

}
