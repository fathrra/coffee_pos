<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class StockOutController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(Request $request)
    {
        $query = StockMovement::with(['ingredient', 'user'])
            ->where('type', 'out')
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
            'quantity' => 'required|numeric|min:0.001',
            'reason' => 'required|string|in:rusak,expired,terbuang,sample,lainnya',
            'notes' => 'nullable|string',
        ], [], [
            'ingredient_id' => 'Bahan',
            'quantity' => 'Jumlah',
            'reason' => 'Alasan',
            'notes' => 'Catatan',
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);

        $movement = $this->inventory->removeStock(
            $ingredient,
            (float) $validated['quantity'],
            $request->user(),
            $this->reasonLabel($validated['reason']) . ($validated['notes'] ? ' — ' . $validated['notes'] : ''),
        );

        return response()->json($movement->load('ingredient', 'user'), 201);
    }

    private function reasonLabel(string $reason): string
    {
        return match ($reason) {
            'rusak' => 'Stok keluar (Rusak)',
            'expired' => 'Stok keluar (Expired)',
            'terbuang' => 'Stok keluar (Terbuang)',
            'sample' => 'Stok keluar (Sample)',
            default => 'Stok keluar (Lainnya)',
        };
    }
}