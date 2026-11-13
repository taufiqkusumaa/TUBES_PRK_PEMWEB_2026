<?php
// ==========================================
// 1. MEMULAI SESSION
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 2. MENGAMBIL BASE URL
// ==========================================
if (!isset($base_url)) {
    $base_url = '/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_04';
}

// ==========================================
// 3. PROTEKSI HALAMAN (WAJIB LOGIN)
// ==========================================
if (!isset($_SESSION['user']) && !isset($_SESSION['username'])) {
    header("Location: " . $base_url . "/login.php");
    exit;
}

// ==========================================
// 4. MENGAMBIL DATA PENGGUNA
// ==========================================
$user = $_SESSION['user'] ?? [];

$nama_user = $user['nama_lengkap']
    ?? $_SESSION['nama_lengkap']
    ?? $user['username']
    ?? $_SESSION['username']
    ?? 'Pengguna';

$role_user = strtolower(trim($user['role'] ?? ($_SESSION['role'] ?? '')));

// ==========================================
// 5. MENENTUKAN HAK AKSES ROLE
// ==========================================
$is_admin    = ($role_user === 'admin');
$is_peternak = ($role_user === 'peternak');
$is_dokter   = ($role_user === 'dokter');

// ==========================================
// 6. MENGAMBIL ALAMAT HALAMAN & MENU AKTIF
// ==========================================
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
$bagian_kesehatan_aktif = strpos($current_uri, '/modul/kesehatan/') !== false || strpos($current_uri, '/modul/bobot/') !== false;
$bagian_pakan_aktif = strpos($current_uri, '/modul/pakan/') !== false || strpos($current_uri, '/modul/log_pakan/') !== false;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sistem Log Ternak - Kelompok 04</title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url; ?>/assets/css/theme.css?v=1">

    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .app-shell {
            min-height: 100vh;
        }

        .app-sidebar {
            --bs-offcanvas-width: 17rem;
            background: var(--brand-dark);
        }

        .app-sidebar .offcanvas-body {
            display: flex;
            flex-direction: column;
        }

        .app-sidebar .nav-link {
            border-radius: 0.5rem;
            color: rgba(255, 255, 255, 0.82);
            padding: 0.65rem 0.75rem;
        }

        .app-sidebar .nav-link:hover,
        .app-sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
        }

        .app-sidebar .nav-section-toggle {
            align-items: center;
            background: transparent;
            border: 0;
            display: flex;
            justify-content: space-between;
            text-align: left;
            width: 100%;
        }

        .app-sidebar .nav-section-toggle::after {
            font-family: "bootstrap-icons";
            content: "\f282";
            transition: transform 0.2s ease;
        }

        .app-sidebar .nav-section-toggle[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }

        .app-sidebar .nav-section-toggle.section-active {
            color: #fff;
        }

        .app-sidebar .nav-section-label {
            color: rgba(255, 255, 255, 0.82);
            font-weight: 400;
            letter-spacing: normal;
            margin: 0.35rem 0 0;
            text-transform: none;
        }

        .app-sidebar .nav-sub-link {
            padding-left: 2.4rem;
        }

        .app-main {
            min-width: 0;
        }

        .app-content {
            flex: 1 0 auto;
        }

        @media (min-width: 992px) {
            .app-sidebar {
                display: flex !important;
                flex: 0 0 17rem;
                height: 100vh;
                position: sticky;
                top: 0;
                transform: none !important;
                visibility: visible !important;
                background-color: var(--brand-dark) !important;
            }
        }
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100">

<div class="app-shell d-flex flex-grow-1">
  <aside class="offcanvas-lg offcanvas-start offcanvas text-bg-success app-sidebar" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header border-bottom border-light border-opacity-25">
      <a class="navbar-brand fw-bold text-white" id="appSidebarLabel" href="<?= htmlspecialchars($base_url); ?>/index.php">
        <i class="bi bi-box-seam me-2"></i>Log Ternak K04
      </a>
      <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Tutup menu"></button>
    </div>
    <div class="offcanvas-body p-3">
      <nav class="nav flex-column" aria-label="Navigasi utama">
        <!-- DASHBOARD: SEMUA ROLE -->
        <a class="nav-link <?= (strpos($current_uri, '/modul/') === false) ? 'active' : ''; ?>" href="<?= htmlspecialchars($base_url); ?>/index.php">
          <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>

        <!-- USER: KHUSUS ADMIN -->
        <?php if ($is_admin): ?>
        <a class="nav-link <?= (strpos($current_uri, '/modul/user/') !== false) ? 'active' : ''; ?>" href="<?= htmlspecialchars($base_url); ?>/modul/user/index.php">
          <i class="bi bi-people me-2"></i>User
        </a>
        <?php endif; ?>

        <!-- KANDANG: ADMIN DAN PETERNAK -->
        <?php if ($is_admin || $is_peternak): ?>
        <a class="nav-link <?= (strpos($current_uri, '/modul/kandang/') !== false) ? 'active' : ''; ?>" href="<?= htmlspecialchars($base_url); ?>/modul/kandang/index.php">
          <i class="bi bi-house-door me-2"></i>Kandang
        </a>
        <?php endif; ?>

        <!-- TERNAK: SEMUA ROLE -->
        <a class="nav-link <?= (strpos($current_uri, '/modul/ternak/') !== false) ? 'active' : ''; ?>" href="<?= htmlspecialchars($base_url); ?>/modul/ternak/index.php">
          <i class="bi bi-box-seam me-2"></i>Ternak
        </a>

        <!-- KESEHATAN (REKAM MEDIS: ADMIN & DOKTER, BOBOT: SEMUA ROLE) -->
        <?php if ($is_admin || $is_peternak || $is_dokter): ?>
        <button class="nav-link nav-section-label nav-section-toggle <?= $bagian_kesehatan_aktif ? 'section-active' : ''; ?>"
                type="button" data-bs-toggle="collapse" data-bs-target="#menuKesehatan"
                aria-expanded="<?= $bagian_kesehatan_aktif ? 'true' : 'false'; ?>" aria-controls="menuKesehatan">
          <span><i class="bi bi-heart-pulse me-2" aria-hidden="true"></i>Kesehatan</span>
        </button>
        <div class="collapse <?= $bagian_kesehatan_aktif ? 'show' : ''; ?>" id="menuKesehatan">
          <?php if ($is_admin || $is_dokter): ?>
          <a class="nav-link nav-sub-link <?= (strpos($current_uri, '/modul/kesehatan/') !== false) ? 'active' : ''; ?>" href="<?= htmlspecialchars($base_url); ?>/modul/kesehatan/index.php">
            <i class="bi bi-clipboard2-pulse me-2"></i>Rekam Medis
          </a>
          <?php endif; ?>
          <a class="nav-link nav-sub-link <?= (strpos($current_uri, '/modul/bobot/') !== false) ? 'active' : ''; ?>" href="<?= htmlspecialchars($base_url); ?>/modul/bobot/index.php">
            <i class="bi bi-speedometer me-2"></i>Bobot
          </a>
        </div>
        <?php endif; ?>

        <!-- PAKAN: ADMIN DAN PETERNAK -->
        <?php if ($is_admin || $is_peternak): ?>
        <button class="nav-link nav-section-label nav-section-toggle <?= $bagian_pakan_aktif ? 'section-active' : ''; ?>"
                type="button" data-bs-toggle="collapse" data-bs-target="#menuPakan"
                aria-expanded="<?= $bagian_pakan_aktif ? 'true' : 'false'; ?>" aria-controls="menuPakan">
          <span><i class="bi bi-basket me-2" aria-hidden="true"></i>Pakan</span>
        </button>
        <div class="collapse <?= $bagian_pakan_aktif ? 'show' : ''; ?>" id="menuPakan">
          <a class="nav-link nav-sub-link <?= (strpos($current_uri, '/modul/pakan/') !== false) ? 'active' : ''; ?>" href="<?= htmlspecialchars($base_url); ?>/modul/pakan/index.php">
            <i class="bi bi-box-seam me-2"></i>Data Pakan Ternak
          </a>
          <a class="nav-link nav-sub-link <?= (strpos($current_uri, '/modul/log_pakan/') !== false) ? 'active' : ''; ?>" href="<?= htmlspecialchars($base_url); ?>/modul/log_pakan/index.php">
            <i class="bi bi-journal-text me-2"></i>Log Pemberian Pakan
          </a>
        </div>
        <?php endif; ?>

        <!-- LAPORAN: SEMUA ROLE -->
        <span class="nav-section-label">Lainnya</span>
        <a class="nav-link <?= (strpos($current_uri, '/modul/laporan/') !== false) ? 'active' : ''; ?>" href="<?= htmlspecialchars($base_url); ?>/modul/laporan/index.php">
          <i class="bi bi-file-earmark-text me-2"></i>Laporan
        </a>
      </nav>

      <!-- INFORMASI PENGGUNA & LOGOUT -->
      <div class="mt-auto pt-3 border-top border-light border-opacity-25">
        <div class="px-2 mb-2 text-white-50 small">
          <i class="bi bi-person-circle me-1 text-white"></i>
          <strong class="text-white"><?= htmlspecialchars($nama_user); ?></strong>
          <?php if ($role_user !== ''): ?>
            <span class="badge bg-light text-success ms-1"><?= htmlspecialchars(ucfirst($role_user)); ?></span>
          <?php endif; ?>
        </div>
        <a class="nav-link" href="<?= htmlspecialchars($base_url); ?>/logout.php"
           onclick="return confirm('Apakah Anda yakin ingin logout?')">
          <i class="bi bi-box-arrow-right me-2"></i>Logout
        </a>
      </div>
    </div>
  </aside>

  <div class="app-main d-flex flex-column flex-grow-1">
    <header class="d-lg-none bg-white border-bottom px-3 py-2">
      <button class="btn btn-success" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Buka menu navigasi">
        <i class="bi bi-list me-1"></i>Menu
      </button>
    </header>
    <main class="app-content container-fluid px-3 px-lg-4 py-4">