<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function exportPdf(Request $request)
    {
        $period = $request->get('period', 7);
        $cutoff = now()->subDays($period);

        $transactions = Transaction::with(['user', 'details.product'])
            ->where('created_at', '>=', $cutoff)
            ->orderByDesc('created_at')
            ->get();

        $revenue = $transactions->sum('total');
        $itemsSold = $transactions->sum(fn ($t) => $t->details->sum('quantity'));
        $profit = $transactions->reduce(function ($sum, $t) {
            return $sum + $t->details->reduce(function ($a, $d) {
                $cost = $d->product ? $d->product->cost * $d->quantity : 0;
                return $a + ($d->subtotal - $cost);
            }, 0);
        }, 0);

        $salesByProduct = [];
        $transactions->each(fn ($t) => $t->details->each(function ($d) use (&$salesByProduct) {
            $pid = $d->product_id;
            $name = $d->product ? $d->product->name : '-';
            $salesByProduct[$pid] = $salesByProduct[$pid] ?? ['name' => $name, 'qty' => 0, 'revenue' => 0];
            $salesByProduct[$pid]['qty'] += $d->quantity;
            $salesByProduct[$pid]['revenue'] += $d->subtotal;
        }));
        uasort($salesByProduct, fn ($a, $b) => $b['qty'] <=> $a['qty']);

        $pdf = Pdf::loadView('pdf.report', [
            'period' => $period,
            'transactions' => $transactions,
            'revenue' => $revenue,
            'itemsSold' => $itemsSold,
            'profit' => $profit,
            'topProducts' => array_slice($salesByProduct, 0, 10),
        ]);

        return $pdf->download('laporan-penjualan-' . $period . 'hari.pdf');
    }

    public function exportCsv(Request $request)
    {
        $period = $request->get('period', 7);
        $cutoff = now()->subDays($period);

        $transactions = Transaction::with(['user', 'details.product'])
            ->where('created_at', '>=', $cutoff)
            ->orderByDesc('created_at')
            ->get();

        $filename = 'laporan-penjualan-' . $period . 'hari.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return new StreamedResponse(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice', 'Tanggal', 'Kasir', 'Customer', 'Subtotal', 'Diskon', 'Pajak', 'Total', 'Bayar', 'Kembalian']);
            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->invoice_number,
                    $t->created_at->format('d/m/Y H:i'),
                    $t->user?->name ?? '-',
                    $t->customer_name ?? '-',
                    $t->subtotal,
                    $t->discount,
                    $t->tax,
                    $t->total,
                    $t->paid_amount,
                    $t->change_amount,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
