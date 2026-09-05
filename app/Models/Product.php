<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'price',
        'cost',
        'stock',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function recipe()
    {
        return $this->hasOne(Recipe::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function addons()
    {
        return $this->hasMany(ProductAddon::class);
    }

    /**
     * Menu stock is computed from the recipe ingredients whenever a recipe
     * exists. Falls back to the manually stored stock column otherwise.
     */
    public function getStockAttribute($value): float|int
    {
        $recipe = $this->getRelationValue('recipe');

        if ($recipe && $recipe->recipeIngredients->isNotEmpty()) {
            return app(InventoryService::class)->calculateMenuStock($this);
        }

        return (float) $value;
    }

    public function hasActiveRecipe(): bool
    {
        return $this->relationLoaded('recipe') && $this->recipe && $this->recipe->recipeIngredients->isNotEmpty();
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
