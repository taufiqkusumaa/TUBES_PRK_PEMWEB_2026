<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

require_login();

$user = current_user();
$isAdmin = $user['role'] === 'admin';

$pdo = db();

$startDate = is_string($_GET['start_date'] ?? null)
    ? trim($_GET['start_date'])
    : '';

$endDate = is_string($_GET['end_date'] ?? null)
    ? trim($_GET['end_date'])
    : '';

$invoice = is_string($_GET['invoice'] ?? null)
    ? trim($_GET['invoice'])
    : '';

$cashierFilter = $isAdmin ? filter_input(INPUT_GET, 'cashier_id', FILTER_VALIDATE_INT) : null;
$cashierFilter = ($cashierFilter !== false && $cashierFilter !== null && $cashierFilter > 0)
    ? $cashierFilter
    : null;

$where = ["s.status = 'completed'"];
$params = [];

if ($startDate !== '') {
    $date = DateTime::createFromFormat('!Y-m-d', $startDate);

    if (!$date || $date->format('Y-m-d') !== $startDate) {
        http_response_code(400);
        exit('Tanggal awal tidak valid.');
    }

    $where[] = 's.sold_at >= :start_date';
    $params['start_date'] = $startDate . ' 00:00:00';
}

if ($endDate !== '') {
    $date = DateTime::createFromFormat('!Y-m-d', $endDate);

    if (!$date || $date->format('Y-m-d') !== $endDate) {
        http_response_code(400);
        exit('Tanggal akhir tidak valid.');
    }

    $where[] = 's.sold_at < :end_date';
    $params['end_date'] = date(
        'Y-m-d',
        strtotime($endDate . ' +1 day')
    ) . ' 00:00:00';
}

if (
    $startDate !== '' &&
    $endDate !== '' &&
    $startDate > $endDate
) {
    http_response_code(400);
    exit('Tanggal awal tidak boleh melebihi tanggal akhir.');
}

if ($invoice !== '') {
    $where[] = 's.invoice_no LIKE :invoice';
    $params['invoice'] = '%' . $invoice . '%';
}

if (!$isAdmin) {
    $where[] = 's.cashier_id = :cashier_id';
    $params['cashier_id'] = (int) $user['id'];
} elseif ($cashierFilter !== null) {
    $where[] = 's.cashier_id = :filter_cashier';
    $params['filter_cashier'] = $cashierFilter;
}

$sql = "
    SELECT
        s.invoice_no,
        s.sold_at,
        u.full_name AS cashier_name,
        s.payment_method,
        s.subtotal,
        s.discount_total,
        s.grand_total,
        s.amount_paid,
        s.change_amount
    FROM sales s
    INNER JOIN users u ON u.id = s.cashier_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY s.sold_at DESC
";

$query = $pdo->prepare($sql);
$query->execute($params);
$sales = $query->fetchAll();

$filename = 'laporan-penjualan-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$output = fopen('php://output', 'w');

if ($output === false) {
    http_response_code(500);
    exit('Gagal membuat file CSV.');
}

fwrite($output, "\xEF\xBB\xBF");

$safeCsvValue = static function (mixed $value): string {
    $value = (string) $value;

    return preg_match('/^[\s]*[=+\-@]/u', $value)
        ? "'" . $value
        : $value;
};

fputcsv($output, [
    'No.',
    'Nomor Invoice',
    'Tanggal Transaksi',
    'Nama Kasir',
    'Metode Pembayaran',
    'Subtotal',
    'Total Diskon',
    'Total Penjualan',
    'Jumlah Dibayar',
    'Kembalian',
]);

foreach ($sales as $index => $sale) {
    fputcsv($output, [
        $index + 1,
        $safeCsvValue($sale['invoice_no']),
        $safeCsvValue($sale['sold_at']),
        $safeCsvValue($sale['cashier_name']),
        $safeCsvValue($sale['payment_method']),
        $sale['subtotal'],
        $sale['discount_total'],
        $sale['grand_total'],
        $sale['amount_paid'],
        $sale['change_amount'],
    ]);
}

fclose($output);
exit;