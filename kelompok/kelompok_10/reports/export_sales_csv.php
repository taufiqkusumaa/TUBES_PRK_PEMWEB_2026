<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
$cashierId = !empty($_GET['cashier_id']) ? (int) $_GET['cashier_id'] : null;

$params = [
    'start_date' => $startDate,
    'end_date' => $endDate,
];
$whereCashier = '';
if ($cashierId !== null && $cashierId > 0) {
    $whereCashier = ' AND s.cashier_id = :cashier_id';
    $params['cashier_id'] = $cashierId;
}

$query = "
    SELECT 
        s.invoice_no,
        s.sold_at,
        u.full_name AS cashier_name,
        s.subtotal,
        s.discount_total,
        s.grand_total,
        COALESCE(SUM(si.qty * si.cost_price_snapshot), 0) AS invoice_hpp,
        s.payment_method,
        s.status
    FROM sales s
    LEFT JOIN users u ON s.cashier_id = u.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE s.status = 'completed'
      AND DATE(s.sold_at) >= :start_date
      AND DATE(s.sold_at) <= :end_date
      {$whereCashier}
    GROUP BY s.id
    ORDER BY s.sold_at DESC
";
$stmt = db()->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

$filename = 'laporan_penjualan_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
// UTF-8 BOM agar Excel membuka karakter dengan benar
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header Kolom
fputcsv($output, [
    'No Invoice',
    'Waktu Transaksi',
    'Nama Kasir',
    'Subtotal (Rp)',
    'Diskon (Rp)',
    'Grand Total (Rp)',
    'HPP Modal (Rp)',
    'Laba Kotor (Rp)',
    'Metode Pembayaran',
    'Status'
]);

foreach ($sales as $s) {
    $grand = (float) $s['grand_total'];
    $hpp = (float) $s['invoice_hpp'];
    $laba = $grand - $hpp;

    fputcsv($output, [
        $s['invoice_no'],
        $s['sold_at'],
        $s['cashier_name'] ?? 'Kasir',
        number_format((float) $s['subtotal'], 0, '', ''),
        number_format((float) $s['discount_total'], 0, '', ''),
        number_format($grand, 0, '', ''),
        number_format($hpp, 0, '', ''),
        number_format($laba, 0, '', ''),
        strtoupper((string) $s['payment_method']),
        $s['status'],
    ]);
}

fclose($output);
exit;

