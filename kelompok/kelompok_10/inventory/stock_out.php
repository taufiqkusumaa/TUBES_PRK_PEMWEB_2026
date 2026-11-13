<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/helpers.php';

require_role('admin');

$pdo = db();
$reasons = inventory_out_reasons();
$types = inventory_movement_types();

$products = $pdo->query(
    'SELECT p.id, p.sku, p.name, p.unit, SUM(b.qty_remaining) AS total
     FROM products p
     JOIN stock_batches b ON b.product_id = p.id
     WHERE b.qty_remaining > 0
     GROUP BY p.id, p.sku, p.name, p.unit
     ORDER BY p.name'
)->fetchAll();

$old = $_SESSION['old_stock_out'] ?? null;
unset($_SESSION['old_stock_out']);
$old = is_array($old) ? $old : null;

$productId = $old !== null
    ? (int) ($old['product_id'] ?? 0)
    : (int) filter_input(INPUT_GET, 'product', FILTER_VALIDATE_INT);
$batchPre = $old === null ? (int) filter_input(INPUT_GET, 'batch', FILTER_VALIDATE_INT) : 0;

$selected = null;
foreach ($products as $p) {
    if ((int) $p['id'] === $productId) {
        $selected = $p;
        break;
    }
}

$batches = [];
if ($selected !== null) {
    $stmt = $pdo->prepare(
        'SELECT id, batch_code, received_at, expiry_date, qty_remaining
         FROM stock_batches
         WHERE product_id = ? AND qty_remaining > 0
         ORDER BY (expiry_date IS NULL), expiry_date ASC, received_at ASC, id ASC'
    );
    $stmt->execute([$productId]);
    $batches = $stmt->fetchAll();
}

$values = array_merge(
    ['batch_id' => $batchPre > 0 ? (string) $batchPre : '', 'qty' => '', 'reason' => '', 'note' => ''],
    $old ?? []
);

$recent = $pdo->query(
    "SELECT m.movement_type, m.quantity, m.reason, m.occurred_at,
            p.sku, p.name, p.unit, b.batch_code, b.id AS batch_id
     FROM inventory_movements m
     JOIN products p ON p.id = m.product_id
     LEFT JOIN stock_batches b ON b.id = m.batch_id
     WHERE m.movement_type IN ('OUT', 'EXPIRED', 'ADJUSTMENT_OUT')
     ORDER BY m.occurred_at DESC, m.id DESC
     LIMIT 10"
)->fetchAll();

$pageTitle = 'Stok keluar';
$activePage = 'stock_out';
$navItems = inventory_nav_items();
require __DIR__ . '/../includes/header.php';
echo inventory_css();
?>
<p class="eyebrow">PERSEDIAAN</p>
<h1>Stok keluar</h1>
<p class="muted">Untuk barang rusak, hilang, kedaluwarsa, pemakaian internal, atau penyesuaian. Penjualan dicatat lewat POS.</p>

<div class="inv-grid">
    <section class="panel">
        <h2>Catat pengeluaran</h2>

        <form method="get" action="stock_out.php" class="form-stack">
            <label for="product">Produk</label>
            <select id="product" name="product" required>
                <option value="">Pilih produk</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $productId === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['sku'] . ' - ' . $p['name'] . ' (sisa ' . (int) $p['total'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="button button-secondary" type="submit">Tampilkan batch</button>
        </form>

        <?php if (!$products): ?>
            <div class="notice notice-error">Belum ada stok yang bisa dikeluarkan.</div>
        <?php endif; ?>

        <?php if ($selected !== null): ?>
            <hr>
            <form method="post" action="stock_out_save.php" class="form-stack">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="product_id" value="<?= (int) $selected['id'] ?>">

                <label for="batch_id">Batch</label>
                <select id="batch_id" name="batch_id" required>
                    <option value="">Pilih batch</option>
                    <?php foreach ($batches as $b): ?>
                        <?php
                        $code = ($b['batch_code'] ?? '') !== '' ? $b['batch_code'] : '#' . (int) $b['id'];
                        $exp = $b['expiry_date'] ? ', exp ' . $b['expiry_date'] : '';
                        ?>
                        <option value="<?= (int) $b['id'] ?>" <?= (string) $values['batch_id'] === (string) $b['id'] ? 'selected' : '' ?>>
                            <?= e($code . ' - sisa ' . (int) $b['qty_remaining'] . ' ' . $selected['unit'] . $exp) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="qty">Jumlah keluar</label>
                <input id="qty" name="qty" type="number" min="1" step="1" required value="<?= e($values['qty']) ?>">

                <label for="reason">Alasan</label>
                <select id="reason" name="reason" required>
                    <option value="">Pilih alasan</option>
                    <?php foreach ($reasons as $key => [$type, $label]): ?>
                        <option value="<?= e($key) ?>" <?= $values['reason'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="note">Catatan (opsional)</label>
                <input id="note" name="note" type="text" maxlength="150" value="<?= e($values['note']) ?>">

                <button class="button button-primary button-wide" type="submit">Simpan stok keluar</button>
            </form>
        <?php elseif ($productId > 0): ?>
            <div class="notice notice-error">Produk itu tidak punya batch dengan sisa stok.</div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>10 pengeluaran terakhir</h2>
            <a class="button button-secondary button-small" href="movements.php">Riwayat lengkap</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Waktu</th><th>Produk</th><th>Batch</th><th class="num">Jumlah</th><th>Alasan</th></tr>
                </thead>
                <tbody>
                <?php if (!$recent): ?>
                    <tr><td class="empty-row" colspan="5">Belum ada stok keluar.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><?= e(substr((string) $r['occurred_at'], 0, 16)) ?></td>
                        <td><strong><?= e($r['name']) ?></strong><br><span class="muted"><?= e($r['sku']) ?></span></td>
                        <td><?= ($r['batch_code'] ?? '') !== '' ? e($r['batch_code']) : ($r['batch_id'] ? '#' . (int) $r['batch_id'] : '-') ?></td>
                        <td class="num">-<?= (int) $r['quantity'] ?> <?= e($r['unit']) ?></td>
                        <td>
                            <span class="badge <?= e($types[$r['movement_type']][1] ?? 'badge-muted') ?>"><?= e($types[$r['movement_type']][0] ?? $r['movement_type']) ?></span>
                            <?php if (($r['reason'] ?? '') !== ''): ?><br><span class="muted"><?= e($r['reason']) ?></span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
