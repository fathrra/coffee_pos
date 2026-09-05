<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(Request $request)
    {
        $query = StockMovement::with(['ingredient', 'user'])
            ->where('type', 'adjustment')
            ->whereNotNull('ingredient_id')
            ->orderByDesc('created_at');

        if ($request->filled('ingredient_id')) {
            $query->where('ingredient_id', $request->ingredient_id);
        }

        return response()->json($query->paginate(min($request->integer('per_page', 30), 100)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'actual_stock' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
        ], [], [
            'ingredient_id' => 'Bahan',
            'actual_stock' => 'Stok aktual',
            'reason' => 'Alasan',
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);

        $movement = $this->inventory->adjustStock(
            $ingredient,
            (float) $validated['actual_stock'],
            $request->user(),
            $validated['reason'] ?: 'Penyesuaian stok',
        );

        return response()->json($movement->load('ingredient', 'user'), 201);
    }
}