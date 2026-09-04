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
                <thead><tr><th>Invoice</th><th>Tanggal</th><th>Atas Nama</th><th>Kasir</th><th>Item</th><th>Subtotal</th><th>Diskon</th><th>Pajak</th><th>Total</th><th>Bayar</th><th>Kembalian</th></tr></thead>
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
        <div class="report-period">
            <button class="period-btn active" data-period="7">7 Hari</button>
            <button class="period-btn" data-period="30">30 Hari</button>
            <button class="period-btn" data-period="90">90 Hari</button>
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
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('modal-stok')">Batal</button>
                <button class="btn btn-primary" onclick="saveStokAdjust()">Simpan</button>
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

@endsection
