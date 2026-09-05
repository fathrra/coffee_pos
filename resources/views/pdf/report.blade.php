<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; padding: 20px; }
        .center { text-align: center; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { font-size: 12px; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 10px; }
        th { background: #f5f0d0; font-weight: bold; }
        .stat-grid { display: flex; gap: 12px; margin-bottom: 20px; }
        .stat-box { flex: 1; border: 2px solid #000; padding: 12px; text-align: center; border-radius: 4px; }
        .stat-box .label { font-size: 9px; color: #666; text-transform: uppercase; font-weight: bold; }
        .stat-box .value { font-size: 18px; font-weight: bold; margin-top: 4px; }
        .divider { border-top: 2px solid #000; margin: 16px 0; }
        .section-title { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="center">
        <div class="title">☕ CoffeePOS</div>
        <div class="subtitle">Laporan Penjualan — {{ $period }} Hari Terakhir</div>
    </div>

    <div class="stat-grid">
        <div class="stat-box">
            <div class="label">Total Transaksi</div>
            <div class="value">{{ $transactions->count() }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Produk Terjual</div>
            <div class="value">{{ $itemsSold }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Total Pendapatan</div>
            <div class="value">Rp{{ number_format($revenue, 0, ',', '.') }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Total Keuntungan</div>
            <div class="value">Rp{{ number_format($profit, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="divider"></div>

    <div class="section-title">Produk Terlaris</div>
    <table>
        <thead>
            <tr><th>No</th><th>Produk</th><th>Terjual</th><th>Pendapatan</th></tr>
        </thead>
        <tbody>
            @php $i = 1; @endphp
            @foreach($topProducts as $product)
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $product['name'] }}</td>
                <td>{{ $product['qty'] }}</td>
                <td>Rp{{ number_format($product['revenue'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="section-title">Detail Transaksi</div>
    <table>
        <thead>
            <tr><th>Invoice</th><th>Tanggal</th><th>Kasir</th><th>Customer</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($transactions->take(50) as $t)
            <tr>
                <td>{{ $t->invoice_number }}</td>
                <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $t->user?->name ?? '-' }}</td>
                <td>{{ $t->customer_name ?? '-' }}</td>
                <td>Rp{{ number_format($t->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="center" style="margin-top: 20px; font-size: 9px; color: #666;">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} — CoffeePOS
    </div>
</body>
</html>
