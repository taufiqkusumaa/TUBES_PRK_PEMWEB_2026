<?php
declare(strict_types=1);

function inventory_nav_items(): array
{
    return [
        ['key' => 'dashboard',  'label' => 'Dashboard',    'href' => 'dashboard.php'],
        ['key' => 'categories', 'label' => 'Kategori',     'href' => 'products/categories.php'],
        ['key' => 'products',   'label' => 'Produk',       'href' => 'products/index.php'],
        ['key' => 'stock_in',   'label' => 'Stok masuk',   'href' => 'inventory/stock_in.php'],
        ['key' => 'stock_out',  'label' => 'Stok keluar',  'href' => 'inventory/stock_out.php'],
        ['key' => 'batches',    'label' => 'Batch',        'href' => 'inventory/batches.php'],
        ['key' => 'movements',  'label' => 'Riwayat stok', 'href' => 'inventory/movements.php'],
        ['key' => 'alerts',     'label' => 'Peringatan',   'href' => 'inventory/alerts.php'],
    ];
}

function inventory_css(): string
{
    return '<link rel="stylesheet" href="../assets/css/inventory.css">';
}

function inventory_redirect(string $relativeUrl): never
{
    header('Location: ' . $relativeUrl);
    exit;
}

function inventory_rupiah(string|int|float|null $value): string
{
    return 'Rp' . number_format((float) $value, 0, ',', '.');
}

function inventory_movement_types(): array
{
    return [
        'IN'             => ['Stok masuk',       'badge-success', '+'],
        'OUT'            => ['Stok keluar',      'badge-warning', '-'],
        'SALE'           => ['Penjualan',        'badge-muted',   '-'],
        'RETURN'         => ['Retur',            'badge-success', '+'],
        'ADJUSTMENT_IN'  => ['Penyesuaian (+)',  'badge-success', '+'],
        'ADJUSTMENT_OUT' => ['Penyesuaian (-)',  'badge-warning', '-'],
        'EXPIRED'        => ['Kedaluwarsa',      'badge-danger',  '-'],
    ];
}

function inventory_out_reasons(): array
{
    return [
        'rusak'       => ['OUT',            'Rusak'],
        'hilang'      => ['OUT',            'Hilang'],
        'pemakaian'   => ['OUT',            'Pemakaian internal'],
        'kedaluwarsa' => ['EXPIRED',        'Kedaluwarsa'],
        'penyesuaian' => ['ADJUSTMENT_OUT', 'Penyesuaian stok'],
    ];
}
