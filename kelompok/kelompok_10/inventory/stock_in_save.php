<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/helpers.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    inventory_redirect('stock_in.php');
}
require_valid_csrf();

$pdo = db();
$userId = (int) (current_user()['id'] ?? 0);

$input = [
    'product_id'    => trim((string) ($_POST['product_id'] ?? '')),
    'supplier_id'   => trim((string) ($_POST['supplier_id'] ?? '')),
    'batch_code'    => trim((string) ($_POST['batch_code'] ?? '')),
    'qty_received'  => trim((string) ($_POST['qty_received'] ?? '')),
    'cost_per_unit' => trim((string) ($_POST['cost_per_unit'] ?? '')),
    'received_at'   => trim((string) ($_POST['received_at'] ?? '')),
    'expiry_date'   => trim((string) ($_POST['expiry_date'] ?? '')),
];

try {

    $productId = filter_var($input['product_id'], FILTER_VALIDATE_INT);
    if (!$productId) {
        throw new InvalidArgumentException('Pilih produk.');
    }
    $prod = $pdo->prepare('SELECT name, is_active FROM products WHERE id = ?');
    $prod->execute([$productId]);
    $product = $prod->fetch();
    if (!$product) {
        throw new InvalidArgumentException('Produk tidak ditemukan.');
    }
    if ((int) $product['is_active'] !== 1) {
        throw new InvalidArgumentException('Produk nonaktif tidak bisa diberi stok.');
    }

    $supplierId = null;
    if ($input['supplier_id'] !== '') {
        $supplierId = filter_var($input['supplier_id'], FILTER_VALIDATE_INT);
        $sup = $pdo->prepare('SELECT id FROM suppliers WHERE id = ?');
        $sup->execute([$supplierId ?: 0]);
        if (!$supplierId || !$sup->fetch()) {
            throw new InvalidArgumentException('Pemasok tidak ditemukan.');
        }
    }

    if (mb_strlen($input['batch_code']) > 80) {
        throw new InvalidArgumentException('Kode batch maksimal 80 karakter.');
    }
    $batchCode = $input['batch_code'] === '' ? null : $input['batch_code'];

    if (!preg_match('/^\d{1,9}$/', $input['qty_received']) || (int) $input['qty_received'] < 1) {
        throw new InvalidArgumentException('Jumlah masuk harus bilangan bulat lebih dari 0.');
    }
    $qty = (int) $input['qty_received'];

    if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $input['cost_per_unit'])) {
        throw new InvalidArgumentException('Harga pokok harus angka 0 atau lebih (maks. 2 desimal).');
    }
    $cost = $input['cost_per_unit'];

    $receivedAt = null;
    if ($input['received_at'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $input['received_at']);
        if (!$dt || $dt->format('Y-m-d\TH:i') !== $input['received_at']) {
            throw new InvalidArgumentException('Format tanggal masuk tidak valid.');
        }
        $receivedAt = $dt->format('Y-m-d H:i:s');
    }

    $expiry = null;
    if ($input['expiry_date'] !== '') {
        $ed = DateTime::createFromFormat('Y-m-d', $input['expiry_date']);
        if (!$ed || $ed->format('Y-m-d') !== $input['expiry_date']) {
            throw new InvalidArgumentException('Format tanggal kedaluwarsa tidak valid.');
        }
        $expiry = $input['expiry_date'];
        $receivedDate = $receivedAt !== null ? substr($receivedAt, 0, 10) : date('Y-m-d');
        if ($expiry < $receivedDate) {
            throw new InvalidArgumentException('Tanggal kedaluwarsa tidak boleh sebelum tanggal masuk.');
        }
    }

    $pdo->beginTransaction();
    try {
        $insBatch = $pdo->prepare(
            'INSERT INTO stock_batches
                (product_id, supplier_id, batch_code, received_at, expiry_date,
                 qty_received, qty_remaining, cost_per_unit, created_by)
             VALUES (?, ?, ?, COALESCE(?, NOW()), ?, ?, ?, ?, ?)'
        );
        $insBatch->execute([$productId, $supplierId, $batchCode, $receivedAt, $expiry, $qty, $qty, $cost, $userId]);
        $batchId = (int) $pdo->lastInsertId();

        $reason = 'Stok masuk' . ($batchCode !== null ? ' batch ' . $batchCode : '');
        $insMove = $pdo->prepare(
            "INSERT INTO inventory_movements
                (product_id, batch_id, user_id, movement_type, quantity, unit_cost, reason, occurred_at)
             VALUES (?, ?, ?, 'IN', ?, ?, ?, COALESCE(?, NOW()))"
        );
        $insMove->execute([$productId, $batchId, $userId, $qty, $cost, $reason, $receivedAt]);

        $pdo->commit();
    } catch (Throwable $inner) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $inner;
    }

    flash('success', 'Stok masuk tercatat: ' . $qty . ' unit ' . $product['name'] . ' (batch #' . $batchId . ').');
    inventory_redirect('stock_in.php');
} catch (InvalidArgumentException $ex) {
    $_SESSION['old_stock_in'] = $input;
    flash('error', $ex->getMessage());
    inventory_redirect('stock_in.php');
} catch (Throwable $ex) {
    error_log('stock_in_save: ' . $ex->getMessage());
    $_SESSION['old_stock_in'] = $input;
    flash('error', 'Gagal menyimpan stok masuk. Tidak ada data yang berubah.');
    inventory_redirect('stock_in.php');
}
