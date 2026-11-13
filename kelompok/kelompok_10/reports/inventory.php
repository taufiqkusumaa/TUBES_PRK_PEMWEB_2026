<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/nav.php';

require_login();
require_role('admin');

$pageTitle = 'Laporan Inventaris';
$activePage = 'inventory';

$navItems = reports_nav_items(true);

$pdo = db();

$inventory = $pdo->query("
    SELECT
        p.id,
        p.sku,
        p.name,
        p.min_stock,
        COALESCE((
            SELECT SUM(sb2.qty_remaining)
            FROM stock_batches sb2
            WHERE sb2.product_id = p.id
              AND (
                  sb2.expiry_date IS NULL
                  OR sb2.expiry_date >= CURDATE()
              )
        ), 0) AS available_stock,
        sb.batch_code,
        sb.expiry_date,
        sb.qty_remaining AS batch_stock
    FROM products p
    LEFT JOIN stock_batches sb
        ON sb.product_id = p.id
        AND sb.qty_remaining > 0
    WHERE p.is_active = 1
    ORDER BY p.name ASC, sb.expiry_date ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= e(app_url('assets/css/recommendations-reports.css')) ?>">

<section class="reports-container">
    <h1>Laporan Inventaris</h1>

    <p class="reports-description">
        Informasi persediaan produk, jumlah stok, batch,
        dan tanggal kedaluwarsa.
    </p>

    <section class="analytics-section">
        <h2>Data Persediaan Barang</h2>

        <a href="<?= e(app_url('reports/export-inventory.php')) ?>">
            Export CSV
        </a>

        <?php if (empty($inventory)): ?>
            <p>Belum ada data persediaan produk.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th>Stok Tersedia</th>
                            <th>Minimum Stok</th>
                            <th>Batch</th>
                            <th>Stok Batch</th>
                            <th>Kedaluwarsa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $index => $product): ?>
                            <?php
                            $availableStock = (int) $product['available_stock'];
                            $minimumStock = (int) $product['min_stock'];
                            $isLowStock = $availableStock <= $minimumStock;

                            $formattedExpiry = $product['expiry_date']
                                ? date('d-m-Y', strtotime($product['expiry_date']))
                                : '-';
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($product['sku']) ?></td>
                                <td><?= e($product['name']) ?></td>
                                <td><?= number_format($availableStock) ?></td>
                                <td><?= number_format($minimumStock) ?></td>
                                <td><?= e($product['batch_code'] ?? '-') ?></td>
                                <td>
                                    <?= $product['batch_stock'] !== null
                                        ? number_format((int) $product['batch_stock'])
                                        : '-' ?>
                                </td>
                                <td><?= e($formattedExpiry) ?></td>
                                <td>
                                    <?php if ($isLowStock): ?>
                                        <span class="stock-status stock-low">
                                            Stok Menipis
                                        </span>
                                    <?php else: ?>
                                        <span class="stock-status stock-ok">
                                            Stok Aman
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>