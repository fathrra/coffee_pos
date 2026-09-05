<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\InventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::with(['user', 'details.product', 'details.variant'])
            ->orderByDesc('created_at')
            ->paginate(50);

        $transactions->getCollection()->transform(function ($t) {
            $t->cashier = $t->user ? $t->user->name : '-';
            $t->details->each(function ($d) {
                $d->name = $d->product ? $d->product->name : '-';
            });

            return $t;
        });

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'nullable|string|unique:transactions,invoice_number',
            'customer_name' => 'nullable|string|max:255',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'change_amount' => 'required|numeric|min:0',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.price' => 'required|numeric|min:0',
            'details.*.variant_id' => 'nullable|exists:product_variants,id',
            'details.*.addons' => 'nullable|array',
            'details.*.addons.*.id' => 'required|exists:product_addons,id',
            'details.*.addons.*.name' => 'required|string|max:255',
            'details.*.addons.*.price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $inventory = app(InventoryService::class);

            // Validate ingredient availability before creating the transaction.
            $ingredientRequirements = $inventory->requiredIngredients(
                collect($validated['details']),
            );
            $inventory->assertStockAvailable($ingredientRequirements);

            $transaction = Transaction::create([
                'invoice_number' => $validated['invoice_number'] ?? $this->generateInvoiceNumber(),
                'customer_name' => $validated['customer_name'] ?? null,
                'user_id' => $request->user()->id,
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'],
                'tax' => $validated['tax'],
                'total' => $validated['total'],
                'paid_amount' => $validated['paid_amount'],
                'change_amount' => $validated['change_amount'],
            ]);

            $products = Product::with(['recipe.recipeIngredients.ingredient', 'variants', 'addons'])
                ->whereIn('id', collect($validated['details'])->pluck('product_id'))
                ->get()
                ->keyBy('id');

            foreach ($validated['details'] as $detail) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $detail['product_id'],
                    'product_variant_id' => $detail['variant_id'] ?? null,
                    'addons' => $detail['addons'] ?? [],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'subtotal' => $detail['price'] * $detail['quantity'],
                ]);

                $product = $products->get((int) $detail['product_id']);

                if ($product && $product->hasActiveRecipe()) {
                    // Menu stock is computed from ingredients, so there is
                    // nothing to decrement on the products table.
                    continue;
                }

                Product::where('id', $detail['product_id'])->decrement('stock', $detail['quantity']);

                StockMovement::create([
                    'product_id' => $detail['product_id'],
                    'user_id' => $request->user()->id,
                    'type' => 'out',
                    'quantity' => $detail['quantity'],
                    'description' => 'Penjualan via '.$transaction->invoice_number,
                ]);
            }

            // Deduct ingredient stock for every menu that has a recipe
            // (variant multipliers and add-on consumption included).
            $inventory->deductIngredientsForSale(
                collect($validated['details']),
                $request->user(),
                $transaction->id,
                $transaction->invoice_number,
            );

            return response()->json($transaction->load(['user', 'details.product', 'details.variant']), 201);
        });
    }

    private function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'INV-'.$date.'-';
        $last = Transaction::where('invoice_number', 'like', $prefix.'%')->orderByDesc('invoice_number')->value('invoice_number');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function receipt(Transaction $transaction)
    {
        $transaction->load(['user', 'details.product', 'details.variant']);

        $productNames = $transaction->details->map(function ($d) {
            $name = $d->product ? $d->product->name : '-';

            if ($d->variant) {
                $name .= ' ('.$d->variant->name.')';
            }

            foreach ($d->addons ?? [] as $addon) {
                $name .= ' + '.$addon['name'];
            }

            return $name;
        });

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadView('pdf.receipt', [
            'transaction' => $transaction,
            'cashier' => $transaction->user ? $transaction->user->name : '-',
            'details' => $transaction->details->map(function ($d) use ($productNames) {
                $d->product_name = $productNames->get($d->id, '-');

                return $d;
            }),
            'storeName' => Setting::get('store_name', 'CoffeePOS'),
            'storeAddress' => Setting::get('store_address', ''),
            'receiptFooter' => Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda!'),
        ]);

        // Ukuran kertas struk 80 x 295 mm (dalam point: 1 mm = 2.8346 pt)
        $pdf->setPaper([0, 0, 226.77, 836.22], 'portrait');

        return $pdf->download('struk-'.$transaction->invoice_number.'.pdf');
    }
}
