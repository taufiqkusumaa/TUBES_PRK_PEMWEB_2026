<?php
require_once __DIR__ . '/../config/auth.php';
$pageTitle = $pageTitle ?? 'SisaSense';
$activePage = $activePage ?? '';
$headerActions = $headerActions ?? '';

$currentUser = current_user();

if (empty($navItems)) {
    if ($currentUser) {
        if ($currentUser['role'] === 'admin') {
            $navItems = [
                ['key' => 'dashboard', 'label' => 'Beranda', 'href' => 'dashboard.php'],
                ['key' => 'pos', 'label' => 'Kasir POS', 'href' => 'sales/pos.php'],
                ['key' => 'products', 'label' => 'Katalog Produk', 'href' => 'products/index.php'],
                ['key' => 'categories', 'label' => 'Kategori', 'href' => 'products/categories.php'],
                ['key' => 'inventory', 'label' => 'Stok & Batch', 'href' => 'inventory/index.php'],
                ['key' => 'sales_history', 'label' => 'Riwayat Transaksi', 'href' => 'sales/history.php'],
                ['key' => 'analytics', 'label' => 'Analisis & Laporan', 'href' => 'reports/index.php'],
                ['key' => 'restock', 'label' => 'Saran Restok', 'href' => 'reports/restock_recommendations.php'],
            ];
        } elseif ($currentUser['role'] === 'cashier') {
            $navItems = [
                ['key' => 'dashboard', 'label' => 'Beranda', 'href' => 'dashboard.php'],
                ['key' => 'pos', 'label' => 'Kasir POS', 'href' => 'sales/pos.php'],
                ['key' => 'products', 'label' => 'Katalog Belanja', 'href' => 'products/index.php'],
                ['key' => 'sales_history', 'label' => 'Riwayat Transaksi', 'href' => 'sales/history.php'],
                ['key' => 'inventory', 'label' => 'Cek Stok', 'href' => 'inventory/index.php'],
            ];
        } else {
            $navItems = [
                ['key' => 'dashboard', 'label' => 'Beranda', 'href' => 'dashboard.php'],
                ['key' => 'products', 'label' => 'Katalog Belanja', 'href' => 'products/index.php'],
                ['key' => 'my_orders', 'label' => 'Riwayat Pesanan', 'href' => 'sales/my_orders.php'],
            ];
        }
    } else {
        $navItems = [
            ['key' => 'dashboard', 'label' => 'Beranda', 'href' => 'dashboard.php'],
            ['key' => 'products', 'label' => 'Katalog Produk', 'href' => 'products/index.php'],
            ['key' => 'categories', 'label' => 'Kategori', 'href' => 'products/categories.php'],
            ['key' => 'login', 'label' => 'Masuk Kasir / Akun', 'href' => 'login.php'],
        ];
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | SisaSense Pangan &amp; POS UMKM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="<?= empty($currentUser) && in_array($activePage, ['login', 'register']) ? 'auth-body' : '' ?>">

<!-- ======================================================== -->
<!-- PRIMARY TOP NAVBAR PERSIS NUTRAMLA (3 ZONES HORIZONTAL) -->
<!-- Ref: media_1794556281525_531c7c35.png                   -->
<!-- ======================================================== -->
<header class="nutramla-header no-print">
    <div class="nutramla-header-inner">
        <!-- ZONA 1: HORIZONTAL MENU LINKS (KIRI) -->
        <nav class="nutramla-nav" aria-label="Navigasi Utama">
            <?php foreach ($navItems as $item): ?>
                <a class="nutramla-nav-link <?= ($item['key'] ?? '') === $activePage ? 'is-active' : '' ?>" href="<?= e(app_url($item['href'])) ?>">
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- ZONA 2: FRAMED RECTANGULAR BADGE LOGO (TENGAH) -->
        <div class="nutramla-brand-center">
            <a href="<?= e(app_url('dashboard.php')) ?>" class="nutramla-badge-logo" title="SisaSense - Pangan &amp; POS UMKM">
                <div class="nutramla-badge-frame">
                    <div class="nutramla-badge-top">
                        <span class="nutramla-badge-corp">UMKM NUSANTARA<sup style="font-size: 8px;">&reg;</sup></span>
                        <span class="nutramla-badge-corp-sub">&amp; Pangan Alami Indonesia</span>
                    </div>
                    <div class="nutramla-badge-bottom">
                        <span class="nutramla-badge-brand">SisaSense<sup style="font-size: 9px; vertical-align: super;">&reg;</sup></span>
                    </div>
                </div>
            </a>
        </div>

        <!-- ZONA 3: UTILITY ACTION ICONS (KANAN) -->
        <div class="nutramla-actions">
            <!-- 1. Search Icon -->
            <div class="nutramla-action-item">
                <a href="<?= e(app_url('products/index.php')) ?>" class="nutramla-icon-btn" title="Cari Produk / SKU" aria-label="Pencarian">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </a>
            </div>

            <!-- 2. User Account Icon with Dropdown -->
            <div class="nutramla-action-item nutramla-user-wrap">
                <?php if ($currentUser): ?>
                    <button type="button" class="nutramla-icon-btn nutramla-user-btn" id="nutramlaUserBtn" aria-label="Menu Pengguna" aria-expanded="false">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </button>
                    <!-- Floating User Dropdown Menu -->
                    <div class="nutramla-dropdown-menu" id="nutramlaUserMenu">
                        <div class="nutramla-dropdown-header">
                            <div class="nutramla-dropdown-avatar">
                                <?= e(mb_strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1))) ?>
                            </div>
                            <div class="nutramla-dropdown-info">
                                <strong><?= e($currentUser['name']) ?></strong>
                                <span class="nutramla-role-badge"><?= e(user_role_label($currentUser)) ?></span>
                            </div>
                        </div>
                        <div class="nutramla-dropdown-divider"></div>
                        <a href="<?= e(app_url('dashboard.php')) ?>" class="nutramla-dropdown-link">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            Beranda Utama
                        </a>
                        <?php if ($currentUser['role'] !== 'customer'): ?>
                            <a href="<?= e(app_url('sales/pos.php')) ?>" class="nutramla-dropdown-link">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                                Kasir POS Kilat
                            </a>
                        <?php else: ?>
                            <a href="<?= e(app_url('sales/my_orders.php')) ?>" class="nutramla-dropdown-link">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Riwayat Pesanan Saya
                            </a>
                        <?php endif; ?>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <a href="<?= e(app_url('reports/index.php')) ?>" class="nutramla-dropdown-link">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
                                Laporan &amp; Analitik
                            </a>
                        <?php endif; ?>
                        <div class="nutramla-dropdown-divider"></div>
                        <form method="post" action="<?= e(app_url('logout.php')) ?>" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <button type="submit" class="nutramla-dropdown-logout-btn">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                                Keluar dari Akun
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <a href="<?= e(app_url('login.php')) ?>" class="nutramla-icon-btn" title="Masuk Akun" aria-label="Masuk Akun">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>

            <!-- 3. Wishlist / Alert Icon with Lime Green Badge -->
            <div class="nutramla-action-item">
                <a href="<?= e($currentUser && $currentUser['role'] === 'admin' ? app_url('reports/restock_recommendations.php') : app_url('products/index.php')) ?>" class="nutramla-icon-btn" title="<?= $currentUser && $currentUser['role'] === 'admin' ? 'Peringatan Stok & Restok' : 'Wishlist Favorit' ?>" aria-label="Wishlist">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                    </svg>
                    <span class="nutramla-count-badge"><?= $currentUser && $currentUser['role'] === 'admin' ? '3' : '0' ?></span>
                </a>
            </div>

            <!-- 4. Shopping Bag / Cart Icon with Lime Green Badge -->
            <div class="nutramla-action-item">
                <a href="<?= e(app_url('sales/pos.php')) ?>" class="nutramla-icon-btn nutramla-cart-btn" title="Kasir POS / Keranjang Belanja" aria-label="Keranjang Belanja">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <span class="nutramla-count-badge" id="nutramlaCartBadge">0</span>
                </a>
            </div>
        </div>
    </div>
</header>

<div class="app-shell">
    <div class="main-column">
        <?php if ($currentUser && !in_array($activePage, ['dashboard', 'pos'])): ?>
            <!-- Sleek Sub-Header for Specific Pages -->
            <div class="sub-header-bar no-print">
                <div class="sub-header-inner">
                    <div class="sub-header-title">
                        <h2><?= e($pageTitle) ?></h2>
                    </div>
                    <div class="sub-header-actions">
                        <span class="badge" style="background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; font-size: 11.5px; padding: 4px 12px; font-weight: 750; border-radius: 9999px;">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #16A34A; display: inline-block;"></span>
                            Toko Buka
                        </span>
                        <?= $headerActions ?>
                        <span class="topbar-date"><?= e(date('d M Y')) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <main class="page-shell<?= $currentUser ? '' : ' page-shell-auth' ?>">
            <?php if ($flashMessage = take_flash()): ?>
                <div class="notice notice-<?= e($flashMessage['type']) ?>" role="status">
                    <span><?= e($flashMessage['message']) ?></span>
                </div>
            <?php endif; ?>
