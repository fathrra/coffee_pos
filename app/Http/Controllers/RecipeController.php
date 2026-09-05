<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecipeController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    /**
     * Menu list with recipe production info (cost, profit, margin, stock).
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'recipe.recipeIngredients.ingredient']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->boolean('has_recipe')) {
            $query->whereHas('recipe');
        }

        if ($request->boolean('no_recipe')) {
            $query->whereDoesntHave('recipe');
        }

        $products = $query->orderBy('name')->paginate(min($request->integer('per_page', 100), 200));

        $products->getCollection()->transform(function (Product $product) {
            $recipe = $product->recipe;
            $product->setAttribute('recipe_id', $recipe ? $recipe->id : null);
            $product->setAttribute('recipe_notes', $recipe ? $recipe->notes : null);
            $product->setAttribute('has_recipe', (bool) $recipe);
            $product->setAttribute('ingredient_count', $recipe ? $recipe->recipeIngredients->count() : 0);
            $product->setAttribute('recipe_cost', $recipe ? $this->inventory->calculateRecipeCost($recipe) : 0);
            $product->setAttribute('profit', $recipe ? $this->inventory->calculateRecipeProfit($product) : 0);
            $product->setAttribute('margin', $recipe ? $this->inventory->calculateRecipeMargin($product) : 0);
            $product->setAttribute('menu_stock', $product->stock);
            $product->setAttribute('recipe_ingredients', $recipe ? $recipe->recipeIngredients->map(fn ($ri) => [
                'id' => $ri->id,
                'ingredient_id' => $ri->ingredient_id,
                'ingredient_name' => $ri->ingredient?->name,
                'quantity' => (float) $ri->quantity,
                'unit' => $ri->unit,
                'available' => $ri->ingredient?->current_stock ?? 0,
                'stock_unit' => $ri->ingredient?->unit,
                'active' => $ri->ingredient?->is_active ?? false,
            ]) : []);

            return $product->makeHidden(['recipe', 'recipeIngredients']);
        });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'notes' => 'nullable|string',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.001',
            'ingredients.*.unit' => 'nullable|string|max:20',
        ], [
            'ingredients.required' => 'Minimal satu bahan harus ditambahkan.',
            'ingredients.min' => 'Minimal satu bahan harus ditambahkan.',
        ], [
            'product_id' => 'Produk',
            'notes' => 'Catatan',
            'ingredients' => 'Bahan',
            'ingredients.*.ingredient_id' => 'Bahan',
            'ingredients.*.quantity' => 'Jumlah',
            'ingredients.*.unit' => 'Satuan',
        ]);

        return DB::transaction(function () use ($validated) {
            $existing = Recipe::where('product_id', $validated['product_id'])->exists();

            $recipe = Recipe::updateOrCreate(
                ['product_id' => $validated['product_id']],
                ['notes' => $validated['notes'] ?? null],
            );

            $inactiveIds = collect($validated['ingredients'])
                ->pluck('ingredient_id')
                ->map(fn ($id) => Ingredient::find($id))
                ->filter(fn ($i) => $i && !$i->is_active)
                ->map(fn ($i) => $i->id)
                ->all();

            if ($inactiveIds) {
                throw ValidationException::withMessages([
                    'ingredients' => 'Bahan yang tidak aktif tidak boleh dipakai dalam resep.',
                ]);
            }

            $recipe->recipeIngredients()->delete();

            foreach ($validated['ingredients'] as $item) {
                $ingredient = Ingredient::find($item['ingredient_id']);

                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?: $ingredient->unit,
                ]);
            }

            return response()->json($recipe->load('product', 'recipeIngredients.ingredient'), $existing ? 200 : 201);
        });
    }

    public function show(Recipe $recipe)
    {
        $recipe->load(['product', 'recipeIngredients.ingredient']);

        return response()->json($recipe);
    }

    public function update(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'ingredients' => 'sometimes|array|min:1',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.001',
            'ingredients.*.unit' => 'nullable|string|max:20',
        ]);

        return DB::transaction(function () use ($validated, $recipe) {
            $recipe->update(['notes' => $validated['notes'] ?? $recipe->notes]);

            if (isset($validated['ingredients'])) {
                $recipe->recipeIngredients()->delete();

                foreach ($validated['ingredients'] as $item) {
                    RecipeIngredient::create([
                        'recipe_id' => $recipe->id,
                        'ingredient_id' => $item['ingredient_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                    ]);
                }
            }

            return response()->json($recipe->load('product', 'recipeIngredients.ingredient'));
        });
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->delete();

        return response()->json(['message' => 'Resep berhasil dihapus.']);
    }

    /**
     * Read-only recipe info and cost for one menu.
     */
    public function menu(Product $product)
    {
        $product->load('recipe.recipeIngredients.ingredient');

        $product->setAttribute('has_recipe', (bool) $product->recipe);
        $product->setAttribute('recipe_cost', $product->recipe ? $this->inventory->calculateRecipeCost($product->recipe) : 0);
        $product->setAttribute('profit', $product->recipe ? $this->inventory->calculateRecipeProfit($product) : 0);
        $product->setAttribute('margin', $product->recipe ? $this->inventory->calculateRecipeMargin($product) : 0);
        $product->setAttribute('menu_stock', $product->stock);

        return response()->json($product->makeHidden(['recipe', 'recipeIngredients']));
    }
}