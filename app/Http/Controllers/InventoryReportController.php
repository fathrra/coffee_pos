<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    /**
     * Aggregated ingredient usage report for a given date range.
     */
    public function report(Request $request)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = $validated['from'] ?? now()->toDateString();
        $to = $validated['to'] ?? now()->toDateString();

        if (strtotime($to) < strtotime($from)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'to' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            ]);
        }

        $movements = StockMovement::query()
            ->whereNotNull('ingredient_id')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->get();

        $ingredients = Ingredient::all()->keyBy('id');

        $rows = $ingredients->map(function (Ingredient $ingredient) use ($movements) {
            $for = fn ($type) => $movements
                ->where('ingredient_id', $ingredient->id)
                ->where('type', $type)
                ->sum('quantity');

            return [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'unit' => $ingredient->unit,
                'stock_in' => $for('in'),
                'stock_out' => abs($for('out')),
                'usage' => abs($for('sale')),
                'adjustment' => $for('adjustment'),
                'current_stock' => $ingredient->current_stock,
                'minimum_stock' => $ingredient->minimum_stock,
                'total_value' => $ingredient->stockValue(),
            ];
        })->values();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'totals' => [
                'stock_in' => $movements->where('type', 'in')->sum('quantity'),
                'stock_out' => $movements->where('type', 'out')->sum('quantity'),
                'usage' => $movements->where('type', 'sale')->sum('quantity'),
                'adjustment' => $movements->where('type', 'adjustment')->sum('quantity'),
                'transaction_count' => $movements->where('reference_type', 'transaction')->pluck('reference_id')->unique()->count(),
            ],
            'rows' => $rows,
        ]);
    }

    /**
     * Recipe cost report (menu, cost, price, profit, margin, stock).
     */
    public function recipeReport()
    {
        $products = Product::with(['recipe.recipeIngredients.ingredient'])
            ->whereHas('recipe')
            ->orderBy('name')
            ->get();

        $rows = $products->map(function (Product $product) {
            $cost = $this->inventory->calculateRecipeCost($product->recipe);
            $price = (float) $product->price;
            $profit = $price - $cost;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'recipe_cost' => $cost,
                'price' => $price,
                'profit' => $profit,
                'margin' => $price > 0 ? ($profit / $price) * 100 : 0,
                'stock' => $product->stock,
            ];
        });

        return response()->json([
            'rows' => $rows,
            'totals' => [
                'recipe_count' => $rows->count(),
                'total_cost' => $rows->sum('recipe_cost'),
                'total_price' => $rows->sum('price'),
                'total_profit' => $rows->sum('profit'),
            ],
        ]);
    }
}