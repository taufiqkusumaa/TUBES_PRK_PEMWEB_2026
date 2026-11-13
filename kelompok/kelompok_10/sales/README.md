# Modul POS (Anggota 3)

Folder: `kelompok/kelompok_10/sales/`. Branch: `feat/kelompok-10-pos`.

## Ringkasan bahan dari tim
1. `config/auth.php`: `require_role`, `current_user`, `csrf_token`, `require_valid_csrf`, `flash`, `e`, `app_url`.
2. `includes/header.php`: pakai `$pageTitle`, `$activePage`, `$navItems`; href menu dibungkus `app_url()`.
3. `inventory/inventory_service.php`: `inv_search_sellable_products`, `inv_get_available_stock`, `inv_allocate_fefo` (wajib dalam transaksi), `inv_deduct_batch`, `inv_restore_batch`, `inv_record_movement`.
4. Fungsi `inv_*` tidak memanggil `commit()`; transaksi dimiliki `sales_checkout()` dan `sales_void()`.
5. CSP hanya mengizinkan sumber sendiri: tanpa script/style inline.

## Cara menjalankan
1. Jalankan Apache dan MySQL di XAMPP, login sebagai admin atau kasir.
2. Buka `sales/index.php`.
3. Siapkan data uji: produk Mi Instan (SKU MI001, Rp3.500), stok masuk 100 unit harga pokok Rp2.500.

## Menguji
Jalankan 16 skenario pada panduan bagian 8, lalu `php -l` pada semua file di `sales/`.

## File
| File | Fungsi |
| --- | --- |
| helpers.php | Menu, CSS/JS, redirect, format rupiah, konversi sen |
| cart.php | Keranjang di session |
| index.php | Halaman POS |
| cart_action.php | Proses keranjang |
| sales_service.php | `sales_checkout()` dan `sales_void()` |
| checkout.php | Proses pembayaran |
| receipt.php | Struk |
| history.php | Riwayat transaksi |
| void.php | Pembatalan (admin saja) |
