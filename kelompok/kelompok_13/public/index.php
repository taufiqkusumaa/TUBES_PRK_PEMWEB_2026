<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user'] = $_SESSION['user'] ?? [
    'nama' => 'Ezzra Rei Setiawan',
    'role' => 'admin'
];

$page = $_GET['page'] ?? 'dashboard';

// Peta halaman dan pemilik modul sesuai SOP Kelompok 13
$routes = [
    'dashboard' => ['file' => 'dashboard.php', 'title' => 'Dashboard & Cetak Dokumen', 'pic' => 'Ezzra Rei Setiawan'],
    'transaksi' => ['file' => 'transaksi.php', 'title' => 'Pemesanan Servis & Tracking Status', 'pic' => 'Taufiq Kusumawibowo'],
    'mekanik'   => ['file' => 'mekanik.php',   'title' => 'Work Order Teknisi & Progres Lapangan', 'pic' => 'Triandika'],
    'sparepart' => ['file' => 'sparepart.php', 'title' => 'Dokumentasi Komponen & Suku Cadang', 'pic' => 'Triandika'],
    'kendaraan' => ['file' => 'kendaraan.php', 'title' => 'Master Kategori & Tipe Kendaraan', 'pic' => 'Raffi Ichwan Pramudya'],
    'layanan'   => ['file' => 'layanan.php',   'title' => 'Master Paket Layanan Servis Mesin', 'pic' => 'Raffi Ichwan Pramudya'],
    'pelanggan' => ['file' => 'pelanggan.php', 'title' => 'Manajemen Pengguna & Hak Akses', 'pic' => 'Fareluna Nazwa Hafiez'],
];

$currentRoute = $routes[$page] ?? $routes['dashboard'];
$viewPath = __DIR__ . '/../app/Views/' . $currentRoute['file'];

// Jika file view milik anggota lain sudah ada dan tidak kosong, muat file tersebut
if (file_exists($viewPath) && filesize($viewPath) > 0) {
    require_once $viewPath;
} else {
    // Tampilkan halaman kerangka modul yang bersangkutan (bukan menduplikasi dashboard)
    $pageTitle = $currentRoute['title'];
    require_once __DIR__ . '/../app/Views/layouts/header.php';
    require_once __DIR__ . '/../app/Views/layouts/sidebar.php';
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
        <div>
            <h5 class="fw-bold mb-1" style="color: var(--text-primary);"><?= htmlspecialchars($currentRoute['title']) ?></h5>
            <div style="color: var(--text-secondary); font-size: 13px;">Modul Fungsional — PIC: <strong><?= htmlspecialchars($currentRoute['pic']) ?></strong></div>
        </div>
        <span class="plate-badge">app/Views/<?= htmlspecialchars($currentRoute['file']) ?></span>
    </div>

    <div class="surface-card p-4">
        <div class="fw-semibold mb-1" style="color: var(--text-primary);">Halaman Modul Siap Diintegrasikan</div>
        <p class="mb-0" style="color: var(--text-secondary); font-size: 13px;">
            Kerangka navigasi global (<code>header.php</code>, <code>sidebar.php</code>, <code>footer.php</code>) telah terhubung ke rute <code>?page=<?= htmlspecialchars($page) ?></code>. Konten tabel dan formulir CRUD pada halaman ini akan otomatis tampil setelah berkas <code>app/Views/<?= htmlspecialchars($currentRoute['file']) ?></code> di-merge dari branch <strong><?= htmlspecialchars($currentRoute['pic']) ?></strong> ke branch <code>staging</code>.
        </p>
    </div>
    <?php
    require_once __DIR__ . '/../app/Views/layouts/footer.php';
}