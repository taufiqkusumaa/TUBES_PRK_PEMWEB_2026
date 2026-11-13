<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

// Filter rentang tanggal (default 30 hari terakhir)
$startDate = trim($_GET['start_date'] ?? date('Y-m-d', strtotime('-29 days')));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

$totalOmzet = 0.0;
$totalHpp = 0.0;
$totalGrossProfit = 0.0;
$totalTransactions = 0;
$marginPercentage = 0.0;

$topProducts = [];
$slowMovingProducts = [];
$dailyTrends = [];
$expiringBatches = [];
$lowStockProducts = [];

try {
    // 1. Metrik Keuangan Utama (Omzet, HPP Snapshot, Laba Kotor)
    $stmtFin = db()->prepare("
        SELECT 
            COALESCE(SUM(s.grand_total), 0) AS total_omzet,
            COUNT(DISTINCT s.id) AS total_tx,
            COALESCE(SUM(si.qty * si.cost_price_snapshot), 0) AS total_hpp
        FROM sales s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        WHERE s.status = 'completed'
          AND DATE(s.sold_at) >= :start_date
          AND DATE(s.sold_at) <= :end_date
    ");
    $stmtFin->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $fin = $stmtFin->fetch();

    $totalOmzet = (float) $fin['total_omzet'];
    $totalHpp = (float) $fin['total_hpp'];
    $totalTransactions = (int) $fin['total_tx'];
    $totalGrossProfit = $totalOmzet - $totalHpp;
    if ($totalOmzet > 0) {
        $marginPercentage = ($totalGrossProfit / $totalOmzet) * 100.0;
    }

    // 2. Tren Omzet 7 Hari Terakhir untuk Visualisasi SVG Sparkline
    $stmtTrend = db()->query("
        SELECT 
            DATE(sold_at) AS tx_date,
            COALESCE(SUM(grand_total), 0) AS daily_omzet
        FROM sales
        WHERE status = 'completed'
          AND sold_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(sold_at)
        ORDER BY tx_date ASC
    ");
    $rawTrends = $stmtTrend->fetchAll(PDO::FETCH_KEY_PAIR);

    // Pastikan 7 hari lengkap ada nilai 0 jika belum ada transaksi
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $dailyTrends[$d] = (float) ($rawTrends[$d] ?? 0.0);
    }

    // 3. Produk Terlaris (Top 5 Best-Selling)
    $stmtTop = db()->prepare("
        SELECT 
            p.name,
            p.sku,
            p.unit,
            COALESCE(SUM(si.qty), 0) AS total_sold,
            COALESCE(SUM(si.line_total), 0) AS total_revenue
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        JOIN products p ON si.product_id = p.id
        WHERE s.status = 'completed'
          AND DATE(s.sold_at) >= :start_date
          AND DATE(s.sold_at) <= :end_date
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $stmtTop->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $topProducts = $stmtTop->fetchAll();

    // 4. Produk Lambat Terjual (Slow Moving - Tidak Terjual > 30 Hari)
    $slowMovingProducts = db()->query("
        SELECT 
            p.id,
            p.name,
            p.sku,
            p.unit,
            p.selling_price,
            COALESCE(SUM(sb.qty_remaining), 0) AS current_stock,
            MAX(s.sold_at) AS last_sold
        FROM products p
        LEFT JOIN stock_batches sb ON p.id = sb.product_id AND sb.qty_remaining > 0
        LEFT JOIN sale_items si ON p.id = si.product_id
        LEFT JOIN sales s ON si.sale_id = s.id AND s.status = 'completed'
        WHERE p.is_active = 1
        GROUP BY p.id
        HAVING last_sold IS NULL OR last_sold < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY current_stock DESC, p.name ASC
        LIMIT 5
    ")->fetchAll();

    // 5. Peringatan Batch Mendekati Kedaluwarsa (<30 hari)
    $expiringBatches = db()->query("
        SELECT 
            sb.batch_code,
            sb.expiry_date,
            sb.qty_remaining,
            p.name AS product_name,
            p.unit,
            DATEDIFF(sb.expiry_date, CURDATE()) AS days_left
        FROM stock_batches sb
        JOIN products p ON sb.product_id = p.id
        WHERE sb.qty_remaining > 0
          AND sb.expiry_date IS NOT NULL
          AND sb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY sb.expiry_date ASC
        LIMIT 5
    ")->fetchAll();

    // 6. Produk Stok Kritis
    $lowStockProducts = db()->query("
        SELECT 
            p.name,
            p.sku,
            p.unit,
            p.min_stock,
            COALESCE(SUM(sb.qty_remaining), 0) AS current_stock
        FROM products p
        LEFT JOIN stock_batches sb ON p.id = sb.product_id AND sb.qty_remaining > 0
        WHERE p.is_active = 1
        GROUP BY p.id
        HAVING current_stock <= p.min_stock
        ORDER BY current_stock ASC
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    error_log('Error loading analytics: ' . $e->getMessage());
    flash('error', 'Gagal memuat analitik penjualan.');
}

$pageTitle = 'Dashboard Analitik & Performa Penjualan';
$activePage = 'analytics';
require __DIR__ . '/../includes/header.php';
?>

<!-- Filter Rentang Periode -->
<div class="panel" style="padding: 16px 20px; margin-bottom: 24px;">
    <form method="get" class="flex-between" style="flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <strong>Periode Analisis:</strong>
            <input type="date" name="start_date" value="<?= e($startDate) ?>" style="min-height: 34px;">
            <span>&ndash;</span>
            <input type="date" name="end_date" value="<?= e($endDate) ?>" style="min-height: 34px;">
            <button type="submit" class="button button-primary button-sm">Perbarui Data</button>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="<?= e(app_url('reports/sales_report.php')) ?>" class="button button-secondary button-sm">Laporan Penjualan</a>
            <a href="<?= e(app_url('reports/inventory_report.php')) ?>" class="button button-secondary button-sm">Laporan Stok</a>
            <a href="<?= e(app_url('reports/restock_recommendations.php')) ?>" class="button button-secondary button-sm">Rekomendasi Restok</a>
            <a href="<?= e(app_url('reports/discount_simulator.php')) ?>" class="button button-secondary button-sm">Simulasi Diskon</a>
        </div>
    </form>
</div>

<!-- KPI Cards (Pastel Style ala Pano) -->
<div class="stats-grid">
    <div class="stat-card stat-card-mint">
        <span class="stat-label">Omzet Penjualan Bersih</span>
        <span class="stat-value">Rp <?= number_format($totalOmzet, 0, ',', '.') ?></span>
        <span class="stat-sub">Dari <?= $totalTransactions ?> transaksi selesai</span>
    </div>
    <div class="stat-card stat-card-blue">
        <span class="stat-label">HPP Terjual (Total Modal)</span>
        <span class="stat-value">Rp <?= number_format($totalHpp, 0, ',', '.') ?></span>
        <span class="stat-sub">Snapshot harga pokok batch FEFO</span>
    </div>
    <div class="stat-card stat-card-coral">
        <span class="stat-label">Laba Kotor (Gross Profit)</span>
        <span class="stat-value">
            Rp <?= number_format($totalGrossProfit, 0, ',', '.') ?>
        </span>
        <span class="stat-sub">Omzet bersih &minus; HPP</span>
    </div>
    <div class="stat-card stat-card-amber">
        <span class="stat-label">Margin Laba Kotor</span>
        <span class="stat-value">
            <?= number_format($marginPercentage, 1, ',', '.') ?>%
        </span>
        <span class="stat-sub">Persentase laba kotor / omzet</span>
    </div>
</div>

<!-- Visualisasi Tren Omzet 7 Hari (Pure SVG Sparkline) -->
<div class="panel" style="margin-bottom: 24px;">
    <div class="panel-header">
        <div>
            <h3>Tren Penjualan 7 Hari Terakhir</h3>
            <p class="muted" style="margin: 0; font-size: 12px;">Visualisasi fluktuasi omzet harian toko.</p>
        </div>
        <span class="muted" style="font-size: 11px;">Rendered via Native SVG (Zero External JS)</span>
    </div>

    <?php
    $maxOmzet = max(1.0, max(array_values($dailyTrends)));
    $svgPoints = [];
    $svgLabels = [];
    $count = count($dailyTrends);
    $idx = 0;
    $width = 900;
    $height = 140;
    $padding = 30;

    foreach ($dailyTrends as $date => $val) {
        $x = $padding + ($idx * (($width - ($padding * 2)) / max(1, $count - 1)));
        $y = ($height - $padding) - (($val / $maxOmzet) * ($height - ($padding * 2)));
        $svgPoints[] = "{$x},{$y}";
        $svgLabels[] = ['x' => $x, 'y' => $y, 'date' => date('d M', strtotime($date)), 'val' => $val];
        $idx++;
    }
    $polylineStr = implode(' ', $svgPoints);
    ?>

    <div style="width: 100%; overflow-x: auto;">
        <svg viewBox="0 0 <?= $width ?> <?= $height ?>" style="width: 100%; height: 160px; display: block;">
            <!-- Horizontal grid lines -->
            <line x1="<?= $padding ?>" y1="<?= $padding ?>" x2="<?= $width - $padding ?>" y2="<?= $padding ?>" stroke="#f1f5f9" stroke-dasharray="4" />
            <line x1="<?= $padding ?>" y1="<?= $height / 2 ?>" x2="<?= $width - $padding ?>" y2="<?= $height / 2 ?>" stroke="#f1f5f9" stroke-dasharray="4" />
            <line x1="<?= $padding ?>" y1="<?= $height - $padding ?>" x2="<?= $width - $padding ?>" y2="<?= $height - $padding ?>" stroke="#e2e8f0" />

            <!-- Gradient Area under curve -->
            <defs>
                <linearGradient id="trendGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#0f766e" stop-opacity="0.25" />
                    <stop offset="100%" stop-color="#0f766e" stop-opacity="0.0" />
                </linearGradient>
            </defs>
            <polygon points="<?= $padding ?>,<?= $height - $padding ?> <?= $polylineStr ?> <?= $width - $padding ?>,<?= $height - $padding ?>" fill="url(#trendGradient)" />

            <!-- Sparkline Path -->
            <polyline fill="none" stroke="#0f766e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="<?= $polylineStr ?>" />

            <!-- Sparkline Dots & Tooltip labels -->
            <?php foreach ($svgLabels as $pt): ?>
                <circle cx="<?= $pt['x'] ?>" cy="<?= $pt['y'] ?>" r="4" fill="#0f766e" stroke="#fff" stroke-width="2" />
                <text x="<?= $pt['x'] ?>" y="<?= $height - 8 ?>" font-size="10" fill="#64748b" text-anchor="middle" font-weight="600"><?= $pt['date'] ?></text>
                <?php if ($pt['val'] > 0): ?>
                    <text x="<?= $pt['x'] ?>" y="<?= $pt['y'] - 10 ?>" font-size="10" fill="#0f766e" text-anchor="middle" font-weight="700">Rp <?= number_format($pt['val'] / 1000, 0) ?>k</text>
                <?php endif; ?>
            <?php endforeach; ?>
        </svg>
    </div>
</div>

<!-- Row: Top Selling & Slow Moving -->
<div class="grid-2" style="margin-bottom: 24px;">
    <!-- Top 5 Best Selling -->
    <div class="panel">
        <div class="panel-header">
            <h3>Produk Terlaris (Top Selling)</h3>
            <span class="muted" style="font-size: 11px;">Berdasarkan total kuantitas terjual</span>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="col-num">Qty Terjual</th>
                        <th class="col-num">Total Omzet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topProducts)): ?>
                        <tr><td colspan="3" style="text-align: center; padding: 24px; color: var(--ink-muted);">Belum ada data penjualan pada periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topProducts as $tp): ?>
                            <tr>
                                <td>
                                    <strong><?= e($tp['name']) ?></strong>
                                    <small style="display: block; color: var(--ink-subtle);"><?= e($tp['sku']) ?></small>
                                </td>
                                <td class="col-num"><span class="badge badge-success"><?= (int) $tp['total_sold'] ?> <?= e($tp['unit']) ?></span></td>
                                <td class="col-num">Rp <?= number_format((float) $tp['total_revenue'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Slow Moving (>30 Hari Tidak Terjual) -->
    <div class="panel">
        <div class="panel-header">
            <h3>Produk Lambat Terjual (Slow-Moving)</h3>
            <span class="badge badge-warning" style="font-size: 10px;">&gt; 30 Hari Tanpa Penjualan</span>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="col-num">Sisa Stok</th>
                        <th>Terakhir Terjual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($slowMovingProducts)): ?>
                        <tr><td colspan="3" style="text-align: center; padding: 24px; color: var(--ink-muted);">Semua produk aktif memiliki pergerakan penjualan yang baik.</td></tr>
                    <?php else: ?>
                        <?php foreach ($slowMovingProducts as $sp): ?>
                            <tr>
                                <td>
                                    <strong><?= e($sp['name']) ?></strong>
                                    <small style="display: block; color: var(--ink-subtle);"><?= e($sp['sku']) ?></small>
                                </td>
                                <td class="col-num"><?= (int) $sp['current_stock'] ?> <?= e($sp['unit']) ?></td>
                                <td style="font-size: 12px; color: #64748b;">
                                    <?= !empty($sp['last_sold']) ? date('d M Y', strtotime($sp['last_sold'])) : '<span style="color:var(--danger);">Belum pernah terjual</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Row: Peringatan Stok & Expiry -->
<div class="grid-2">
    <!-- Peringatan Batch Mendekati Kedaluwarsa -->
    <div class="panel">
        <div class="panel-header">
            <h3>Peringatan Kedaluwarsa (&le; 30 Hari)</h3>
            <a href="<?= e(app_url('inventory/batches.php?status=expiring')) ?>" class="button button-secondary button-sm">Semua Batch</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch Code</th>
                        <th>Produk</th>
                        <th class="col-num">Sisa</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expiringBatches)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 20px; color: var(--success-text);">Aman! Tidak ada batch yang mendekati masa kedaluwarsa.</td></tr>
                    <?php else: ?>
                        <?php foreach ($expiringBatches as $eb): 
                            $days = (int) $eb['days_left'];
                        ?>
                            <tr>
                                <td class="col-code"><?= e($eb['batch_code']) ?></td>
                                <td><?= e($eb['product_name']) ?></td>
                                <td class="col-num"><?= (int) $eb['qty_remaining'] ?> <?= e($eb['unit']) ?></td>
                                <td>
                                    <?php if ($days < 0): ?>
                                        <span class="badge badge-danger">Expired</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= $days ?> hari lagi</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Peringatan Stok Menipis -->
    <div class="panel">
        <div class="panel-header">
            <h3>Peringatan Stok Menipis (&le; Batas Min)</h3>
            <a href="<?= e(app_url('reports/restock_recommendations.php')) ?>" class="button button-primary button-sm">Saran Restok &rarr;</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="col-num">Stok Saat Ini</th>
                        <th class="col-num">Batas Min</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStockProducts)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 20px; color: var(--success-text);">Semua stok produk saat ini berada di atas batas minimum.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lowStockProducts as $lp): 
                            $stk = (int) $lp['current_stock'];
                        ?>
                            <tr>
                                <td>
                                    <strong><?= e($lp['name']) ?></strong>
                                    <small style="display: block; color: var(--ink-subtle);"><?= e($lp['sku']) ?></small>
                                </td>
                                <td class="col-num"><strong><?= $stk ?></strong> <?= e($lp['unit']) ?></td>
                                <td class="col-num"><?= (int) $lp['min_stock'] ?> <?= e($lp['unit']) ?></td>
                                <td>
                                    <?php if ($stk <= 0): ?>
                                        <span class="badge badge-danger">Habis</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Kritis</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
