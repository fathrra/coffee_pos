<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAddon extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'price',
        'ingredient_id',
        'ingredient_quantity',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'ingredient_quantity' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
