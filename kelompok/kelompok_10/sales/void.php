<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_role('admin');
require_once __DIR__ . '/sales_service.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}

require_valid_csrf();

$saleId = (int) ($_POST['sale_id'] ?? 0);
$reason = (string) ($_POST['reason'] ?? '');
$user = current_user();

try {
    sales_void(db(), $saleId, (int) $user['id'], $reason);
    flash('success', 'Transaksi dibatalkan dan stok dipulihkan.');
} catch (PDOException $e) {
    error_log('sales_void (DB): ' . $e->getMessage());
    flash('error', 'Pembatalan gagal disimpan. Tidak ada perubahan. Coba lagi.');
} catch (InvalidArgumentException | RuntimeException $e) {
    flash('error', $e->getMessage());
} catch (Throwable $e) {
    error_log('sales_void: ' . $e->getMessage());
    flash('error', 'Pembatalan gagal diproses.');
}

sales_redirect($saleId > 0 ? 'receipt.php?id=' . $saleId : 'history.php');
