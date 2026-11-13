<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$items = [];
$totalCostValuation = 0.0;
$totalRetailValuation = 0.0;
$totalUnits = 0;

try {
    $items = db()->query("
        SELECT 
            p.sku,
            p.name AS product_name,
            c.name AS category_name,
            p.unit,
            p.min_stock,
            p.selling_price,
            COALESCE(SUM(sb.qty_remaining), 0) AS total_stock,
            COALESCE(AVG(sb.cost_per_unit), 0) AS avg_cost,
            COALESCE(SUM(sb.qty_remaining * sb.cost_per_unit), 0) AS total_cost_value,
            COALESCE(SUM(sb.qty_remaining * p.selling_price), 0) AS total_retail_value
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN stock_batches sb ON p.id = sb.product_id AND sb.qty_remaining > 0
        WHERE p.is_active = 1
        GROUP BY p.id
        ORDER BY total_cost_value DESC, p.name ASC
    ")->fetchAll();

    foreach ($items as $item) {
        $totalCostValuation += (float) $item['total_cost_value'];
        $totalRetailValuation += (float) $item['total_retail_value'];
        $totalUnits += (int) $item['total_stock'];
    }
} catch (PDOException $e) {
    error_log('Error loading inventory report: ' . $e->getMessage());
    flash('error', 'Gagal memuat laporan stok persediaan.');
}

$pageTitle = 'Laporan Nilai Persediaan & Aset Gudang';
$activePage = 'analytics';
require __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">Total Nilai Persediaan (HPP)</span>
        <span class="stat-value" style="color: var(--primary);">Rp <?= number_format($totalCostValuation, 0, ',', '.') ?></span>
        <span class="stat-sub">Modal aset barang di rak & gudang</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Potensi Nilai Penjualan (Retail)</span>
        <span class="stat-value">Rp <?= number_format($totalRetailValuation, 0, ',', '.') ?></span>
        <span class="stat-sub">Jika seluruh stok terjual sesuai harga jual</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Potensi Margin Total</span>
        <span class="stat-value" style="color: var(--success-text);">
            Rp <?= number_format(max(0, $totalRetailValuation - $totalCostValuation), 0, ',', '.') ?>
        </span>
        <span class="stat-sub">Proyeksi laba kotor kotor</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Total Unit Fisik</span>
        <span class="stat-value"><?= number_format($totalUnits, 0, ',', '.') ?> Unit</span>
        <span class="stat-sub">Akumulasi <?= count($items) ?> SKU aktif</span>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Laporan Penilaian Aset Persediaan</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Daftar kuantitas stok fisik dan nilai valuasi perolehan modal.</p>
        </div>
        <a href="<?= e(app_url('reports/export_inventory_csv.php')) ?>" class="button button-primary button-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
            Ekspor CSV Stok
        </a>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th class="col-num">Stok Sisa</th>
                    <th class="col-num">Batas Min</th>
                    <th class="col-num">Rerata HPP</th>
                    <th class="col-num">Harga Jual</th>
                    <th class="col-num">Valuasi HPP</th>
                    <th class="col-num">Valuasi Retail</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="9" style="text-align: center; padding: 36px; color: var(--ink-muted);">Tidak ada data produk aktif.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): 
                        $stock = (int) $item['total_stock'];
                        $min = (int) $item['min_stock'];
                    ?>
                        <tr>
                            <td class="col-code"><?= e($item['sku']) ?></td>
                            <td><strong><?= e($item['product_name']) ?></strong></td>
                            <td><?= e($item['category_name'] ?? 'Umum') ?></td>
                            <td class="col-num">
                                <?php if ($stock <= 0): ?>
                                    <span class="badge badge-danger">0</span>
                                <?php elseif ($stock <= $min): ?>
                                    <span class="badge badge-warning"><?= $stock ?> <?= e($item['unit']) ?></span>
                                <?php else: ?>
                                    <span><?= $stock ?> <?= e($item['unit']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="col-num"><?= $min ?> <?= e($item['unit']) ?></td>
                            <td class="col-num">Rp <?= number_format((float) $item['avg_cost'], 0, ',', '.') ?></td>
                            <td class="col-num">Rp <?= number_format((float) $item['selling_price'], 0, ',', '.') ?></td>
                            <td class="col-num"><strong>Rp <?= number_format((float) $item['total_cost_value'], 0, ',', '.') ?></strong></td>
                            <td class="col-num">Rp <?= number_format((float) $item['total_retail_value'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

