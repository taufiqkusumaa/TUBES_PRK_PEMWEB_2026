<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../inventory/helpers.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    inventory_redirect('index.php');
}
require_valid_csrf();

$pdo = db();
$action = (string) ($_POST['action'] ?? 'save');
$id = (int) filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($action === 'toggle') {
    try {
        if ($id <= 0) {
            throw new InvalidArgumentException('Produk tidak valid.');
        }
        $stmt = $pdo->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('Produk tidak ditemukan.');
        }
        flash('success', 'Status produk diperbarui.');
    } catch (InvalidArgumentException $ex) {
        flash('error', $ex->getMessage());
    } catch (PDOException $ex) {
        error_log('toggle product: ' . $ex->getMessage());
        flash('error', 'Gagal mengubah status produk.');
    }
    inventory_redirect('index.php');
}

$input = [
    'category_id'   => trim((string) ($_POST['category_id'] ?? '')),
    'sku'           => strtoupper(trim((string) ($_POST['sku'] ?? ''))),
    'name'          => trim((string) ($_POST['name'] ?? '')),
    'unit'          => trim((string) ($_POST['unit'] ?? '')) ?: 'pcs',
    'selling_price' => trim((string) ($_POST['selling_price'] ?? '')),
    'min_stock'     => trim((string) ($_POST['min_stock'] ?? '')),
];
$back = 'form.php' . ($id > 0 ? '?id=' . $id : '');

try {
    $categoryId = filter_var($input['category_id'], FILTER_VALIDATE_INT);
    if (!$categoryId) {
        throw new InvalidArgumentException('Pilih kategori.');
    }
    $cat = $pdo->prepare('SELECT is_active FROM categories WHERE id = ?');
    $cat->execute([$categoryId]);
    $category = $cat->fetch();
    if (!$category) {
        throw new InvalidArgumentException('Kategori tidak ditemukan.');
    }

    $existing = null;
    if ($id > 0) {
        $ex = $pdo->prepare('SELECT id, category_id FROM products WHERE id = ?');
        $ex->execute([$id]);
        $existing = $ex->fetch();
        if (!$existing) {
            throw new InvalidArgumentException('Produk tidak ditemukan.');
        }
    }

    $categoryChanged = $existing === null || (int) $existing['category_id'] !== $categoryId;
    if ((int) $category['is_active'] !== 1 && $categoryChanged) {
        throw new InvalidArgumentException('Kategori tersebut sedang nonaktif.');
    }

    if (!preg_match('/^[A-Z0-9][A-Z0-9._-]{0,49}$/', $input['sku'])) {
        throw new InvalidArgumentException('SKU wajib diisi (huruf besar, angka, titik, strip; maks. 50 karakter).');
    }
    if ($input['name'] === '' || mb_strlen($input['name']) > 150) {
        throw new InvalidArgumentException('Nama produk wajib diisi, maksimal 150 karakter.');
    }
    if (mb_strlen($input['unit']) > 30) {
        throw new InvalidArgumentException('Satuan maksimal 30 karakter.');
    }
    if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $input['selling_price'])) {
        throw new InvalidArgumentException('Harga jual harus angka 0 atau lebih (maks. 2 desimal).');
    }
    if (!preg_match('/^\d{1,9}$/', $input['min_stock'])) {
        throw new InvalidArgumentException('Stok minimum harus bilangan bulat 0 atau lebih.');
    }

    $dup = $pdo->prepare('SELECT id FROM products WHERE sku = ? AND id <> ?');
    $dup->execute([$input['sku'], $id]);
    if ($dup->fetch()) {
        throw new InvalidArgumentException('SKU sudah dipakai produk lain.');
    }

    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE products SET category_id = ?, sku = ?, name = ?, unit = ?, selling_price = ?, min_stock = ? WHERE id = ?'
        );
        $stmt->execute([$categoryId, $input['sku'], $input['name'], $input['unit'], $input['selling_price'], (int) $input['min_stock'], $id]);
        flash('success', 'Produk berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, sku, name, unit, selling_price, min_stock) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$categoryId, $input['sku'], $input['name'], $input['unit'], $input['selling_price'], (int) $input['min_stock']]);
        flash('success', 'Produk berhasil ditambahkan.');
    }
    inventory_redirect('index.php');
} catch (InvalidArgumentException $ex) {
    $_SESSION['old_product'] = $input;
    flash('error', $ex->getMessage());
    inventory_redirect($back);
} catch (PDOException $ex) {
    error_log('save product: ' . $ex->getMessage());
    $_SESSION['old_product'] = $input;
    $duplicate = ($ex->errorInfo[1] ?? 0) === 1062;
    flash('error', $duplicate ? 'SKU sudah dipakai produk lain.' : 'Gagal menyimpan produk. Coba lagi.');
    inventory_redirect($back);
}
