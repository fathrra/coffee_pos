<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public const UNITS = ['gram', 'kg', 'ml', 'liter', 'pcs'];

    /**
     * Maximum units of a product that can be made given its recipe,
     * limited by the most constraining ingredient.
     */
    public function calculateMenuStock(Product $product): int
    {
        $recipe = $product->recipe;

        if (! $recipe || $recipe->recipeIngredients->isEmpty()) {
            return 0;
        }

        $stocks = [];

        foreach ($recipe->recipeIngredients as $ri) {
            $ingredient = $ri->ingredient;

            if (! $ingredient || ! $ingredient->is_active) {
                return 0;
            }

            if ($ingredient->current_stock <= 0 || $ri->quantity <= 0) {
                return 0;
            }

            $stocks[] = (int) floor($ingredient->current_stock / $ri->quantity);
        }

        return $stocks ? min($stocks) : 0;
    }

    /**
     * Estimated production cost for a recipe.
     */
    public function calculateRecipeCost(Recipe $recipe): float
    {
        return $recipe->recipeIngredients
            ->reduce(fn (float $carry, RecipeIngredient $ri) => $carry + $ri->cost(), 0.0);
    }

    /**
     * Profit (selling price - recipe cost).
     */
    public function calculateRecipeProfit(Product $product): float
    {
        return (float) $product->price - $this->calculateRecipeCost($product->recipe);
    }

    /**
     * Margin percentage against selling price.
     */
    public function calculateRecipeMargin(Product $product): float
    {
        $price = (float) $product->price;
        if ($price <= 0) {
            return 0;
        }

        $cost = $this->calculateRecipeCost($product->recipe);

        return (($price - $cost) / $price) * 100;
    }

    /**
     * Aggregated ingredient requirements for a set of cart details.
     *
     * @param  array<int, array{
     *     product_id: int|string,
     *     quantity: int|string,
     *     variant_id?: int|string|null,
     *     addons?: array<int, array{id: int|string}|int|string>,
     * }>  $details
     * @return Collection<string, array{ingredient: Ingredient, required: float, per_unit: float, unit: string}>
     */
    public function requiredIngredients(Collection $details): Collection
    {
        $requirements = collect();

        foreach ($details as $detail) {
            $product = Product::with(['recipe.recipeIngredients.ingredient'])
                ->find($detail['product_id']);

            if (! $product) {
                continue;
            }

            $qty = (int) $detail['quantity'];
            $multiplier = $this->variantMultiplier($detail['variant_id'] ?? null);

            foreach ($product->recipe?->recipeIngredients ?? [] as $ri) {
                if (! $ri->ingredient || ! $ri->ingredient->is_active) {
                    continue;
                }

                $this->addRequirement($requirements, $ri->ingredient, (float) $ri->quantity * $qty * $multiplier, (float) $ri->quantity * $multiplier, $ri->unit ?: $ri->ingredient->unit);
            }

            foreach ($this->resolveAddons($detail['addons'] ?? []) as $addon) {
                if (! $addon->ingredient || ! $addon->ingredient->is_active) {
                    continue;
                }

                $quantity = (float) ($addon->ingredient_quantity ?? 0);

                $this->addRequirement($requirements, $addon->ingredient, $quantity * $qty, $quantity, $addon->unit ?: $addon->ingredient->unit);
            }
        }

        return $requirements;
    }

    private function variantMultiplier(int|string|null $variantId): float
    {
        if (! $variantId) {
            return 1.0;
        }

        $variant = ProductVariant::find($variantId);

        return $variant ? (float) $variant->multiplier : 1.0;
    }

    /**
     * @param  array<int, array{id: int|string}|int|string>  $addons
     * @return \Illuminate\Database\Eloquent\Collection<int, ProductAddon>
     */
    private function resolveAddons(array $addons)
    {
        if (empty($addons)) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        $ids = collect($addons)->map(fn ($addon) => is_array($addon) ? $addon['id'] : $addon);

        return ProductAddon::with('ingredient')->whereIn('id', $ids)->get();
    }

    private function addRequirement(Collection $requirements, Ingredient $ingredient, float $required, float $perUnit, string $unit): void
    {
        $key = (string) $ingredient->id;
        $row = $requirements->get($key);

        if (! $row) {
            $row = [
                'ingredient' => $ingredient,
                'required' => 0.0,
                'per_unit' => $perUnit,
                'unit' => $unit,
            ];
        }

        $row['required'] += $required;
        $requirements->put($key, $row);
    }

    /**
     * Validate that all required ingredient quantities are available.
     *
     * @throws ValidationException
     */
    public function assertStockAvailable(Collection $requirements): void
    {
        $shortages = [];

        foreach ($requirements as $row) {
            /** @var Ingredient $ingredient */
            $ingredient = $row['ingredient'];
            $required = $row['required'];

            if ($required > $ingredient->current_stock) {
                $shortages[] = sprintf(
                    '%s hanya tersedia %s %s. Kebutuhan: %s %s.',
                    $ingredient->name,
                    rtrim(rtrim(number_format($ingredient->current_stock, 3), '0'), '.'),
                    $ingredient->unit,
                    rtrim(rtrim(number_format($required, 3), '0'), '.'),
                    $row['unit'],
                );
            }
        }

        if ($shortages) {
            throw ValidationException::withMessages([
                'stock' => 'Stok bahan tidak mencukupi.'.PHP_EOL.implode(PHP_EOL, $shortages),
            ]);
        }
    }

    /**
     * Deduct ingredient stock from a sale.
     *
     * @param  array<int, array{product_id: int|string, quantity: int|string}>  $details
     */
    public function deductIngredientsForSale(Collection $details, User $user, ?int $referenceId = null, ?string $invoice = null): void
    {
        $requirements = $this->requiredIngredients($details);
        $this->assertStockAvailable($requirements);

        foreach ($requirements as $row) {
            /** @var Ingredient $ingredient */
            $ingredient = $row['ingredient'];
            $required = $row['required'];

            $before = $ingredient->current_stock;
            $after = $before - $required;

            $ingredient->forceFill(['current_stock' => $after])->save();

            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => $user->id,
                'type' => 'sale',
                'quantity' => $required,
                'before_stock' => $before,
                'after_stock' => $after,
                'reference_type' => 'transaction',
                'reference_id' => $referenceId,
                'reason' => 'Penjualan '.($invoice ?: ''),
            ]);
        }
    }

    /**
     * Record incoming stock for an ingredient (purchase from supplier).
     */
    public function addStock(Ingredient $ingredient, float $quantity, User $user, ?int $supplierId = null, ?string $reason = null): StockMovement
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity harus lebih dari 0.']);
        }

        $before = $ingredient->current_stock;
        $after = $before + $quantity;

        $ingredient->forceFill(['current_stock' => $after])->save();

        return StockMovement::create([
            'ingredient_id' => $ingredient->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => $quantity,
            'before_stock' => $before,
            'after_stock' => $after,
            'reference_type' => $supplierId ? 'supplier' : null,
            'reference_id' => $supplierId,
            'reason' => $reason ?: 'Stok masuk',
        ]);
    }

    /**
     * Record outgoing stock for an ingredient (waste, expired, sample, etc).
     */
    public function removeStock(Ingredient $ingredient, float $quantity, User $user, ?string $reason = null): StockMovement
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity harus lebih dari 0.']);
        }

        if ($quantity > $ingredient->current_stock) {
            throw ValidationException::withMessages([
                'quantity' => sprintf(
                    'Stok %s tidak mencukupi. Tersedia: %s %s.',
                    $ingredient->name,
                    number_format($ingredient->current_stock, 0),
                    $ingredient->unit,
                ),
            ]);
        }

        $before = $ingredient->current_stock;
        $after = $before - $quantity;

        $ingredient->forceFill(['current_stock' => $after])->save();

        return StockMovement::create([
            'ingredient_id' => $ingredient->id,
            'user_id' => $user->id,
            'type' => 'out',
            'quantity' => $quantity,
            'before_stock' => $before,
            'after_stock' => $after,
            'reason' => $reason ?: 'Stok keluar',
        ]);
    }

    /**
     * Correct an ingredient stock to an actual counted value.
     */
    public function adjustStock(Ingredient $ingredient, float $actualStock, User $user, ?string $reason = null): StockMovement
    {
        if ($actualStock < 0) {
            throw ValidationException::withMessages(['actual_stock' => 'Stok aktual tidak boleh negatif.']);
        }

        $before = $ingredient->current_stock;
        $after = $actualStock;
        $diff = $after - $before;

        $ingredient->forceFill(['current_stock' => $after])->save();

        return StockMovement::create([
            'ingredient_id' => $ingredient->id,
            'user_id' => $user->id,
            'type' => 'adjustment',
            'quantity' => $diff,
            'before_stock' => $before,
            'after_stock' => $after,
            'reason' => $reason ?: 'Penyesuaian stok',
        ]);
    }
}
