<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

// Parameter model persediaan
$leadTimeDays = max(1, (int) ($_GET['lead_time'] ?? 7)); // Rata-rata waktu pengiriman pemasok (hari)
$safetyBufferDays = max(1, (int) ($_GET['safety_days'] ?? 3)); // Cadangan pengaman (hari)

$recommendations = [];

try {
    // Ambil data produk, stok tersedia saat ini, dan penjualan 30 hari terakhir
    $query = "
        SELECT 
            p.id,
            p.sku,
            p.name,
            p.unit,
            p.min_stock,
            p.selling_price,
            c.name AS category_name,
            COALESCE(SUM(sb.qty_remaining), 0) AS current_stock,
            COALESCE(sales_30d.total_qty_30d, 0) AS total_sold_30d
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN stock_batches sb ON p.id = sb.product_id AND sb.qty_remaining > 0
        LEFT JOIN (
            SELECT 
                si.product_id,
                SUM(si.qty) AS total_qty_30d
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            WHERE s.status = 'completed'
              AND s.sold_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY si.product_id
        ) sales_30d ON p.id = sales_30d.product_id
        WHERE p.is_active = 1
        GROUP BY p.id
        ORDER BY current_stock ASC, total_sold_30d DESC
    ";
    $products = db()->query($query)->fetchAll();

    foreach ($products as $p) {
        $sold30d = (int) $p['total_sold_30d'];
        $currentStock = (int) $p['current_stock'];
        $minStock = (int) $p['min_stock'];

        $hasSufficientData = $sold30d > 0;
        $ads = $hasSufficientData ? ($sold30d / 30.0) : 0.0; // Average Daily Sales

        if ($hasSufficientData) {
            $safetyStock = (int) ceil($ads * $safetyBufferDays);
            $reorderPoint = (int) ceil(($ads * $leadTimeDays) + $safetyStock);
            $targetStock = (int) ceil(($ads * ($leadTimeDays + 14)) + $safetyStock);
            $suggestedQty = max(0, $targetStock - $currentStock);
        } else {
            // Fallback jika belum cukup data historis penjualan (Sesuai Panduan C6)
            $safetyStock = $minStock;
            $reorderPoint = $minStock;
            $targetStock = $minStock * 2;
            $suggestedQty = max(0, $targetStock - $currentStock);
        }

        // Tentukan urgensi
        if ($currentStock <= 0) {
            $statusBadge = '<span class="badge badge-danger">Stok Habis (0)</span>';
            $urgency = 1;
        } elseif ($currentStock <= $reorderPoint) {
            $statusBadge = '<span class="badge badge-warning">Segera Restok</span>';
            $urgency = 2;
        } else {
            $statusBadge = '<span class="badge badge-success">Stok Cukup</span>';
            $urgency = 3;
        }

        $p['ads'] = $ads;
        $p['safety_stock'] = $safetyStock;
        $p['reorder_point'] = $reorderPoint;
        $p['suggested_qty'] = $suggestedQty;
        $p['has_data'] = $hasSufficientData;
        $p['status_badge'] = $statusBadge;
        $p['urgency'] = $urgency;

        $recommendations[] = $p;
    }

    // Urutkan dari yang paling mendesak
    usort($recommendations, fn($a, $b) => $a['urgency'] <=> $b['urgency']);

} catch (PDOException $e) {
    error_log('Error loading restock recommendations: ' . $e->getMessage());
    flash('error', 'Gagal memuat rekomendasi restok.');
}

$pageTitle = 'Rekomendasi Restok Barang (Metode ADS & ROP)';
$activePage = 'restock';
require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Sistem Pendukung Keputusan: Rekomendasi Restok</h2>
            <p class="muted" style="margin: 0; font-size: 13px;">Kalkulasi kuantitas pesanan optimal berdasarkan laju penjualan harian (ADS), lead time pemasok, dan safety stock.</p>
        </div>
        <form method="get" style="display: flex; gap: 8px; align-items: center;">
            <label style="font-size: 12px; color: #475569;">Lead Time (Hari):</label>
            <input type="number" name="lead_time" value="<?= $leadTimeDays ?>" min="1" max="60" style="width: 70px; min-height: 32px; padding: 2px 6px;">
            <label style="font-size: 12px; color: #475569;">Safety Buffer (Hari):</label>
            <input type="number" name="safety_days" value="<?= $safetyBufferDays ?>" min="1" max="30" style="width: 70px; min-height: 32px; padding: 2px 6px;">
            <button type="submit" class="button button-secondary button-sm">Hitung Ulang</button>
        </form>
    </div>

    <!-- Rumus Referensi -->
    <div class="notice notice-warning" style="margin-bottom: 20px;">
        <div>
            <strong>Formula yang Digunakan (Sesuai Panduan C6):</strong>
            <span style="font-size: 12px; display: block; margin-top: 4px;">
                &bull; Laju Harian (ADS) = Penjualan 30 Hari / 30 &nbsp;|&nbsp; 
                &bull; Reorder Point (ROP) = (ADS &times; Lead Time) + Safety Stock &nbsp;|&nbsp; 
                &bull; Saran Restok = Max(0, Target Stock &minus; Stok Saat Ini)
            </span>
        </div>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU & Produk</th>
                    <th class="col-num">Stok Saat Ini</th>
                    <th class="col-num">Laju ADS (30 Hari)</th>
                    <th class="col-num">Safety Stock</th>
                    <th class="col-num">Titik Pesan (ROP)</th>
                    <th class="col-num">Saran Kuantitas Restok</th>
                    <th>Status Kebutuhan</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recommendations)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 36px; color: var(--ink-muted);">Tidak ada data produk aktif.</td></tr>
                <?php else: ?>
                    <?php foreach ($recommendations as $r): ?>
                        <tr>
                            <td>
                                <strong><?= e($r['name']) ?></strong>
                                <small style="display: block; color: var(--ink-subtle);"><?= e($r['sku']) ?> &bull; <?= e($r['category_name'] ?? 'Umum') ?></small>
                            </td>
                            <td class="col-num">
                                <strong><?= $r['current_stock'] ?></strong> <?= e($r['unit']) ?>
                            </td>
                            <td class="col-num">
                                <?php if ($r['has_data']): ?>
                                    <?= number_format($r['ads'], 2, ',', '.') ?> <?= e($r['unit']) ?>/hr
                                    <small style="display: block; color: var(--ink-subtle);">(Total <?= $r['total_sold_30d'] ?> unit/30hr)</small>
                                <?php else: ?>
                                    <small style="color: var(--ink-muted);">Data belum cukup*</small>
                                <?php endif; ?>
                            </td>
                            <td class="col-num"><?= $r['safety_stock'] ?> <?= e($r['unit']) ?></td>
                            <td class="col-num"><strong><?= $r['reorder_point'] ?></strong> <?= e($r['unit']) ?></td>
                            <td class="col-num">
                                <?php if ($r['suggested_qty'] > 0): ?>
                                    <strong style="color: var(--primary); font-size: 15px;">+<?= $r['suggested_qty'] ?> <?= e($r['unit']) ?></strong>
                                <?php else: ?>
                                    <span style="color: var(--ink-subtle);">0 (Cukup)</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $r['status_badge'] ?></td>
                            <td style="text-align: right;">
                                <?php if ($r['suggested_qty'] > 0 && current_user()['role'] === 'admin'): ?>
                                    <a href="<?= e(app_url('inventory/incoming.php?product_id=' . $r['id'])) ?>" class="button button-primary button-sm">
                                        + Pesan Stok
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p class="muted" style="font-size: 11px; margin-top: 14px;">
        *Catatan: Produk yang belum memiliki riwayat transaksi penjualan selama 30 hari terakhir menggunakan batas minimum master produk sebagai dasar cadangan pengaman (safety stock).
    </p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

