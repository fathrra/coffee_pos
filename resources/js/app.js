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
    users: [],
    ingredients: [],
    suppliers: [],
    recipeData: [],
    invSummary: null,
    invTab: 'ingredients',
    invPeriod: 'today',
    stockInHistory: [],
    stockOutHistory: [],
    adjustHistory: [],
    resepRows: [],
    invReport: null,
    recipeReport: null,
    invReportLoaded: false,
    lowStockLoaded: false,
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
    loadUsers();
    seedHistory();
    loadInventoryContext();

    setupNav();
    setupKasir();
    setupProduk();
    setupKategori();
    setupUsers();
    setupInventory();
    setupResep();
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

async function loadUsers() {
    try {
        const data = await apiFetch('/users');
        APP.users = data.data || data;
    } catch (e) {
        APP.users = [];
    }
}

/* ============================ INVENTORY DATA ============================ */
async function loadIngredients() {
    try {
        const data = await apiFetch('/ingredients?per_page=200');
        APP.ingredients = data.data || (Array.isArray(data) ? data : []);
    } catch (e) {
        APP.ingredients = [];
    }
}

async function loadSuppliers() {
    try {
        const data = await apiFetch('/suppliers?per_page=200');
        APP.suppliers = data.data || (Array.isArray(data) ? data : []);
    } catch (e) {
        APP.suppliers = [];
    }
}

async function loadRecipeData() {
    try {
        const data = await apiFetch('/recipes?per_page=200');
        APP.recipeData = data.data || (Array.isArray(data) ? data : []);
    } catch (e) {
        APP.recipeData = [];
    }
}

async function loadStockInHistory() {
    try {
        const data = await apiFetch('/stock-in?per_page=50');
        APP.stockInHistory = data.data || (Array.isArray(data) ? data : []);
    } catch (e) {
        APP.stockInHistory = [];
    }
}

async function loadStockOutHistory() {
    try {
        const data = await apiFetch('/stock-out?per_page=50');
        APP.stockOutHistory = data.data || (Array.isArray(data) ? data : []);
    } catch (e) {
        APP.stockOutHistory = [];
    }
}

async function loadAdjustHistory() {
    try {
        const data = await apiFetch('/stock-adjustments?per_page=50');
        APP.adjustHistory = data.data || (Array.isArray(data) ? data : []);
    } catch (e) {
        APP.adjustHistory = [];
    }
}

async function loadInventorySummary() {
    try {
        APP.invSummary = await apiFetch('/inventory/summary');
    } catch (e) {
        APP.invSummary = null;
    }
}

async function loadInventoryContext() {
    APP.invReportLoaded = false;
    APP.lowStockLoaded = false;
    await Promise.allSettled([
        loadIngredients(),
        loadSuppliers(),
        loadRecipeData(),
        loadStockInHistory(),
        loadStockOutHistory(),
        loadAdjustHistory(),
        loadInventorySummary(),
    ]);
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

            if (APP.user.role === 'admin' && ['inventory', 'resep', 'dashboard'].includes(page)) {
                loadInventoryContext().finally(() => renderAll());
            } else {
                renderAll();
            }
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

    let data;
    try {
        data = await apiFetch('/transactions', {
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

        const tx = data.data || data;
        APP.transactions.unshift({
            ...tx,
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

        // Refresh stock (menu stock is computed from recipes on the server)
        await Promise.allSettled([loadProducts(), loadInventoryContext()]);
    } catch (e) {
        showToast(e.message || 'Transaksi gagal disimpan. Stok tidak mencukupi?');
        return;
    }

    // Store last transaction ID for PDF receipt
    APP.lastTransactionId = data && data.id ? data.id : null;

    // Show receipt
    const receiptId = (data && data.id) ? data.id : null;
    const baseUrl = window.__base_url || window.location.origin;

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
        ${receiptId ? `<div style="text-align:center;margin-top:14px;"><a href="${baseUrl}/transactions/${receiptId}/receipt" target="_blank" class="btn btn-primary" style="display:inline-block;text-decoration:none;padding:8px 16px;">Unduh Struk PDF</a></div>` : ''}
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
            await apiFetch(`/categories/${id}`, { method: 'PUT', body: JSON.stringify({ name }) });
            const c = APP.categories.find(c => c.id == id);
            if (c) c.name = name;
            showToast('Kategori berhasil diperbarui.');
            closeModal('modal-kategori');
        } else {
            const data = await apiFetch('/categories', { method: 'POST', body: JSON.stringify({ name }) });
            APP.categories.push(data.data || { id: APP.nextCatId++, name });
            showToast('Kategori berhasil ditambahkan.');
            closeModal('modal-kategori');
        }
    } catch (e) {
        showToast(e.message || 'Gagal menyimpan kategori.');
        return;
    }
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
    const deskripsi = document.getElementById('stok-deskripsi') ? document.getElementById('stok-deskripsi').value.trim() : '';
    const p = APP.products.find(p => p.id === id);
    if (jumlah <= 0) { showToast('Jumlah harus lebih dari 0.'); return; }
    if (jenis === 'tambah') {
        p.stock += jumlah;
    } else {
        if (jumlah > p.stock) { showToast('Jumlah pengurangan melebihi stok tersedia.'); return; }
        p.stock -= jumlah;
    }
    try {
        await apiFetch(`/products/${id}`, { method: 'PUT', body: JSON.stringify({ stock: p.stock }) });
    } catch (e) {
        if (jenis === 'tambah') p.stock -= jumlah; else p.stock += jumlah;
        showToast(e.message || 'Gagal menyesuaikan stok.');
        return;
    }
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
            const baseUrl = window.__base_url || window.location.origin;
            const pdfBtn = document.getElementById('btn-export-pdf');
            const csvBtn = document.getElementById('btn-export-csv');
            if (pdfBtn) pdfBtn.href = baseUrl + '/reports/export-pdf?period=' + APP.reportPeriod;
            if (csvBtn) csvBtn.href = baseUrl + '/reports/export-csv?period=' + APP.reportPeriod;
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

/* ============================ USER MANAGEMENT ============================ */
function renderUsers() {
    document.getElementById('users-table').innerHTML = APP.users.map(u => `
        <tr>
            <td style="font-weight:700;">${u.name}</td>
            <td>${u.email}</td>
            <td><span class="badge ${u.role === 'admin' ? 'badge-green' : 'badge-yellow'}">${u.role === 'admin' ? 'Admin' : 'Kasir'}</span></td>
            <td>${fmtDateShort(u.created_at)}</td>
            <td style="white-space:nowrap;">
                <button class="icon-btn" onclick="editUser(${u.id})">Edit</button>
                <button class="icon-btn danger" onclick="deleteUser(${u.id})">Hapus</button>
            </td>
        </tr>
    `).join('') || `<tr><td colspan="5"><div class="empty-state"><div class="em-ic">&#128100;</div>Belum ada user.</div></td></tr>`;
}

function setupUsers() {
    document.getElementById('btn-add-user').addEventListener('click', () => {
        document.getElementById('user-modal-title').textContent = 'Tambah User';
        document.getElementById('user-id').value = '';
        document.getElementById('user-nama').value = '';
        document.getElementById('user-email').value = '';
        document.getElementById('user-password').value = '';
        document.getElementById('user-password-confirm').value = '';
        document.getElementById('user-role').value = 'kasir';
        openModal('modal-user');
    });
}

function editUser(id) {
    const u = APP.users.find(u => u.id === id);
    if (!u) return;
    document.getElementById('user-modal-title').textContent = 'Edit User';
    document.getElementById('user-id').value = u.id;
    document.getElementById('user-nama').value = u.name;
    document.getElementById('user-email').value = u.email;
    document.getElementById('user-password').value = '';
    document.getElementById('user-password-confirm').value = '';
    document.getElementById('user-role').value = u.role;
    openModal('modal-user');
}

async function saveUser() {
    const id = document.getElementById('user-id').value;
    const name = document.getElementById('user-nama').value.trim();
    const email = document.getElementById('user-email').value.trim();
    const password = document.getElementById('user-password').value;
    const password_confirmation = document.getElementById('user-password-confirm').value;
    const role = document.getElementById('user-role').value;

    if (!name) { showToast('Nama wajib diisi.'); return; }
    if (!email) { showToast('Email wajib diisi.'); return; }
    if (!id && !password) { showToast('Password wajib diisi.'); return; }
    if (password && password !== password_confirmation) { showToast('Konfirmasi password tidak cocok.'); return; }

    try {
        const payload = { name, email, role };
        if (password) { payload.password = password; payload.password_confirmation = password_confirmation; }

        if (id) {
            await apiFetch(`/users/${id}`, { method: 'PUT', body: JSON.stringify(payload) });
            const idx = APP.users.findIndex(u => u.id == id);
            if (idx >= 0) APP.users[idx] = { ...APP.users[idx], name, email, role };
            showToast('User berhasil diperbarui.');
            closeModal('modal-user');
        } else {
            const data = await apiFetch('/users', { method: 'POST', body: JSON.stringify(payload) });
            APP.users.push(data.data || { id: Date.now(), name, email, role });
            showToast('User berhasil ditambahkan.');
            closeModal('modal-user');
        }
    } catch (e) {
        showToast(e.message || 'Gagal menyimpan user.');
        return;
    }
    renderUsers();
}

async function deleteUser(id) {
    if (!confirm('Hapus user ini?')) return;
    try { await apiFetch(`/users/${id}`, { method: 'DELETE' }); } catch (e) {}
    APP.users = APP.users.filter(u => u.id !== id);
    renderUsers();
    showToast('User dihapus.');
}

/* ============================ INVENTORY ============================ */
function fmtQty(n) {
    const v = Number(n) || 0;
    if (Number.isInteger(v)) return v.toLocaleString('id-ID');
    return v.toLocaleString('id-ID', { maximumFractionDigits: 2 });
}
function ingStatus(ing) {
    const stock = Number(ing.current_stock) || 0;
    if (stock <= 0) return { label: 'Stok Habis', cls: 'badge-red' };
    if (stock <= (Number(ing.minimum_stock) || 0)) return { label: 'Stok Menipis', cls: 'badge-yellow' };
    return { label: 'Stok Aman', cls: 'badge-green' };
}
function setInvTab(tab) {
    APP.invTab = tab;
    document.querySelectorAll('#inv-tabs .cat-tab').forEach(b => b.classList.toggle('active', b.dataset.invtab === tab));
    document.querySelectorAll('#page-inventory .subview').forEach(v => v.classList.toggle('active', v.id === 'inv-view-' + tab));
}
function renderInventoryStats() {
    const s = APP.invSummary;
    const grid = document.getElementById('inv-stat-grid');
    if (grid) {
        grid.innerHTML = s ? `
            <div class="card stat-card"><div class="stat-label">Total Bahan</div><div class="stat-value">${s.total_ingredients}</div><div class="stat-delta">${s.active_ingredients} aktif</div></div>
            <div class="card stat-card"><div class="stat-label">Stok Menipis</div><div class="stat-value">${s.low_stock}</div><div class="stat-delta">di bawah minimum</div></div>
            <div class="card stat-card"><div class="stat-label">Out of Stock</div><div class="stat-value">${s.out_of_stock}</div><div class="stat-delta">stok habis</div></div>
            <div class="card stat-card"><div class="stat-label">Total Nilai Inventory</div><div class="stat-value" style="font-size:19px;">${rupiah(s.total_value)}</div><div class="stat-delta">${s.products_with_recipe} menu punya resep</div></div>
        ` : '<div class="card stat-card"><div class="stat-label">Total Bahan</div><div class="stat-value">-</div></div>';
    }
    const dash = document.getElementById('dash-inv-stats');
    if (dash) {
        dash.innerHTML = s ? `
            <div class="card stat-card"><div class="stat-label">Total Bahan</div><div class="stat-value">${s.total_ingredients}</div><div class="stat-delta">bahan aktif</div></div>
            <div class="card stat-card"><div class="stat-label">Stok Menipis</div><div class="stat-value">${s.low_stock}</div><div class="stat-delta">di bawah minimum</div></div>
            <div class="card stat-card"><div class="stat-label">Out of Stock</div><div class="stat-value">${s.out_of_stock}</div><div class="stat-delta">stok habis</div></div>
            <div class="card stat-card"><div class="stat-label">Nilai Inventory</div><div class="stat-value" style="font-size:19px;">${rupiah(s.total_value)}</div><div class="stat-delta">estimasi</div></div>
        ` : '';
    }
}

function renderBahanTable() {
    const search = (document.getElementById('bahan-search')?.value || '').toLowerCase();
    const status = document.getElementById('bahan-filter-status')?.value || 'all';
    let list = APP.ingredients.filter(i => i.name.toLowerCase().includes(search));
    if (status === 'inactive') list = list.filter(i => !i.is_active);
    else if (status === 'good') list = list.filter(i => i.is_active && (Number(i.current_stock) > (Number(i.minimum_stock))));
    else if (status === 'low') list = list.filter(i => i.is_active && (Number(i.current_stock) > 0 && Number(i.current_stock) <= Number(i.minimum_stock)));
    else if (status === 'out') list = list.filter(i => Number(i.current_stock) <= 0);
    else list = list.filter(i => i.is_active);

    const wrap = document.getElementById('bahan-table');
    if (!wrap) return;
    wrap.innerHTML = list.map(i => {
        const s = ingStatus(i);
        return `<tr>
            <td><div class="cell-flex"><span class="prod-thumb">&#129519;</span><span style="font-weight:700;">${i.name}</span></div></td>
            <td>${i.unit}</td>
            <td>${fmtQty(i.current_stock)}</td>
            <td class="muted">${fmtQty(i.minimum_stock)}</td>
            <td>${rupiah(i.cost_per_unit)}</td>
            <td class="muted">${rupiah((Number(i.current_stock) || 0) * (Number(i.cost_per_unit) || 0))}</td>
            <td><span class="badge ${s.cls}">${s.label}</span></td>
            <td class="muted">${i.recipe_ingredients_count || 0} resep</td>
            <td style="white-space:nowrap;">
                <button class="icon-btn" onclick="editBahan(${i.id})">Edit</button>
                <button class="icon-btn" onclick="toggleBahan(${i.id})">${i.is_active ? 'Nonaktifkan' : 'Aktifkan'}</button>
                <button class="icon-btn danger" onclick="deleteBahan(${i.id})">Hapus</button>
            </td>
        </tr>`;
    }).join('') || `<tr><td colspan="9"><div class="empty-state"><div class="em-ic">&#129519;</div>Belum ada bahan baku.</div></td></tr>`;
}

function renderSuppliersTable() {
    const search = (document.getElementById('supplier-search')?.value || '').toLowerCase();
    const list = APP.suppliers.filter(s =>
        s.name.toLowerCase().includes(search) ||
        (s.email || '').toLowerCase().includes(search) ||
        (s.phone || '').toLowerCase().includes(search)
    );
    const wrap = document.getElementById('supplier-table');
    if (!wrap) return;
    wrap.innerHTML = list.map(s => `
        <tr>
            <td style="font-weight:700;">${s.name}</td>
            <td>${s.phone || '-'}</td>
            <td class="muted">${s.email || '-'}</td>
            <td class="muted">${s.address || '-'}</td>
            <td class="muted">${s.notes || '-'}</td>
            <td><span class="badge ${s.is_active ? 'badge-green' : 'badge-red'}">${s.is_active ? 'Aktif' : 'Nonaktif'}</span></td>
            <td style="white-space:nowrap;">
                <button class="icon-btn" onclick="editSupplier(${s.id})">Edit</button>
                <button class="icon-btn danger" onclick="deleteSupplier(${s.id})">Hapus</button>
            </td>
        </tr>
    `).join('') || `<tr><td colspan="7"><div class="empty-state"><div class="em-ic">&#128230;</div>Belum ada supplier.</div></td></tr>`;
}

function fillIngredientSelect(el, selectedId) {
    el.innerHTML = APP.ingredients.filter(i => i.is_active).map(i =>
        `<option value="${i.id}">${i.name} (${fmtQty(i.current_stock)} ${i.unit})</option>`
    ).join('');
    if (selectedId) el.value = selectedId;
}
function fillSupplierSelect(el, selectedId) {
    el.innerHTML = `<option value="">— Pilih Supplier —</option>` + APP.suppliers.filter(s => s.is_active).map(s =>
        `<option value="${s.id}">${s.name}</option>`
    ).join('');
    if (selectedId) el.value = selectedId;
}

function renderStockIn() {
    fillSupplierSelect(document.getElementById('stockin-supplier'));
    fillIngredientSelect(document.getElementById('stockin-ingredient'));
    renderStockInHistory();
}
function renderStockInHistory() {
    const wrap = document.getElementById('stockin-table');
    if (!wrap) return;
    wrap.innerHTML = APP.stockInHistory.map(m => `
        <tr>
            <td class="muted">${fmtDateShort(m.created_at)}</td>
            <td>${m.ingredient ? m.ingredient.name : '-'}</td>
            <td>+${fmtQty(m.quantity)} ${m.ingredient?.unit || ''}</td>
            <td>${fmtQty(m.after_stock)}</td>
            <td class="muted">${m.user ? m.user.name : '-'}</td>
        </tr>
    `).join('') || `<tr><td colspan="5"><div class="empty-state"><div class="em-ic">&#128230;</div>Belum ada stok masuk.</div></td></tr>`;
}

function renderStockOut() {
    fillIngredientSelect(document.getElementById('stockout-ingredient'));
    updateStockOutHint();
    renderStockOutHistory();
}
function updateStockOutHint() {
    const id = document.getElementById('stockout-ingredient').value;
    const ing = APP.ingredients.find(i => i.id == id);
    document.getElementById('stockout-current').textContent = ing
        ? `Stok saat ini: ${fmtQty(ing.current_stock)} ${ing.unit}`
        : '';
}
function renderStockOutHistory() {
    const wrap = document.getElementById('stockout-table');
    if (!wrap) return;
    wrap.innerHTML = APP.stockOutHistory.map(m => `
        <tr>
            <td class="muted">${fmtDateShort(m.created_at)}</td>
            <td>${m.ingredient ? m.ingredient.name : '-'}</td>
            <td>-${fmtQty(m.quantity)} ${m.ingredient?.unit || ''}</td>
            <td class="muted">${m.reason || '-'}</td>
            <td class="muted">${m.user ? m.user.name : '-'}</td>
        </tr>
    `).join('') || `<tr><td colspan="5"><div class="empty-state"><div class="em-ic">&#128230;</div>Belum ada stok keluar.</div></td></tr>`;
}

function renderAdjust() {
    fillIngredientSelect(document.getElementById('adjust-ingredient'));
    updateAdjustStock();
    renderAdjustHistory();
}
function updateAdjustStock() {
    const id = document.getElementById('adjust-ingredient')?.value;
    const ing = APP.ingredients.find(i => i.id == id);
    if (!ing) return;
    document.getElementById('adjust-system-stock').value = ing.current_stock;
    document.getElementById('adjust-actual-stock').value = ing.current_stock;
    updateAdjustDiff();
}
function updateAdjustDiff() {
    const system = Number(document.getElementById('adjust-system-stock').value) || 0;
    const actual = Number(document.getElementById('adjust-actual-stock').value) || 0;
    const diff = actual - system;
    document.getElementById('adjust-diff').value = (diff > 0 ? '+' : '') + fmtQty(diff);
}
function renderAdjustHistory() {
    const wrap = document.getElementById('adjust-table');
    if (!wrap) return;
    wrap.innerHTML = APP.adjustHistory.map(m => {
        const diff = Number(m.quantity) || 0;
        return `<tr>
            <td class="muted">${fmtDateShort(m.created_at)}</td>
            <td>${m.ingredient ? m.ingredient.name : '-'}</td>
            <td>${diff > 0 ? '+' : ''}${fmtQty(diff)}</td>
            <td>${fmtQty(m.before_stock)}</td>
            <td>${fmtQty(m.after_stock)}</td>
            <td class="muted">${m.reason || '-'}</td>
        </tr>`;
    }).join('') || `<tr><td colspan="6"><div class="empty-state"><div class="em-ic">&#128230;</div>Belum ada penyesuaian stok.</div></td></tr>`;
}

function renderLowStock() {
    if (APP.lowStockLoaded) return;
    loadLowStockData();
}
async function loadLowStockData() {
    try {
        const data = await apiFetch('/inventory/low-stock');
        const wrap = document.getElementById('lowstock-table');
        if (!wrap) return;
        wrap.innerHTML = data.map(i => {
            const s = ingStatus(i);
            return `<tr>
                <td style="font-weight:700;">${i.name}</td>
                <td>${i.unit}</td>
                <td>${fmtQty(i.current_stock)}</td>
                <td>${fmtQty(i.minimum_stock)}</td>
                <td><span class="badge ${s.cls}">${s.label}</span></td>
            </tr>`;
        }).join('') || `<tr><td colspan="5"><div class="empty-state"><div class="em-ic">&#128994;</div>Semua stok dalam kondisi aman.</div></td></tr>`;
        APP.lowStockLoaded = true;
    } catch (e) {}
}

function renderInventoryReport() {
    if (APP.invReportLoaded) return;
    const customWrap = document.getElementById('inv-report-custom');
    customWrap.style.display = APP.invPeriod === 'custom' ? 'flex' : 'none';

    const range = invReportRange();
    Promise.all([
        apiFetch(`/reports/inventory?from=${range.from}&to=${range.to}`).then(data => {
            APP.invReport = data;
            document.getElementById('inv-report-stats').innerHTML = `
                <div class="card stat-card"><div class="stat-label">Total Stok Masuk</div><div class="stat-value">${fmtQty(data.totals.stock_in)}</div></div>
                <div class="card stat-card"><div class="stat-label">Total Stok Keluar</div><div class="stat-value">${fmtQty(data.totals.stock_out)}</div></div>
                <div class="card stat-card"><div class="stat-label">Total Terpakai (Jual)</div><div class="stat-value">${fmtQty(data.totals.usage)}</div></div>
                <div class="card stat-card"><div class="stat-label">Total Penyesuaian</div><div class="stat-value">${fmtQty(data.totals.adjustment)}</div></div>
            `;
            document.getElementById('inv-report-table').innerHTML = data.rows.map(r => `
                <tr>
                    <td style="font-weight:700;">${r.name}</td>
                    <td>+${fmtQty(r.stock_in)}</td>
                    <td>${r.stock_out ? '-' + fmtQty(r.stock_out) : '0'}</td>
                    <td>${r.usage ? '-' + fmtQty(r.usage) : '0'}</td>
                    <td>${(Number(r.adjustment) > 0 ? '+' : '') + fmtQty(r.adjustment)}</td>
                    <td>${fmtQty(r.current_stock)} ${r.unit}</td>
                    <td class="muted">${rupiah(r.total_value)}</td>
                </tr>
            `).join('') || `<tr><td colspan="7"><div class="empty-state">Tidak ada data.</div></td></tr>`;
        }).catch(() => {
            document.getElementById('inv-report-table').innerHTML = `<tr><td colspan="7"><div class="empty-state">Gagal memuat laporan.</div></td></tr>`;
        }),
        apiFetch(`/reports/inventory/recipes`).then(data => {
            APP.recipeReport = data;
            document.getElementById('recipe-report-table').innerHTML = data.rows.map(r => `
                <tr>
                    <td style="font-weight:700;">${r.name}</td>
                    <td>${rupiah(r.recipe_cost)}</td>
                    <td>${rupiah(r.price)}</td>
                    <td style="color:var(--green);font-weight:700;">${rupiah(r.profit)}</td>
                    <td>${Number(r.margin).toFixed(2)}%</td>
                    <td>${fmtQty(r.stock)}</td>
                </tr>
            `).join('') || `<tr><td colspan="6"><div class="empty-state">Belum ada resep.</div></td></tr>`;
        }).catch(() => {}),
    ]).finally(() => { APP.invReportLoaded = true; });
}
function invReportRange() {
    const now = new Date();
    const iso = d => d.toISOString().slice(0, 10);
    let from, to;
    if (APP.invPeriod === 'today') { from = to = iso(now); }
    else if (APP.invPeriod === '7') { from = iso(new Date(now.getTime() - 6 * 86400000)); to = iso(now); }
    else if (APP.invPeriod === '30') { from = iso(new Date(now.getTime() - 29 * 86400000)); to = iso(now); }
    else {
        from = document.getElementById('inv-report-from')?.value || iso(now);
        to = document.getElementById('inv-report-to')?.value || iso(now);
    }
    return { from, to };
}

async function saveStockIn() {
    const payload = {
        supplier_id: document.getElementById('stockin-supplier').value || null,
        ingredient_id: document.getElementById('stockin-ingredient').value,
        quantity: Number(document.getElementById('stockin-qty').value) || 0,
        unit_cost: document.getElementById('stockin-cost').value || null,
        date: document.getElementById('stockin-date').value || null,
        notes: document.getElementById('stockin-notes').value.trim() || null,
    };
    if (!payload.ingredient_id) { showToast('Pilih bahan terlebih dahulu.'); return; }
    if (payload.quantity <= 0) { showToast('Jumlah harus lebih dari 0.'); return; }
    try {
        await apiFetch('/stock-in', { method: 'POST', body: JSON.stringify(payload) });
        showToast('Stok masuk berhasil disimpan.');
        document.getElementById('stockin-qty').value = '';
        document.getElementById('stockin-cost').value = '';
        document.getElementById('stockin-notes').value = '';
        await loadInventoryContext();
        renderAll();
    } catch (e) {
        showToast(e.message || 'Gagal menyimpan stok masuk.');
    }
}

async function saveStockOut() {
    const payload = {
        ingredient_id: document.getElementById('stockout-ingredient').value,
        quantity: Number(document.getElementById('stockout-qty').value) || 0,
        reason: document.getElementById('stockout-reason').value,
        notes: document.getElementById('stockout-notes').value.trim(),
    };
    if (!payload.ingredient_id) { showToast('Pilih bahan terlebih dahulu.'); return; }
    if (payload.quantity <= 0) { showToast('Jumlah harus lebih dari 0.'); return; }
    try {
        await apiFetch('/stock-out', { method: 'POST', body: JSON.stringify(payload) });
        showToast('Stok keluar berhasil disimpan.');
        document.getElementById('stockout-qty').value = '';
        document.getElementById('stockout-notes').value = '';
        await loadInventoryContext();
        renderAll();
    } catch (e) {
        showToast(e.message || 'Gagal menyimpan stok keluar.');
    }
}

async function saveAdjust() {
    const payload = {
        ingredient_id: document.getElementById('adjust-ingredient').value,
        actual_stock: Number(document.getElementById('adjust-actual-stock').value) || 0,
        reason: document.getElementById('adjust-reason').value.trim(),
    };
    if (!payload.ingredient_id) { showToast('Pilih bahan terlebih dahulu.'); return; }
    try {
        await apiFetch('/stock-adjustments', { method: 'POST', body: JSON.stringify(payload) });
        showToast('Penyesuaian stok berhasil disimpan.');
        document.getElementById('adjust-reason').value = '';
        await loadInventoryContext();
        renderAll();
    } catch (e) {
        showToast(e.message || 'Gagal menyimpan penyesuaian.');
    }
}

function setupInventory() {
    document.querySelectorAll('#inv-tabs .cat-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#inv-tabs .cat-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            setInvTab(btn.dataset.invtab);
            if (btn.dataset.invtab === 'report') renderInventoryReport();
            if (btn.dataset.invtab === 'low') renderLowStock();
        });
    });

    const listed = document.getElementById('bahan-search');
    if (listed) listed.addEventListener('input', renderBahanTable);
    const bFilter = document.getElementById('bahan-filter-status');
    if (bFilter) bFilter.addEventListener('change', renderBahanTable);
    const supSearch = document.getElementById('supplier-search');
    if (supSearch) supSearch.addEventListener('input', renderSuppliersTable);

    document.getElementById('btn-add-bahan')?.addEventListener('click', () => openBahanModal());
    document.getElementById('btn-add-supplier')?.addEventListener('click', () => openSupplierModal());
    document.getElementById('btn-stockin-save')?.addEventListener('click', saveStockIn);
    document.getElementById('btn-stockout-save')?.addEventListener('click', saveStockOut);
    document.getElementById('btn-adjust-save')?.addEventListener('click', saveAdjust);

    document.getElementById('stockout-ingredient')?.addEventListener('change', updateStockOutHint);
    document.getElementById('adjust-ingredient')?.addEventListener('change', updateAdjustStock);
    document.getElementById('adjust-actual-stock')?.addEventListener('input', updateAdjustDiff);

    document.querySelectorAll('[data-invperiod]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('[data-invperiod]').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            APP.invPeriod = btn.dataset.invperiod;
            APP.invReportLoaded = false;
            renderInventoryReport();
        });
    });
    const todayIso = new Date().toISOString().slice(0, 10);
    const fromEl = document.getElementById('inv-report-from');
    const toEl = document.getElementById('inv-report-to');
    if (fromEl) {
        if (!fromEl.value) fromEl.value = todayIso;
        fromEl.addEventListener('change', () => { if (APP.invPeriod === 'custom') { APP.invReportLoaded = false; renderInventoryReport(); } });
    }
    if (toEl) {
        if (!toEl.value) toEl.value = todayIso;
        toEl.addEventListener('change', () => { if (APP.invPeriod === 'custom') { APP.invReportLoaded = false; renderInventoryReport(); } });
    }
}

/* ============================ BAHAN (Ingredients) ============================ */
function openBahanModal(id) {
    const existing = id ? APP.ingredients.find(i => i.id === id) : null;
    document.getElementById('bahan-modal-title').textContent = existing ? 'Edit Bahan' : 'Tambah Bahan';
    document.getElementById('bahan-id').value = existing ? existing.id : '';
    document.getElementById('bahan-nama').value = existing ? existing.name : '';
    document.getElementById('bahan-unit').value = existing ? existing.unit : 'gram';
    document.getElementById('bahan-stok').value = existing ? existing.current_stock : 0;
    document.getElementById('bahan-minimum').value = existing ? existing.minimum_stock : 0;
    document.getElementById('bahan-harga').value = existing ? existing.cost_per_unit : 0;
    document.getElementById('bahan-deskripsi').value = existing ? (existing.description || '') : '';
    openModal('modal-bahan');
}
function editBahan(id) { openBahanModal(id); }

async function toggleBahan(id) {
    const i = APP.ingredients.find(i => i.id === id);
    if (!i) return;
    try {
        await apiFetch(`/ingredients/${id}`, { method: 'PUT', body: JSON.stringify({ is_active: !i.is_active }) });
    } catch (e) {}
    await loadInventoryContext();
    renderAll();
    showToast(`Bahan ${i.is_active ? 'dinonaktifkan' : 'diaktifkan'}.`);
}

async function deleteBahan(id) {
    if (!confirm('Hapus bahan ini?')) return;
    try {
        await apiFetch(`/ingredients/${id}`, { method: 'DELETE' });
        showToast('Bahan berhasil dihapus.');
    } catch (e) {
        showToast(e.message || 'Gagal menghapus bahan.');
    }
    await loadInventoryContext();
    renderAll();
}

async function saveBahan() {
    const id = document.getElementById('bahan-id').value;
    const name = document.getElementById('bahan-nama').value.trim();
    const unit = document.getElementById('bahan-unit').value;
    if (!name) { showToast('Nama bahan wajib diisi.'); return; }
    const payload = {
        name,
        unit,
        current_stock: Number(document.getElementById('bahan-stok').value) || 0,
        minimum_stock: Number(document.getElementById('bahan-minimum').value) || 0,
        cost_per_unit: Number(document.getElementById('bahan-harga').value) || 0,
        description: document.getElementById('bahan-deskripsi').value.trim(),
    };
    try {
        if (id) {
            await apiFetch(`/ingredients/${id}`, { method: 'PUT', body: JSON.stringify(payload) });
            showToast('Bahan berhasil diperbarui.');
        } else {
            await apiFetch('/ingredients', { method: 'POST', body: JSON.stringify(payload) });
            showToast('Bahan berhasil ditambahkan.');
        }
    } catch (e) {
        showToast(e.message || 'Gagal menyimpan bahan.');
        return;
    }
    closeModal('modal-bahan');
    await loadInventoryContext();
    renderAll();
}

/* ============================ SUPPLIER ============================ */
function openSupplierModal(id) {
    const existing = id ? APP.suppliers.find(s => s.id === id) : null;
    document.getElementById('supplier-modal-title').textContent = existing ? 'Edit Supplier' : 'Tambah Supplier';
    document.getElementById('supplier-id').value = existing ? existing.id : '';
    document.getElementById('supplier-nama').value = existing ? existing.name : '';
    document.getElementById('supplier-telepon').value = existing ? (existing.phone || '') : '';
    document.getElementById('supplier-email').value = existing ? (existing.email || '') : '';
    document.getElementById('supplier-alamat').value = existing ? (existing.address || '') : '';
    document.getElementById('supplier-catatan').value = existing ? (existing.notes || '') : '';
    document.getElementById('supplier-status').value = existing ? (existing.is_active ? '1' : '0') : '1';
    openModal('modal-supplier');
}
function editSupplier(id) { openSupplierModal(id); }

async function deleteSupplier(id) {
    if (!confirm('Hapus supplier ini?')) return;
    try {
        await apiFetch(`/suppliers/${id}`, { method: 'DELETE' });
        showToast('Supplier berhasil dihapus.');
    } catch (e) {
        showToast(e.message || 'Gagal menghapus supplier.');
    }
    await loadInventoryContext();
    renderAll();
}

async function saveSupplier() {
    const id = document.getElementById('supplier-id').value;
    const name = document.getElementById('supplier-nama').value.trim();
    if (!name) { showToast('Nama supplier wajib diisi.'); return; }
    const payload = {
        name,
        phone: document.getElementById('supplier-telepon').value.trim(),
        email: document.getElementById('supplier-email').value.trim(),
        address: document.getElementById('supplier-alamat').value.trim(),
        notes: document.getElementById('supplier-catatan').value.trim(),
        is_active: document.getElementById('supplier-status').value === '1',
    };
    try {
        if (id) {
            await apiFetch(`/suppliers/${id}`, { method: 'PUT', body: JSON.stringify(payload) });
            showToast('Supplier berhasil diperbarui.');
        } else {
            await apiFetch('/suppliers', { method: 'POST', body: JSON.stringify(payload) });
            showToast('Supplier berhasil ditambahkan.');
        }
    } catch (e) {
        showToast(e.message || 'Gagal menyimpan supplier.');
        return;
    }
    closeModal('modal-supplier');
    await loadInventoryContext();
    renderAll();
}

/* ============================ RESEP ============================ */
function setupResep() {
    document.getElementById('resep-search')?.addEventListener('input', renderResepTable);
    document.getElementById('resep-filter')?.addEventListener('change', renderResepTable);
    document.getElementById('btn-add-resep')?.addEventListener('click', () => openResep(null));
    document.getElementById('btn-resep-add-row')?.addEventListener('click', addResepRow);
}

function renderResepTable() {
    const search = (document.getElementById('resep-search')?.value || '').toLowerCase();
    const filter = document.getElementById('resep-filter')?.value || 'all';
    let list = APP.recipeData.filter(p => p.name.toLowerCase().includes(search));
    if (filter === 'has') list = list.filter(p => p.has_recipe);
    if (filter === 'none') list = list.filter(p => !p.has_recipe);

    const wrap = document.getElementById('resep-table');
    if (!wrap) return;
    wrap.innerHTML = list.map(p => {
        const s = stockStatus(p.menu_stock);
        const margin = Number(p.margin) || 0;
        return `<tr>
            <td><div class="cell-flex"><span class="prod-thumb">${p.image || '&#9749;'}</span><span style="font-weight:700;">${p.name}</span></div></td>
            <td class="muted">${catName(p.category_id)}</td>
            <td><span class="badge ${p.has_recipe ? 'badge-green' : 'badge-red'}">${p.has_recipe ? 'Ada' : 'Belum'}</span></td>
            <td>${p.ingredient_count}</td>
            <td>${rupiah(p.recipe_cost)}</td>
            <td>${rupiah(p.price)}</td>
            <td style="color:var(--green);font-weight:700;">${rupiah(p.profit)}</td>
            <td>${margin.toFixed(2)}%</td>
            <td>${fmtQty(p.menu_stock)}</td>
            <td><span class="badge ${s.cls}">${s.label}</span></td>
            <td style="white-space:nowrap;">
                <button class="icon-btn" onclick="openResep(${p.id})">${p.has_recipe ? 'Atur Resep' : 'Buat Resep'}</button>
                ${p.has_recipe ? `<button class="icon-btn danger" onclick="deleteResep(${p.id})">Hapus</button>` : ''}
            </td>
        </tr>`;
    }).join('') || `<tr><td colspan="11"><div class="empty-state"><div class="em-ic">&#127860;</div>Belum ada menu.</div></td></tr>`;
}

function activeIngredientOptions(selectedId) {
    return APP.ingredients.filter(i => i.is_active).map(i =>
        `<option value="${i.id}" ${i.id == selectedId ? 'selected' : ''}>${i.name}</option>`
    ).join('');
}

function openResep(productId) {
    const product = productId ? APP.recipeData.find(p => p.id === productId) : null;
    const sel = document.getElementById('resep-product-select');
    document.getElementById('resep-modal-title').textContent = product
        ? (product.has_recipe ? 'Atur Resep' : 'Buat Resep')
        : 'Tambah Resep';
    document.getElementById('resep-notes').value = product ? (product.recipe_notes || '') : '';
    document.getElementById('resep-product-id').value = product ? product.id : '';

    sel.innerHTML = `<option value="">— Pilih Menu —</option>` + APP.products.map(p => `<option value="${p.id}">${p.name}</option>`);
    sel.disabled = !!productId;
    if (product) {
        sel.value = product.id;
        document.getElementById('resep-harga-jual').value = product.price;
        APP.resepRows = (product.recipe_ingredients || []).map(ri => ({
            ingredient_id: ri.ingredient_id,
            quantity: ri.quantity,
            unit: ri.unit || (APP.ingredients.find(i => i.id == ri.ingredient_id)?.unit || ''),
        }));
    } else {
        document.getElementById('resep-harga-jual').value = '';
        APP.resepRows = [{ ingredient_id: APP.ingredients.find(i => i.is_active)?.id || '', quantity: '', unit: '' }];
    }
    sel.onchange = () => {
        const p = APP.products.find(pp => pp.id == sel.value);
        document.getElementById('resep-product-id').value = sel.value;
        document.getElementById('resep-harga-jual').value = p ? p.price : '';
        updateResepSummary();
    };
    renderResepRows();
    updateResepSummary();
    openModal('modal-resep');
}

function renderResepRows() {
    const wrap = document.getElementById('resep-ingredient-rows');
    if (!APP.resepRows.length) {
        APP.resepRows = [{ ingredient_id: APP.ingredients.find(i => i.is_active)?.id || '', quantity: '', unit: '' }];
    }
    wrap.innerHTML = APP.resepRows.map((row, idx) => `
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
            <select class="input" style="flex:2;padding:7px 10px;" data-row="${idx}" data-field="ingredient_id">${activeIngredientOptions(row.ingredient_id)}</select>
            <input class="input" type="number" min="0" step="any" style="flex:1;padding:7px 10px;" value="${row.quantity}" data-row="${idx}" data-field="quantity" placeholder="Jumlah">
            <input class="input" style="flex:1;padding:7px 10px;" value="${row.unit || ''}" data-row="${idx}" data-field="unit" placeholder="unit">
            <button class="icon-btn danger" onclick="removeResepRow(${idx})" style="margin:0;">Hapus</button>
        </div>
    `).join('');
    wrap.querySelectorAll('input,select').forEach(el => {
        el.addEventListener('change', () => {
            const row = Number(el.dataset.row);
            const field = el.dataset.field;
            APP.resepRows[row][field] = el.value;
            if (field === 'ingredient_id') {
                const ing = APP.ingredients.find(i => i.id == el.value);
                if (ing && !APP.resepRows[row].unit) APP.resepRows[row].unit = ing.unit;
                renderResepRows();
            }
            updateResepSummary();
        });
        el.addEventListener('input', () => {
            const row = Number(el.dataset.row);
            const field = el.dataset.field;
            APP.resepRows[row][field] = el.value;
            updateResepSummary();
        });
    });
}

function addResepRow() {
    APP.resepRows.push({ ingredient_id: '', quantity: '', unit: '' });
    renderResepRows();
    updateResepSummary();
}
function removeResepRow(idx) {
    APP.resepRows.splice(idx, 1);
    renderResepRows();
    updateResepSummary();
}

function updateResepSummary() {
    const price = Number(document.getElementById('resep-harga-jual').value) || 0;
    let cost = 0;
    APP.resepRows.forEach(row => {
        const ing = APP.ingredients.find(i => i.id == row.ingredient_id);
        if (ing) cost += (Number(row.quantity) || 0) * (Number(ing.cost_per_unit) || 0);
    });
    document.getElementById('resep-sum-cost').textContent = rupiah(cost);
    document.getElementById('resep-sum-price').textContent = rupiah(price);
    document.getElementById('resep-sum-profit').textContent = rupiah(price - cost);
    document.getElementById('resep-sum-margin').textContent = price > 0 ? ((price - cost) / price * 100).toFixed(2) + '%' : '0%';
}

async function saveResep() {
    const productId = document.getElementById('resep-product-id').value;
    const selectedProductId = document.getElementById('resep-product-select').value;
    const ingredients = APP.resepRows
        .filter(r => r.ingredient_id && Number(r.quantity) > 0)
        .map(r => ({ ingredient_id: Number(r.ingredient_id), quantity: Number(r.quantity), unit: r.unit || null }));
    if (!ingredients.length) { showToast('Minimal satu bahan dengan jumlah > 0.'); return; }
    const notes = document.getElementById('resep-notes').value.trim();
    try {
        await apiFetch('/recipes', {
            method: 'POST',
            body: JSON.stringify({ product_id: Number(productId) || Number(selectedProductId), notes: notes || null, ingredients }),
        });
        showToast('Resep berhasil disimpan.');
        closeModal('modal-resep');
        await loadInventoryContext();
        renderAll();
    } catch (e) {
        showToast(e.message || 'Gagal menyimpan resep.');
    }
}

async function deleteResep(productId) {
    const p = APP.recipeData.find(p => p.id === productId);
    if (!p || !p.recipe_id) return;
    if (!confirm('Hapus resep ' + p.name + '?')) return;
    try {
        await apiFetch(`/recipes/${p.recipe_id}`, { method: 'DELETE' });
        showToast('Resep berhasil dihapus.');
    } catch (e) {
        showToast(e.message || 'Gagal menghapus resep.');
    }
    await loadInventoryContext();
    renderAll();
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
    renderUsers();
    if (APP.user.role === 'admin') {
        renderInventoryStats();
        renderBahanTable();
        renderSuppliersTable();
        renderResepTable();
        if (APP.invTab === 'in') renderStockIn();
        if (APP.invTab === 'out') renderStockOut();
        if (APP.invTab === 'adjust') renderAdjust();
        if (APP.invTab === 'low') renderLowStock();
        if (APP.invTab === 'report') renderInventoryReport();
    }
    setInvTab(APP.invTab);
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
    editUser,
    saveUser,
    deleteUser,
    editBahan,
    toggleBahan,
    deleteBahan,
    saveBahan,
    editSupplier,
    deleteSupplier,
    saveSupplier,
    openResep,
    deleteResep,
    saveResep,
    addResepRow,
    removeResepRow,
});

