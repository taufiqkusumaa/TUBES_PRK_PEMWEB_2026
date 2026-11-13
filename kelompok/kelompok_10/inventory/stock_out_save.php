<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/inventory_service.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    inventory_redirect('stock_out.php');
}
require_valid_csrf();

$pdo = db();
$userId = (int) (current_user()['id'] ?? 0);
$reasons = inventory_out_reasons();

$input = [
    'product_id' => trim((string) ($_POST['product_id'] ?? '')),
    'batch_id'   => trim((string) ($_POST['batch_id'] ?? '')),
    'qty'        => trim((string) ($_POST['qty'] ?? '')),
    'reason'     => trim((string) ($_POST['reason'] ?? '')),
    'note'       => trim((string) ($_POST['note'] ?? '')),
];
$back = 'stock_out.php';

try {

    $productId = filter_var($input['product_id'], FILTER_VALIDATE_INT);
    $batchId = filter_var($input['batch_id'], FILTER_VALIDATE_INT);
    if (!$productId) {
        throw new InvalidArgumentException('Pilih produk.');
    }
    if (!$batchId) {
        throw new InvalidArgumentException('Pilih batch.');
    }
    if (!preg_match('/^\d{1,9}$/', $input['qty']) || (int) $input['qty'] < 1) {
        throw new InvalidArgumentException('Jumlah keluar harus bilangan bulat lebih dari 0.');
    }
    $qty = (int) $input['qty'];
    if (!isset($reasons[$input['reason']])) {
        throw new InvalidArgumentException('Pilih alasan pengeluaran.');
    }
    if (mb_strlen($input['note']) > 150) {
        throw new InvalidArgumentException('Catatan maksimal 150 karakter.');
    }
    [$movementType, $reasonLabel] = $reasons[$input['reason']];
    $reasonText = $reasonLabel . ($input['note'] !== '' ? ': ' . $input['note'] : '');

    $pdo->beginTransaction();
    try {
        $sel = $pdo->prepare(
            'SELECT id, product_id, qty_remaining, cost_per_unit
             FROM stock_batches WHERE id = ? FOR UPDATE'
        );
        $sel->execute([$batchId]);
        $batch = $sel->fetch();

        if (!$batch || (int) $batch['product_id'] !== $productId) {
            throw new InvalidArgumentException('Batch tidak ditemukan untuk produk ini.');
        }
        if ($qty > (int) $batch['qty_remaining']) {
            throw new InvalidArgumentException(
                'Jumlah melebihi sisa batch (sisa ' . (int) $batch['qty_remaining'] . ').'
            );
        }

        if (!inv_deduct_batch($pdo, $batchId, $qty)) {
            throw new RuntimeException('Gagal mengurangi stok batch.');
        }
        inv_record_movement($pdo, [
            'product_id'    => $productId,
            'batch_id'      => $batchId,
            'user_id'       => $userId,
            'movement_type' => $movementType,
            'quantity'      => $qty,
            'unit_cost'     => $batch['cost_per_unit'],
            'reason'        => $reasonText,
        ]);

        $pdo->commit();
    } catch (Throwable $inner) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $inner;
    }

    flash('success', 'Stok keluar tercatat: ' . $qty . ' unit (' . $reasonLabel . ').');
    inventory_redirect('stock_out.php');
} catch (InvalidArgumentException $ex) {
    $_SESSION['old_stock_out'] = $input;
    flash('error', $ex->getMessage());
    inventory_redirect($back);
} catch (Throwable $ex) {
    error_log('stock_out_save: ' . $ex->getMessage());
    $_SESSION['old_stock_out'] = $input;
    flash('error', 'Gagal menyimpan stok keluar. Tidak ada data yang berubah.');
    inventory_redirect($back);
}
