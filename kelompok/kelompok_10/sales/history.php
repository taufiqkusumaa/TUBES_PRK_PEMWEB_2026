<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$search = trim($_GET['q'] ?? '');
$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

$params = [];
$whereClauses = ['s.status = "completed"'];

if ($search !== '') {
    $whereClauses[] = '(s.invoice_no LIKE :search OR u.full_name LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($startDate !== '') {
    $whereClauses[] = 'DATE(s.sold_at) >= :start_date';
    $params['start_date'] = $startDate;
}

if ($endDate !== '') {
    $whereClauses[] = 'DATE(s.sold_at) <= :end_date';
    $params['end_date'] = $endDate;
}

$whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

$sales = [];
$totalOmzet = 0.0;
$totalTransactions = 0;

try {
    $query = "
        SELECT 
            s.*,
            u.full_name AS cashier_name,
            COUNT(si.id) AS item_lines,
            COALESCE(SUM(si.qty), 0) AS total_units_sold
        FROM sales s
        LEFT JOIN users u ON s.cashier_id = u.id
        LEFT JOIN sale_items si ON s.id = si.sale_id
        {$whereSql}
        GROUP BY s.id
        ORDER BY s.sold_at DESC
        LIMIT 200
    ";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();

    foreach ($sales as $s) {
        $totalOmzet += (float) $s['grand_total'];
        $totalTransactions++;
    }
} catch (PDOException $e) {
    error_log('Error loading sales history: ' . $e->getMessage());
    flash('error', 'Gagal memuat riwayat transaksi penjualan.');
}

$pageTitle = 'Riwayat Transaksi Penjualan';
$activePage = 'sales_history';
require __DIR__ . '/../includes/header.php';
?>

<!-- KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">Total Omzet (Periode Terpilih)</span>
        <span class="stat-value" style="color: var(--primary);">Rp <?= number_format($totalOmzet, 0, ',', '.') ?></span>
        <span class="stat-sub"><?= date('d M Y', strtotime($startDate)) ?> &ndash; <?= date('d M Y', strtotime($endDate)) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Jumlah Transaksi Selesai</span>
        <span class="stat-value"><?= $totalTransactions ?> Transaksi</span>
        <span class="stat-sub">Invoice status: completed</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Rata-rata Nilai Belanja (AOV)</span>
        <span class="stat-value">
            Rp <?= $totalTransactions > 0 ? number_format($totalOmzet / $totalTransactions, 0, ',', '.') : 0 ?>
        </span>
        <span class="stat-sub">Per struk penjualan</span>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Daftar Struk & Riwayat Penjualan</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Semua transaksi penjualan tunai yang tercatat di kasir POS.</p>
        </div>
        <a href="<?= e(app_url('sales/pos.php')) ?>" class="button button-primary button-sm">+ Buka Kasir POS</a>
    </div>

    <!-- Filter Form -->
    <form method="get" class="filter-bar">
        <div class="filter-group">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari no. invoice / kasir..." class="search-input">
            <div style="display: flex; align-items: center; gap: 6px;">
                <label style="font-size: 12px; color: var(--ink-muted);">Dari:</label>
                <input type="date" name="start_date" value="<?= e($startDate) ?>" style="min-height: 34px;">
                <label style="font-size: 12px; color: var(--ink-muted);">Sampai:</label>
                <input type="date" name="end_date" value="<?= e($endDate) ?>" style="min-height: 34px;">
            </div>
            <button type="submit" class="button button-secondary button-sm">Filter</button>
            <?php if ($search !== '' || $startDate !== date('Y-m-01') || $endDate !== date('Y-m-d')): ?>
                <a href="<?= e(app_url('sales/history.php')) ?>" class="button button-secondary button-sm">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Waktu Transaksi</th>
                    <th>Kasir</th>
                    <th class="col-num">Total Qty</th>
                    <th class="col-num">Subtotal</th>
                    <th class="col-num">Diskon</th>
                    <th class="col-num">Grand Total</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: var(--ink-muted);">
                            Belum ada riwayat transaksi penjualan pada periode ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales as $s): ?>
                        <tr>
                            <td class="col-code">
                                <a href="<?= e(app_url('sales/receipt.php?id=' . $s['id'])) ?>" style="font-weight: 700;">
                                    <?= e($s['invoice_no']) ?>
                                </a>
                            </td>
                            <td style="white-space: nowrap; font-size: 12px; color: #475569;">
                                <?= date('d/m/Y H:i', strtotime($s['sold_at'])) ?>
                            </td>
                            <td><?= e($s['cashier_name'] ?? 'Kasir') ?></td>
                            <td class="col-num"><?= (int) $s['total_units_sold'] ?> item</td>
                            <td class="col-num">Rp <?= number_format((float) $s['subtotal'], 0, ',', '.') ?></td>
                            <td class="col-num">
                                <?= (float) $s['discount_total'] > 0 ? '-Rp ' . number_format((float) $s['discount_total'], 0, ',', '.') : '-' ?>
                            </td>
                            <td class="col-num">
                                <strong>Rp <?= number_format((float) $s['grand_total'], 0, ',', '.') ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-success"><span class="badge-dot"></span>Selesai</span>
                                <span class="badge badge-neutral" style="font-size: 10px; text-transform: uppercase; margin-left: 4px;">
                                    <?= e($s['payment_method'] ?? 'cash') ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= e(app_url('sales/receipt.php?id=' . $s['id'])) ?>" class="button button-secondary button-sm">Struk</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
