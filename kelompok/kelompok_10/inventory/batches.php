<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$productId = !empty($_GET['product_id']) ? (int) $_GET['product_id'] : null;
$statusFilter = $_GET['status'] ?? 'all';

$params = [];
$whereClauses = ['sb.qty_remaining > 0'];

if ($productId !== null && $productId > 0) {
    $whereClauses[] = 'sb.product_id = :p_id';
    $params['p_id'] = $productId;
}

$whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

$batches = [];
try {
    $query = "
        SELECT 
            sb.*,
            p.name AS product_name,
            p.sku AS product_sku,
            p.unit AS product_unit,
            s.name AS supplier_name,
            u.full_name AS creator_name
        FROM stock_batches sb
        JOIN products p ON sb.product_id = p.id
        LEFT JOIN suppliers s ON sb.supplier_id = s.id
        LEFT JOIN users u ON sb.created_by = u.id
        {$whereSql}
        ORDER BY 
            CASE WHEN sb.expiry_date IS NULL THEN 1 ELSE 0 END,
            sb.expiry_date ASC,
            sb.received_at ASC
    ";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $allBatches = $stmt->fetchAll();

    foreach ($allBatches as $b) {
        $expiry = $b['expiry_date'];
        $diffDays = null;
        if ($expiry !== null) {
            $diffDays = (int) (new DateTime('today'))->diff(new DateTime($expiry))->format('%r%a');
        }

        if ($statusFilter === 'expired' && ($diffDays === null || $diffDays >= 0)) continue;
        if ($statusFilter === 'expiring' && ($diffDays === null || $diffDays < 0 || $diffDays > 30)) continue;
        if ($statusFilter === 'safe' && $diffDays !== null && $diffDays <= 30) continue;

        $b['diff_days'] = $diffDays;
        $batches[] = $b;
    }
} catch (PDOException $e) {
    error_log('Error loading batches: ' . $e->getMessage());
    flash('error', 'Gagal memuat daftar batch persediaan.');
}

$pageTitle = 'Daftar Batch Persediaan (FEFO)';
$activePage = 'inventory';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Daftar Batch Persediaan & Pelacakan Kedaluwarsa</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Urutan prioritas pengeluaran stok di kasir mengikuti aturan FEFO (First Expired, First Out).</p>
        </div>
        <div class="gap-2" style="display: flex;">
            <a href="<?= e(app_url('inventory/index.php')) ?>" class="button button-secondary button-sm">Ringkasan Stok</a>
            <?php if (current_user()['role'] === 'admin'): ?>
                <a href="<?= e(app_url('inventory/incoming.php')) ?>" class="button button-primary button-sm">+ Stok Masuk</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="get" class="filter-bar">
        <div class="filter-group">
            <select name="status">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Semua Kondisi Batch</option>
                <option value="safe" <?= $statusFilter === 'safe' ? 'selected' : '' ?>>Masa Simpan Aman (&gt; 30 Hari / Non-Expiry)</option>
                <option value="expiring" <?= $statusFilter === 'expiring' ? 'selected' : '' ?>>Mendekati Kedaluwarsa (&le; 30 Hari)</option>
                <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Sudah Kedaluwarsa</option>
            </select>
            <button type="submit" class="button button-secondary button-sm">Filter</button>
            <?php if ($statusFilter !== 'all'): ?>
                <a href="<?= e(app_url('inventory/batches.php')) ?>" class="button button-secondary button-sm">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>Produk</th>
                    <th>Supplier</th>
                    <th class="col-num">Tgl Masuk</th>
                    <th>Tgl Kedaluwarsa</th>
                    <th>Status Masa Simpan</th>
                    <th class="col-num">Sisa / Diterima</th>
                    <th class="col-num">HPP per Unit</th>
                    <th class="col-num">Subtotal Aset</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: var(--ink-muted);">
                            Tidak ada batch aktif yang sesuai filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($batches as $b): 
                        $remaining = (int) $b['qty_remaining'];
                        $received = (int) $b['qty_received'];
                        $cost = (float) $b['cost_per_unit'];
                        $subtotal = $remaining * $cost;
                        $diffDays = $b['diff_days'];
                    ?>
                        <tr>
                            <td class="col-code"><?= e($b['batch_code']) ?></td>
                            <td>
                                <strong><?= e($b['product_name']) ?></strong>
                                <small style="display: block; color: var(--ink-subtle);"><?= e($b['product_sku']) ?></small>
                            </td>
                            <td><?= e($b['supplier_name'] ?? '-') ?></td>
                            <td class="col-num"><?= date('d/m/Y', strtotime($b['received_at'])) ?></td>
                            <td>
                                <?= !empty($b['expiry_date']) ? date('d M Y', strtotime($b['expiry_date'])) : '<span style="color:var(--ink-subtle);">-</span>' ?>
                            </td>
                            <td>
                                <?php if ($diffDays === null): ?>
                                    <span class="badge badge-neutral">Tanpa Expiry</span>
                                <?php elseif ($diffDays < 0): ?>
                                    <span class="badge badge-danger">Expired (<?= abs($diffDays) ?> hari lalu)</span>
                                <?php elseif ($diffDays <= 30): ?>
                                    <span class="badge badge-warning"><?= $diffDays ?> hari lagi</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Aman (<?= $diffDays ?> hari)</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-num">
                                <strong><?= $remaining ?></strong> / <small style="color: var(--ink-subtle);"><?= $received ?> <?= e($b['product_unit']) ?></small>
                            </td>
                            <td class="col-num">Rp <?= number_format($cost, 0, ',', '.') ?></td>
                            <td class="col-num"><strong>Rp <?= number_format($subtotal, 0, ',', '.') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
