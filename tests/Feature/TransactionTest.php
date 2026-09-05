<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'kasir']);
    }

    public function test_can_create_transaction(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create([
            'name' => 'Espresso',
            'category_id' => $category->id,
            'price' => 15000,
            'cost' => 5000,
            'stock' => 50,
        ]);

        $response = $this->actingAs($this->user)->postJson('/transactions', [
            'invoice_number' => 'INV-TEST-001',
            'subtotal' => 15000,
            'discount' => 0,
            'tax' => 0,
            'total' => 15000,
            'paid_amount' => 20000,
            'change_amount' => 5000,
            'details' => [
                ['product_id' => $product->id, 'quantity' => 1, 'price' => 15000],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', ['invoice_number' => 'INV-TEST-001']);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 49]);
    }

    public function test_stock_movement_created_on_transaction(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create([
            'name' => 'Latte',
            'category_id' => $category->id,
            'price' => 20000,
            'cost' => 8000,
            'stock' => 30,
        ]);

        $this->actingAs($this->user)->postJson('/transactions', [
            'invoice_number' => 'INV-TEST-002',
            'subtotal' => 40000,
            'discount' => 0,
            'tax' => 0,
            'total' => 40000,
            'paid_amount' => 50000,
            'change_amount' => 10000,
            'details' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 20000],
            ],
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 2,
        ]);
    }
}
