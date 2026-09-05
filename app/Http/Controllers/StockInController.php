<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class StockInController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(Request $request)
    {
        $query = StockMovement::with(['ingredient', 'user'])
            ->where('type', 'in')
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
            'supplier_id' => 'nullable|exists:suppliers,id',
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_cost' => 'nullable|numeric|min:0',
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
        ], [], [
            'supplier_id' => 'Supplier',
            'ingredient_id' => 'Bahan',
            'quantity' => 'Jumlah',
            'unit_cost' => 'Harga satuan',
            'date' => 'Tanggal',
            'notes' => 'Catatan',
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);

        // When a unit cost is provided, update the ingredient's reference cost.
        if (!empty($validated['unit_cost'])) {
            $ingredient->cost_per_unit = $validated['unit_cost'];
        }

        $movement = $this->inventory->addStock(
            $ingredient,
            (float) $validated['quantity'],
            $request->user(),
            $validated['supplier_id'] ?? null,
            $validated['notes'] ?: 'Stok masuk',
        );

        if ($movement && !empty($validated['date'])) {
            $movement->created_at = $validated['date'];
            $movement->save();
        }

        return response()->json($movement->load('ingredient', 'user'), 201);
    }
}