<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();
        $matcha = Ingredient::where('name', 'Matcha Bubuk')->first();
        $gula = Ingredient::where('name', 'Gula Pasir')->first();
        $susu = Ingredient::where('name', 'Susu UHT')->first();
        $cup = Ingredient::where('name', 'Cup 16 oz')->first();

        $recipes = [
            'Americano' => [
                ['ingredient' => $kopi, 'quantity' => 18, 'unit' => 'gram'],
                ['ingredient' => $susu, 'quantity' => 150, 'unit' => 'ml'],
                ['ingredient' => $cup, 'quantity' => 1, 'unit' => 'pcs'],
            ],
            'Cafe Latte' => [
                ['ingredient' => $kopi, 'quantity' => 18, 'unit' => 'gram'],
                ['ingredient' => $susu, 'quantity' => 150, 'unit' => 'ml'],
                ['ingredient' => $cup, 'quantity' => 1, 'unit' => 'pcs'],
            ],
            'Matcha Latte' => [
                ['ingredient' => $matcha, 'quantity' => 10, 'unit' => 'gram'],
                ['ingredient' => $susu, 'quantity' => 150, 'unit' => 'ml'],
                ['ingredient' => $gula, 'quantity' => 10, 'unit' => 'gram'],
                ['ingredient' => $cup, 'quantity' => 1, 'unit' => 'pcs'],
            ],
        ];

        foreach ($recipes as $productName => $ingredients) {
            $product = Product::where('name', $productName)->first();
            if (! $product) {
                continue;
            }

            $recipe = Recipe::firstOrCreate(
                ['product_id' => $product->id],
                ['notes' => null],
            );

            foreach ($ingredients as $item) {
                if (! $item['ingredient']) {
                    continue;
                }
                RecipeIngredient::updateOrCreate(
                    ['recipe_id' => $recipe->id, 'ingredient_id' => $item['ingredient']->id],
                    ['quantity' => $item['quantity'], 'unit' => $item['unit']],
                );
            }
        }
    }
}