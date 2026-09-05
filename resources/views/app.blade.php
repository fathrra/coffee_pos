@extends('layouts.app')

@section('title', ucfirst($activePage ?? 'dashboard') . ' — CoffeePOS')

@section('content')

    {{-- ===================== DASHBOARD ===================== --}}
    <section class="page {{ ($activePage ?? 'dashboard') === 'dashboard' ? 'active' : '' }}" id="page-dashboard">
        <div class="topbar">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <div class="page-sub" id="today-date"></div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="logout-btn" title="Keluar">&#10005;</button>
                </form>
            </div>
        </div>

        <div class="stat-grid" id="stat-grid"></div>

        @if(auth()->user()->role === 'admin')
        <div class="stat-grid" id="dash-inv-stats" style="grid-template-columns:repeat(4,1fr);"></div>
        @endif

        <div class="dash-grid">
            <div class="card card-pad">
                <div class="section-title">Grafik Penjualan (7 hari terakhir)</div>
                <canvas id="salesChart" height="130"></canvas>
            </div>
            <div class="card card-pad">
                <div class="section-title">Produk Terlaris</div>
                <div id="top-products"></div>
            </div>
        </div>

        <div class="dash-grid" style="margin-top:16px;">
            <div class="card card-pad">
                <div class="section-title">Transaksi Terbaru</div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Invoice</th><th>Kasir</th><th>Item</th><th>Total</th><th>Waktu</th></tr></thead>
                        <tbody id="recent-tx"></tbody>
                    </table>
                </div>
            </div>
            <div class="card card-pad">
                <div class="section-title">Informasi Stok</div>
                <div id="stock-info"></div>
            </div>
        </div>
    </section>

    {{-- ===================== KASIR ===================== --}}
    <section class="page {{ ($activePage ?? '') === 'kasir' ? 'active' : '' }}" id="page-kasir">
        <div class="topbar">
            <div>
                <h1 class="page-title">Kasir</h1>
                <div class="page-sub">Pilih produk lalu proses pembayaran cash</div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>

        <div class="pos-grid">
            <div>
                <div class="toolbar">
                    <input class="input" id="pos-search" placeholder="Cari produk..." style="flex:1;min-width:180px;">
                </div>
                <div class="cat-tabs" id="pos-cats"></div>
                <div class="product-grid" id="pos-products"></div>
            </div>

            <div class="cart-panel">
                <div class="cart-head">
                    <h3>Keranjang</h3>
                    <button class="icon-btn" id="clear-cart">Kosongkan</button>
                </div>
                <div class="cart-items" id="cart-items"></div>
                <div class="cart-sum">
                    <div class="sum-row"><label style="font-size:12px;color:var(--muted);">Atas Nama</label><input class="input" id="input-customer-name" type="text" placeholder="Opsional" style="flex:1;"></div>
                    <div class="sum-row"><span>Subtotal</span><span id="sum-subtotal">Rp0</span></div>
                    <div class="sum-row"><span>Diskon (Rp)</span><input class="input" id="input-discount" type="number" min="0" value="0"></div>
                    <div class="sum-row"><span>Pajak (%)</span><input class="input" id="input-tax" type="number" min="0" value="0"></div>
                    <div class="sum-row total"><span>Total</span><span id="sum-total">Rp0</span></div>
                </div>
                <div class="pay-row">
                    <label>Uang Cash Diterima</label>
                    <input class="pay-input" id="input-cash" type="number" min="0" placeholder="0">
                    <div class="change-line"><span>Kembalian</span><span class="val" id="change-amount">Rp0</span></div>
                    <button class="pay-btn" id="btn-pay" disabled>Selesaikan Transaksi</button>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== PRODUK ===================== --}}
    <section class="page {{ ($activePage ?? '') === 'produk' ? 'active' : '' }}" id="page-produk">
        <div class="topbar">
            <div>
                <h1 class="page-title">Manajemen Produk</h1>
                <div class="page-sub">Kelola seluruh produk coffee shop</div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>
        <div class="toolbar">
            <input class="input" id="produk-search" placeholder="Cari produk..." style="min-width:200px;">
            <select class="input" id="produk-filter-cat"></select>
            <div class="spacer"></div>
            <button class="btn btn-primary" id="btn-add-produk">+ Tambah Produk</button>
        </div>
        <div class="card table-wrap">
            <table>
                <thead><tr><th>Produk</th><th>Kategori</th><th>Harga Jual</th><th>Harga Modal</th><th>Stok</th><th>Status</th><th></th></tr></thead>
                <tbody id="produk-table"></tbody>
            </table>
        </div>
    </section>

    {{-- ===================== KATEGORI ===================== --}}
    <section class="page {{ ($activePage ?? '') === 'kategori' ? 'active' : '' }}" id="page-kategori">
        <div class="topbar">
            <div>
                <h1 class="page-title">Manajemen Kategori</h1>
                <div class="page-sub">Kelompokkan produk berdasarkan kategori</div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>
        <div class="toolbar">
            <div class="spacer"></div>
            <button class="btn btn-primary" id="btn-add-kategori">+ Tambah Kategori</button>
        </div>
        <div class="card card-pad">
            <div class="catlist" id="kategori-list"></div>
        </div>
    </section>

    {{-- ===================== STOK ===================== --}}
    <section class="page {{ ($activePage ?? '') === 'stok' ? 'active' : '' }}" id="page-stok">
        <div class="topbar">
            <div>
                <h1 class="page-title">Manajemen Stok</h1>
                <div class="page-sub">Pantau dan sesuaikan stok produk</div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>
        <div class="stat-grid" id="stok-stat-grid" style="grid-template-columns:repeat(3,1fr);"></div>
        <div class="card table-wrap">
            <table>
                <thead><tr><th>Produk</th><th>Kategori</th><th>Stok Saat Ini</th><th>Status</th><th>Sesuaikan</th></tr></thead>
                <tbody id="stok-table"></tbody>
            </table>
        </div>
    </section>

    {{-- ===================== TRANSAKSI ===================== --}}
    <section class="page {{ ($activePage ?? '') === 'transaksi' ? 'active' : '' }}" id="page-transaksi">
        <div class="topbar">
            <div>
                <h1 class="page-title">Riwayat Transaksi</h1>
                <div class="page-sub">Seluruh transaksi yang telah dilakukan</div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>
        <div class="toolbar">
            <input class="input" id="tx-search" placeholder="Cari nomor invoice...">
            <input class="input" id="tx-date" type="date">
            <div class="spacer"></div>
        </div>
        <div class="card table-wrap">
            <table>
                <thead><tr><th>Invoice</th><th>Tanggal</th><th>Atas Nama</th><th>Kasir</th><th>Item</th><th>Subtotal</th><th>Diskon</th><th>Pajak</th><th>Total</th><th>Bayar</th><th>Kembalian</th><th>Struk</th></tr></thead>
                <tbody id="tx-table"></tbody>
            </table>
        </div>
    </section>

    {{-- ===================== LAPORAN ===================== --}}
    <section class="page {{ ($activePage ?? '') === 'laporan' ? 'active' : '' }}" id="page-laporan">
        <div class="topbar">
            <div>
                <h1 class="page-title">Laporan Penjualan</h1>
                <div class="page-sub">Ringkasan performa penjualan coffee shop</div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>
        <div class="report-period" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
            <div style="display:flex;gap:8px;">
                <button class="period-btn active" data-period="7">7 Hari</button>
                <button class="period-btn" data-period="30">30 Hari</button>
                <button class="period-btn" data-period="90">90 Hari</button>
            </div>
            @if(auth()->user()->role === 'admin')
            <div style="display:flex;gap:8px;">
                <a href="{{ route('reports.export-pdf') }}?period=7" class="btn btn-ghost" id="btn-export-pdf" style="text-decoration:none;font-size:12px;">Export PDF</a>
                <a href="{{ route('reports.export-csv') }}?period=7" class="btn btn-ghost" id="btn-export-csv" style="text-decoration:none;font-size:12px;">Export CSV</a>
            </div>
            @endif
        </div>
        <div class="stat-grid" id="laporan-stats"></div>
        <div class="dash-grid">
            <div class="card card-pad">
                <div class="section-title">Grafik Pendapatan</div>
                <canvas id="reportChart" height="140"></canvas>
            </div>
            <div class="card card-pad">
                <div class="section-title">Produk Terlaris</div>
                <div id="laporan-top-products"></div>
            </div>
        </div>
    </section>

    {{-- ===================== USERS ===================== --}}
    <section class="page {{ ($activePage ?? '') === 'users' ? 'active' : '' }}" id="page-users">
        <div class="topbar">
            <div>
                <h1 class="page-title">Manajemen User</h1>
                <div class="page-sub">Kelola akun pengguna sistem</div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>
        <div class="toolbar">
            <div class="spacer"></div>
            <button class="btn btn-primary" id="btn-add-user">+ Tambah User</button>
        </div>
        <div class="card table-wrap">
            <table>
                <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Dibuat</th><th></th></tr></thead>
                <tbody id="users-table"></tbody>
            </table>
        </div>
    </section>

    {{-- ===================== PENGATURAN ===================== --}}
    <section class="page {{ ($activePage ?? '') === 'pengaturan' ? 'active' : '' }}" id="page-pengaturan">
        <div class="topbar">
            <div>
                <h1 class="page-title">Pengaturan</h1>
                <div class="page-sub">Pengaturan identitas toko dan struk</div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>
        <div class="card card-pad" style="max-width:560px;">
            <div class="section-title">Identitas Toko</div>
            <div class="field"><label>Nama Toko</label><input class="input" id="set-store-name"></div>
            <div class="field"><label>Alamat</label><textarea class="input" id="set-store-address" rows="2"></textarea></div>
            <div class="field"><label>Footer Struk</label><input class="input" id="set-receipt-footer" placeholder="Terima kasih atas kunjungan Anda!"></div>
            <div class="field"><label>Pajak Default (%)</label><input class="input" id="set-default-tax" type="number" min="0" max="100" value="0"></div>
            <div class="modal-actions" style="justify-content:flex-end;padding-top:8px;">
                <button class="btn btn-primary" onclick="savePengaturan()">Simpan Pengaturan</button>
            </div>
        </div>
    </section>

    {{-- ===================== RESEP ===================== --}}
    <section class="page {{ ($activePage ?? '') === 'resep' ? 'active' : '' }}" id="page-resep">
        <div class="topbar">
            <div>
                <h1 class="page-title">Menu &amp; Resep</h1>
                <div class="page-sub">Kelola resep tiap menu dan hitung estimasi modal produksi</div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>
        <div class="toolbar">
            <input class="input" id="resep-search" placeholder="Cari menu..." style="min-width:200px;">
            <select class="input" id="resep-filter">
                <option value="all">Semua Menu</option>
                <option value="has">Punya Resep</option>
                <option value="none">Belum Punya Resep</option>
            </select>
            <div class="spacer"></div>
            <button class="btn btn-primary" id="btn-add-resep">+ Tambah Resep</button>
        </div>
        <div class="card table-wrap">
            <table>
                <thead><tr><th>Menu</th><th>Kategori</th><th>Resep</th><th>Bahan</th><th>Modal</th><th>Harga Jual</th><th>Profit</th><th>Margin</th><th>Stok Menu</th><th>Status</th><th></th></tr></thead>
                <tbody id="resep-table"></tbody>
            </table>
        </div>
    </section>

    {{-- ===================== INVENTORY ===================== --}}
    <section class="page {{ ($activePage ?? '') === 'inventory' ? 'active' : '' }}" id="page-inventory">
        <div class="topbar">
            <div>
                <h1 class="page-title">Inventory</h1>
                <div class="page-sub">Kelola bahan baku, supplier, dan pergerakan stok</div>
            </div>
            <div class="role-pill">
                <span class="avatar">{{ substr(auth()->user()->name, 0, 1) }}</span>
                {{ auth()->user()->name }}
                <span class="muted" style="font-weight:700;">&middot; {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </div>

        <div class="stat-grid" id="inv-stat-grid" style="grid-template-columns:repeat(4,1fr);"></div>

        <div class="cat-tabs" id="inv-tabs">
            <button class="cat-tab active" data-invtab="ingredients">Bahan Baku</button>
            <button class="cat-tab" data-invtab="suppliers">Supplier</button>
            <button class="cat-tab" data-invtab="in">Stok Masuk</button>
            <button class="cat-tab" data-invtab="out">Stok Keluar</button>
            <button class="cat-tab" data-invtab="adjust">Penyesuaian</button>
            <button class="cat-tab" data-invtab="low">Stok Menipis</button>
            <button class="cat-tab" data-invtab="report">Laporan</button>
        </div>

        {{-- Tab: Bahan Baku --}}
        <div class="subview active" id="inv-view-ingredients">
            <div class="toolbar">
                <input class="input" id="bahan-search" placeholder="Cari bahan..." style="min-width:200px;">
                <select class="input" id="bahan-filter-status">
                    <option value="all">Semua Status</option>
                    <option value="good">Stok Aman</option>
                    <option value="low">Stok Menipis</option>
                    <option value="out">Stok Habis</option>
                    <option value="inactive">Nonaktif</option>
                </select>
                <div class="spacer"></div>
                <button class="btn btn-primary" id="btn-add-bahan">+ Tambah Bahan</button>
            </div>
            <div class="card table-wrap">
                <table>
                    <thead><tr><th>Bahan</th><th>Unit</th><th>Stok</th><th>Minimum</th><th>Harga/Unit</th><th>Nilai Stok</th><th>Status</th><th>Dipakai</th><th></th></tr></thead>
                    <tbody id="bahan-table"></tbody>
                </table>
            </div>
        </div>

        {{-- Tab: Supplier --}}
        <div class="subview" id="inv-view-suppliers">
            <div class="toolbar">
                <input class="input" id="supplier-search" placeholder="Cari supplier..." style="min-width:200px;">
                <div class="spacer"></div>
                <button class="btn btn-primary" id="btn-add-supplier">+ Tambah Supplier</button>
            </div>
            <div class="card table-wrap">
                <table>
                    <thead><tr><th>Nama</th><th>Telepon</th><th>Email</th><th>Alamat</th><th>Catatan</th><th>Status</th><th></th></tr></thead>
                    <tbody id="supplier-table"></tbody>
                </table>
            </div>
        </div>

        {{-- Tab: Stok Masuk --}}
        <div class="subview" id="inv-view-in">
            <div class="dash-grid" style="align-items:start;">
                <div class="card card-pad">
                    <div class="section-title">Stok Masuk (Pembelian)</div>
                    <div class="field"><label>Supplier</label><select class="input" id="stockin-supplier"></select></div>
                    <div class="field"><label>Bahan</label><select class="input" id="stockin-ingredient"></select></div>
                    <div class="field"><label>Jumlah</label><input class="input" type="number" id="stockin-qty" min="0" step="any"></div>
                    <div class="field"><label>Harga Satuan (Rp)</label><input class="input" type="number" id="stockin-cost" min="0" step="any"></div>
                    <div class="field"><label>Tanggal</label><input class="input" type="date" id="stockin-date"></div>
                    <div class="field"><label>Catatan</label><input class="input" id="stockin-notes" placeholder="Contoh: PO dari supplier"></div>
                    <button class="btn btn-primary" id="btn-stockin-save" style="width:100%;">Simpan Stok Masuk</button>
                </div>
                <div class="card table-wrap" style="padding:14px 18px;">
                    <div class="section-title">Riwayat Stok Masuk</div>
                    <table>
                        <thead><tr><th>Waktu</th><th>Bahan</th><th>Jumlah</th><th>Stok Setelah</th><th>User</th></tr></thead>
                        <tbody id="stockin-table"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab: Stok Keluar --}}
        <div class="subview" id="inv-view-out">
            <div class="dash-grid" style="align-items:start;">
                <div class="card card-pad">
                    <div class="section-title">Stok Keluar</div>
                    <div class="field"><label>Bahan</label><select class="input" id="stockout-ingredient"></select><div class="muted" id="stockout-current" style="font-size:12px;margin-top:4px;"></div></div>
                    <div class="field"><label>Jumlah</label><input class="input" type="number" id="stockout-qty" min="0" step="any"></div>
                    <div class="field"><label>Alasan</label>
                        <select class="input" id="stockout-reason">
                            <option value="rusak">Rusak</option>
                            <option value="expired">Expired</option>
                            <option value="terbuang">Terbuang</option>
                            <option value="sample">Sample</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="field"><label>Catatan</label><input class="input" id="stockout-notes"></div>
                    <button class="btn btn-primary" id="btn-stockout-save" style="width:100%;">Simpan Stok Keluar</button>
                </div>
                <div class="card table-wrap" style="padding:14px 18px;">
                    <div class="section-title">Riwayat Stok Keluar</div>
                    <table>
                        <thead><tr><th>Waktu</th><th>Bahan</th><th>Jumlah</th><th>Alasan</th><th>User</th></tr></thead>
                        <tbody id="stockout-table"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab: Penyesuaian --}}
        <div class="subview" id="inv-view-adjust">
            <div class="dash-grid" style="align-items:start;">
                <div class="card card-pad">
                    <div class="section-title">Penyesuaian Stok</div>
                    <div class="field"><label>Bahan</label><select class="input" id="adjust-ingredient"></select></div>
                    <div class="field"><label>Stok Sistem</label><input class="input" id="adjust-system-stock" readonly></div>
                    <div class="field"><label>Stok Aktual</label><input class="input" type="number" id="adjust-actual-stock" min="0" step="any"></div>
                    <div class="field"><label>Selisih</label><input class="input" id="adjust-diff" readonly></div>
                    <div class="field"><label>Alasan</label><input class="input" id="adjust-reason" placeholder="Contoh: Stock opname"></div>
                    <button class="btn btn-primary" id="btn-adjust-save" style="width:100%;">Simpan Penyesuaian</button>
                </div>
                <div class="card table-wrap" style="padding:14px 18px;">
                    <div class="section-title">Riwayat Penyesuaian</div>
                    <table>
                        <thead><tr><th>Waktu</th><th>Bahan</th><th>Selisih</th><th>Sebelum</th><th>Sesudah</th><th>Alasan</th></tr></thead>
                        <tbody id="adjust-table"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab: Stok Menipis --}}
        <div class="subview" id="inv-view-low">
            <div class="card table-wrap">
                <table>
                    <thead><tr><th>Bahan</th><th>Unit</th><th>Stok</th><th>Minimum</th><th>Status</th></tr></thead>
                    <tbody id="lowstock-table"></tbody>
                </table>
            </div>
        </div>

        {{-- Tab: Laporan --}}
        <div class="subview" id="inv-view-report">
            <div class="report-period" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div style="display:flex;gap:8px;">
                    <button class="period-btn active" data-invperiod="today">Hari Ini</button>
                    <button class="period-btn" data-invperiod="7">7 Hari</button>
                    <button class="period-btn" data-invperiod="30">30 Hari</button>
                    <button class="period-btn" data-invperiod="custom">Kustom</button>
                </div>
                <div style="display:flex;gap:8px;align-items:center;" id="inv-report-custom">
                    <input class="input" type="date" id="inv-report-from" style="padding:6px 10px;">
                    <span class="muted" style="font-size:13px;font-weight:700;">s/d</span>
                    <input class="input" type="date" id="inv-report-to" style="padding:6px 10px;">
                </div>
            </div>
            <div class="stat-grid" id="inv-report-stats" style="grid-template-columns:repeat(4,1fr);"></div>
            <div class="card table-wrap">
                <div class="section-title" style="padding:14px 18px 0;">Pemakaian Bahan</div>
                <table>
                    <thead><tr><th>Bahan</th><th>Stok Masuk</th><th>Stok Keluar</th><th>Terpakai (Jual)</th><th>Penyesuaian</th><th>Stok Saat Ini</th><th>Nilai</th></tr></thead>
                    <tbody id="inv-report-table"></tbody>
                </table>
            </div>
            <div style="margin-top:18px;" class="card table-wrap">
                <div class="section-title" style="padding:14px 18px 0;">Laporan Resep &amp; Margin</div>
                <table>
                    <thead><tr><th>Menu</th><th>Modal</th><th>Harga</th><th>Profit</th><th>Margin</th><th>Stok</th></tr></thead>
                    <tbody id="recipe-report-table"></tbody>
                </table>
            </div>
        </div>
    </section>

@endsection

@section('modals')

    <!-- Modal: Produk -->
    <div class="modal-bg" id="modal-produk">
        <div class="modal">
            <h3 id="produk-modal-title">Tambah Produk</h3>
            <input type="hidden" id="produk-id">
            <div class="field"><label>Nama Produk</label><input class="input" id="produk-nama"></div>
            <div class="field"><label>Kategori</label><select class="input" id="produk-kategori"></select></div>
            <div class="field"><label>Harga Jual (Rp)</label><input class="input" type="number" id="produk-harga"></div>
            <div class="field"><label>Harga Modal (Rp)</label><input class="input" type="number" id="produk-modal-cost"></div>
            <div class="field"><label>Stok</label><input class="input" type="number" id="produk-stok"></div>
            <div class="field"><label>Ikon</label><input class="input" id="produk-icon" placeholder="&#9749; (emoji singkat)"></div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('modal-produk')">Batal</button>
                <button class="btn btn-primary" onclick="saveProduk()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal: Kategori -->
    <div class="modal-bg" id="modal-kategori">
        <div class="modal">
            <h3 id="kategori-modal-title">Tambah Kategori</h3>
            <input type="hidden" id="kategori-id">
            <div class="field"><label>Nama Kategori</label><input class="input" id="kategori-nama"></div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('modal-kategori')">Batal</button>
                <button class="btn btn-primary" onclick="saveKategori()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal: Sesuaikan Stok -->
    <div class="modal-bg" id="modal-stok">
        <div class="modal">
            <h3>Sesuaikan Stok</h3>
            <input type="hidden" id="stok-produk-id">
            <div class="field"><label id="stok-produk-nama" style="font-size:14px;color:var(--ink);font-weight:800;"></label></div>
            <div class="field"><label>Jenis Penyesuaian</label>
                <select class="input" id="stok-jenis">
                    <option value="tambah">Tambah Stok</option>
                    <option value="kurang">Kurangi Stok</option>
                </select>
            </div>
            <div class="field"><label>Jumlah</label><input class="input" type="number" id="stok-jumlah" min="1" value="1"></div>
            <div class="field"><label>Keterangan</label><input class="input" type="text" id="stok-deskripsi" placeholder="Contoh: Restock dari supplier"></div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('modal-stok')">Batal</button>
                <button class="btn btn-primary" onclick="saveStokAdjust()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal: User -->
    <div class="modal-bg" id="modal-user">
        <div class="modal">
            <h3 id="user-modal-title">Tambah User</h3>
            <input type="hidden" id="user-id">
            <div class="field"><label>Nama Lengkap</label><input class="input" id="user-nama"></div>
            <div class="field"><label>Email</label><input class="input" type="email" id="user-email"></div>
            <div class="field"><label>Password</label><input class="input" type="password" id="user-password" placeholder="Kosongkan jika tidak mengubah"></div>
            <div class="field"><label>Konfirmasi Password</label><input class="input" type="password" id="user-password-confirm"></div>
            <div class="field"><label>Role</label>
                <select class="input" id="user-role">
                    <option value="kasir">Kasir</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('modal-user')">Batal</button>
                <button class="btn btn-primary" onclick="saveUser()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal: Struk -->
    <div class="modal-bg" id="modal-struk">
        <div class="modal">
            <h3>&#10003; Transaksi Berhasil</h3>
            <div id="struk-content" class="receipt"></div>
            <div class="modal-actions">
                <button class="btn btn-primary" onclick="closeModal('modal-struk')" style="flex:none;width:100%;">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal: Pilih Varian/Add-on (POS) -->
    <div class="modal-bg" id="modal-pick-product">
        <div class="modal">
            <h3>Pilih Varian &amp; Tambahan</h3>
            <div id="pick-product-content"></div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('modal-pick-product')">Batal</button>
                <button class="btn btn-primary" onclick="confirmAddPick()">Tambah ke Keranjang</button>
            </div>
        </div>
    </div>

    <!-- Modal: Kelola Varian & Add-on (Admin) -->
    <div class="modal-bg" id="modal-varian">
        <div class="modal" style="max-width:680px;">
            <h3>Varian &amp; Add-on &mdash; <span id="varian-product-name"></span></h3>
            <div class="field">
                <label>Varian (mempengaruhi harga &amp; pemakaian bahan)</label>
                <div id="varian-rows"></div>
                <button class="btn btn-ghost" onclick="addVarianRow()" style="margin-top:4px;padding:6px 12px;font-size:13px;">+ Tambah Varian</button>
            </div>
            <div class="field">
                <label>Add-on / Tambahan</label>
                <div id="addon-rows"></div>
                <button class="btn btn-ghost" onclick="addAddonRow()" style="margin-top:4px;padding:6px 12px;font-size:13px;">+ Tambah Add-on</button>
            </div>
            <div class="muted" style="font-size:12px;margin-top:6px;">Multiplier 1 = ukuran standar. Varian dengan multiplier 2 memakai bahan 2 kali lipat. Add-on hanya memakai bahan bila dipilih.</div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('modal-varian')">Batal</button>
                <button class="btn btn-primary" onclick="saveVarian()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal: Bahan -->
    <div class="modal-bg" id="modal-bahan">
        <div class="modal">
            <h3 id="bahan-modal-title">Tambah Bahan</h3>
            <input type="hidden" id="bahan-id">
            <div class="field"><label>Nama Bahan</label><input class="input" id="bahan-nama" placeholder="Contoh: Kopi Arabica"></div>
            <div class="field"><label>Unit</label>
                <select class="input" id="bahan-unit">
                    <option value="gram">gram</option>
                    <option value="kg">kg</option>
                    <option value="ml">ml</option>
                    <option value="liter">liter</option>
                    <option value="pcs">pcs</option>
                </select>
            </div>
            <div class="field"><label>Stok Saat Ini</label><input class="input" type="number" id="bahan-stok" min="0" step="any" value="0"></div>
            <div class="field"><label>Stok Minimum</label><input class="input" type="number" id="bahan-minimum" min="0" step="any" value="0"></div>
            <div class="field"><label>Harga per Unit (Rp)</label><input class="input" type="number" id="bahan-harga" min="0" step="any" value="0"></div>
            <div class="field"><label>Keterangan</label><input class="input" id="bahan-deskripsi"></div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('modal-bahan')">Batal</button>
                <button class="btn btn-primary" onclick="saveBahan()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal: Supplier -->
    <div class="modal-bg" id="modal-supplier">
        <div class="modal">
            <h3 id="supplier-modal-title">Tambah Supplier</h3>
            <input type="hidden" id="supplier-id">
            <div class="field"><label>Nama Supplier</label><input class="input" id="supplier-nama"></div>
            <div class="field"><label>Telepon</label><input class="input" id="supplier-telepon"></div>
            <div class="field"><label>Email</label><input class="input" type="email" id="supplier-email"></div>
            <div class="field"><label>Alamat</label><textarea class="input" id="supplier-alamat" rows="2"></textarea></div>
            <div class="field"><label>Catatan</label><input class="input" id="supplier-catatan"></div>
            <div class="field"><label>Status</label>
                <select class="input" id="supplier-status">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('modal-supplier')">Batal</button>
                <button class="btn btn-primary" onclick="saveSupplier()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal: Resep -->
    <div class="modal-bg" id="modal-resep">
        <div class="modal" style="max-width:640px;">
            <h3 id="resep-modal-title">Atur Resep</h3>
            <input type="hidden" id="resep-product-id">
            <div class="field"><label>Menu</label>
                <select class="input" id="resep-product-select" disabled></select>
            </div>
            <div class="field"><label>Harga Jual</label><input class="input" id="resep-harga-jual" readonly></div>
            <div class="field"><label>Catatan Resep</label><input class="input" id="resep-notes" placeholder="Catatan opsional"></div>
            <div class="section-title" style="color:var(--brown);">Bahan Resep</div>
            <div id="resep-ingredient-rows"></div>
            <button class="btn btn-ghost" id="btn-resep-add-row" style="width:100%;margin:10px 0;">+ Tambah Bahan</button>
            <div class="card card-pad" style="padding:12px 16px;margin-bottom:14px;">
                <div class="sum-row" style="justify-content:space-between;"><span>Estimasi Modal</span><span id="resep-sum-cost">Rp0</span></div>
                <div class="sum-row" style="justify-content:space-between;"><span>Harga Jual</span><span id="resep-sum-price">Rp0</span></div>
                <div class="sum-row" style="justify-content:space-between;"><span>Profit</span><span id="resep-sum-profit">Rp0</span></div>
                <div class="sum-row" style="justify-content:space-between;"><span>Margin</span><span id="resep-sum-margin">0%</span></div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('modal-resep')">Batal</button>
                <button class="btn btn-primary" onclick="saveResep()">Simpan Resep</button>
            </div>
        </div>
    </div>

@endsection
