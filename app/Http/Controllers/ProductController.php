<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(
            Product::with(['category', 'recipe.recipeIngredients.ingredient', 'variants', 'addons'])->paginate(100)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $product = Product::create($validated);

        return response()->json($product->load(['category', 'variants', 'addons']), 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'price' => 'sometimes|numeric|min:0',
            'cost' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'image' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $product->update($validated);

        return response()->json($product->load(['category', 'variants', 'addons']));
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }

    public function variants(Product $product)
    {
        return response()->json($product->variants()->orderBy('id')->get());
    }

    public function saveVariants(Request $request, Product $product)
    {
        $validated = $request->validate([
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.multiplier' => 'required|numeric|min:0',
            'variants.*.is_active' => 'boolean',
        ]);

        $product->variants()->delete();

        foreach ($validated['variants'] ?? [] as $variant) {
            $product->variants()->create([
                'name' => $variant['name'],
                'price' => $variant['price'],
                'multiplier' => $variant['multiplier'],
                'is_active' => $variant['is_active'] ?? true,
            ]);
        }

        return response()->json($product->variants()->orderBy('id')->get());
    }

    public function addons(Product $product)
    {
        return response()->json($product->addons()->with('ingredient')->orderBy('id')->get());
    }

    public function saveAddons(Request $request, Product $product)
    {
        $validated = $request->validate([
            'addons' => 'nullable|array',
            'addons.*.name' => 'required|string|max:255',
            'addons.*.price' => 'required|numeric|min:0',
            'addons.*.ingredient_id' => 'nullable|exists:ingredients,id',
            'addons.*.ingredient_quantity' => 'nullable|numeric|min:0',
            'addons.*.unit' => 'nullable|string|max:20',
            'addons.*.is_active' => 'boolean',
        ]);

        $product->addons()->delete();

        foreach ($validated['addons'] ?? [] as $addon) {
            $product->addons()->create([
                'name' => $addon['name'],
                'price' => $addon['price'],
                'ingredient_id' => $addon['ingredient_id'] ?? null,
                'ingredient_quantity' => $addon['ingredient_quantity'] ?? null,
                'unit' => $addon['unit'] ?? null,
                'is_active' => $addon['is_active'] ?? true,
            ]);
        }

        return response()->json($product->addons()->with('ingredient')->orderBy('id')->get());
    }
}
