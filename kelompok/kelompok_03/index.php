<?php
// Memulai sesi agar dapat membaca status login pengguna
session_start();

// Mengatur zona waktu default ke Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

// Menghubungkan ke database klinik_antrian menggunakan PDO
require_once __DIR__ . '/config/koneksi.php';

// Memeriksa apakah pengguna saat ini sudah login
$sudahLogin = isset($_SESSION['user_id']);
$userRole   = $_SESSION['role'] ?? '';

// Array nama hari dan bulan dalam Bahasa Indonesia untuk format tanggal yang ramah pengguna
$hariIndonesia = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$bulanIndonesia = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Menentukan nama hari ini dan tanggal hari ini dalam format Y-m-d untuk query database
$hariIni = $hariIndonesia[(int) date('N')];
$tanggalDb = date('Y-m-d');
$tanggalTampil = $hariIni . ', ' . date('j') . ' ' . $bulanIndonesia[(int) date('n')] . ' ' . date('Y');

// Mengambil seluruh data dokter yang terdaftar di database
try {
    // Query untuk mengambil seluruh dokter beserta spesialis, kuota harian, dan jadwal praktiknya
    $stmtDokter = $pdo->query('SELECT id, nama, spesialis, kuota_per_hari, hari_praktik FROM dokter ORDER BY spesialis ASC, nama ASC');
    $semuaDokter = $stmtDokter->fetchAll();
} catch (PDOException $e) {
    // Mencatat log jika terjadi kegagalan query dokter
    error_log('[index.php] Gagal memuat data dokter: ' . $e->getMessage());
    $semuaDokter = [];
}

// Memproses status dokter hari ini (menghitung sisa kuota antrean dan jadwal praktik)
$daftarDokter = [];
$daftarSpesialis = [];

// Menyiapkan prepared statement untuk menghitung jumlah antrean terdaftar pada hari ini
$stmtHitungAntrian = $pdo->prepare('SELECT COUNT(*) FROM antrian WHERE dokter_id = ? AND tanggal = ?');

// Menyiapkan prepared statement untuk mengecek apakah pasien yang login sudah mengambil antrean dokter ini
$stmtCekAntrianSaya = null;
if ($sudahLogin && $userRole === 'pasien') {
    $stmtCekAntrianSaya = $pdo->prepare('SELECT nomor, status FROM antrian WHERE user_id = ? AND dokter_id = ? AND tanggal = ? LIMIT 1');
}

// Melakukan iterasi pada setiap dokter untuk menentukan status real-time hari ini
foreach ($semuaDokter as $doc) {
    // Memasukkan spesialis ke daftar pilihan filter poli
    if (!in_array($doc['spesialis'], $daftarSpesialis, true)) {
        $daftarSpesialis[] = $doc['spesialis'];
    }

    // Memeriksa apakah hari ini termasuk dalam jadwal hari praktik dokter
    $hariPraktikArray = array_map(
        fn($h) => mb_strtolower(trim($h)),
        explode(',', $doc['hari_praktik'])
    );
    $apakahPraktikHariIni = in_array(mb_strtolower($hariIni), $hariPraktikArray, true);

    // Menghitung berapa nomor antrean yang sudah diambil untuk dokter ini pada tanggal hari ini
    $stmtHitungAntrian->execute([$doc['id'], $tanggalDb]);
    $kuotaTerpakai = (int) $stmtHitungAntrian->fetchColumn();

    // Menghitung sisa kuota yang masih dapat diperebutkan oleh pasien
    $sisaKuota = max(0, (int) $doc['kuota_per_hari'] - $kuotaTerpakai);

    // Menentukan status praktik dan label warna badge
    if (!$apakahPraktikHariIni) {
        $statusKode  = 'belum';
        $statusLabel = 'Tidak Praktik';
    } elseif ($sisaKuota <= 0) {
        $statusKode  = 'penuh';
        $statusLabel = 'Kuota Penuh';
    } else {
        $statusKode  = 'praktik';
        $statusLabel = 'Sedang Praktik';
    }

    // Memeriksa apakah pasien yang sedang login telah memiliki nomor antrean ke dokter ini
    $antrianSaya = null;
    if ($stmtCekAntrianSaya) {
        $stmtCekAntrianSaya->execute([$_SESSION['user_id'], $doc['id'], $tanggalDb]);
        $antrianSaya = $stmtCekAntrianSaya->fetch();
    }

    // Menyimpan data dokter yang sudah diolah ke dalam array untuk dirender ke tabel HTML
    $daftarDokter[] = [
        'id'            => (int) $doc['id'],
        'nama'          => $doc['nama'],
        'spesialis'     => $doc['spesialis'],
        'hari_praktik'  => $doc['hari_praktik'],
        'kuota_total'   => (int) $doc['kuota_per_hari'],
        'sisa_kuota'    => $sisaKuota,
        'status_kode'   => $statusKode,
        'status_label'  => $statusLabel,
        'bisa_diambil'  => $apakahPraktikHariIni && ($sisaKuota > 0),
        'antrian_saya'  => $antrianSaya,
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Dokter Hari Ini - Sistem Antrian Klinik</title>

    <!-- Google Fonts untuk tipografi halaman depan -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- CSS Utama Sistem Antrian Klinik -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="halaman-depan">

<!-- Navigasi Atas (Topbar) -->
<header class="topbar">
    <a href="index.php" class="brand fw-bold">Klinik FK Unila</a>
    <nav aria-label="Menu utama">
        <?php if ($sudahLogin): ?>
            <?php if ($userRole === 'admin'): ?>
                <!-- Tombol navigasi jika yang login adalah admin -->
                <a href="admin/admin.php">Dashboard Admin</a>
            <?php else: ?>
                <!-- Tombol navigasi jika yang login adalah pasien -->
                <a href="pasien/pasien.php">Antrean Saya</a>
            <?php endif; ?>
            <a href="auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Keluar</a>
        <?php else: ?>
            <!-- Tombol navigasi untuk pengunjung yang belum login -->
            <a href="auth/login.php">Masuk</a>
            <a href="auth/register.php" class="tombol-kecil">Daftar Akun</a>
        <?php endif; ?>
    </nav>
</header>

<!-- Konten Utama Halaman Depan -->
<main class="wadah">
    <section class="pembuka">
        <!-- Menampilkan tanggal saat ini secara dinamis -->
        <p class="tanggal"><?= htmlspecialchars($tanggalTampil, ENT_QUOTES, 'UTF-8') ?></p>
        <h1>Dokter Praktik Hari Ini</h1>
        <p>Lihat jadwal dokter yang bertugas, periksa sisa kuota, dan ambil nomor antrean secara online tanpa perlu antre di klinik.</p>
    </section>

    <!-- Filter Poli/Spesialis -->
    <div class="filter">
        <label for="filter-poli">Filter Spesialis / Poli:</label>
        <select id="filter-poli">
            <option value="">Semua Poli / Spesialis</option>
            <?php foreach ($daftarSpesialis as $spesialis): ?>
                <option value="<?= htmlspecialchars($spesialis, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($spesialis, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (empty($daftarDokter)): ?>
        <!-- Ditampilkan jika belum ada data dokter di database -->
        <p class="kosong">Belum ada data dokter yang terdaftar di sistem.</p>
    <?php else: ?>
    <!-- Tabel Informasi Dokter dan Kuota Antrean -->
    <table class="tabel-dokter">
        <thead>
            <tr>
                <th scope="col">Nama Dokter</th>
                <th scope="col">Spesialis / Poli</th>
                <th scope="col">Jadwal Praktik</th>
                <th scope="col">Sisa Kuota</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="sr-only">Aksi</span></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($daftarDokter as $d): ?>
            <tr data-poli="<?= htmlspecialchars($d['spesialis'], ENT_QUOTES, 'UTF-8') ?>">
                <!-- Kolom Nama Dokter -->
                <td data-label="Dokter">
                    <strong><?= htmlspecialchars($d['nama'], ENT_QUOTES, 'UTF-8') ?></strong>
                </td>

                <!-- Kolom Spesialis -->
                <td data-label="Spesialis">
                    <?= htmlspecialchars($d['spesialis'], ENT_QUOTES, 'UTF-8') ?>
                </td>

                <!-- Kolom Jadwal Hari Praktik -->
                <td data-label="Jadwal Praktik">
                    <?= htmlspecialchars(str_replace(',', ', ', $d['hari_praktik']), ENT_QUOTES, 'UTF-8') ?>
                </td>

                <!-- Kolom Sisa Kuota -->
                <td data-label="Sisa Kuota">
                    <strong><?= (int) $d['sisa_kuota'] ?></strong> / <?= (int) $d['kuota_total'] ?>
                </td>

                <!-- Kolom Status Praktik -->
                <td data-label="Status">
                    <span class="lencana lencana-<?= htmlspecialchars($d['status_kode'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($d['status_label'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </td>

                <!-- Kolom Aksi Ambil Nomor Antrean -->
                <td class="aksi">
                    <?php if (!empty($d['antrian_saya'])): ?>
                        <!-- Jika pasien sudah memiliki nomor antrean ke dokter ini -->
                        <span class="lencana lencana-praktik">
                            Antrean Anda: #<?= (int) $d['antrian_saya']['nomor'] ?>
                        </span>
                        <a href="pasien/pasien.php" class="tombol-kecil" style="margin-left: 6px;">Lihat Tiket</a>
                    <?php elseif (!$d['bisa_diambil']): ?>
                        <!-- Jika kuota habis atau bukan jadwal praktik dokter -->
                        <span class="nonaktif"><?= htmlspecialchars($d['status_label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php else: ?>
                        <!-- Tombol ambil nomor antrean -->
                        <?php if (!$sudahLogin): ?>
                            <!-- Pasien belum login: diarahkan ke halaman login terlebih dahulu -->
                            <a href="auth/login.php" class="tombol-kecil">Masuk untuk Antre</a>
                        <?php elseif ($userRole === 'pasien'): ?>
                            <!-- Pasien sudah login: form POST langsung ke script backend ambil_antrian.php -->
                            <form action="pasien/ambil_antrian.php" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin mengambil nomor antrean untuk <?= htmlspecialchars($d['nama'], ENT_QUOTES, 'UTF-8') ?>?');">
                                <input type="hidden" name="dokter_id" value="<?= (int) $d['id'] ?>">
                                <button type="submit" class="tombol-kecil">Ambil Nomor</button>
                            </form>
                        <?php else: ?>
                            <!-- Admin login: tautan menuju dashboard admin -->
                            <a href="admin/admin.php" class="tombol-kecil">Panel Admin</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Pesan jika hasil pencarian/filter dropdown poli tidak ditemukan -->
    <p id="pesan-filter" class="kosong" hidden>Tidak ada dokter di spesialis ini yang sesuai filter.</p>
    <?php endif; ?>
</main>

<!-- JavaScript untuk filter dropdown poli dan interaktivitas -->
<script src="pasien/pasien.js"></script>
</body>
</html>
