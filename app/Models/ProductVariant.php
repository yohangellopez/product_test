<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table= 'product_variants';

    protected $fillable=[
        'ref',
        'description',
        'price',
        'product_id',
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }
}
