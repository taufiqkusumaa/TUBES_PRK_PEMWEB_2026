<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$filterType = $_GET['type'] ?? 'all';
$limit = 100;

$params = [];
$whereSql = '';

if ($filterType !== 'all') {
    $whereSql = 'WHERE im.movement_type = :type';
    $params['type'] = $filterType;
}

$movements = [];
try {
    $query = "
        SELECT 
            im.*,
            p.name AS product_name,
            p.sku AS product_sku,
            p.unit AS product_unit,
            sb.batch_code,
            u.full_name AS operator_name,
            s.invoice_no
        FROM inventory_movements im
        JOIN products p ON im.product_id = p.id
        LEFT JOIN stock_batches sb ON im.batch_id = sb.id
        LEFT JOIN users u ON im.user_id = u.id
        LEFT JOIN sales s ON im.sale_id = s.id
        {$whereSql}
        ORDER BY im.occurred_at DESC
        LIMIT {$limit}
    ";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $movements = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error loading movements: ' . $e->getMessage());
    flash('error', 'Gagal memuat riwayat mutasi stok.');
}

$pageTitle = 'Riwayat Mutasi Persediaan (Audit Log)';
$activePage = 'inventory';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Riwayat Mutasi & Audit Persediaan</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Catatan permanen setiap stok masuk, penjualan POS, dan penyesuaian barang.</p>
        </div>
        <a href="<?= e(app_url('inventory/index.php')) ?>" class="button button-secondary button-sm">Kembali ke Stok</a>
    </div>

    <!-- Filter Bar -->
    <form method="get" class="filter-bar">
        <div class="filter-group">
            <select name="type">
                <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Semua Jenis Mutasi</option>
                <option value="IN" <?= $filterType === 'IN' ? 'selected' : '' ?>>Stok Masuk (IN)</option>
                <option value="SALE" <?= $filterType === 'SALE' ? 'selected' : '' ?>>Penjualan POS (SALE)</option>
                <option value="ADJUSTMENT_OUT" <?= $filterType === 'ADJUSTMENT_OUT' ? 'selected' : '' ?>>Penyesuaian Keluar</option>
                <option value="EXPIRED" <?= $filterType === 'EXPIRED' ? 'selected' : '' ?>>Kedaluwarsa (EXPIRED)</option>
                <option value="OUT" <?= $filterType === 'OUT' ? 'selected' : '' ?>>Rusak / Hilang (OUT)</option>
                <option value="RETURN" <?= $filterType === 'RETURN' ? 'selected' : '' ?>>Retur Pembatalan</option>
            </select>
            <button type="submit" class="button button-secondary button-sm">Filter</button>
            <?php if ($filterType !== 'all'): ?>
                <a href="<?= e(app_url('inventory/movements.php')) ?>" class="button button-secondary button-sm">Reset</a>
            <?php endif; ?>
        </div>
        <span class="muted" style="font-size: 12px;">Menampilkan <?= count($movements) ?> aktivitas terbaru</span>
    </form>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Tipe Mutasi</th>
                    <th>Produk</th>
                    <th>Batch</th>
                    <th class="col-num">Kuantitas</th>
                    <th class="col-num">HPP Satuan</th>
                    <th>Keterangan / Referensi</th>
                    <th>Operator</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: var(--ink-muted);">
                            Belum ada riwayat pergerakan stok.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): 
                        $type = $m['movement_type'];
                        $isPlus = in_array($type, ['IN', 'ADJUSTMENT_IN', 'RETURN'], true);
                    ?>
                        <tr>
                            <td style="white-space: nowrap; font-size: 12px; color: #475569;">
                                <?= date('d/m/Y H:i', strtotime($m['occurred_at'])) ?>
                            </td>
                            <td>
                                <?php if ($type === 'IN'): ?>
                                    <span class="badge badge-success">+ MASUK</span>
                                <?php elseif ($type === 'SALE'): ?>
                                    <span class="badge badge-info">&minus; TERJUAL</span>
                                <?php elseif ($type === 'EXPIRED'): ?>
                                    <span class="badge badge-danger">&minus; EXPIRED</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">&minus; <?= e($type) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($m['product_name']) ?></strong>
                                <small style="display: block; color: var(--ink-subtle);"><?= e($m['product_sku']) ?></small>
                            </td>
                            <td class="col-code"><?= e($m['batch_code'] ?? '-') ?></td>
                            <td class="col-num">
                                <strong style="color: <?= $isPlus ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <?= $isPlus ? '+' : '-' ?><?= (int) $m['quantity'] ?> <?= e($m['product_unit']) ?>
                                </strong>
                            </td>
                            <td class="col-num">
                                <?= $m['unit_cost'] !== null ? 'Rp ' . number_format((float) $m['unit_cost'], 0, ',', '.') : '-' ?>
                            </td>
                            <td>
                                <?= e($m['reason'] ?? '-') ?>
                                <?php if (!empty($m['invoice_no'])): ?>
                                    <small style="display: block; color: var(--primary);">Invoice: <?= e($m['invoice_no']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($m['operator_name'] ?? 'Sistem') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
