<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->seedBaseIngredients();
    }

    private function seedBaseIngredients(): void
    {
        Ingredient::create([
            'name' => 'Kopi Arabica', 'unit' => 'gram',
            'current_stock' => 1000, 'minimum_stock' => 200, 'cost_per_unit' => 120,
        ]);
        Ingredient::create([
            'name' => 'Matcha Bubuk', 'unit' => 'gram',
            'current_stock' => 950, 'minimum_stock' => 100, 'cost_per_unit' => 400,
        ]);
        Ingredient::create([
            'name' => 'Gula Pasir', 'unit' => 'gram',
            'current_stock' => 980, 'minimum_stock' => 100, 'cost_per_unit' => 30,
        ]);
        Ingredient::create([
            'name' => 'Susu UHT', 'unit' => 'ml',
            'current_stock' => 5000, 'minimum_stock' => 1000, 'cost_per_unit' => 15,
        ]);
        Ingredient::create([
            'name' => 'Cup 16 oz', 'unit' => 'pcs',
            'current_stock' => 100, 'minimum_stock' => 50, 'cost_per_unit' => 1000,
        ]);
    }

    private function createCoffeeProduct(string $name, int $price): Product
    {
        $category = Category::firstOrCreate(['slug' => 'coffee'], ['name' => 'Coffee']);

        return Product::create([
            'name' => $name,
            'category_id' => $category->id,
            'price' => $price,
            'cost' => (int) floor($price * 0.5),
            'stock' => 10,
        ]);
    }

    private function attachRecipe(Product $product, array $rows): void
    {
        $recipe = Recipe::create(['product_id' => $product->id]);
        foreach ($rows as $row) {
            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $row[0],
                'quantity' => $row[1],
                'unit' => $row[2],
            ]);
        }
    }

    public function test_menu_stock_is_minimum_ingredient_capacity(): void
    {
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();
        $susu = Ingredient::where('name', 'Susu UHT')->first();
        $cup = Ingredient::where('name', 'Cup 16 oz')->first();

        $latte = $this->createCoffeeProduct('Latte Test', 18000);
        $this->attachRecipe($latte, [
            [$kopi->id, 18, 'gram'],
            [$susu->id, 150, 'ml'],
            [$cup->id, 1, 'pcs'],
        ]);

        $this->assertEquals(33, $latte->refresh()->stock);
    }

    public function test_sale_deducts_ingredient_stocks_and_moves_are_recorded(): void
    {
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();
        $matcha = Ingredient::where('name', 'Matcha Bubuk')->first();
        $gula = Ingredient::where('name', 'Gula Pasir')->first();
        $susu = Ingredient::where('name', 'Susu UHT')->first();
        $cup = Ingredient::where('name', 'Cup 16 oz')->first();

        $latte = $this->createCoffeeProduct('Latte Cafe', 18000);
        $this->attachRecipe($latte, [[$kopi->id, 18, 'gram'], [$susu->id, 150, 'ml'], [$cup->id, 1, 'pcs']]);
        $americano = $this->createCoffeeProduct('Americano', 15000);
        $this->attachRecipe($americano, [[$kopi->id, 18, 'gram'], [$susu->id, 150, 'ml'], [$cup->id, 1, 'pcs']]);
        $matchaLatte = $this->createCoffeeProduct('Matcha Latte', 20000);
        $this->attachRecipe($matchaLatte, [[$matcha->id, 10, 'gram'], [$susu->id, 150, 'ml'], [$gula->id, 10, 'gram'], [$cup->id, 1, 'pcs']]);

        $this->actingAs($this->admin)->postJson('/transactions', [
            'invoice_number' => 'INV-INV-001',
            'subtotal' => 250000,
            'discount' => 0,
            'tax' => 0,
            'total' => 250000,
            'paid_amount' => 250000,
            'change_amount' => 0,
            'details' => [
                ['product_id' => $latte->id, 'quantity' => 5, 'price' => 18000],
                ['product_id' => $americano->id, 'quantity' => 8, 'price' => 15000],
                ['product_id' => $matchaLatte->id, 'quantity' => 2, 'price' => 20000],
            ],
        ])->assertStatus(201);

        $this->assertEquals(766, Ingredient::find($kopi->id)->current_stock);
        $this->assertEquals(930, Ingredient::find($matcha->id)->current_stock);
        $this->assertEquals(960, Ingredient::find($gula->id)->current_stock);
        $this->assertEquals(2750, Ingredient::find($susu->id)->current_stock);
        $this->assertEquals(85, Ingredient::find($cup->id)->current_stock);

        $this->assertEquals(20, StockMovement::where('ingredient_id', $matcha->id)
            ->where('type', 'sale')->sum('quantity'));

        // Recipe products are not decremented on the products table...
        $this->assertEquals(10, DB::table('products')->where('id', $latte->id)->value('stock'));
        // ...but menu stock reflects the lowest ingredient capacity: min(42, 18, 85) = 18.
        $this->assertEquals(18, $latte->refresh()->stock);
    }

    public function test_sale_rejected_when_ingredient_stock_insufficient(): void
    {
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();
        $susu = Ingredient::where('name', 'Susu UHT')->first();
        $cup = Ingredient::where('name', 'Cup 16 oz')->first();

        $latte = $this->createCoffeeProduct('Latte Cafe', 18000);
        $this->attachRecipe($latte, [[$kopi->id, 18, 'gram'], [$susu->id, 150, 'ml'], [$cup->id, 1, 'pcs']]);

        $susu->update(['current_stock' => 200]);

        $this->actingAs($this->admin)->postJson('/transactions', [
            'invoice_number' => 'INV-REJ-01',
            'subtotal' => 36000,
            'discount' => 0,
            'tax' => 0,
            'total' => 36000,
            'paid_amount' => 50000,
            'change_amount' => 14000,
            'details' => [
                ['product_id' => $latte->id, 'quantity' => 2, 'price' => 18000],
            ],
        ])->assertStatus(422);

        $this->assertEquals(0, Transaction::where('invoice_number', 'INV-REJ-01')->count());
        $this->assertEquals(200, $susu->refresh()->current_stock);
        $this->assertEquals(1000, $kopi->fresh()->current_stock);
        $this->assertCount(0, StockMovement::where('type', 'sale')->get());
    }

    public function test_admin_can_stock_in_via_supplier(): void
    {
        $supplier = Supplier::create(['name' => 'PT Kopi Nusantara', 'is_active' => true]);
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();

        $this->actingAs($this->admin)->postJson('/stock-in', [
            'supplier_id' => $supplier->id,
            'ingredient_id' => $kopi->id,
            'quantity' => 500,
            'unit_cost' => 110,
            'notes' => 'Restock mingguan',
        ])->assertStatus(201);

        $this->assertEquals(1500, $kopi->fresh()->current_stock);
        $movement = StockMovement::where('ingredient_id', $kopi->id)->where('type', 'in')->latest()->first();
        $this->assertNotNull($movement);
        $this->assertEquals(1000, $movement->before_stock);
        $this->assertEquals(1500, $movement->after_stock);
        $this->assertEquals('supplier', $movement->reference_type);
        $this->assertEquals($supplier->id, $movement->reference_id);
    }

    public function test_admin_can_stock_out(): void
    {
        $susu = Ingredient::where('name', 'Susu UHT')->first();

        $this->actingAs($this->admin)->postJson('/stock-out', [
            'ingredient_id' => $susu->id,
            'quantity' => 250,
            'reason' => 'expired',
            'notes' => 'Batch lama',
        ])->assertStatus(201);

        $this->assertEquals(4750, $susu->fresh()->current_stock);
        $movement = StockMovement::where('ingredient_id', $susu->id)->where('type', 'out')->latest()->first();
        $this->assertEquals(4750, $movement->after_stock);
    }

    public function test_admin_can_adjust_stock(): void
    {
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();

        $this->actingAs($this->admin)->postJson('/stock-adjustments', [
            'ingredient_id' => $kopi->id,
            'actual_stock' => 900,
            'reason' => 'Hasil stock opname',
        ])->assertStatus(201);

        $this->assertEquals(900, $kopi->fresh()->current_stock);
        $adjust = StockMovement::where('ingredient_id', $kopi->id)->where('type', 'adjustment')->latest()->first();
        $this->assertEquals(-100, (float) $adjust->quantity);
        $this->assertEquals(1000, (float) $adjust->before_stock);
        $this->assertEquals(900, (float) $adjust->after_stock);
    }

    public function test_stock_operations_require_admin_role(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();

        $this->actingAs($kasir)->postJson('/stock-in', [
            'ingredient_id' => $kopi->id,
            'quantity' => 10,
        ])->assertStatus(403);

        $this->actingAs($kasir)->getJson('/inventory/summary')->assertStatus(403);
        $this->actingAs($kasir)->getJson('/recipes')->assertStatus(403);
    }

    public function test_inventory_report_returns_summary_and_rows(): void
    {
        $supplier = Supplier::create(['name' => 'PT Susu Segar', 'is_active' => true]);
        $susu = Ingredient::where('name', 'Susu UHT')->first();

        $this->actingAs($this->admin)->postJson('/stock-in', [
            'supplier_id' => $supplier->id,
            'ingredient_id' => $susu->id,
            'quantity' => 1000,
            'notes' => 'Restock',
        ])->assertStatus(201);

        $response = $this->actingAs($this->admin)->getJson('/reports/inventory')
            ->assertStatus(200);

        $response->assertJsonPath('totals.stock_in', 1000);
    }

    public function test_recipe_list_endpoint_returns_menu_data(): void
    {
        $cappuccino = $this->createCoffeeProduct('Cappuccino', 18000);
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();
        $this->attachRecipe($cappuccino, [[$kopi->id, 18, 'gram']]);

        $row = $this->actingAs($this->admin)->getJson('/recipes')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $product = $row->json('data')[0];
        $this->assertEquals($cappuccino->id, $product['id']);
        $this->assertTrue($product['has_recipe']);
        $this->assertNotNull($product['recipe_id']);
        $this->assertEquals(1, $product['ingredient_count']);
        $this->assertEquals(2160, $product['recipe_cost']);
        $this->assertEquals(15840, $product['profit']);
        $this->assertEquals(55, $product['menu_stock']);
    }

    public function test_low_stock_endpoint_lists_depleted_ingredients(): void
    {
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();
        $susu = Ingredient::where('name', 'Susu UHT')->first();
        $inactive = Ingredient::create([
            'name' => 'Bahan Nonaktif', 'unit' => 'pcs',
            'current_stock' => 0, 'minimum_stock' => 5, 'cost_per_unit' => 10,
            'is_active' => false,
        ]);

        $kopi->update(['current_stock' => 50]); // < minimum (200)

        $rows = $this->actingAs($this->admin)->getJson('/inventory/low-stock')
            ->assertStatus(200)
            ->json();

        $ids = collect($rows)->pluck('id')->all();
        $this->assertContains($kopi->id, $ids);
        $this->assertNotContains($susu->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    public function test_recipe_crud_sets_and_resets_materials(): void
    {
        $cappuccino = $this->createCoffeeProduct('Cappuccino', 18000);
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();
        $susu = Ingredient::where('name', 'Susu UHT')->first();

        $this->actingAs($this->admin)->postJson('/recipes', [
            'product_id' => $cappuccino->id,
            'notes' => 'Cappuccino klasik',
            'ingredients' => [
                ['ingredient_id' => $kopi->id, 'quantity' => 18, 'unit' => 'gram'],
                ['ingredient_id' => $susu->id, 'quantity' => 150, 'unit' => 'ml'],
            ],
        ])->assertStatus(201);

        $this->assertDatabaseHas('recipes', ['product_id' => $cappuccino->id]);
        $recipe = Recipe::where('product_id', $cappuccino->id)->first();
        $this->assertDatabaseCount('recipe_ingredients', 2);

        $this->actingAs($this->admin)->postJson('/recipes', [
            'product_id' => $cappuccino->id,
            'ingredients' => [
                ['ingredient_id' => $kopi->id, 'quantity' => 18, 'unit' => 'gram'],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseCount('recipe_ingredients', 1);

        $this->actingAs($this->admin)->deleteJson("/recipes/{$recipe->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('recipes', ['product_id' => $cappuccino->id]);
        $this->assertDatabaseCount('recipe_ingredients', 0);
    }

    public function test_sale_with_variant_multiplier_deducts_scaled_ingredients(): void
    {
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();
        $susu = Ingredient::where('name', 'Susu UHT')->first();
        $cup = Ingredient::where('name', 'Cup 16 oz')->first();

        $latte = $this->createCoffeeProduct('Latte Varian', 18000);
        $this->attachRecipe($latte, [[$kopi->id, 18, 'gram'], [$susu->id, 150, 'ml'], [$cup->id, 1, 'pcs']]);

        $variant = ProductVariant::create([
            'product_id' => $latte->id,
            'name' => 'Double',
            'price' => 5000,
            'multiplier' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->postJson('/transactions', [
            'subtotal' => 23000,
            'discount' => 0,
            'tax' => 0,
            'total' => 23000,
            'paid_amount' => 30000,
            'change_amount' => 7000,
            'details' => [
                [
                    'product_id' => $latte->id,
                    'quantity' => 1,
                    'price' => 23000,
                    'variant_id' => $variant->id,
                    'addons' => [],
                ],
            ],
        ])->assertStatus(201);

        $this->assertEquals(964, Ingredient::find($kopi->id)->current_stock);
        $this->assertEquals(4700, Ingredient::find($susu->id)->current_stock);
        $this->assertEquals(98, Ingredient::find($cup->id)->current_stock);

        $this->assertDatabaseHas('transaction_details', [
            'transaction_id' => Transaction::max('id'),
            'product_variant_id' => $variant->id,
        ]);
    }

    public function test_sale_with_addon_deducts_addon_ingredient_and_snapshots_addons(): void
    {
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();
        $susu = Ingredient::where('name', 'Susu UHT')->first();
        $cup = Ingredient::where('name', 'Cup 16 oz')->first();

        $latte = $this->createCoffeeProduct('Latte Addon', 18000);
        $this->attachRecipe($latte, [[$kopi->id, 18, 'gram'], [$susu->id, 150, 'ml'], [$cup->id, 1, 'pcs']]);

        $addon = ProductAddon::create([
            'product_id' => $latte->id,
            'name' => 'Extra Shot',
            'price' => 5000,
            'ingredient_id' => $kopi->id,
            'ingredient_quantity' => 10,
            'unit' => 'gram',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->postJson('/transactions', [
            'subtotal' => 23000,
            'discount' => 0,
            'tax' => 0,
            'total' => 23000,
            'paid_amount' => 30000,
            'change_amount' => 7000,
            'details' => [
                [
                    'product_id' => $latte->id,
                    'quantity' => 1,
                    'price' => 23000,
                    'addons' => [['id' => $addon->id, 'name' => 'Extra Shot', 'price' => 5000]],
                ],
            ],
        ])->assertStatus(201);

        $this->assertEquals(972, Ingredient::find($kopi->id)->current_stock);
        $this->assertEquals(4850, Ingredient::find($susu->id)->current_stock);
        $this->assertEquals(99, Ingredient::find($cup->id)->current_stock);

        $detail = DB::table('transaction_details')
            ->where('product_id', $latte->id)
            ->first();
        $this->assertNotNull($detail);
        $this->assertSame('[{"id":'.$addon->id.',"name":"Extra Shot","price":5000}]', json_encode(json_decode($detail->addons)));
    }

    public function test_admin_can_save_variants_and_addons_for_product(): void
    {
        $kopi = Ingredient::where('name', 'Kopi Arabica')->first();
        $latte = $this->createCoffeeProduct('Latte Admin', 18000);

        $this->actingAs($this->admin)->postJson("/products/{$latte->id}/variants", [
            'variants' => [
                ['name' => 'Small', 'price' => 0, 'multiplier' => 1, 'is_active' => true],
                ['name' => 'Large', 'price' => 5000, 'multiplier' => 1.5, 'is_active' => true],
            ],
        ])->assertStatus(200);

        $this->actingAs($this->admin)->postJson("/products/{$latte->id}/addons", [
            'addons' => [
                ['name' => 'Extra Shot', 'price' => 5000, 'ingredient_id' => $kopi->id, 'ingredient_quantity' => 10, 'unit' => 'gram', 'is_active' => true],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseCount('product_variants', 2);
        $this->assertDatabaseHas('product_addons', ['product_id' => $latte->id, 'name' => 'Extra Shot', 'ingredient_id' => $kopi->id]);
    }

    public function test_save_variants_requires_admin_role(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $latte = $this->createCoffeeProduct('Latte Kasir', 18000);

        $this->actingAs($kasir)->postJson("/products/{$latte->id}/variants", [
            'variants' => [['name' => 'Large', 'price' => 5000, 'multiplier' => 1.5, 'is_active' => true]],
        ])->assertStatus(403);

        $this->assertDatabaseCount('product_variants', 0);
    }
}
