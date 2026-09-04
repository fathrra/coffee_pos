# CoffeePOS — Halaman Lain (Transaksi, Stok, Produk, Pengaturan)

## Tujuan
Menambahkan halaman frontend POS yang tersisa (Transaksi, Stok, Produk, Pengaturan) tanpa mengubah tampilan Dashboard yang sudah jadi. Semua halaman baru mengikuti design system neo-brutalis oranye/krem Dashboard yang sudah ada.

## Scope
- Frontend UI only — data masih mock/static, tanpa backend.
- Tidak merubah visual Dashboard (tidak redesign, tidak mengganti warna, tidak mengubah card style).
- Membuat 4 route baru:
  1. `/transaksi` — daftar transaksi dengan pencarian/tanggal dan ringkasan.
  2. `/stok` — daftar bahan baku stok, status stok rendah, form tambah stok (UI).
  3. `/produk` — daftar menu/produk kopi dengan kategori dan harga.
  4. `/pengaturan` — profil kasir, info warung, preferensi singkat.

## Struktur Perubahan

### Shared
- Buat `src/components/pos-card.tsx` sebagai komponen Card reusable untuk halaman baru, mengikuti style Dashboard (orange bg, border hitam tebal, rounded-3xl, shadow hitam offset).
- Pindahkan navbar dari `src/routes/index.tsx` ke `src/routes/__root.tsx` supaya navigasi berfungsi di semua halaman.
  - Navbar tetap dengan style yang sama: bottom bar di HP, rail kiri full-height di desktop.
  - Tombol nav diganti menjadi `<Link>` TanStack Router dengan state aktif berdasarkan route.

### Halaman Baru
Setiap halaman menggunakan `main` wrapper yang sama dengan Dashboard agar padding/navbar tidak bertabrakan.

1. **Transaksi** (`src/routes/transaksi.tsx`)
   - Hero card "Hi Fathur" versi mini + ringkasan total penjualan & jumlah transaksi.
   - Filter tanggal (select/static chip) dan input pencarian invoice/kasir.
   - Tabel transaksi sama dengan Dashboard tapi lebih lengkap (status, metode bayar).
   - Pagination dummy.

2. **Stok** (`src/routes/stok.tsx`)
   - Kartu ringkasan: total item, stok menipis, bahan habis.
   - Daftar stok dengan indikator warna: hijau/orange/merah (tetap dalam palette yang relevan, misal cream/ink/orange).
   - Tombol aksi "Tambah stok" (buka form modal/sheet dummy).

3. **Produk** (`src/routes/produk.tsx`)
   - Grid card produk (gambar placeholder/icon, nama, harga, kategori, status aktif).
   - Toggle kategori (Kopi, Non-Kopi, Makanan).
   - Tombol "Tambah produk" (form dummy).

4. **Pengaturan** (`src/routes/pengaturan.tsx`)
   - Profil kasir (avatar placeholder, nama, peran).
   - Info warung (nama, alamat, nomor).
   - Daftar item pengaturan statis (printer, pajak, nota, logout).

### Metadata
Setiap route baru mendapat `head()` dengan title, description, og:title, og:description sesuai halaman.

## Hal yang Tidak Akan Dilakukan
- Tidak mengganti warna/tema Dashboard.
- Tidak menambahkan backend atau database.
- Tidak membuat ulang Dashboard.

## Verifikasi
- Build/typecheck berhasil.
- Screenshot tiap halaman di desktop dan mobile untuk memastikan navbar tetap konsisten.
