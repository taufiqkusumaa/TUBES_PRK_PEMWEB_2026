<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

echo "=== TEST RENDERING HALAMAN DENGAN NUTRAMLA TOP NAVBAR ===" . PHP_EOL;

// 1. Test Admin Dashboard
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Administrator Toko',
    'username' => 'admin',
    'email' => 'admin@sisasense.id',
    'role' => 'admin',
    'is_active' => true,
];

ob_start();
include __DIR__ . '/../dashboard.php';
$adminHtml = ob_get_clean();

assert(str_contains($adminHtml, 'nutramla-header'), 'Header NutrAmla harus hadir di Dashboard Admin');
assert(str_contains($adminHtml, 'nutramla-brand-center'), 'Badge Logo Tengah harus hadir');
assert(str_contains($adminHtml, 'nutramla-count-badge'), 'Lime Green Count Badge harus hadir');
assert(!str_contains($adminHtml, '<aside class="sidebar">'), 'Sidebar kiri lama TIDAK boleh ada lagi');
assert(!str_contains($adminHtml, 'nutramla-announcement-bar'), 'Announcement bar atas harus dihapus sesuai permintaan user');
assert(!str_contains($adminHtml, 'nutramla-badge-devanagari'), 'Tulisan India/Devanagari harus dihapus sesuai permintaan user');
assert(!str_contains($adminHtml, 'सिसासेंस'), 'Karakter Devanagari tidak boleh ada lagi');
assert(str_contains($adminHtml, 'nutramla-hero-showcase'), 'NutrAmla Panoramic Hero Showcase harus hadir');
assert(str_contains($adminHtml, 'A Tangy Twist of Taste &amp; Goodness!'), 'Tagline NutrAmla harus hadir');
assert(str_contains($adminHtml, 'Shop Now'), 'Tombol Shop Now harus hadir');
assert(str_contains($adminHtml, 'nutramla-footer'), 'Footer 4 kolom NutrAmla harus hadir');
echo "[PASS] Dashboard Admin berhasil dirender dengan Navbar Atas NutrAmla & tanpa sidebar & tanpa tulisan India & ada Hero Showcase!" . PHP_EOL;

// 2. Test Kasir POS
ob_start();
include __DIR__ . '/../sales/pos.php';
$posHtml = ob_get_clean();

assert(str_contains($posHtml, 'nutramla-header'), 'Header NutrAmla harus hadir di POS');
assert(str_contains($posHtml, 'Konfirmasi Pembayaran &amp; Cetak Struk'), 'Tombol checkout modal harus ada');
assert(str_contains($posHtml, 'nutramlaCartBadge'), 'Cart badge sinkronisasi harus ada');
assert(!str_contains($posHtml, 'SisaSense Point of Sale &mdash; Transaksi Cepat'), 'Banner Kasir Aktif lama (Gambar 2) harus dihapus');
assert(str_contains($posHtml, 'nutramla-product-card'), 'Card produk ala NutrAmla (Gambar 1) harus hadir');
assert(str_contains($posHtml, 'nutramla-card-rating'), 'Rating bintang (142) harus ada');
assert(str_contains($posHtml, 'nutramla-card-wishlist'), 'Tombol Wishlist love harus ada');
assert(str_contains($posHtml, 'nutramla-card-stepper'), 'Stepper [-] [1] [+] harus ada');
assert(str_contains($posHtml, 'nutramla-card-add-btn'), 'Tombol Add cart harus ada');
assert(str_contains($posHtml, 'nutramla-card-buynow-btn'), 'Tombol Buy Now hijau harus ada');
echo "[PASS] Kasir POS berhasil dirender dengan Card NutrAmla (Gambar 1) & Banner lama terhapus (Gambar 2)!" . PHP_EOL;

// 3. Test Katalog Produk
ob_start();
include __DIR__ . '/../products/index.php';
$productsHtml = ob_get_clean();

assert(str_contains($productsHtml, 'Shop Collections'), 'Section Shop Collections ala NutrAmla harus ada');
assert(str_contains($productsHtml, 'Candy &amp; Snacks'), 'Koleksi Candy & Snacks harus ada');
assert(str_contains($productsHtml, 'nutramla-header'), 'Header NutrAmla harus hadir di Katalog');
echo "[PASS] Katalog Produk berhasil dirender dengan Shop Collections 4 cards!" . PHP_EOL;

// 4. Test Login (Guest)
unset($_SESSION['user']);
ob_start();
include __DIR__ . '/../login.php';
$loginHtml = ob_get_clean();

assert(str_contains($loginHtml, 'nutramla-header'), 'Header NutrAmla harus hadir di Login');
assert(str_contains($loginHtml, 'Selamat Datang Kembali'), 'Form login harus hadir');
assert(!str_contains($loginHtml, '<aside class="sidebar">'), 'Sidebar kiri TIDAK boleh ada di Login');
echo "[PASS] Halaman Login (Guest) berhasil dirender bersih dengan Top Navbar!" . PHP_EOL;

echo "SEMUA TEST RENDERING BERHASIL 100%!" . PHP_EOL;

