/* ============================ APP STATE ============================ */
const APP = {
    user: null,
    categories: [],
    products: [],
    transactions: [],
    cart: [],
    nextProdId: 1,
    nextCatId: 1,
    nextTxId: 1,
    posActiveCat: 'all',
    reportPeriod: 7,
    salesChartInstance: null,
    reportChartInstance: null,
};

/* ============================ INIT ============================ */
document.addEventListener('DOMContentLoaded', () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta ? meta.content : '';

    APP.user = {
        name: window.__user_name || 'User',
        role: window.__user_role || 'admin',
    };

    document.getElementById('today-date').textContent = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    loadCategories();
    loadProducts();
    loadTransactions();
    seedHistory();

    setupNav();
    setupKasir();
    setupProduk();
    setupKategori();
    renderAll();
});

/* ============================ API HELPERS ============================ */
function apiUrl(path) {
    const base = window.__base_url || window.location.origin;
    return base.replace(/\/$/, '') + path;
}

async function apiFetch(path, options = {}) {
    const defaults = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
    };
    const res = await fetch(apiUrl(path), { ...defaults, ...options });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Request failed');
    return data;
}

async function loadCategories() {
    try {
        const data = await apiFetch('/categories');
        APP.categories = data.data || data;
        APP.nextCatId = Math.max(0, ...APP.categories.map(c => c.id)) + 1;
    } catch (e) {
        APP.categories = [];
    }
}

async function loadProducts() {
    try {
        const data = await apiFetch('/products');
        APP.products = data.data || data;
        APP.nextProdId = Math.max(0, ...APP.products.map(p => p.id)) + 1;
    } catch (e) {
        APP.products = [];
    }
}

async function loadTransactions() {
    try {
        const data = await apiFetch('/transactions');
        APP.transactions = (data.data || data).map(t => ({
            ...t,
            subtotal: Number(t.subtotal) || 0,
            discount: Number(t.discount) || 0,
            tax: Number(t.tax) || 0,
            total: Number(t.total) || 0,
            paid_amount: Number(t.paid_amount || t.paid) || 0,
            change_amount: Number(t.change_amount || t.change) || 0,
            date: new Date(t.created_at),
            details: (t.details || []).map(d => ({
                ...d,
                quantity: Number(d.quantity || d.qty) || 0,
                price: Number(d.price) || 0,
                subtotal: Number(d.subtotal || d.qty * d.price) || 0,
            })),
        }));
        APP.transactions.sort((a, b) => b.date - a.date);
        APP.nextTxId = Math.max(0, ...APP.transactions.map(t => t.id)) + 1;
    } catch (e) {
        APP.transactions = [];
    }
}

/* ============================ SEED HISTORY (demo) ============================ */
function seedHistory() {
    if (APP.transactions.length > 0) return;
    const cashiers = [APP.user.name, 'Rani', 'Dimas'];
    const customerNames = ['Andi', 'Sari', 'Budi', 'Maya', 'Rizki', 'Dewi', 'Farhan', 'Lestari', null, null];
    const now = new Date();
    for (let d = 13; d >= 0; d--) {
        const day = new Date(now); day.setDate(now.getDate() - d);
        const txCount = 2 + Math.floor(Math.random() * 4);
        for (let t = 0; t < txCount; t++) {
            const itemCount = 1 + Math.floor(Math.random() * 3);
            let details = [];
            let subtotal = 0;
            for (let i = 0; i < itemCount; i++) {
                const p = APP.products[Math.floor(Math.random() * APP.products.length)];
                if (!p) continue;
                const qty = 1 + Math.floor(Math.random() * 2);
                details.push({ product_id: p.id, name: p.name, price: p.price, quantity: qty, subtotal: p.price * qty });
                subtotal += p.price * qty;
            }
            const discount = Math.random() < 0.2 ? Math.round(subtotal * 0.1 / 1000) * 1000 : 0;
            const tax = 0;
            const total = subtotal - discount + tax;
            const paid = Math.ceil(total / 5000) * 5000 + (Math.random() < 0.3 ? 5000 : 0);
            const txDate = new Date(day); txDate.setHours(8 + Math.floor(Math.random() * 11), Math.floor(Math.random() * 60));
            APP.transactions.push({
                id: APP.nextTxId++,
                invoice_number: 'INV-' + txDate.toISOString().slice(0, 10).replace(/-/g, '') + '-' + String(t + 1).padStart(3, '0'),
                customer_name: customerNames[Math.floor(Math.random() * customerNames.length)],
                cashier: cashiers[Math.floor(Math.random() * cashiers.length)],
                details, subtotal, discount, tax, total,
                paid_amount: paid, change_amount: paid - total,
                date: txDate,
            });
        }
    }
    APP.transactions.sort((a, b) => b.date - a.date);
}

/* ============================ HELPERS ============================ */
function rupiah(n) { const v = Number(n); return 'Rp' + (isNaN(v) ? 0 : Math.round(v)).toLocaleString('id-ID'); }
function catName(id) { const c = APP.categories.find(c => c.id == id); return c ? c.name : '-'; }
function stockStatus(stock) {
    if (stock <= 0) return { label: 'Stok Habis', cls: 'badge-red' };
    if (stock <= 8) return { label: 'Stok Menipis', cls: 'badge-yellow' };
    return { label: 'Stok Aman', cls: 'badge-green' };
}
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => t.classList.remove('show'), 2200);
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openModal(id) { document.getElementById(id).classList.add('open'); }
function fmtDateShort(d) {
    if (!(d instanceof Date)) d = new Date(d);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}
function isToday(d) {
    if (!(d instanceof Date)) d = new Date(d);
    const t = new Date();
    return d.getFullYear() === t.getFullYear() && d.getMonth() === t.getMonth() && d.getDate() === t.getDate();
}

/* ============================ NAVIGATION ============================ */
function setupNav() {
    document.querySelectorAll('.navbtn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.navbtn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const page = btn.dataset.page;
            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.getElementById('page-' + page).classList.add('active');
            renderAll();
        });
    });

    document.querySelectorAll('.modal-bg').forEach(bg => {
        bg.addEventListener('click', (e) => { if (e.target === bg) bg.classList.remove('open'); });
    });
}

/* ============================ DASHBOARD ============================ */
function renderDashboard() {
    const todayTx = APP.transactions.filter(t => isToday(t.date));
    const todaySales = todayTx.reduce((s, t) => s + t.total, 0);
    const todayItems = todayTx.reduce((s, t) => s + t.details.reduce((a, d) => a + (d.quantity || d.qty || 0), 0), 0);
    const todayProfit = todayTx.reduce((s, t) => {
        return s + t.details.reduce((a, d) => {
            const p = APP.products.find(p => p.id == (d.product_id || d.productId));
            const cost = p ? p.cost * (d.quantity || d.qty || 0) : 0;
            return a + (d.subtotal - cost);
        }, 0);
    }, 0);

    document.getElementById('stat-grid').innerHTML = `
        <div class="card stat-card">
            <div class="stat-label">Total Penjualan Hari Ini</div>
            <div class="stat-value">${rupiah(todaySales)}</div>
            <div class="stat-delta">${todayTx.length} transaksi</div>
        </div>
        <div class="card stat-card">
            <div class="stat-label">Total Transaksi Hari Ini</div>
            <div class="stat-value">${todayTx.length}</div>
            <div class="stat-delta">sejak pukul 00:00</div>
        </div>
        <div class="card stat-card">
            <div class="stat-label">Produk Terjual</div>
            <div class="stat-value">${todayItems}</div>
            <div class="stat-delta">item hari ini</div>
        </div>
        <div class="card stat-card">
            <div class="stat-label">Total Keuntungan</div>
            <div class="stat-value">${rupiah(todayProfit)}</div>
            <div class="stat-delta">estimasi hari ini</div>
        </div>
    `;

    const salesByProduct = {};
    APP.transactions.forEach(t => t.details.forEach(d => {
        const pid = d.product_id || d.productId;
        salesByProduct[pid] = (salesByProduct[pid] || 0) + (d.quantity || d.qty || 0);
    }));
    const ranked = Object.entries(salesByProduct).sort((a, b) => b[1] - a[1]).slice(0, 5);
    document.getElementById('top-products').innerHTML = ranked.map(([pid, qty], i) => {
        const p = APP.products.find(p => p.id == pid);
        if (!p) return '';
        return `<div class="top-product-row"><span><span class="rank">${i + 1}</span>${p.name}</span><span class="muted">${qty} terjual</span></div>`;
    }).join('') || '<div class="muted" style="font-size:13px;">Belum ada data penjualan.</div>';

    document.getElementById('recent-tx').innerHTML = APP.transactions.slice(0, 6).map(t => `
        <tr><td>${t.invoice_number}</td><td>${t.cashier || (t.user ? t.user.name : '-')}</td><td>${t.details.reduce((a, d) => a + (d.quantity || d.qty || 0), 0)}</td><td>${rupiah(t.total)}</td><td class="muted">${fmtDateShort(t.date)}</td></tr>
    `).join('');

    const lowStock = APP.products.filter(p => p.stock <= 8).sort((a, b) => a.stock - b.stock);
    document.getElementById('stock-info').innerHTML = lowStock.length ? lowStock.slice(0, 6).map(p => {
        const s = stockStatus(p.stock);
        return `<div class="stock-mini"><span>${p.image || '&#9749;'} ${p.name}</span><span class="badge ${s.cls}">${p.stock} - ${s.label}</span></div>`;
    }).join('') : '<div class="muted" style="font-size:13px;">Semua stok dalam kondisi aman.</div>';

    renderSalesChart();
}

function renderSalesChart() {
    const days = [];
    const labels = [];
    const now = new Date();
    for (let i = 6; i >= 0; i--) {
        const d = new Date(now); d.setDate(now.getDate() - i);
        days.push(d);
        labels.push(d.toLocaleDateString('id-ID', { weekday: 'short' }));
    }
    const data = days.map(d => {
        return APP.transactions.filter(t => t.date.toDateString() === d.toDateString()).reduce((s, t) => s + t.total, 0);
    });
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;
    if (APP.salesChartInstance) APP.salesChartInstance.destroy();
    APP.salesChartInstance = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ data, backgroundColor: '#09080E', borderRadius: 5, maxBarThickness: 34 }] },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => rupiah(c.raw) } } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => v >= 1000 ? (v / 1000) + 'k' : v, font: { size: 11 } }, grid: { color: '#FE6807' } },
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#09080E' } }
            }
        }
    });
}

/* ============================ KASIR / POS ============================ */
function setupKasir() {
    document.getElementById('pos-search').addEventListener('input', renderPosProducts);
    document.getElementById('clear-cart').addEventListener('click', () => { APP.cart = []; renderCart(); });
    ['input-discount', 'input-tax', 'input-cash'].forEach(id => {
        document.getElementById(id).addEventListener('input', updateCartSums);
    });
    document.getElementById('btn-pay').addEventListener('click', processPayment);
}

function renderPosCats() {
    const el = document.getElementById('pos-cats');
    let html = `<button class="cat-tab ${APP.posActiveCat === 'all' ? 'active' : ''}" data-cat="all">Semua</button>`;
    APP.categories.forEach(c => {
        html += `<button class="cat-tab ${APP.posActiveCat == c.id ? 'active' : ''}" data-cat="${c.id}">${c.name}</button>`;
    });
    el.innerHTML = html;
    el.querySelectorAll('.cat-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            APP.posActiveCat = btn.dataset.cat === 'all' ? 'all' : Number(btn.dataset.cat);
            renderPosCats();
            renderPosProducts();
        });
    });
}

function renderPosProducts() {
    const search = document.getElementById('pos-search').value.toLowerCase();
    let list = APP.products.filter(p => p.is_active);
    if (APP.posActiveCat !== 'all') list = list.filter(p => p.category_id == APP.posActiveCat);
    if (search) list = list.filter(p => p.name.toLowerCase().includes(search));
    document.getElementById('pos-products').innerHTML = list.map(p => {
        const disabled = p.stock <= 0;
        return `<div class="prod-card ${disabled ? 'disabled' : ''}" ${disabled ? '' : `onclick="addToCart(${p.id})"`}>
            <div class="prod-icon">${p.image || '&#9749;'}</div>
            <div class="prod-name">${p.name}</div>
            <div class="prod-cat">${catName(p.category_id)}</div>
            <div class="prod-price">${rupiah(p.price)}</div>
            <div class="prod-stock" style="color:${disabled ? '#B14834' : '#7A6E60'}">${disabled ? 'Stok habis' : 'Stok: ' + p.stock}</div>
        </div>`;
    }).join('') || `<div class="empty-state" style="grid-column:1/-1;"><div class="em-ic">&#128269;</div>Produk tidak ditemukan.</div>`;
}

function addToCart(productId) {
    const p = APP.products.find(p => p.id === productId);
    const existing = APP.cart.find(c => c.productId === productId);
    const inCart = existing ? existing.qty : 0;
    if (inCart >= p.stock) { showToast('Stok tidak mencukupi.'); return; }
    if (existing) existing.qty++;
    else APP.cart.push({ productId, qty: 1 });
    renderCart();
    renderPosProducts();
}

function changeQty(productId, delta) {
    const item = APP.cart.find(c => c.productId === productId);
    if (!item) return;
    const p = APP.products.find(p => p.id === productId);
    const newQty = item.qty + delta;
    if (newQty <= 0) { APP.cart = APP.cart.filter(c => c.productId !== productId); }
    else if (newQty > p.stock) { showToast('Stok tidak mencukupi.'); return; }
    else { item.qty = newQty; }
    renderCart();
}

function renderCart() {
    const wrap = document.getElementById('cart-items');
    if (APP.cart.length === 0) {
        wrap.innerHTML = `<div class="cart-empty">Keranjang masih kosong.<br>Pilih produk untuk memulai transaksi.</div>`;
    } else {
        wrap.innerHTML = APP.cart.map(c => {
            const p = APP.products.find(p => p.id === c.productId);
            return `<div class="cart-item">
                <div>
                    <div class="ci-name">${p.name}</div>
                    <div class="ci-price">${rupiah(p.price)}</div>
                </div>
                <div class="qty-ctrl">
                    <button class="qty-btn" onclick="changeQty(${p.id},-1)">&minus;</button>
                    <span class="qty-num">${c.qty}</span>
                    <button class="qty-btn" onclick="changeQty(${p.id},1)">+</button>
                </div>
            </div>`;
        }).join('');
    }
    updateCartSums();
}

function updateCartSums() {
    const subtotal = APP.cart.reduce((s, c) => {
        const p = APP.products.find(p => p.id === c.productId);
        return s + p.price * c.qty;
    }, 0);
    const discount = Number(document.getElementById('input-discount').value) || 0;
    const taxPct = Number(document.getElementById('input-tax').value) || 0;
    const tax = Math.max(subtotal - discount, 0) * (taxPct / 100);
    const total = Math.max(subtotal - discount + tax, 0);
    document.getElementById('sum-subtotal').textContent = rupiah(subtotal);
    document.getElementById('sum-total').textContent = rupiah(total);

    const cash = Number(document.getElementById('input-cash').value) || 0;
    const change = cash - total;
    const changeEl = document.getElementById('change-amount');
    changeEl.textContent = rupiah(Math.max(change, 0));
    changeEl.classList.toggle('neg', change < 0);

    const payBtn = document.getElementById('btn-pay');
    payBtn.disabled = !(APP.cart.length > 0 && total > 0 && cash >= total);

    return { subtotal, discount, tax, total, cash, change };
}

async function processPayment() {
    const sums = updateCartSums();
    if (APP.cart.length === 0 || sums.cash < sums.total) return;

    const customerName = document.getElementById('input-customer-name').value.trim();

    const details = APP.cart.map(c => {
        const p = APP.products.find(p => p.id === c.productId);
        return { product_id: p.id, name: p.name, price: p.price, quantity: c.qty, subtotal: p.price * c.qty };
    });

    const now = new Date();
    const invoice = 'INV-' + now.toISOString().slice(0, 10).replace(/-/g, '') + '-' + String(APP.transactions.length + 1).padStart(3, '0');

    try {
        const data = await apiFetch('/transactions', {
            method: 'POST',
            body: JSON.stringify({
                invoice_number: invoice,
                customer_name: customerName || null,
                subtotal: sums.subtotal,
                discount: sums.discount,
                tax: sums.tax,
                total: sums.total,
                paid_amount: sums.cash,
                change_amount: sums.change,
                details: details.map(d => ({ product_id: d.product_id, quantity: d.quantity, price: d.price })),
            }),
        });

        APP.transactions.unshift({
            ...data.data || data,
            date: new Date(),
            details,
            invoice_number: invoice,
            customer_name: customerName,
            subtotal: sums.subtotal,
            discount: sums.discount,
            tax: sums.tax,
            total: sums.total,
            paid_amount: sums.cash,
            change_amount: sums.change,
            cashier: APP.user.name,
        });

        // Deduct stock locally
        details.forEach(d => {
            const p = APP.products.find(p => p.id == d.product_id);
            if (p) p.stock -= d.quantity;
        });
    } catch (e) {
        // Fallback: deduct locally even if API fails (demo mode)
        APP.transactions.unshift({
            id: APP.nextTxId++,
            invoice_number: invoice,
            customer_name: customerName,
            cashier: APP.user.name,
            details, subtotal: sums.subtotal, discount: sums.discount, tax: sums.tax,
            total: sums.total, paid_amount: sums.cash, change_amount: sums.change, date: now,
        });
        details.forEach(d => {
            const p = APP.products.find(p => p.id == d.product_id);
            if (p) p.stock -= d.quantity;
        });
    }

    // Show receipt
    document.getElementById('struk-content').innerHTML = `
        <div style="text-align:center;margin-bottom:14px;">
            <div style="font-size:22px;">&#9749; CoffeePOS</div>
            <div class="muted" style="font-size:12px;">${invoice}</div>
            <div class="muted" style="font-size:12px;">${fmtDateShort(now)}</div>
            ${customerName ? `<div style="font-size:13px;margin-top:6px;font-weight:700;">Atas Nama: ${customerName}</div>` : ''}
        </div>
        <div style="border-top:1px dashed var(--line);border-bottom:1px dashed var(--line);padding:10px 0;margin-bottom:10px;">
            ${details.map(d => `<div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px;font-family:'Outfit',sans-serif;font-style:italic;"><span>${d.name} &times;${d.quantity}</span><span>${rupiah(d.subtotal)}</span></div>`).join('')}
        </div>
        <div style="font-family:'Outfit',sans-serif;font-size:13px;font-style:italic;">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Subtotal</span><span>${rupiah(sums.subtotal)}</span></div>
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Diskon</span><span>-${rupiah(sums.discount)}</span></div>
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Pajak</span><span>${rupiah(sums.tax)}</span></div>
            <div style="display:flex;justify-content:space-between;font-weight:800;font-size:15px;margin:8px 0;"><span>Total</span><span>${rupiah(sums.total)}</span></div>
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span>Cash</span><span>${rupiah(sums.cash)}</span></div>
            <div style="display:flex;justify-content:space-between;font-weight:700;color:var(--green);"><span>Kembalian</span><span>${rupiah(sums.change)}</span></div>
        </div>
    `;
    openModal('modal-struk');

    APP.cart = [];
    document.getElementById('input-discount').value = 0;
    document.getElementById('input-tax').value = 0;
    document.getElementById('input-cash').value = '';
    document.getElementById('input-customer-name').value = '';
    renderCart();
    renderPosProducts();
    showToast('Transaksi berhasil disimpan.');
}

/* ============================ PRODUK ============================ */
function setupProduk() {
    document.getElementById('produk-search').addEventListener('input', renderProdukTable);
    document.getElementById('produk-filter-cat').addEventListener('change', renderProdukTable);
    document.getElementById('btn-add-produk').addEventListener('click', () => {
        document.getElementById('produk-modal-title').textContent = 'Tambah Produk';
        document.getElementById('produk-id').value = '';
        document.getElementById('produk-nama').value = '';
        document.getElementById('produk-harga').value = '';
        document.getElementById('produk-modal-cost').value = '';
        document.getElementById('produk-stok').value = '';
        document.getElementById('produk-icon').value = '\u2615';
        fillKategoriSelect('produk-kategori');
        openModal('modal-produk');
    });
}

function renderProdukFilterOptions() {
    const sel = document.getElementById('produk-filter-cat');
    sel.innerHTML = `<option value="all">Semua Kategori</option>` + APP.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
}

function renderProdukTable() {
    const search = document.getElementById('produk-search').value.toLowerCase();
    const filterCat = document.getElementById('produk-filter-cat').value;
    let list = APP.products.filter(p => p.name.toLowerCase().includes(search));
    if (filterCat && filterCat !== 'all') list = list.filter(p => p.category_id == Number(filterCat));

    document.getElementById('produk-table').innerHTML = list.map(p => {
        const s = stockStatus(p.stock);
        return `<tr>
            <td><div class="cell-flex"><span class="prod-thumb">${p.image || '&#9749;'}</span><span style="font-weight:700;">${p.name}</span></div></td>
            <td>${catName(p.category_id)}</td>
            <td>${rupiah(p.price)}</td>
            <td class="muted">${rupiah(p.cost)}</td>
            <td>${p.stock}</td>
            <td><span class="badge ${p.is_active ? 'badge-green' : 'badge-red'}">${p.is_active ? 'Aktif' : 'Nonaktif'}</span></td>
            <td style="white-space:nowrap;">
                <button class="icon-btn" onclick="editProduk(${p.id})">Edit</button>
                <button class="icon-btn" onclick="toggleProdukStatus(${p.id})">${p.is_active ? 'Nonaktifkan' : 'Aktifkan'}</button>
                <button class="icon-btn danger" onclick="deleteProduk(${p.id})">Hapus</button>
            </td>
        </tr>`;
    }).join('') || `<tr><td colspan="7"><div class="empty-state"><div class="em-ic">&#9749;</div>Belum ada produk.</div></td></tr>`;
}

function fillKategoriSelect(selectId) {
    document.getElementById(selectId).innerHTML = APP.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
}

function editProduk(id) {
    const p = APP.products.find(p => p.id === id);
    document.getElementById('produk-modal-title').textContent = 'Edit Produk';
    document.getElementById('produk-id').value = p.id;
    document.getElementById('produk-nama').value = p.name;
    document.getElementById('produk-harga').value = p.price;
    document.getElementById('produk-modal-cost').value = p.cost;
    document.getElementById('produk-stok').value = p.stock;
    document.getElementById('produk-icon').value = p.image || '';
    fillKategoriSelect('produk-kategori');
    document.getElementById('produk-kategori').value = p.category_id;
    openModal('modal-produk');
}

async function saveProduk() {
    const id = document.getElementById('produk-id').value;
    const name = document.getElementById('produk-nama').value.trim();
    const category_id = Number(document.getElementById('produk-kategori').value);
    const price = Number(document.getElementById('produk-harga').value) || 0;
    const cost = Number(document.getElementById('produk-modal-cost').value) || 0;
    const stock = Number(document.getElementById('produk-stok').value) || 0;
    const icon = document.getElementById('produk-icon').value.trim() || '\u2615';
    if (!name) { showToast('Nama produk wajib diisi.'); return; }
    if (!APP.categories.length) { showToast('Buat kategori terlebih dahulu.'); return; }

    try {
        if (id) {
            const data = await apiFetch(`/products/${id}`, {
                method: 'PUT',
                body: JSON.stringify({ name, category_id, price, cost, stock, image: icon }),
            });
            const idx = APP.products.findIndex(p => p.id == id);
            if (idx >= 0) Object.assign(APP.products[idx], data.data || data);
            showToast('Produk berhasil diperbarui.');
        } else {
            const data = await apiFetch('/products', {
                method: 'POST',
                body: JSON.stringify({ name, category_id, price, cost, stock, image: icon }),
            });
            APP.products.push(data.data || data);
            showToast('Produk berhasil ditambahkan.');
        }
    } catch (e) {
        // Fallback: local only
        if (id) {
            const p = APP.products.find(p => p.id == id);
            if (p) Object.assign(p, { name, category_id, price, cost, stock, image: icon });
            showToast('Produk berhasil diperbarui.');
        } else {
            APP.products.push({ id: APP.nextProdId++, name, category_id, price, cost, stock, image: icon, is_active: true });
            showToast('Produk berhasil ditambahkan.');
        }
    }
    closeModal('modal-produk');
    renderAll();
}

async function toggleProdukStatus(id) {
    const p = APP.products.find(p => p.id === id);
    try {
        await apiFetch(`/products/${id}`, { method: 'PUT', body: JSON.stringify({ is_active: !p.is_active }) });
    } catch (e) {}
    p.is_active = !p.is_active;
    renderAll();
    showToast(`Status produk diubah menjadi ${p.is_active ? 'Aktif' : 'Nonaktif'}.`);
}

async function deleteProduk(id) {
    if (!confirm('Hapus produk ini?')) return;
    try { await apiFetch(`/products/${id}`, { method: 'DELETE' }); } catch (e) {}
    APP.products = APP.products.filter(p => p.id !== id);
    renderAll();
    showToast('Produk dihapus.');
}

/* ============================ KATEGORI ============================ */
function setupKategori() {
    document.getElementById('btn-add-kategori').addEventListener('click', () => {
        document.getElementById('kategori-modal-title').textContent = 'Tambah Kategori';
        document.getElementById('kategori-id').value = '';
        document.getElementById('kategori-nama').value = '';
        openModal('modal-kategori');
    });
}

function renderKategoriList() {
    document.getElementById('kategori-list').innerHTML = APP.categories.map(c => {
        const count = APP.products.filter(p => p.category_id == c.id).length;
        return `<div class="catlist-item">
            <div><div class="cn">${c.name}</div><div class="cc">${count} produk</div></div>
            <div>
                <button class="icon-btn" onclick="editKategori(${c.id})">Edit</button>
                <button class="icon-btn danger" onclick="deleteKategori(${c.id})">Hapus</button>
            </div>
        </div>`;
    }).join('') || `<div class="empty-state"><div class="em-ic">&#127991;</div>Belum ada kategori.</div>`;
}

function editKategori(id) {
    const c = APP.categories.find(c => c.id === id);
    document.getElementById('kategori-modal-title').textContent = 'Edit Kategori';
    document.getElementById('kategori-id').value = c.id;
    document.getElementById('kategori-nama').value = c.name;
    openModal('modal-kategori');
}

async function saveKategori() {
    const id = document.getElementById('kategori-id').value;
    const name = document.getElementById('kategori-nama').value.trim();
    if (!name) { showToast('Nama kategori wajib diisi.'); return; }

    try {
        if (id) {
            const data = await apiFetch(`/categories/${id}`, { method: 'PUT', body: JSON.stringify({ name }) });
            const c = APP.categories.find(c => c.id == id);
            if (c) c.name = name;
            showToast('Kategori berhasil diperbarui.');
        } else {
            const data = await apiFetch('/categories', { method: 'POST', body: JSON.stringify({ name }) });
            APP.categories.push(data.data || { id: APP.nextCatId++, name });
            showToast('Kategori berhasil ditambahkan.');
        }
    } catch (e) {
        if (id) {
            const c = APP.categories.find(c => c.id == id);
            if (c) c.name = name;
            showToast('Kategori berhasil diperbarui.');
        } else {
            APP.categories.push({ id: APP.nextCatId++, name });
            showToast('Kategori berhasil ditambahkan.');
        }
    }
    closeModal('modal-kategori');
    renderAll();
}

async function deleteKategori(id) {
    if (APP.products.some(p => p.category_id == id)) { showToast('Tidak bisa menghapus - masih ada produk di kategori ini.'); return; }
    if (!confirm('Hapus kategori ini?')) return;
    try { await apiFetch(`/categories/${id}`, { method: 'DELETE' }); } catch (e) {}
    APP.categories = APP.categories.filter(c => c.id !== id);
    renderAll();
    showToast('Kategori dihapus.');
}

/* ============================ STOK ============================ */
function renderStok() {
    const aman = APP.products.filter(p => p.stock > 8).length;
    const menipis = APP.products.filter(p => p.stock > 0 && p.stock <= 8).length;
    const habis = APP.products.filter(p => p.stock <= 0).length;
    document.getElementById('stok-stat-grid').innerHTML = `
        <div class="card stat-card"><div class="stat-label">&#128994; Stok Aman</div><div class="stat-value">${aman}</div></div>
        <div class="card stat-card"><div class="stat-label">&#128992; Stok Menipis</div><div class="stat-value">${menipis}</div></div>
        <div class="card stat-card"><div class="stat-label">&#128308; Stok Habis</div><div class="stat-value">${habis}</div></div>
    `;
    document.getElementById('stok-table').innerHTML = APP.products.map(p => {
        const s = stockStatus(p.stock);
        return `<tr>
            <td><div class="cell-flex"><span class="prod-thumb">${p.image || '&#9749;'}</span><span style="font-weight:700;">${p.name}</span></div></td>
            <td>${catName(p.category_id)}</td>
            <td>${p.stock}</td>
            <td><span class="badge ${s.cls}">${s.label}</span></td>
            <td><button class="icon-btn" onclick="openStokModal(${p.id})">Sesuaikan</button></td>
        </tr>`;
    }).join('');
}

function openStokModal(id) {
    const p = APP.products.find(p => p.id === id);
    document.getElementById('stok-produk-id').value = id;
    document.getElementById('stok-produk-nama').textContent = `${p.image || '&#9749;'} ${p.name} - stok saat ini: ${p.stock}`;
    document.getElementById('stok-jenis').value = 'tambah';
    document.getElementById('stok-jumlah').value = 1;
    openModal('modal-stok');
}

async function saveStokAdjust() {
    const id = Number(document.getElementById('stok-produk-id').value);
    const jenis = document.getElementById('stok-jenis').value;
    const jumlah = Number(document.getElementById('stok-jumlah').value) || 0;
    const p = APP.products.find(p => p.id === id);
    if (jumlah <= 0) { showToast('Jumlah harus lebih dari 0.'); return; }
    if (jenis === 'tambah') {
        p.stock += jumlah;
    } else {
        if (jumlah > p.stock) { showToast('Jumlah pengurangan melebihi stok tersedia.'); return; }
        p.stock -= jumlah;
    }
    try { await apiFetch(`/products/${id}`, { method: 'PUT', body: JSON.stringify({ stock: p.stock }) }); } catch (e) {}
    closeModal('modal-stok');
    renderAll();
    showToast('Stok berhasil disesuaikan.');
}

/* ============================ TRANSAKSI ============================ */
function renderTransaksi() {
    const search = document.getElementById('tx-search').value.toLowerCase();
    const dateFilter = document.getElementById('tx-date').value;
    let list = APP.transactions.filter(t => (t.invoice_number || '').toLowerCase().includes(search));
    if (dateFilter) {
        list = list.filter(t => t.date.toISOString().slice(0, 10) === dateFilter);
    }
    document.getElementById('tx-table').innerHTML = list.slice(0, 150).map(t => `
        <tr>
            <td style="font-weight:700;">${t.invoice_number}</td>
            <td class="muted">${fmtDateShort(t.date)}</td>
            <td>${t.customer_name || '-'}</td>
            <td>${t.cashier || (t.user ? t.user.name : '-')}</td>
            <td>${t.details.reduce((a, d) => a + (d.quantity || d.qty || 0), 0)}</td>
            <td>${rupiah(t.subtotal)}</td>
            <td class="muted">${rupiah(t.discount)}</td>
            <td class="muted">${rupiah(t.tax)}</td>
            <td style="font-weight:700;">${rupiah(t.total)}</td>
            <td>${rupiah(t.paid_amount || t.paid)}</td>
            <td>${rupiah(t.change_amount || t.change)}</td>
        </tr>
    `).join('') || `<tr><td colspan="11"><div class="empty-state"><div class="em-ic">&#129535;</div>Tidak ada transaksi ditemukan.</div></td></tr>`;
}

/* ============================ LAPORAN ============================ */
function renderLaporan() {
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.onclick = () => {
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            APP.reportPeriod = Number(btn.dataset.period);
            renderLaporan();
        };
    });

    const cutoff = new Date(); cutoff.setDate(cutoff.getDate() - APP.reportPeriod);
    const periodTx = APP.transactions.filter(t => t.date >= cutoff);
    const revenue = periodTx.reduce((s, t) => s + t.total, 0);
    const itemsSold = periodTx.reduce((s, t) => s + t.details.reduce((a, d) => a + (d.quantity || d.qty || 0), 0), 0);
    const profit = periodTx.reduce((s, t) => {
        return s + t.details.reduce((a, d) => {
            const p = APP.products.find(p => p.id == (d.product_id || d.productId));
            const cost = p ? p.cost * (d.quantity || d.qty || 0) : 0;
            return a + (d.subtotal - cost);
        }, 0);
    }, 0);

    document.getElementById('laporan-stats').innerHTML = `
        <div class="card stat-card"><div class="stat-label">Total Transaksi</div><div class="stat-value">${periodTx.length.toLocaleString('id-ID')}</div></div>
        <div class="card stat-card"><div class="stat-label">Produk Terjual</div><div class="stat-value">${itemsSold.toLocaleString('id-ID')}</div></div>
        <div class="card stat-card"><div class="stat-label">Total Pendapatan</div><div class="stat-value">${rupiah(revenue)}</div></div>
        <div class="card stat-card"><div class="stat-label">Total Keuntungan</div><div class="stat-value">${rupiah(profit)}</div></div>
    `;

    const salesByProduct = {};
    periodTx.forEach(t => t.details.forEach(d => {
        const pid = d.product_id || d.productId;
        salesByProduct[pid] = (salesByProduct[pid] || 0) + (d.quantity || d.qty || 0);
    }));
    const ranked = Object.entries(salesByProduct).sort((a, b) => b[1] - a[1]).slice(0, 5);
    document.getElementById('laporan-top-products').innerHTML = ranked.map(([pid, qty], i) => {
        const p = APP.products.find(p => p.id == pid);
        if (!p) return '';
        return `<div class="top-product-row"><span><span class="rank">${i + 1}</span>${p.name}</span><span class="muted">${qty} terjual</span></div>`;
    }).join('') || '<div class="muted" style="font-size:13px;">Belum ada data pada periode ini.</div>';

    const days = [];
    const step = APP.reportPeriod > 30 ? 7 : 1;
    for (let i = APP.reportPeriod - 1; i >= 0; i -= step) {
        const d = new Date(); d.setDate(d.getDate() - i);
        days.push(d);
    }
    const labels = days.map(d => d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const data = days.map(d => {
        const rangeStart = new Date(d);
        const rangeEnd = new Date(d); rangeEnd.setDate(rangeEnd.getDate() + step);
        return APP.transactions.filter(t => t.date >= rangeStart && t.date < rangeEnd).reduce((s, t) => s + t.total, 0);
    });
    const ctx = document.getElementById('reportChart');
    if (APP.reportChartInstance) APP.reportChartInstance.destroy();
    APP.reportChartInstance = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets: [{ data, borderColor: '#09080E', backgroundColor: 'rgba(9,8,14,0.12)', fill: true, tension: .35, pointRadius: 2 }] },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => rupiah(c.raw) } } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => v >= 1000 ? (v / 1000) + 'k' : v, font: { size: 11 } }, grid: { color: '#FE6807' } },
                x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, color: '#09080E' } }
            }
        }
    });
}

/* ============================ RENDER ALL ============================ */
function renderAll() {
    renderDashboard();
    renderPosCats();
    renderPosProducts();
    renderCart();
    renderProdukFilterOptions();
    renderProdukTable();
    renderKategoriList();
    renderStok();
    renderTransaksi();
    renderLaporan();
}

/* ============================ EXPOSE GLOBALS ============================ */
/* Fungsi yang dipanggil via atribut onclick="..." di HTML harus diekspos
   ke window agar tersedia di scope global (karena Vite di-bundle ESM). */
Object.assign(window, {
    rupiah,
    showToast,
    closeModal,
    openModal,
    addToCart,
    changeQty,
    editProduk,
    toggleProdukStatus,
    deleteProduk,
    saveProduk,
    editKategori,
    deleteKategori,
    saveKategori,
    openStokModal,
    saveStokAdjust,
});

