<?php

require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'cashier']);
require_once __DIR__ . '/../inventory/inventory_service.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/cart.php';

$pdo      = db();
$q        = trim((string) ($_GET['q'] ?? ''));
$products = inv_search_sellable_products($pdo, $q);
$cart     = cart_detail($pdo);
$canPay   = !empty($cart['items']) && !$cart['has_issue'];

$pageTitle  = 'Kasir (POS)';
$activePage = 'pos';
$navItems   = sales_nav_items();

require __DIR__ . '/../includes/header.php';
echo sales_css_tag();
?>
<div class="pos-layout">

    <section class="pos-panel pos-search">
        <h2>Cari Produk</h2>
        <form method="get" action="index.php" class="pos-search-form">
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Nama produk atau SKU" autocomplete="off" autofocus>
            <button type="submit" class="btn">Cari</button>
        </form>

        <?php if (!$products): ?>
            <p class="pos-empty">Tidak ada produk yang cocok atau stok kosong.</p>
        <?php else: ?>
            <div class="pos-product-grid">
                <?php foreach ($products as $p):
                    $stock = (int) ($p['available_stock'] ?? $p['stock'] ?? $p['qty_available'] ?? 0);
                    ?>
                    <article class="pos-product-card">
                        <h3><?= e((string) $p['name']) ?></h3>
                        <p class="pos-muted">SKU: <?= e((string) $p['sku']) ?></p>
                        <p class="pos-price"><?= e(sales_rupiah((string) $p['selling_price'])) ?></p>
                        <p class="pos-muted">Stok: <?= e((string) $stock) ?> <?= e((string) ($p['unit'] ?? '')) ?></p>
                        <form method="post" action="cart_action.php" class="pos-add-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="q" value="<?= e($q) ?>">
                            <input type="number" name="qty" value="1" min="1" max="<?= $stock ?>" class="pos-qty-input" aria-label="Jumlah">
                            <button type="submit" class="btn btn-primary">Tambah</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="pos-panel pos-cart">
        <h2>Keranjang</h2>

        <?php if (empty($cart['items'])): ?>
            <p class="pos-empty">Keranjang masih kosong.</p>
        <?php else: ?>
            <table class="pos-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="num">Harga</th>
                        <th>Jumlah</th>
                        <th class="num">Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cart['items'] as $item): ?>
                    <tr<?= $item['problem'] ? ' class="pos-row-warning"' : '' ?>>
                        <td>
                            <strong><?= e((string) $item['name']) ?></strong><br>
                            <span class="pos-muted"><?= e((string) $item['sku']) ?></span>
                            <?php if ($item['problem']): ?>
                                <br><span class="pos-warning"><?= e((string) $item['problem']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= e(sales_rupiah($item['price'])) ?></td>
                        <td>
                            <form method="post" action="cart_action.php" class="pos-inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                <input type="hidden" name="q" value="<?= e($q) ?>">
                                <input type="number" name="qty" value="<?= (int) $item['qty'] ?>" min="0" class="pos-qty-input" aria-label="Jumlah">
                                <button type="submit" class="btn btn-small">Ubah</button>
                            </form>
                        </td>
                        <td class="num"><?= e(sales_rupiah($item['subtotal'])) ?></td>
                        <td>
                            <form method="post" action="cart_action.php" class="pos-inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                <input type="hidden" name="q" value="<?= e($q) ?>">
                                <button type="submit" class="btn btn-small btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="num">Total</th>
                        <th class="num"><?= e(sales_rupiah($cart['total'])) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>

            <form method="post" action="cart_action.php" class="pos-clear-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="clear">
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <button type="submit" class="btn btn-small" data-confirm="Kosongkan seluruh keranjang?">Kosongkan keranjang</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($cart['has_issue'])): ?>
            <p class="pos-warning">Ada item bermasalah (stok berkurang / produk nonaktif). Perbaiki keranjang sebelum membayar.</p>
        <?php endif; ?>

        <h2>Pembayaran</h2>
        <form method="post" action="checkout.php" class="pos-pay-form" id="pay-form" data-total-cents="<?= (int) $cart['total_cents'] ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="payment_method">Metode pembayaran</label>
            <select name="payment_method" id="payment_method">
                <?php foreach (SALES_PAYMENT_LABELS as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>

            <div id="cash-fields">
                <label for="amount_paid">Uang diterima (tunai)</label>
                <input type="text" name="amount_paid" id="amount_paid" inputmode="decimal" autocomplete="off" placeholder="mis. 50000">
                <p class="pos-change">Kembalian: <strong id="change-preview">-</strong></p>
            </div>

            <label for="notes">Catatan (opsional)</label>
            <input type="text" name="notes" id="notes" maxlength="255" autocomplete="off">

            <button type="submit" class="btn btn-primary btn-block"<?= $canPay ? '' : ' disabled' ?>>
                Bayar <?= e(sales_rupiah($cart['total'])) ?>
            </button>
        </form>
    </section>
</div>
<?php
echo sales_js_tag();
require __DIR__ . '/../includes/footer.php';
