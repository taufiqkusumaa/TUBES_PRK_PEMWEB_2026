<?php
// Ambil path halaman yang sedang dibuka, contoh: /TUBES.../admin/obat/index.php
$halaman = $_SERVER['SCRIPT_NAME'] ?? '';

// Menu aktif ditentukan dari folder, jadi halaman tambah/edit ikut menyala
$aktifDashboard = basename(dirname($halaman)) === 'admin';
$aktifObat      = strpos($halaman, '/admin/obat/') !== false;
$aktifKategori  = strpos($halaman, '/admin/kategori/') !== false;
?>
<aside class="admin-sidebar">
    <div class="admin-logo">
        <a href="/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22/frontend/index.html" style="text-decoration: none; color: inherit;">
            KlikObat
        </a>
    </div>

    <nav>
        <ul class="admin-menu">
            <li>
                <a href="/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22/admin/index.php"
                   class="<?= $aktifDashboard ? 'active' : '' ?>">Dashboard</a>
            </li>
            <li>
                <a href="/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22/admin/obat/index.php"
                   class="<?= $aktifObat ? 'active' : '' ?>">Data Obat</a>
            </li>
            <li>
                <a href="/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22/admin/kategori/index.php"
                   class="<?= $aktifKategori ? 'active' : '' ?>">Data Kategori</a>
            </li>
            <li>
                <a href="/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22/admin/logout.php" style="color: #ef4444;">Keluar</a>
            </li>
        </ul>
    </nav>
</aside>