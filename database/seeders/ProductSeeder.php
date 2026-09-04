<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $coffee = Category::where('slug', 'coffee')->first();
        $nonCoffee = Category::where('slug', 'non-coffee')->first();
        $tea = Category::where('slug', 'tea')->first();
        $food = Category::where('slug', 'food')->first();
        $snack = Category::where('slug', 'snack')->first();

        Product::create([
            'category_id' => $coffee->id,
            'name' => 'Americano',
            'price' => 15000,
            'cost' => 8000,
            'stock' => 30,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $coffee->id,
            'name' => 'Cappuccino',
            'price' => 18000,
            'cost' => 10000,
            'stock' => 25,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $coffee->id,
            'name' => 'Cafe Latte',
            'price' => 18000,
            'cost' => 10000,
            'stock' => 25,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $nonCoffee->id,
            'name' => 'Matcha Latte',
            'price' => 20000,
            'cost' => 11000,
            'stock' => 20,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $nonCoffee->id,
            'name' => 'Chocolate',
            'price' => 18000,
            'cost' => 9000,
            'stock' => 20,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $tea->id,
            'name' => 'Lemon Tea',
            'price' => 12000,
            'cost' => 6000,
            'stock' => 30,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $food->id,
            'name' => 'Croissant',
            'price' => 20000,
            'cost' => 12000,
            'stock' => 15,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $food->id,
            'name' => 'French Fries',
            'price' => 15000,
            'cost' => 8000,
            'stock' => 20,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $snack->id,
            'name' => 'Cookies',
            'price' => 10000,
            'cost' => 5000,
            'stock' => 25,
            'is_active' => true,
        ]);
    }
}