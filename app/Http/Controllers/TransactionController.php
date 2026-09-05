<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::with(['user', 'details.product'])
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
            'invoice_number' => 'required|string|unique:transactions,invoice_number',
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
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $inventory = app(InventoryService::class);

            // Validate ingredient availability before creating the transaction.
            $ingredientRequirements = $inventory->requiredIngredients(
                collect($validated['details']),
            );
            $inventory->assertStockAvailable($ingredientRequirements);

            $transaction = Transaction::create([
                'invoice_number' => $validated['invoice_number'],
                'customer_name' => $validated['customer_name'] ?? null,
                'user_id' => $request->user()->id,
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'],
                'tax' => $validated['tax'],
                'total' => $validated['total'],
                'paid_amount' => $validated['paid_amount'],
                'change_amount' => $validated['change_amount'],
            ]);

            $products = Product::with('recipe.recipeIngredients.ingredient')
                ->whereIn('id', collect($validated['details'])->pluck('product_id'))
                ->get()
                ->keyBy('id');

            foreach ($validated['details'] as $detail) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $detail['product_id'],
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
                    'description' => 'Penjualan via ' . $validated['invoice_number'],
                ]);
            }

            // Deduct ingredient stock for every menu that has a recipe.
            $inventory->deductIngredientsForSale(
                collect($validated['details']),
                $request->user(),
                $transaction->id,
                $validated['invoice_number'],
            );

            return response()->json($transaction->load(['user', 'details.product']), 201);
        });
    }

    public function receipt(Transaction $transaction)
    {
        $transaction->load(['user', 'details.product']);

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadView('pdf.receipt', [
            'transaction' => $transaction,
            'cashier' => $transaction->user ? $transaction->user->name : '-',
            'details' => $transaction->details->map(function ($d) {
                $d->product_name = $d->product ? $d->product->name : '-';
                return $d;
            }),
        ]);

        // Ukuran kertas struk 80 x 295 mm (dalam point: 1 mm = 2.8346 pt)
        $pdf->setPaper([0, 0, 226.77, 836.22], 'portrait');

        return $pdf->download('struk-' . $transaction->invoice_number . '.pdf');
    }
}
