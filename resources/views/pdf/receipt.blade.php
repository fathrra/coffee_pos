<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; width: 220px; margin: 0 auto; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .title { font-size: 16px; font-weight: bold; }
        .subtitle { font-size: 9px; color: #666; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .item { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .item-name { flex: 1; }
        .item-qty { width: 30px; text-align: center; }
        .item-price { width: 65px; text-align: right; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .total-row { font-size: 13px; font-weight: bold; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; margin: 6px 0; display: flex; justify-content: space-between; }
        .footer { text-align: center; font-size: 9px; margin-top: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="center">
        <div class="title">☕ CoffeePOS</div>
        <div class="subtitle">Kopi Nusantara POS System</div>
    </div>

    <div class="divider"></div>

    <div style="margin-bottom: 6px;">
        <div class="item"><span class="item-name bold">Invoice</span><span>{{ $transaction->invoice_number }}</span></div>
        <div class="item"><span class="item-name bold">Tanggal</span><span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="item"><span class="item-name bold">Kasir</span><span>{{ $cashier }}</span></div>
        @if($transaction->customer_name)
        <div class="item"><span class="item-name bold">Atas Nama</span><span>{{ $transaction->customer_name }}</span></div>
        @endif
    </div>

    <div class="divider"></div>

    @foreach($details as $detail)
    <div class="item">
        <span class="item-name">{{ $detail->product_name }}</span>
        <span class="item-qty">x{{ $detail->quantity }}</span>
        <span class="item-price">Rp{{ number_format($detail->subtotal, 0, ',', '.') }}</span>
    </div>
    @endforeach

    <div class="divider"></div>

    <div class="summary-row"><span>Subtotal</span><span>Rp{{ number_format($transaction->subtotal, 0, ',', '.') }}</span></div>
    <div class="summary-row"><span>Diskon</span><span>-Rp{{ number_format($transaction->discount, 0, ',', '.') }}</span></div>
    <div class="summary-row"><span>Pajak</span><span>Rp{{ number_format($transaction->tax, 0, ',', '.') }}</span></div>

    <div class="total-row">
        <span>Total</span>
        <span>Rp{{ number_format($transaction->total, 0, ',', '.') }}</span>
    </div>

    <div class="summary-row"><span>Cash</span><span>Rp{{ number_format($transaction->paid_amount, 0, ',', '.') }}</span></div>
    <div class="summary-row bold"><span>Kembalian</span><span>Rp{{ number_format($transaction->change_amount, 0, ',', '.') }}</span></div>

    <div class="divider"></div>

    <div class="footer">
        Terima kasih atas kunjungan Anda!<br>
        Sampai jumpa lagi ☕
    </div>
</body>
</html>
