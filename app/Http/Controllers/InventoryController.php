<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Summary stats for the dashboard / inventory overview.
     */
    public function summary()
    {
        $ingredients = Ingredient::all();

        return response()->json([
            'total_ingredients' => $ingredients->count(),
            'active_ingredients' => $ingredients->where('is_active', true)->count(),
            'low_stock' => $ingredients->where('is_active', true)
                ->filter(fn ($i) => $i->current_stock <= $i->minimum_stock && $i->current_stock > 0)
                ->count(),
            'out_of_stock' => $ingredients->where('is_active', true)
                ->filter(fn ($i) => $i->current_stock <= 0)
                ->count(),
            'total_value' => $ingredients->sum(fn ($i) => $i->stockValue()),
            'products_with_recipe' => \App\Models\Product::whereHas('recipe')->count(),
        ]);
    }

    public function lowStock()
    {
        $ingredients = Ingredient::withCount('recipeIngredients')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereColumn('current_stock', '<=', 'minimum_stock')
                    ->orWhere('current_stock', '<=', 0);
            })
            ->orderBy('current_stock')
            ->get();

        return response()->json($ingredients);
    }

    public function movements(Request $request)
    {
        $query = StockMovement::with(['ingredient', 'product', 'user'])
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('ingredient_id')) {
            $query->where('ingredient_id', $request->ingredient_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return response()->json($query->paginate(50));
    }
}