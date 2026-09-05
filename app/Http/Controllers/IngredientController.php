<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $query = Ingredient::query()
            ->withCount('recipeIngredients')
            ->withCount('stockMovements');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'good' => $query->whereColumn('current_stock', '>', 'minimum_stock'),
                'low' => $query->where('current_stock', '<=', 'minimum_stock')->where('current_stock', '>', 0),
                'out' => $query->where('current_stock', '<=', 0),
                'inactive' => $query->where('is_active', false),
                default => $query,
            };
        }

        return response()->json($query->orderBy('name')->paginate(min($request->integer('per_page', 50), 200)));
    }

    public function store(Request $request)
    {
        $validated = $this->validateIngredient($request);

        $ingredient = Ingredient::create($validated);

        return response()->json($ingredient, 201);
    }

    public function show(Ingredient $ingredient)
    {
        $ingredient->load(['stockMovements.user']);

        return response()->json($ingredient);
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $this->validateIngredient($request, $ingredient);

        $ingredient->update($validated);

        return response()->json($ingredient);
    }

    public function destroy(Ingredient $ingredient)
    {
        if ($ingredient->recipeIngredients()->count() > 0) {
            return response()->json([
                'message' => 'Tidak bisa menghapus — bahan ini masih dipakai di resep.',
            ], 422);
        }

        if ($ingredient->stockMovements()->count() > 0) {
            return response()->json([
                'message' => 'Tidak bisa menghapus — bahan ini memiliki riwayat stok.',
            ], 422);
        }

        $ingredient->delete();

        return response()->json(['message' => 'Bahan berhasil dihapus.']);
    }

    private function validateIngredient(Request $request, ?Ingredient $ingredient = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|in:' . implode(',', \App\Services\InventoryService::UNITS),
            'current_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ], [], [
            'name' => 'Nama',
            'unit' => 'Satuan',
            'current_stock' => 'Stok',
            'minimum_stock' => 'Stok minimum',
            'cost_per_unit' => 'Biaya per satuan',
            'description' => 'Keterangan',
            'is_active' => 'Status aktif',
        ]);
    }
}