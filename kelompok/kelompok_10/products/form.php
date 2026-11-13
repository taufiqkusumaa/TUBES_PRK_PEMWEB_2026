<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../inventory/helpers.php';

require_role('admin');

$pdo = db();

$id = (int) filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT id, category_id, sku, name, unit, selling_price, min_stock FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch() ?: null;
    if ($product === null) {
        flash('error', 'Produk tidak ditemukan.');
        inventory_redirect('index.php');
    }
}

$old = $_SESSION['old_product'] ?? null;
unset($_SESSION['old_product']);

$values = array_merge(
    ['category_id' => '', 'sku' => '', 'name' => '', 'unit' => 'pcs', 'selling_price' => '', 'min_stock' => '0'],
    $product ?? [],
    is_array($old) ? $old : []
);

$catStmt = $pdo->prepare('SELECT id, name FROM categories WHERE is_active = 1 OR id = ? ORDER BY name');
$catStmt->execute([(int) $values['category_id']]);
$categories = $catStmt->fetchAll();

$pageTitle = $product ? 'Ubah produk' : 'Tambah produk';
$activePage = 'products';
$navItems = inventory_nav_items();
require __DIR__ . '/../includes/header.php';
echo inventory_css();
?>
<p class="eyebrow">PRODUK</p>
<h1><?= e($pageTitle) ?></h1>

<section class="panel narrow-page">
    <?php if (!$categories): ?>
        <div class="notice notice-error">Belum ada kategori aktif. <a href="categories.php">Buat kategori dulu</a>.</div>
    <?php endif; ?>

    <form method="post" action="save.php" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <?php if ($product): ?>
            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
        <?php endif; ?>

        <label for="category_id">Kategori</label>
        <select id="category_id" name="category_id" required>
            <option value="">Pilih kategori</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $values['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="sku">SKU / kode produk</label>
        <input id="sku" name="sku" type="text" maxlength="50" required value="<?= e($values['sku']) ?>">
        <p class="field-hint">Huruf besar, angka, titik, strip. Harus unik.</p>

        <label for="name">Nama produk</label>
        <input id="name" name="name" type="text" maxlength="150" required value="<?= e($values['name']) ?>">

        <label for="unit">Satuan</label>
        <input id="unit" name="unit" type="text" maxlength="30" required value="<?= e($values['unit']) ?>">

        <label for="selling_price">Harga jual (Rp)</label>
        <input id="selling_price" name="selling_price" type="number" min="0" step="0.01" required value="<?= e($values['selling_price']) ?>">
        <p class="field-hint">Harga beli dicatat per batch saat stok masuk.</p>

        <label for="min_stock">Stok minimum</label>
        <input id="min_stock" name="min_stock" type="number" min="0" step="1" required value="<?= e($values['min_stock']) ?>">

        <button class="button button-primary button-wide" type="submit" <?= $categories ? '' : 'disabled' ?>>
            <?= $product ? 'Simpan perubahan' : 'Tambah produk' ?>
        </button>
        <a class="button button-secondary button-wide" href="index.php">Batal</a>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
