<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

require_login();
require_role(['admin', 'cashier']);

$pdo = db();

if (!isset($_SESSION['pos_cart'])) {
    $_SESSION['pos_cart'] = [];
}

$cart = &$_SESSION['pos_cart'];
$notice = $_SESSION['pos_notice'] ?? '';
unset($_SESSION['pos_notice']);

$keyword = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $action = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($action === 'add' || $action === 'update') {
        $stmt = $pdo->prepare("
            SELECT
                p.id,
                p.name,
                COALESCE(stock.available_qty, 0) AS available_qty
            FROM products p
            LEFT JOIN (
                SELECT product_id, SUM(qty_remaining) AS available_qty
                FROM stock_batches
                WHERE expiry_date IS NULL
                   OR expiry_date >= CURDATE()
                GROUP BY product_id
            ) stock ON stock.product_id = p.id
            WHERE p.id = :id AND p.is_active = 1
        ");

        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            $notice = 'Produk tidak ditemukan.';
        } else {
            $stock = (int) $product['available_qty'];

            if ($action === 'add') {
                $newQty = ($cart[$productId] ?? 0) + 1;

                if ($stock <= 0 || $newQty > $stock) {
                    $notice = 'Stok produk tidak mencukupi.';
                } else {
                    $cart[$productId] = $newQty;
                    $notice = 'Produk berhasil ditambahkan.';
                }
            }

            if ($action === 'update') {
                $qty = (int) ($_POST['qty'] ?? 1);

                if ($qty <= 0) {
                    unset($cart[$productId]);
                    $notice = 'Produk dihapus dari keranjang.';
                } elseif ($qty > $stock) {
                    $notice = 'Jumlah melebihi stok tersedia.';
                } else {
                    $cart[$productId] = $qty;
                    $notice = 'Jumlah produk berhasil diperbarui.';
                }
            }
        }
    }

    if ($action === 'remove') {
        unset($cart[$productId]);
        $notice = 'Produk dihapus dari keranjang.';
    }

    $_SESSION['pos_notice'] = $notice;

    $search = trim($_POST['search'] ?? '');
    header('Location: pos.php' . ($search !== '' ? '?search=' . urlencode($search) : ''));
    exit;
}

$sql = "
    SELECT
        p.id,
        p.sku,
        p.name,
        p.unit,
        p.selling_price,
        COALESCE(stock.available_qty, 0) AS available_qty
    FROM products p
    LEFT JOIN (
        SELECT
            product_id,
            SUM(qty_remaining) AS available_qty
        FROM stock_batches
        WHERE expiry_date IS NULL
           OR expiry_date >= CURDATE()
        GROUP BY product_id
    ) stock ON stock.product_id = p.id
    WHERE p.is_active = 1
";

$params = [];

if ($keyword !== '') {
    $sql .= " AND (p.name LIKE :name OR p.sku LIKE :sku)";
    $params['name'] = '%' . $keyword . '%';
    $params['sku'] = '%' . $keyword . '%';
}

$sql .= " ORDER BY p.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$cartProducts = [];

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.sku,
            p.name,
            p.unit,
            p.selling_price,
            COALESCE(stock.available_qty, 0) AS available_qty
        FROM products p
        LEFT JOIN (
            SELECT product_id, SUM(qty_remaining) AS available_qty
            FROM stock_batches
            WHERE expiry_date IS NULL
               OR expiry_date >= CURDATE()
            GROUP BY product_id
        ) stock ON stock.product_id = p.id
        WHERE p.id IN ($placeholders)
          AND p.is_active = 1
    ");

    $stmt->execute($ids);
    $cartProducts = $stmt->fetchAll();
}

$subtotal = 0;
$totalItems = 0;

foreach ($cartProducts as $item) {
    $qty = (int) $cart[$item['id']];
    $subtotal += (float) $item['selling_price'] * $qty;
    $totalItems += $qty;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - SisaSense</title>
</head>
<body>

<h1>Point of Sale (POS)</h1>
<p>Selamat datang di halaman kasir SisaSense.</p>

<?php if ($notice !== ''): ?>
    <p><?= e($notice) ?></p>
<?php endif; ?>

<hr>

<h2>Daftar Produk</h2>

<form method="GET">
    <input
        type="text"
        name="search"
        placeholder="Cari nama atau SKU produk..."
        value="<?= e($keyword) ?>"
    >
    <button type="submit">Cari</button>
    <a href="pos.php">Reset</a>
</form>

<br>

<table border="1" cellpadding="10" cellspacing="0">
    <thead>
        <tr>
            <th>No.</th>
            <th>SKU</th>
            <th>Nama Produk</th>
            <th>Harga Jual</th>
            <th>Stok Tersedia</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $index => $product): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= e($product['sku']) ?></td>
                    <td><?= e($product['name']) ?></td>
                    <td>
                        Rp <?= number_format(
                            (float) $product['selling_price'],
                            0,
                            ',',
                            '.'
                        ) ?>
                    </td>
                    <td>
                        <?= e($product['available_qty']) ?>
                        <?= e($product['unit']) ?>
                    </td>
                    <td>
                        <?php if ((int) $product['available_qty'] > 0): ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token"
                                    value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id"
                                    value="<?= (int) $product['id'] ?>">
                                <input type="hidden" name="search"
                                    value="<?= e($keyword) ?>">
                                <button type="submit">Tambah ke Keranjang</button>
                            </form>
                        <?php else: ?>
                            Stok Habis
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">Produk tidak ditemukan.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<p>Jumlah produk ditemukan: <?= count($products) ?></p>

<hr>

<h2>Keranjang Belanja</h2>

<?php if (count($cartProducts) > 0): ?>

    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($cartProducts as $index => $item): ?>
                <?php
                    $qty = (int) $cart[$item['id']];
                    $itemSubtotal = (float) $item['selling_price'] * $qty;
                ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= e($item['name']) ?></td>
                    <td>
                        Rp <?= number_format(
                            (float) $item['selling_price'],
                            0,
                            ',',
                            '.'
                        ) ?>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="csrf_token"
                                value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id"
                                value="<?= (int) $item['id'] ?>">
                            <input type="hidden" name="search"
                                value="<?= e($keyword) ?>">

                            <input
                                type="number"
                                name="qty"
                                min="1"
                                max="<?= (int) $item['available_qty'] ?>"
                                value="<?= $qty ?>"
                                required
                            >
                            <button type="submit">Ubah</button>
                        </form>
                    </td>
                    <td>
                        Rp <?= number_format(
                            $itemSubtotal,
                            0,
                            ',',
                            '.'
                        ) ?>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="csrf_token"
                                value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id"
                                value="<?= (int) $item['id'] ?>">
                            <input type="hidden" name="search"
                                value="<?= e($keyword) ?>">
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Total Barang: <?= $totalItems ?></h3>

    <h3>
        Total Belanja: Rp <?= number_format(
            $subtotal,
            0,
            ',',
            '.'
        ) ?>
    </h3>

<?php else: ?>

    <p>Keranjang masih kosong. Silakan tambahkan produk.</p>

<?php endif; ?>

</body>
</html>
