<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable= [
        'ref',
        'description',
        'image',
        'price',
        'status',
        'variant'
    ];


    public function product_variants(){
        return $this->hasMany(ProductVariant::class);
    }
}
