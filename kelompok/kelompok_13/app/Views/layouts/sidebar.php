<?php
$currentPage = $_GET['page'] ?? 'dashboard';
$userRole = strtolower($_SESSION['user']['role'] ?? 'admin');
?>
<aside class="sidebar no-print" id="appSidebar">
    <div class="sidebar-section">Menu Utama</div>
    <ul class="nav flex-column">
        <?php if ($userRole === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard & Cetak</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (in_array($userRole, ['customer', 'pelanggan', 'admin'])): ?>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'transaksi' ? 'active' : '' ?>" href="index.php?page=transaksi">
                <i class="bi bi-calendar2-check"></i>
                <span>Pemesanan & Tracking</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <?php if (in_array($userRole, ['teknisi', 'mekanik', 'admin'])): ?>
    <div class="sidebar-section">Operasional Bengkel</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'mekanik' ? 'active' : '' ?>" href="index.php?page=mekanik">
                <i class="bi bi-clipboard-check"></i>
                <span>Work Order Teknisi</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'sparepart' ? 'active' : '' ?>" href="index.php?page=sparepart">
                <i class="bi bi-box-seam"></i>
                <span>Dokumentasi & Suku Cadang</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>

    <?php if ($userRole === 'admin'): ?>
    <div class="sidebar-section">Master Data & Sistem</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'kendaraan' ? 'active' : '' ?>" href="index.php?page=kendaraan">
                <i class="bi bi-truck-front"></i>
                <span>Master Kendaraan</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'layanan' ? 'active' : '' ?>" href="index.php?page=layanan">
                <i class="bi bi-sliders"></i>
                <span>Paket Layanan Servis</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'pelanggan' ? 'active' : '' ?>" href="index.php?page=pelanggan">
                <i class="bi bi-people"></i>
                <span>Manajemen Pengguna</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>
</aside>

<main class="main-content">