<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$search = trim($_GET['q'] ?? '');
$filterStock = $_GET['stock_status'] ?? 'all';

$params = [];
$whereSql = 'WHERE p.is_active = 1';

if ($search !== '') {
    $whereSql .= ' AND (p.name LIKE :search OR p.sku LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$inventory = [];
$totalValuation = 0.0;
$lowStockCount = 0;
$outOfStockCount = 0;
$expiringCount = 0;

try {
    // Ambil data stok gabungan batch
    $query = "
        SELECT 
            p.id,
            p.sku,
            p.name,
            p.unit,
            p.selling_price,
            p.min_stock,
            c.name AS category_name,
            COALESCE(SUM(sb.qty_remaining), 0) AS total_stock,
            COALESCE(SUM(sb.qty_remaining * sb.cost_per_unit), 0) AS total_cost_value,
            COUNT(sb.id) AS active_batches_count,
            MIN(CASE WHEN sb.qty_remaining > 0 AND sb.expiry_date IS NOT NULL THEN sb.expiry_date END) AS nearest_expiry
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN stock_batches sb ON p.id = sb.product_id AND sb.qty_remaining > 0
        {$whereSql}
        GROUP BY p.id
        ORDER BY total_stock ASC, p.name ASC
    ";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $allRows = $stmt->fetchAll();

    // Hitung ringkasan
    foreach ($allRows as $row) {
        $stock = (int) $row['total_stock'];
        $min = (int) $row['min_stock'];
        $totalValuation += (float) $row['total_cost_value'];

        if ($stock <= 0) {
            $outOfStockCount++;
        } elseif ($stock <= $min) {
            $lowStockCount++;
        }

        if (!empty($row['nearest_expiry'])) {
            $daysToExpiry = (int) (new DateTime($row['nearest_expiry']))->diff(new DateTime('today'))->format('%r%a');
            if ($daysToExpiry <= 30) {
                $expiringCount++;
            }
        }

        // Terapkan filter stock_status jika ada
        if ($filterStock === 'out' && $stock > 0) continue;
        if ($filterStock === 'low' && ($stock <= 0 || $stock > $min)) continue;
        if ($filterStock === 'safe' && $stock <= $min) continue;

        $inventory[] = $row;
    }
} catch (PDOException $e) {
    error_log('Error loading inventory: ' . $e->getMessage());
    flash('error', 'Gagal memuat data persediaan.');
}

$pageTitle = 'Manajemen Stok & Persediaan';
$activePage = 'inventory';
require __DIR__ . '/../includes/header.php';
?>

<!-- KPI Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">Total Nilai Aset Stok (HPP)</span>
        <span class="stat-value">Rp <?= number_format($totalValuation, 0, ',', '.') ?></span>
        <span class="stat-sub">Berdasarkan harga pokok per unit batch</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Stok Habis (0 Unit)</span>
        <span class="stat-value" style="color: <?= $outOfStockCount > 0 ? 'var(--danger)' : 'var(--ink)' ?>;">
            <?= $outOfStockCount ?> Produk
        </span>
        <span class="stat-sub <?= $outOfStockCount > 0 ? 'stat-trend-down' : '' ?>">Perlu segera restok</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Stok Kritis (&le; Batas Min)</span>
        <span class="stat-value" style="color: <?= $lowStockCount > 0 ? 'var(--warning)' : 'var(--ink)' ?>;">
            <?= $lowStockCount ?> Produk
        </span>
        <span class="stat-sub">Mendekati reorder point</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Batch Expired &lt; 30 Hari</span>
        <span class="stat-value" style="color: <?= $expiringCount > 0 ? 'var(--warning)' : 'var(--ink)' ?>;">
            <?= $expiringCount ?> Batch
        </span>
        <span class="stat-sub"><a href="<?= e(app_url('inventory/batches.php')) ?>">Periksa detail batch &rarr;</a></span>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Monitoring Persediaan & Batch Real-time</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Stok tersedia dihitung secara akurat dari akumulasi batch aktif (FEFO/FIFO).</p>
        </div>
        <div class="gap-2" style="display: flex;">
            <a href="<?= e(app_url('inventory/movements.php')) ?>" class="button button-secondary button-sm">Riwayat Mutasi</a>
            <a href="<?= e(app_url('inventory/batches.php')) ?>" class="button button-secondary button-sm">Daftar Batch</a>
            <?php if (current_user()['role'] === 'admin'): ?>
                <a href="<?= e(app_url('inventory/adjustment.php')) ?>" class="button button-secondary button-sm">Penyesuaian Stok</a>
                <a href="<?= e(app_url('inventory/incoming.php')) ?>" class="button button-primary button-sm">+ Stok Masuk</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="get" class="filter-bar">
        <div class="filter-group">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama atau SKU..." class="search-input">
            <select name="stock_status">
                <option value="all" <?= $filterStock === 'all' ? 'selected' : '' ?>>Semua Kondisi Stok</option>
                <option value="out" <?= $filterStock === 'out' ? 'selected' : '' ?>>Stok Habis (0)</option>
                <option value="low" <?= $filterStock === 'low' ? 'selected' : '' ?>>Stok Kritis (&le; Min)</option>
                <option value="safe" <?= $filterStock === 'safe' ? 'selected' : '' ?>>Stok Aman (&gt; Min)</option>
            </select>
            <button type="submit" class="button button-secondary button-sm">Terapkan</button>
            <?php if ($search !== '' || $filterStock !== 'all'): ?>
                <a href="<?= e(app_url('inventory/index.php')) ?>" class="button button-secondary button-sm">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th class="col-num">Batas Min</th>
                    <th class="col-num">Stok Sisa</th>
                    <th class="col-num">Batch Aktif</th>
                    <th>Kedaluwarsa Terdekat</th>
                    <th class="col-num">Estimasi Aset (HPP)</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventory)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: var(--ink-muted);">
                            Tidak ada data persediaan yang sesuai filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inventory as $item): 
                        $stock = (int) $item['total_stock'];
                        $min = (int) $item['min_stock'];
                        $batches = (int) $item['active_batches_count'];
                    ?>
                        <tr>
                            <td class="col-code"><?= e($item['sku']) ?></td>
                            <td>
                                <strong><?= e($item['name']) ?></strong>
                            </td>
                            <td><?= e($item['category_name'] ?? 'Umum') ?></td>
                            <td class="col-num"><?= $min ?> <?= e($item['unit']) ?></td>
                            <td class="col-num">
                                <?php if ($stock <= 0): ?>
                                    <span class="badge badge-danger">Habis (0)</span>
                                <?php elseif ($stock <= $min): ?>
                                    <span class="badge badge-warning"><?= $stock ?> <?= e($item['unit']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= $stock ?> <?= e($item['unit']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="col-num"><?= $batches ?> Batch</td>
                            <td>
                                <?php if (!empty($item['nearest_expiry'])): 
                                    $dateStr = date('d M Y', strtotime($item['nearest_expiry']));
                                    $diffDays = (int) (new DateTime('today'))->diff(new DateTime($item['nearest_expiry']))->format('%r%a');
                                ?>
                                    <?php if ($diffDays < 0): ?>
                                        <span class="badge badge-danger" title="<?= $dateStr ?>">Expired (<?= abs($diffDays) ?> hr lalu)</span>
                                    <?php elseif ($diffDays <= 30): ?>
                                        <span class="badge badge-warning" title="<?= $dateStr ?>"><?= $diffDays ?> hari lagi</span>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #475569;"><?= $dateStr ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: var(--ink-subtle); font-size: 12px;">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-num">Rp <?= number_format((float) $item['total_cost_value'], 0, ',', '.') ?></td>
                            <td style="text-align: right;">
                                <?php if (current_user()['role'] === 'admin'): ?>
                                    <a href="<?= e(app_url('inventory/incoming.php?product_id=' . $item['id'])) ?>" class="button button-secondary button-sm">+ Stok</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

