<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 4mm 3mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9.5px; color: #000;
            width: 100%;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .title { font-size: 13px; font-weight: bold; letter-spacing: 0.5px; }
        .subtitle { font-size: 7.5px; color: #666; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .item { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2px; }
        .item-name { flex: 1; padding-right: 6px; }
        .item-qty { width: 24px; text-align: center; flex-shrink: 0; }
        .item-price { width: 60px; text-align: right; flex-shrink: 0; white-space: nowrap; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 2px; width: 100%; }
        .total-row {
            font-size: 12px; font-weight: bold;
            border-top: 1px dashed #000; border-bottom: 1px dashed #000;
            padding: 4px 0; margin: 5px 0;
            display: flex; justify-content: space-between;
        }
        .footer { text-align: center; font-size: 8px; margin-top: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="center">
        <div class="title">CoffeePOS</div>
        <div class="subtitle">Kopi Nusantara POS System</div>
    </div>

    <div class="divider"></div>

    <div style="margin-bottom: 4px;">
        <div class="item"><span class="item-name bold">Invoice</span><span class="item-price">{{ $transaction->invoice_number }}</span></div>
        <div class="item"><span class="item-name bold">Tanggal</span><span class="item-price">{{ $transaction->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="item"><span class="item-name bold">Kasir</span><span class="item-price">{{ $cashier }}</span></div>
        @if($transaction->customer_name)
        <div class="item"><span class="item-name bold">Atas Nama</span><span class="item-price">{{ $transaction->customer_name }}</span></div>
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
        Sampai jumpa lagi
    </div>
</body>
</html>