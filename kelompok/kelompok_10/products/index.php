<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$search = trim($_GET['q'] ?? '');
$categoryId = !empty($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$statusFilter = $_GET['status'] ?? 'all';

$params = [];
$whereClauses = [];

if ($search !== '') {
    $whereClauses[] = '(p.name LIKE :search OR p.sku LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($categoryId !== null && $categoryId > 0) {
    $whereClauses[] = 'p.category_id = :cat_id';
    $params['cat_id'] = $categoryId;
}

if ($statusFilter === 'active') {
    $whereClauses[] = 'p.is_active = 1';
} elseif ($statusFilter === 'inactive') {
    $whereClauses[] = 'p.is_active = 0';
}

$whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$products = [];
$categories = [];
try {
    $categories = db()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
    
    $query = "
        SELECT 
            p.*, 
            c.name AS category_name,
            COALESCE(SUM(sb.qty_remaining), 0) AS total_stock
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN stock_batches sb ON p.id = sb.product_id
        {$whereSql}
        GROUP BY p.id
        ORDER BY p.name ASC
    ";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error loading products: ' . $e->getMessage());
    flash('error', 'Gagal memuat katalog produk.');
}

$currentUser = current_user();
$isAdmin = $currentUser['role'] === 'admin';

$pageTitle = 'Katalog Produk';
$activePage = 'products';
$headerActions = $isAdmin ? '<a href="' . e(app_url('products/create.php')) . '" class="button button-primary button-sm">+ Tambah Produk</a>' : '';

require __DIR__ . '/../includes/header.php';
?>

<!-- ======================================================== -->
<!-- SIGNATURE: SHOP COLLECTIONS ALA NUTRAMLA (SHOPIFY STYLE) -->
<!-- ======================================================== -->
<div class="shop-collections-section">
    <div class="shop-collections-header">
        <h2 class="shop-collections-title">Shop Collections</h2>
        <p class="shop-collections-subtitle">Pilih koleksi untuk memfilter katalog produk secara instan</p>
    </div>

    <div class="shop-collections-grid">
        <a href="<?= e(app_url('products/index.php?category_id=1')) ?>" class="collection-card <?= $categoryId === 1 ? 'is-active' : '' ?>">
            <div class="collection-img-wrap">
                <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=500&auto=format&fit=crop&q=80" alt="Camilan & Roti" loading="lazy">
            </div>
            <div class="collection-info">
                <h3 class="collection-title">Candy &amp; Snacks</h3>
                <span class="collection-link"><?= $categoryId === 1 ? '&bull; Sedang Dilihat' : 'Shop Collection &rarr;' ?></span>
            </div>
        </a>

        <a href="<?= e(app_url('products/index.php?category_id=4')) ?>" class="collection-card <?= $categoryId === 4 ? 'is-active' : '' ?>">
            <div class="collection-img-wrap">
                <img src="https://thumb.wikimedia.org/wikipedia/commons/thumb/d/db/Kue_Lapis_Legit.JPG/500px-Kue_Lapis_Legit.JPG" alt="Gifting Kits & Tradisional" loading="lazy">
            </div>
            <div class="collection-info">
                <h3 class="collection-title">Gifting Kits</h3>
                <span class="collection-link"><?= $categoryId === 4 ? '&bull; Sedang Dilihat' : 'Shop Collection &rarr;' ?></span>
            </div>
        </a>

        <a href="<?= e(app_url('products/index.php?category_id=2')) ?>" class="collection-card <?= $categoryId === 2 ? 'is-active' : '' ?>">
            <div class="collection-img-wrap">
                <img src="https://thumb.wikimedia.org/wikipedia/commons/thumb/c/cd/Miunman_Es_Kunyit_Asam.jpg/500px-Miunman_Es_Kunyit_Asam.jpg" alt="Juice & Minuman Herbal" loading="lazy">
            </div>
            <div class="collection-info">
                <h3 class="collection-title">Juice &amp; Coffee</h3>
                <span class="collection-link"><?= $categoryId === 2 ? '&bull; Sedang Dilihat' : 'Shop Collection &rarr;' ?></span>
            </div>
        </a>

        <a href="<?= e(app_url('products/index.php')) ?>" class="collection-card <?= ($categoryId === null || $categoryId === 0) ? 'is-active' : '' ?>">
            <div class="collection-img-wrap">
                <img src="https://thumb.wikimedia.org/wikipedia/commons/thumb/c/c7/Cooking_oil.jpg/500px-Cooking_oil.jpg" alt="All Products" loading="lazy">
            </div>
            <div class="collection-info">
                <h3 class="collection-title">All Products</h3>
                <span class="collection-link"><?= ($categoryId === null || $categoryId === 0) ? '&bull; Semua Produk' : 'Shop Collection &rarr;' ?></span>
            </div>
        </a>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2><?= $isAdmin ? 'Katalog Produk & Master Data' : 'Daftar Katalog Produk Toko' ?></h2>
            <p class="muted" style="margin: 0; font-size: 13px;">
                <?= $isAdmin ? 'Kelola SKU, harga jual, dan batas stok minimum barang.' : 'Daftar seluruh produk yang tersedia di toko SisaSense.' ?>
            </p>
        </div>
        <?php if ($isAdmin): ?>
            <div class="gap-2" style="display: flex;">
                <a href="<?= e(app_url('products/categories.php')) ?>" class="button button-secondary button-sm">Kelola Kategori</a>
                <a href="<?= e(app_url('products/create.php')) ?>" class="button button-primary button-sm">+ Produk Baru</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filter Bar -->
    <form method="get" class="filter-bar">
        <div class="filter-group">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama atau SKU produk..." class="search-input">
            <select name="category_id">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Hanya Aktif</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Non-aktif</option>
            </select>
            <button type="submit" class="button button-secondary button-sm">Filter</button>
            <?php if ($search !== '' || $categoryId !== null || $statusFilter !== 'all'): ?>
                <a href="<?= e(app_url('products/index.php')) ?>" class="button button-secondary button-sm">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 56px;">Foto</th>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th class="col-num">Harga Jual</th>
                    <th class="col-num">Ketersediaan Stok</th>
                    <?php if ($isAdmin): ?>
                        <th class="col-num">Batas Min</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 9 : 6 ?>" style="text-align: center; padding: 40px; color: var(--ink-muted);">
                            Belum ada produk yang ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): 
                        $stock = (int) $p['total_stock'];
                        $minStock = (int) $p['min_stock'];
                        $isActive = (int) $p['is_active'] === 1;
                        $img = !empty($p['image_url']) ? $p['image_url'] : 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=150&q=80';
                    ?>
                        <tr>
                            <td>
                                <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" class="table-img-thumb" loading="lazy">
                            </td>
                            <td class="col-code"><?= e($p['sku']) ?></td>
                            <td>
                                <strong style="font-size: 13.5px;"><?= e($p['name']) ?></strong>
                                <small style="display: block; color: var(--ink-subtle);"><?= e($p['unit']) ?></small>
                            </td>
                            <td><span class="badge badge-neutral" style="font-size: 11px;"><?= e($p['category_name'] ?? 'Tanpa Kategori') ?></span></td>
                            <td class="col-num"><strong>Rp <?= number_format((float) $p['selling_price'], 0, ',', '.') ?></strong></td>
                            <td class="col-num">
                                <?php if ($stock <= 0): ?>
                                    <span class="badge badge-danger">Habis (0)</span>
                                <?php elseif ($stock <= $minStock): ?>
                                    <span class="badge badge-warning"><?= $stock ?> <?= e($p['unit']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= $stock ?> <?= e($p['unit']) ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td class="col-num"><?= $minStock ?> <?= e($p['unit']) ?></td>
                                <td>
                                    <?php if ($isActive): ?>
                                        <span class="badge badge-success"><span class="badge-dot"></span>Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral"><span class="badge-dot"></span>Non-aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?= e(app_url('products/edit.php?id=' . $p['id'])) ?>" class="button button-secondary button-sm">Ubah</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
