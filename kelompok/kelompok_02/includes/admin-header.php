<?php
$adminPageTitle = $adminPageTitle ?? 'Dashboard';
$adminActive = $adminActive ?? '';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminPageTitle) ?> | LABCAT Admin</title>
    <link rel="stylesheet" href="<?= url('assets/css/admin/category.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin/equipment.css') ?>">
    <script src="<?= url('assets/js/admin/category.js') ?>" defer></script>
    <script src="<?= url('assets/js/admin/equipment.js') ?>" defer></script>
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar" data-admin-sidebar>
        <a class="brand brand-sidebar" href="<?= url('admin/dashboard.php') ?>">
            <img class="brand-icon" src="<?= url('assets/images/flask-mark-white.svg') ?>" alt="">
            <span class="brand-copy">
                <strong>LABCAT</strong>
                <small>ADMIN PANEL</small>
            </span>
        </a>

        <div class="sidebar-label">MENU UTAMA</div>
        <nav class="sidebar-nav">
            <a class="<?= $adminActive === 'dashboard' ? 'active' : '' ?>" href="<?= url('admin/dashboard.php') ?>">
                <span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg></span> Dashboard
            </a>
            <a class="<?= $adminActive === 'equipment' ? 'active' : '' ?>" href="<?= url('admin/equipment.php') ?>">
                <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3M8 15h8"/></svg></span> Data Alat
            </a>
            <a class="<?= $adminActive === 'categories' ? 'active' : '' ?>" href="<?= url('admin/categories.php') ?>">
                <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M20 13 13 20 3 10V4h6l11 9Z"/><circle cx="7.5" cy="7.5" r="1"/></svg></span> Kategori
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <span class="avatar"><?= e(strtoupper(substr($_SESSION['admin']['name'] ?? 'A', 0, 1))) ?></span>
                <span>
                    <strong><?= e($_SESSION['admin']['name'] ?? 'Administrator') ?></strong>
                    <small>Administrator</small>
                </span>
            </div>
            <form method="post" action="<?= url('admin/logout.php') ?>">
                <?= csrf_field() ?>
                <button class="sidebar-logout" type="submit"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3M13 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg></span> Keluar</button>
            </form>
        </div>
    </aside>

    <section class="admin-main">
        <header class="admin-topbar">
            <button class="nav-toggle admin-nav-toggle" type="button" data-admin-toggle aria-label="Buka menu">☰</button>
            <div class="topbar-title"><?= e($adminPageTitle) ?></div>
            <a class="topbar-public" href="<?= url('index.php') ?>" target="_blank" rel="noopener">Lihat Website ↗</a>
        </header>

        <main class="admin-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>