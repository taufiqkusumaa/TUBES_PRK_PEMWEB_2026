<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_login();

$items = db()->query("
    SELECT 
        p.sku,
        p.name AS product_name,
        c.name AS category_name,
        p.unit,
        p.min_stock,
        p.selling_price,
        COALESCE(SUM(sb.qty_remaining), 0) AS total_stock,
        COALESCE(AVG(sb.cost_per_unit), 0) AS avg_cost,
        COALESCE(SUM(sb.qty_remaining * sb.cost_per_unit), 0) AS total_cost_value,
        COALESCE(SUM(sb.qty_remaining * p.selling_price), 0) AS total_retail_value
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN stock_batches sb ON p.id = sb.product_id AND sb.qty_remaining > 0
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY total_cost_value DESC, p.name ASC
")->fetchAll();

$filename = 'laporan_stok_persediaan_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
// UTF-8 BOM
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, [
    'SKU',
    'Nama Produk',
    'Kategori',
    'Satuan',
    'Stok Fisik Tersedia',
    'Batas Minimum Stok',
    'Rerata HPP Modal (Rp)',
    'Harga Jual (Rp)',
    'Total Valuasi HPP (Rp)',
    'Total Valuasi Retail (Rp)'
]);

foreach ($items as $item) {
    fputcsv($output, [
        $item['sku'],
        $item['product_name'],
        $item['category_name'] ?? 'Umum',
        $item['unit'],
        (int) $item['total_stock'],
        (int) $item['min_stock'],
        number_format((float) $item['avg_cost'], 0, '', ''),
        number_format((float) $item['selling_price'], 0, '', ''),
        number_format((float) $item['total_cost_value'], 0, '', ''),
        number_format((float) $item['total_retail_value'], 0, '', ''),
    ]);
}

fclose($output);
exit;

