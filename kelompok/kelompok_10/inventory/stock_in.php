<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/helpers.php';

require_role('admin');

$pdo = db();

$products = $pdo->query('SELECT id, sku, name, unit FROM products WHERE is_active = 1 ORDER BY name')->fetchAll();
$suppliers = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();

$recent = $pdo->query(
    'SELECT b.id, b.batch_code, b.expiry_date, b.qty_received, b.qty_remaining,
            p.sku, p.name, p.unit
     FROM stock_batches b
     JOIN products p ON p.id = b.product_id
     ORDER BY b.id DESC
     LIMIT 10'
)->fetchAll();

$old = $_SESSION['old_stock_in'] ?? null;
unset($_SESSION['old_stock_in']);
$preselect = (int) filter_input(INPUT_GET, 'product', FILTER_VALIDATE_INT);
$values = array_merge(
    [
        'product_id' => $preselect > 0 ? (string) $preselect : '',
        'supplier_id' => '', 'batch_code' => '', 'qty_received' => '',
        'cost_per_unit' => '', 'received_at' => '', 'expiry_date' => '',
    ],
    is_array($old) ? $old : []
);

$pageTitle = 'Stok masuk';
$activePage = 'stock_in';
$navItems = inventory_nav_items();
require __DIR__ . '/../includes/header.php';
echo inventory_css();
?>
<p class="eyebrow">PERSEDIAAN</p>
<h1>Stok masuk</h1>
<p class="muted">Setiap penerimaan barang dicatat sebagai satu batch.</p>

<div class="inv-grid">
    <section class="panel">
        <h2>Catat penerimaan</h2>
        <?php if (!$products): ?>
            <div class="notice notice-error">Belum ada produk aktif. <a href="../products/form.php">Tambah produk dulu</a>.</div>
        <?php endif; ?>
        <form method="post" action="stock_in_save.php" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="product_id">Produk</label>
            <select id="product_id" name="product_id" required>
                <option value="">Pilih produk</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (string) $values['product_id'] === (string) $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['sku'] . ' - ' . $p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="batch_code">Kode batch (opsional)</label>
            <input id="batch_code" name="batch_code" type="text" maxlength="80" value="<?= e($values['batch_code']) ?>">

            <label for="qty_received">Jumlah masuk</label>
            <input id="qty_received" name="qty_received" type="number" min="1" step="1" required value="<?= e($values['qty_received']) ?>">

            <label for="cost_per_unit">Harga pokok per unit (Rp)</label>
            <input id="cost_per_unit" name="cost_per_unit" type="number" min="0" step="0.01" required value="<?= e($values['cost_per_unit']) ?>">

            <label for="received_at">Tanggal masuk</label>
            <input id="received_at" name="received_at" type="datetime-local" value="<?= e($values['received_at']) ?>">
            <p class="field-hint">Kosongkan untuk memakai waktu sekarang.</p>

            <label for="expiry_date">Tanggal kedaluwarsa (opsional)</label>
            <input id="expiry_date" name="expiry_date" type="date" value="<?= e($values['expiry_date']) ?>">

            <label for="supplier_id">Pemasok (opsional)</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">Tanpa pemasok</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= (string) $values['supplier_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <button class="button button-primary button-wide" type="submit" <?= $products ? '' : 'disabled' ?>>Simpan stok masuk</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>10 penerimaan terakhir</h2>
            <a class="button button-secondary button-small" href="batches.php">Lihat semua batch</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Produk</th><th>Batch</th><th class="num">Masuk</th><th class="num">Sisa</th><th>Kedaluwarsa</th></tr>
                </thead>
                <tbody>
                <?php if (!$recent): ?>
                    <tr><td class="empty-row" colspan="5">Belum ada penerimaan stok.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><strong><?= e($r['name']) ?></strong><br><span class="muted"><?= e($r['sku']) ?></span></td>
                        <td><?= ($r['batch_code'] ?? '') !== '' ? e($r['batch_code']) : '#' . (int) $r['id'] ?></td>
                        <td class="num"><?= (int) $r['qty_received'] ?> <?= e($r['unit']) ?></td>
                        <td class="num"><?= (int) $r['qty_remaining'] ?></td>
                        <td><?= $r['expiry_date'] ? e($r['expiry_date']) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
