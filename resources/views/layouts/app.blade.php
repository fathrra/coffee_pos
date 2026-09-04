<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CoffeePOS — Sistem Kasir')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        window.__user_name = '{{ auth()->user()->name }}';
        window.__user_role = '{{ auth()->user()->role }}';
        window.__base_url = '{{ url('/') }}';
    </script>
</head>
<body>

    <div class="app" id="app-shell">

        <aside class="sidebar">
            <div class="brand-icon">
                <img src="{{ asset('logo.png') }}" alt="Logo" style="width:100%;height:100%;object-fit:contain;border-radius:50%;">
            </div>
            <nav id="nav">
                <button class="navbtn {{ ($activePage ?? 'dashboard') === 'dashboard' ? 'active' : '' }}" data-page="dashboard" title="Dashboard">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
                </button>
                <button class="navbtn {{ ($activePage ?? '') === 'kasir' ? 'active' : '' }}" data-page="kasir" title="Kasir">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
                </button>
                @if(auth()->user()->role === 'admin')
                <button class="navbtn {{ ($activePage ?? '') === 'produk' ? 'active' : '' }}" data-page="produk" title="Produk">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg></span>
                </button>
                <button class="navbtn {{ ($activePage ?? '') === 'kategori' ? 'active' : '' }}" data-page="kategori" title="Kategori">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10h10V2z"/><path d="M22 12H12v10h10V12z"/><path d="M22 2H12v5h10V2z"/><path d="M7 12H2v10h5V12z"/></svg></span>
                </button>
                <button class="navbtn {{ ($activePage ?? '') === 'stok' ? 'active' : '' }}" data-page="stok" title="Stok">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg></span>
                </button>
                <button class="navbtn {{ ($activePage ?? '') === 'transaksi' ? 'active' : '' }}" data-page="transaksi" title="Transaksi">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg></span>
                </button>
                <button class="navbtn {{ ($activePage ?? '') === 'laporan' ? 'active' : '' }}" data-page="laporan" title="Laporan">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg></span>
                </button>
                @else
                <button class="navbtn {{ ($activePage ?? '') === 'transaksi' ? 'active' : '' }}" data-page="transaksi" title="Transaksi">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg></span>
                </button>
                @endif
            </nav>
            <div class="sidebar-foot">POS</div>
        </aside>

        <main class="main">
            @yield('content')
        </main>

    </div>

    @yield('modals')

    <div class="toast" id="toast"></div>

</body>
</html>
