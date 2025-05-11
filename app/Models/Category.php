<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'image']; // field yang dapat diisi massal
    public function products()
    {
        return $this->hasMany(\App\Models\Product::class, 'category_id');
    }
    
}
