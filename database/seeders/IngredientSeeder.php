<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            ['name' => 'Kopi Arabica', 'unit' => 'gram', 'current_stock' => 1000, 'minimum_stock' => 200, 'cost_per_unit' => 120, 'description' => 'Biji kopi arabica sangrai medium'],
            ['name' => 'Matcha Bubuk', 'unit' => 'gram', 'current_stock' => 950, 'minimum_stock' => 100, 'cost_per_unit' => 400, 'description' => 'Matcha powder grade A'],
            ['name' => 'Gula Pasir', 'unit' => 'gram', 'current_stock' => 980, 'minimum_stock' => 100, 'cost_per_unit' => 30, 'description' => 'Gula pasir halus'],
            ['name' => 'Susu UHT', 'unit' => 'ml', 'current_stock' => 5000, 'minimum_stock' => 1000, 'cost_per_unit' => 15, 'description' => 'Susu UHT full cream'],
            ['name' => 'Cup 16 oz', 'unit' => 'pcs', 'current_stock' => 100, 'minimum_stock' => 50, 'cost_per_unit' => 1000, 'description' => 'Gelas plastik 16 oz + tutup'],
        ];

        $admin = User::where('role', 'admin')->first();

        foreach ($ingredients as $data) {
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $data['name']],
                $data + ['is_active' => true],
            );

            if (! StockMovement::where('ingredient_id', $ingredient->id)->exists()) {
                StockMovement::create([
                    'user_id' => $admin?->id,
                    'ingredient_id' => $ingredient->id,
                    'type' => 'in',
                    'quantity' => $ingredient->current_stock,
                    'before_stock' => 0,
                    'after_stock' => $ingredient->current_stock,
                    'reference_type' => 'system',
                    'reference_id' => null,
                    'reason' => 'Stok awal',
                ]);
            }
        }
    }
}