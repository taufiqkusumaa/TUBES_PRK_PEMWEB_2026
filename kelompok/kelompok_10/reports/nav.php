<?php
declare(strict_types=1);

function reports_nav_items(bool $isAdmin): array
{
    $items = [
        ['key' => 'home', 'label' => 'Beranda', 'href' => 'dashboard.php'],
    ];

    if ($isAdmin && is_file(__DIR__ . '/../analytics/index.php')) {
        $items[] = ['key' => 'analytics', 'label' => 'Dashboard Analitik', 'href' => 'analytics/index.php'];
    }

    $items[] = ['key' => 'reports', 'label' => 'Laporan', 'href' => 'reports/index.php'];
    $items[] = ['key' => 'sales', 'label' => 'Laporan Penjualan', 'href' => 'reports/sales.php'];

    if ($isAdmin) {
        $items[] = ['key' => 'inventory', 'label' => 'Laporan Inventaris', 'href' => 'reports/inventory.php'];
        $items[] = ['key' => 'restock', 'label' => 'Rekomendasi Restock', 'href' => 'recommendations/restock.php'];
        $items[] = ['key' => 'discount', 'label' => 'Simulasi Diskon', 'href' => 'recommendations/discount.php'];
    }

    return $items;
}
