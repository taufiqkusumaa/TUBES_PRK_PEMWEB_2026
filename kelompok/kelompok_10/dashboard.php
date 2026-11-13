<?php
declare(strict_types=1);
require_once __DIR__ . '/config/auth.php';
require_login();

$user = current_user();
$isAdmin = $user['role'] === 'admin';
$isCashier = $user['role'] === 'cashier';
$isCustomer = $user['role'] === 'customer';

$todayOmzet = 0.0;
$todayTransactions = 0;
$outOfStockCount = 0;
$expiringCount = 0;
$recentSales = [];
$urgentAlerts = [];

// Data khusus pembeli
$customerOrdersCount = 0;
$customerTotalSpent = 0.0;
$availableProducts = [];

try {
    if ($isCustomer) {
        // Data belanja pelanggan
        $stmtCust = db()->prepare("
            SELECT COUNT(id) AS tx_count, COALESCE(SUM(grand_total), 0) AS total_spent
            FROM sales 
            WHERE customer_id = :uid AND status = 'completed'
        ");
        $stmtCust->execute(['uid' => (int) $user['id']]);
        $custSummary = $stmtCust->fetch();
        $customerOrdersCount = (int) ($custSummary['tx_count'] ?? 0);
        $customerTotalSpent = (float) ($custSummary['total_spent'] ?? 0);

        // Katalog etalase produk untuk pembeli (dengan foto internet nyata)
        $availableProducts = db()->query("
            SELECT p.id, p.sku, p.name, p.unit, p.selling_price, p.image_url, c.name AS category_name,
                   COALESCE(SUM(sb.qty_remaining), 0) AS stock
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN stock_batches sb ON p.id = sb.product_id 
                AND sb.qty_remaining > 0 
                AND (sb.expiry_date IS NULL OR sb.expiry_date >= CURDATE())
            WHERE p.is_active = 1
            GROUP BY p.id
            ORDER BY stock DESC, p.name ASC
            LIMIT 36
        ")->fetchAll();
    } else {
        // Data manajemen untuk admin / kasir
        $stmtToday = db()->query("
            SELECT 
                COALESCE(SUM(grand_total), 0) AS today_omzet,
                COUNT(id) AS today_tx
            FROM sales 
            WHERE status = 'completed' AND DATE(sold_at) = CURDATE()
        ");
        $today = $stmtToday->fetch();
        $todayOmzet = (float) ($today['today_omzet'] ?? 0);
        $todayTransactions = (int) ($today['today_tx'] ?? 0);

        $stmtOut = db()->query("
            SELECT COUNT(p.id) FROM products p
            LEFT JOIN stock_batches sb ON p.id = sb.product_id AND sb.qty_remaining > 0
            WHERE p.is_active = 1
            GROUP BY p.id
            HAVING COALESCE(SUM(sb.qty_remaining), 0) <= 0
        ");
        $outOfStockCount = $stmtOut ? count($stmtOut->fetchAll()) : 0;

        $stmtExp = db()->query("
            SELECT COUNT(id) FROM stock_batches 
            WHERE qty_remaining > 0 
              AND expiry_date IS NOT NULL 
              AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        $expiringCount = (int) $stmtExp->fetchColumn();

        $recentSales = db()->query("
            SELECT s.*, u.full_name AS cashier_name, COUNT(si.id) AS item_count
            FROM sales s
            LEFT JOIN users u ON s.cashier_id = u.id
            LEFT JOIN sale_items si ON s.id = si.sale_id
            WHERE s.status = 'completed'
            GROUP BY s.id
            ORDER BY s.sold_at DESC
            LIMIT 5
        ")->fetchAll();

        $urgentAlerts = db()->query("
            SELECT p.name, p.sku, p.unit, p.min_stock, COALESCE(SUM(sb.qty_remaining), 0) AS stock
            FROM products p
            LEFT JOIN stock_batches sb ON p.id = sb.product_id AND sb.qty_remaining > 0
            WHERE p.is_active = 1
            GROUP BY p.id
            HAVING stock <= p.min_stock
            ORDER BY stock ASC
            LIMIT 4
        ")->fetchAll();
    }
} catch (PDOException $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    flash('error', 'Gagal memuat beberapa data dashboard.');
}

$pageTitle = $isCustomer ? 'Portal Pelanggan' : 'Beranda Utama';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<?php if ($isCustomer): ?>
    <!-- ======================================================== -->
    <!-- NUTRAMLA PANORAMIC HERO SHOWCASE (IMAGE 2 REPLICA)      -->
    <!-- Ref: media_1794557405195_736320e1.jpg                   -->
    <!-- ======================================================== -->
    <div class="nutramla-hero-showcase">
        <div class="nutramla-hero-center">
            <h1 class="nutramla-hero-brand-name">NutrAmla</h1>
            <p class="nutramla-hero-tagline">A Tangy Twist of Taste &amp; Goodness!</p>
            <a href="<?= e(app_url('products/index.php')) ?>" class="nutramla-hero-pill-btn">
                Shop Now
            </a>
        </div>

        <div class="nutramla-hero-table-row">
            <a href="<?= e(app_url('products/index.php?category_id=1')) ?>" class="nutramla-hero-pill-item">
                <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=100&auto=format&fit=crop&q=80" alt="Snack">
                <span>Camilan &amp; Snack</span>
            </a>
            <a href="<?= e(app_url('products/index.php?category_id=2')) ?>" class="nutramla-hero-pill-item">
                <img src="https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=100&auto=format&fit=crop&q=80" alt="Madu">
                <span>Madu &amp; Minuman Herbal</span>
            </a>
            <a href="<?= e(app_url('products/index.php?category_id=4')) ?>" class="nutramla-hero-pill-item">
                <img src="https://images.unsplash.com/photo-1586201375761-83865001e31c?w=100&auto=format&fit=crop&q=80" alt="Sembako">
                <span>Pangan Pokok &amp; Sembako</span>
            </a>
            <a href="<?= e(app_url('sales/my_orders.php')) ?>" class="nutramla-hero-pill-item">
                <span>📦 Riwayat Belanja Saya &rarr;</span>
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-card-mint">
            <span class="stat-label">Total Pesanan Saya</span>
            <span class="stat-value"><?= $customerOrdersCount ?> Transaksi</span>
            <span class="stat-sub">Struk belanja yang terdaftar</span>
        </div>
        <div class="stat-card stat-card-blue">
            <span class="stat-label">Total Akumulasi Belanja</span>
            <span class="stat-value">Rp <?= number_format($customerTotalSpent, 0, ',', '.') ?></span>
            <span class="stat-sub">Pengeluaran tercatat di kasir</span>
        </div>
        <div class="stat-card stat-card-amber">
            <span class="stat-label">Status Akun Pelanggan</span>
            <span class="stat-value" style="font-size: 22px;">
                <span class="badge badge-success" style="font-size: 13px; padding: 6px 14px; background: #00A896; color: #fff;">&check; Member Aktif</span>
            </span>
            <span class="stat-sub">Terverifikasi di database toko</span>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- SIGNATURE: SHOP COLLECTIONS ALA NUTRAMLA (SHOPIFY STYLE) -->
    <!-- ======================================================== -->
    <div class="shop-collections-section">
        <div class="shop-collections-header">
            <h2 class="shop-collections-title">Shop Collections</h2>
            <p class="shop-collections-subtitle">Pilihan komoditas &amp; pangan alami unggulan mitra UMKM</p>
        </div>

        <div class="shop-collections-grid">
            <a href="<?= e(app_url('products/index.php?category_id=1')) ?>" class="collection-card">
                <div class="collection-img-wrap">
                    <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=500&auto=format&fit=crop&q=80" alt="Camilan & Roti" loading="lazy">
                </div>
                <div class="collection-info">
                    <h3 class="collection-title">Candy &amp; Snacks</h3>
                    <span class="collection-link">Shop Collection &rarr;</span>
                </div>
            </a>

            <a href="<?= e(app_url('products/index.php?category_id=4')) ?>" class="collection-card">
                <div class="collection-img-wrap">
                    <img src="https://thumb.wikimedia.org/wikipedia/commons/thumb/d/db/Kue_Lapis_Legit.JPG/500px-Kue_Lapis_Legit.JPG" alt="Gifting Kits & Tradisional" loading="lazy">
                </div>
                <div class="collection-info">
                    <h3 class="collection-title">Gifting Kits</h3>
                    <span class="collection-link">Shop Collection &rarr;</span>
                </div>
            </a>

            <a href="<?= e(app_url('products/index.php?category_id=2')) ?>" class="collection-card">
                <div class="collection-img-wrap">
                    <img src="https://thumb.wikimedia.org/wikipedia/commons/thumb/c/cd/Miunman_Es_Kunyit_Asam.jpg/500px-Miunman_Es_Kunyit_Asam.jpg" alt="Juice & Minuman Herbal" loading="lazy">
                </div>
                <div class="collection-info">
                    <h3 class="collection-title">Juice &amp; Coffee</h3>
                    <span class="collection-link">Shop Collection &rarr;</span>
                </div>
            </a>

            <a href="<?= e(app_url('products/index.php')) ?>" class="collection-card">
                <div class="collection-img-wrap">
                    <img src="https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c7/Cooking_oil.jpg/500px-Cooking_oil.jpg" alt="All Products" loading="lazy">
                </div>
                <div class="collection-info">
                    <h3 class="collection-title">All Products</h3>
                    <span class="collection-link">Shop Collection &rarr;</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Etalase Katalog Produk dengan Foto Real -->
    <div class="panel">
        <div class="panel-header">
            <div>
                <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 20px; font-weight: 800; color: #0F172A; margin: 0 0 4px;">Etalase Produk Pilihan UMKM</h3>
                <p class="muted" style="margin: 0; font-size: 13px;">Daftar ketersediaan barang real-time di toko fisik SisaSense.</p>
            </div>
            <a href="<?= e(app_url('products/index.php')) ?>" class="button button-secondary button-sm" style="border-radius: var(--radius-full);">Lihat Semua Katalog &rarr;</a>
        </div>

        <div class="pos-catalog-grid" style="grid-template-columns: repeat(auto-fill, minmax(235px, 1fr));">
            <?php foreach ($availableProducts as $prod): 
                $stk = (int) $prod['stock'];
                $isOut = $stk <= 0;
                $imageUrl = !empty($prod['image_url']) ? $prod['image_url'] : 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=400&q=80';
                $sellingPrice = (float) $prod['selling_price'];
                $originalPrice = round($sellingPrice * 1.12 / 500) * 500;
                $savings = max(1000, $originalPrice - $sellingPrice);
                $reviewCount = 80 + (($prod['id'] * 17) % 120);
            ?>
                <div class="nutramla-product-card product-card <?= $isOut ? 'is-out-of-stock' : '' ?>" style="cursor: default;">
                    <div class="nutramla-card-img-wrap">
                        <img src="<?= e($imageUrl) ?>" alt="<?= e($prod['name']) ?>" class="nutramla-card-img" loading="lazy">
                        
                        <!-- Rating Badge (Top Left ala NutrAmla) -->
                        <span class="nutramla-card-rating">
                            <span class="stars">★★★★★</span>
                            <span class="count">(<?= $reviewCount ?>)</span>
                        </span>

                        <!-- Wishlist Button (Top Right ala NutrAmla) -->
                        <button type="button" class="nutramla-card-wishlist" onclick="this.querySelector('svg').setAttribute('fill', this.querySelector('svg').getAttribute('fill') === '#22C55E' ? 'none' : '#22C55E')" title="Tambah ke Favorit">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                            </svg>
                        </button>

                        <?php if ($isOut): ?>
                            <span class="nutramla-card-stock-badge out">Stok Habis</span>
                        <?php elseif ($stk <= 5): ?>
                            <span class="nutramla-card-stock-badge low">Sisa <?= $stk ?> <?= e($prod['unit']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="nutramla-card-body">
                        <div>
                            <span class="product-card-category" style="margin-bottom: 4px;"><?= e($prod['category_name'] ?? 'Umum') ?></span>
                            <h4 class="nutramla-card-title" title="<?= e($prod['name']) ?>"><?= e($prod['name']) ?></h4>
                        </div>

                        <!-- Baris Harga & Hemat -->
                        <div class="nutramla-card-price-row">
                            <span class="nutramla-card-price">Rp <?= number_format($sellingPrice, 0, ',', '.') ?></span>
                            <span class="nutramla-card-original-price">Rp <?= number_format($originalPrice, 0, ',', '.') ?></span>
                            <span class="nutramla-card-save-badge">Hemat Rp <?= number_format($savings, 0, ',', '.') ?></span>
                        </div>

                        <a href="<?= e(app_url('sales/pos.php')) ?>" class="nutramla-card-buynow-btn" style="text-decoration: none;">
                            <?= $isOut ? 'Lihat di Katalog' : 'Beli Sekarang' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php else: ?>
    <!-- ======================================================== -->
    <!-- NUTRAMLA PANORAMIC HERO SHOWCASE (ADMIN & KASIR)        -->
    <!-- Ref: media_1794557405195_736320e1.jpg                   -->
    <!-- ======================================================== -->
    <div class="nutramla-hero-showcase">
        <div class="nutramla-hero-center">
            <h1 class="nutramla-hero-brand-name">NutrAmla</h1>
            <p class="nutramla-hero-tagline">A Tangy Twist of Taste &amp; Goodness! &bull; Kasir POS &amp; Alokasi FEFO</p>
            <div style="display: flex; gap: 14px; align-items: center; justify-content: center; flex-wrap: wrap;">
                <a href="<?= e(app_url('sales/pos.php')) ?>" class="nutramla-hero-pill-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                    Buka Kasir POS
                </a>
                <a href="<?= e(app_url('products/index.php')) ?>" class="nutramla-hero-pill-btn" style="background: rgba(255, 255, 255, 0.22); border: 1.5px solid rgba(255, 255, 255, 0.45); backdrop-filter: blur(6px);">
                    Shop Now / Katalog
                </a>
            </div>
        </div>

        <div class="nutramla-hero-table-row">
            <div class="nutramla-hero-pill-item" style="cursor: default;">
                <span>👤 Sesi: <strong><?= e($user['name']) ?></strong> (<?= $isAdmin ? 'Administrator' : 'Kasir Toko' ?>)</span>
            </div>
            <a href="<?= e(app_url('sales/pos.php')) ?>" class="nutramla-hero-pill-item">
                <span>🛒 Transaksi Baru &rarr;</span>
            </a>
            <?php if ($isAdmin): ?>
                <a href="<?= e(app_url('inventory/incoming.php')) ?>" class="nutramla-hero-pill-item">
                    <span>📦 + Stok Masuk FEFO</span>
                </a>
                <a href="<?= e(app_url('reports/index.php')) ?>" class="nutramla-hero-pill-item">
                    <span>📊 Analisis Penjualan &amp; Laba</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Cards Hari Ini (Pastel Pano Style) -->
    <div class="stats-grid">
        <div class="stat-card stat-card-mint">
            <span class="stat-label">Omzet Penjualan Hari Ini</span>
            <span class="stat-value">Rp <?= number_format($todayOmzet, 0, ',', '.') ?></span>
            <span class="stat-sub">Dari <?= $todayTransactions ?> struk transaksi</span>
        </div>
        <div class="stat-card stat-card-blue">
            <span class="stat-label">Transaksi Hari Ini</span>
            <span class="stat-value"><?= $todayTransactions ?> Struk</span>
            <span class="stat-sub"><a href="<?= e(app_url('sales/history.php')) ?>">Lihat riwayat struk &rarr;</a></span>
        </div>
        <div class="stat-card stat-card-coral">
            <span class="stat-label">Stok Habis (0 Unit)</span>
            <span class="stat-value">
                <?= $outOfStockCount ?> Produk
            </span>
            <span class="stat-sub <?= $outOfStockCount > 0 ? 'stat-trend-down' : '' ?>">Perlu segera restok</span>
        </div>
        <div class="stat-card stat-card-amber">
            <span class="stat-label">Batch Kedaluwarsa &lt; 30 Hari</span>
            <span class="stat-value">
                <?= $expiringCount ?> Batch
            </span>
            <span class="stat-sub"><a href="<?= e(app_url('inventory/batches.php?status=expiring')) ?>">Pantau masa simpan &rarr;</a></span>
        </div>
    </div>

    <div class="grid-2">
        <!-- Tabel 5 Transaksi Terbaru -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 2px;">Transaksi Penjualan Terakhir</h3>
                    <p class="muted" style="margin: 0; font-size: 12px;">Invoice penjualan yang baru saja diselesaikan.</p>
                </div>
                <a href="<?= e(app_url('sales/history.php')) ?>" class="button button-secondary button-sm" style="border-radius: var(--radius-full);">Semua Riwayat</a>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Waktu</th>
                            <th class="col-num">Total Bayar</th>
                            <th style="text-align: right;">Struk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentSales)): ?>
                            <tr><td colspan="4" style="text-align: center; padding: 24px; color: var(--ink-muted);">Belum ada transaksi penjualan hari ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentSales as $s): ?>
                                <tr>
                                    <td class="col-code"><?= e($s['invoice_no']) ?></td>
                                    <td style="font-size: 12px; color: #64748b;"><?= date('H:i', strtotime($s['sold_at'])) ?> WIB</td>
                                    <td class="col-num"><strong>Rp <?= number_format((float) $s['grand_total'], 0, ',', '.') ?></strong></td>
                                    <td style="text-align: right;">
                                        <a href="<?= e(app_url('sales/receipt.php?id=' . $s['id'])) ?>" class="button button-secondary button-sm" style="border-radius: var(--radius-full);">Cetak</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alert Stok & Pintasan Cepat -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 2px;">Peringatan Stok Kritis</h3>
                        <p class="muted" style="margin: 0; font-size: 11.5px;">Produk dengan stok di bawah batas aman.</p>
                    </div>
                    <?php if ($isAdmin): ?>
                        <a href="<?= e(app_url('reports/restock_recommendations.php')) ?>" class="button button-primary button-sm" style="border-radius: var(--radius-full);">Rekomendasi Restok</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($urgentAlerts)): ?>
                    <div style="padding: 16px; background: #E6F7F0; border-radius: var(--radius); color: #047857; font-size: 13px; font-weight: 600;">
                        &check; Persediaan aman. Seluruh produk aktif berada di atas batas minimum stok.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($urgentAlerts as $a): 
                            $stk = (int) $a['stock'];
                        ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border: 1px solid var(--line); border-radius: var(--radius); background: #FAFDFB;">
                                <div>
                                    <strong style="font-size: 13px; color: var(--ink);"><?= e($a['name']) ?></strong>
                                    <small style="display: block; color: var(--ink-subtle);"><?= e($a['sku']) ?></small>
                                </div>
                                <div style="text-align: right;">
                                    <?php if ($stk <= 0): ?>
                                        <span class="badge badge-danger">Habis (0)</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Sisa <?= $stk ?> <?= e($a['unit']) ?></span>
                                    <?php endif; ?>
                                    <small style="display: block; color: var(--ink-muted); font-size: 10px; margin-top: 2px;">Min: <?= (int) $a['min_stock'] ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h3 style="font-family: 'Playfair Display', Georgia, serif; font-size: 16px; margin: 0 0 12px; font-weight: 800; color: #0F172A;">Pintasan Cepat</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <a href="<?= e(app_url('products/index.php')) ?>" class="button button-secondary" style="font-size: 12px; justify-content: flex-start; border-radius: var(--radius);">
                        Katalog Produk
                    </a>
                    <a href="<?= e(app_url('inventory/index.php')) ?>" class="button button-secondary" style="font-size: 12px; justify-content: flex-start; border-radius: var(--radius);">
                        Persediaan Gudang
                    </a>
                    <?php if ($isAdmin): ?>
                        <a href="<?= e(app_url('reports/index.php')) ?>" class="button button-secondary" style="font-size: 12px; justify-content: flex-start; border-radius: var(--radius);">
                            Analitik Omzet
                        </a>
                        <a href="<?= e(app_url('reports/discount_simulator.php')) ?>" class="button button-secondary" style="font-size: 12px; justify-content: flex-start; border-radius: var(--radius);">
                            Simulasi Diskon
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
