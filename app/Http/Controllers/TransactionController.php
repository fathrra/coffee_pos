<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::with(['user', 'details.product'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($t) {
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

            foreach ($validated['details'] as $detail) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'subtotal' => $detail['price'] * $detail['quantity'],
                ]);

                Product::where('id', $detail['product_id'])->decrement('stock', $detail['quantity']);
            }

            return response()->json($transaction->load(['user', 'details.product']), 201);
        });
    }
}
