<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
$cashierId = !empty($_GET['cashier_id']) ? (int) $_GET['cashier_id'] : null;

$cashiers = db()->query('SELECT id, full_name FROM users ORDER BY full_name ASC')->fetchAll();

$params = [
    'start_date' => $startDate,
    'end_date' => $endDate,
];
$whereCashier = '';
if ($cashierId !== null && $cashierId > 0) {
    $whereCashier = ' AND s.cashier_id = :cashier_id';
    $params['cashier_id'] = $cashierId;
}

$sales = [];
$totalSubtotal = 0.0;
$totalDiscount = 0.0;
$totalGrand = 0.0;
$totalHpp = 0.0;

try {
    $query = "
        SELECT 
            s.*,
            u.full_name AS cashier_name,
            COALESCE(SUM(si.qty * si.cost_price_snapshot), 0) AS invoice_hpp,
            COUNT(si.id) AS item_count
        FROM sales s
        LEFT JOIN users u ON s.cashier_id = u.id
        LEFT JOIN sale_items si ON s.id = si.sale_id
        WHERE s.status = 'completed'
          AND DATE(s.sold_at) >= :start_date
          AND DATE(s.sold_at) <= :end_date
          {$whereCashier}
        GROUP BY s.id
        ORDER BY s.sold_at DESC
    ";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();

    foreach ($sales as $s) {
        $totalSubtotal += (float) $s['subtotal'];
        $totalDiscount += (float) $s['discount_total'];
        $totalGrand += (float) $s['grand_total'];
        $totalHpp += (float) $s['invoice_hpp'];
    }
} catch (PDOException $e) {
    error_log('Error loading sales report: ' . $e->getMessage());
    flash('error', 'Gagal memuat laporan penjualan.');
}

$totalGrossProfit = $totalGrand - $totalHpp;
$marginPct = $totalGrand > 0 ? ($totalGrossProfit / $totalGrand) * 100 : 0;

$pageTitle = 'Laporan Rekapitulasi Penjualan';
$activePage = 'analytics';
require __DIR__ . '/../includes/header.php';
?>

<!-- KPI Ringkasan Laporan -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">Total Omzet Bersih</span>
        <span class="stat-value" style="color: var(--primary);">Rp <?= number_format($totalGrand, 0, ',', '.') ?></span>
        <span class="stat-sub"><?= count($sales) ?> transaksi selesai</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Total HPP Modal</span>
        <span class="stat-value">Rp <?= number_format($totalHpp, 0, ',', '.') ?></span>
        <span class="stat-sub">Harga perolehan persediaan</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Laba Kotor</span>
        <span class="stat-value" style="color: var(--success-text);">Rp <?= number_format($totalGrossProfit, 0, ',', '.') ?></span>
        <span class="stat-sub">Omzet &minus; HPP</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Margin Laba</span>
        <span class="stat-value"><?= number_format($marginPct, 1, ',', '.') ?>%</span>
        <span class="stat-sub">Efisiensi margin penjualan</span>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Laporan Penjualan</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Daftar transaksi penjualan terperinci per invoice beserta HPP dan laba.</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="<?= e(app_url('reports/export_sales_csv.php?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) . '&cashier_id=' . ($cashierId ?? ''))) ?>" class="button button-primary button-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                Ekspor CSV (Excel)
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="get" class="filter-bar">
        <div class="filter-group">
            <div style="display: flex; align-items: center; gap: 6px;">
                <label style="font-size: 12px; color: var(--ink-muted);">Periode:</label>
                <input type="date" name="start_date" value="<?= e($startDate) ?>" style="min-height: 34px;">
                <span>s/d</span>
                <input type="date" name="end_date" value="<?= e($endDate) ?>" style="min-height: 34px;">
            </div>
            <select name="cashier_id" style="min-height: 34px;">
                <option value="">Semua Kasir</option>
                <?php foreach ($cashiers as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $cashierId === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-secondary button-sm">Terapkan Filter</button>
        </div>
    </form>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Waktu</th>
                    <th>Kasir</th>
                    <th class="col-num">Subtotal</th>
                    <th class="col-num">Diskon</th>
                    <th class="col-num">Grand Total</th>
                    <th class="col-num">HPP (Modal)</th>
                    <th class="col-num">Laba Kotor</th>
                    <th style="text-align: right;">Struk</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr><td colspan="9" style="text-align: center; padding: 36px; color: var(--ink-muted);">Tidak ada transaksi pada filter periode ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($sales as $s): 
                        $grand = (float) $s['grand_total'];
                        $hpp = (float) $s['invoice_hpp'];
                        $profit = $grand - $hpp;
                    ?>
                        <tr>
                            <td class="col-code">
                                <a href="<?= e(app_url('sales/receipt.php?id=' . $s['id'])) ?>">
                                    <?= e($s['invoice_no']) ?>
                                </a>
                            </td>
                            <td style="white-space: nowrap; font-size: 12px; color: #475569;">
                                <?= date('d/m/Y H:i', strtotime($s['sold_at'])) ?>
                            </td>
                            <td><?= e($s['cashier_name'] ?? 'Kasir') ?></td>
                            <td class="col-num">Rp <?= number_format((float) $s['subtotal'], 0, ',', '.') ?></td>
                            <td class="col-num">
                                <?= (float) $s['discount_total'] > 0 ? '-Rp ' . number_format((float) $s['discount_total'], 0, ',', '.') : '-' ?>
                            </td>
                            <td class="col-num"><strong>Rp <?= number_format($grand, 0, ',', '.') ?></strong></td>
                            <td class="col-num" style="color: var(--ink-muted);">Rp <?= number_format($hpp, 0, ',', '.') ?></td>
                            <td class="col-num" style="color: <?= $profit >= 0 ? 'var(--success-text)' : 'var(--danger-text)' ?>; font-weight: 700;">
                                Rp <?= number_format($profit, 0, ',', '.') ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= e(app_url('sales/receipt.php?id=' . $s['id'])) ?>" class="button button-secondary button-sm">Lihat</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

