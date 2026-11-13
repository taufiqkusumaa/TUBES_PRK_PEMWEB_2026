<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/inventory_service.php';

require_role('admin');

$pdo = db();

$daysInput = filter_input(INPUT_GET, 'days', FILTER_VALIDATE_INT);
$days = ($daysInput !== false && $daysInput !== null && $daysInput >= 1 && $daysInput <= 365) ? $daysInput : 30;

$lowStock = inv_get_low_stock($pdo);
$expiring = inv_get_expiring_batches($pdo, $days);

$pageTitle = 'Peringatan stok';
$activePage = 'alerts';
$navItems = inventory_nav_items();
require __DIR__ . '/../includes/header.php';
echo inventory_css();
?>
<p class="eyebrow">PERSEDIAAN</p>
<h1>Peringatan</h1>
<p class="muted">Stok di bawah minimum dan batch yang sudah atau akan kedaluwarsa dalam <?= (int) $days ?> hari.</p>

<section class="panel">
    <div class="panel-header">
        <h2>Stok menipis (<?= count($lowStock) ?>)</h2>
    </div>
    <p class="muted">Aturan: stok tersedia kurang dari atau sama dengan stok minimum produk. Batch kedaluwarsa tidak dihitung.</p>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Produk</th><th class="num">Stok tersedia</th><th class="num">Stok minimum</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (!$lowStock): ?>
                <tr><td class="empty-row" colspan="5">Tidak ada produk di bawah stok minimum.</td></tr>
            <?php endif; ?>
            <?php foreach ($lowStock as $p): ?>
                <?php $empty = (int) $p['stock'] === 0; ?>
                <tr>
                    <td><strong><?= e($p['name']) ?></strong><br><span class="muted"><?= e($p['sku']) ?></span></td>
                    <td class="num"><?= (int) $p['stock'] ?> <?= e($p['unit']) ?></td>
                    <td class="num"><?= (int) $p['min_stock'] ?></td>
                    <td><span class="badge <?= $empty ? 'badge-danger' : 'badge-warning' ?>"><?= $empty ? 'Habis' : 'Menipis' ?></span></td>
                    <td><a class="button button-secondary button-small" href="stock_in.php?product=<?= (int) $p['id'] ?>">Tambah stok</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>Kedaluwarsa (<?= count($expiring) ?>)</h2>
        <form method="get" action="alerts.php" class="inline-form">
            <label for="days" class="muted">Dalam</label>
            <input id="days" name="days" type="number" min="1" max="365" value="<?= (int) $days ?>">
            <span class="muted">hari</span>
            <button class="button button-secondary button-small" type="submit">Terapkan</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Produk</th><th>Batch</th><th class="num">Sisa</th><th>Kedaluwarsa</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (!$expiring): ?>
                <tr><td class="empty-row" colspan="6">Tidak ada batch yang kedaluwarsa dalam <?= (int) $days ?> hari.</td></tr>
            <?php endif; ?>
            <?php foreach ($expiring as $b): ?>
                <?php $left = (int) $b['days_left']; ?>
                <tr>
                    <td><strong><?= e($b['name']) ?></strong><br><span class="muted"><?= e($b['sku']) ?></span></td>
                    <td><?= ($b['batch_code'] ?? '') !== '' ? e($b['batch_code']) : '#' . (int) $b['id'] ?></td>
                    <td class="num"><?= (int) $b['qty_remaining'] ?> <?= e($b['unit']) ?></td>
                    <td><?= e($b['expiry_date']) ?></td>
                    <td>
                        <?php if ($left < 0): ?>
                            <span class="badge badge-danger">Sudah kedaluwarsa</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Segera (<?= $left ?> hari)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($left < 0): ?>
                            <a class="button button-secondary button-small" href="stock_out.php?product=<?= (int) $b['product_id'] ?>&amp;batch=<?= (int) $b['id'] ?>">Keluarkan</a>
                        <?php else: ?>
                            <span class="muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
