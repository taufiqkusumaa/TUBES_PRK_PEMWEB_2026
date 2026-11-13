<?php
// Memastikan sesi telah berjalan dan pengguna sudah login sebagai pasien
require_once __DIR__ . '/../includes/cek_login.php';

// Memeriksa hak akses, jika role adalah admin maka diarahkan ke halaman panel admin
if ($_SESSION['role'] !== 'pasien') {
    header('Location: ../admin/admin.php');
    exit;
}

// Mengatur zona waktu WIB
date_default_timezone_set('Asia/Jakarta');

// Menangkap notifikasi pesan kilat (flash message) dari sesi jika baru saja mengambil antrean
$flashPesan = $_SESSION['flash_pesan'] ?? null;
$flashTipe  = $_SESSION['flash_tipe'] ?? 'info';
// Menghapus flash message agar tidak muncul kembali setelah halaman dimuat ulang
unset($_SESSION['flash_pesan'], $_SESSION['flash_tipe']);

// Mengambil data identitas pengguna yang sedang login
$userId = (int) $_SESSION['user_id'];
$namaPasien = $_SESSION['nama'] ?? 'Pasien';

// Data penanggalan hari ini dalam format Bahasa Indonesia
$namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$hariIni = $namaHari[(int) date('N')];
$tanggalHariIni = date('Y-m-d');
$tanggalTampil = $hariIni . ', ' . date('j') . ' ' . $namaBulan[(int) date('n')] . ' ' . date('Y');

// 1. QUERY: Mengambil daftar tiket antrean aktif milik pasien ini pada hari ini
$stmtAntrianAktif = $pdo->prepare(
    'SELECT a.id, a.nomor, a.status, a.tanggal, a.created_at,
            d.id AS dokter_id, d.nama AS nama_dokter, d.spesialis, d.kuota_per_hari
     FROM antrian a
     JOIN dokter d ON a.dokter_id = d.id
     WHERE a.user_id = ? AND a.tanggal = ?
     ORDER BY a.id DESC'
);
$stmtAntrianAktif->execute([$userId, $tanggalHariIni]);
$daftarAntrianAktif = $stmtAntrianAktif->fetchAll();

// Menyiapkan prepared statement untuk mencari nomor antrean yang sedang dipanggil oleh dokter bersangkutan
$stmtSedangPanggil = $pdo->prepare(
    "SELECT nomor FROM antrian WHERE dokter_id = ? AND tanggal = ? AND status = 'dipanggil' ORDER BY nomor DESC LIMIT 1"
);

// Menyiapkan prepared statement untuk menghitung sisa antrean yang masih menunggu di depan nomor pasien
$stmtSisaDiDepan = $pdo->prepare(
    "SELECT COUNT(*) FROM antrian WHERE dokter_id = ? AND tanggal = ? AND status = 'menunggu' AND nomor < ?"
);

// Mengolah informasi status antrean real-time untuk setiap tiket yang dimiliki pasien hari ini
$antrianAktifDiproses = [];
$dokterSudahDiambil = [];

foreach ($daftarAntrianAktif as $antrian) {
    // Mencatat ID dokter yang sudah diambil antreannya oleh pasien hari ini agar tidak bisa mengambil ganda
    $dokterSudahDiambil[] = (int) $antrian['dokter_id'];

    // Mengambil nomor yang saat ini sedang dipanggil oleh dokter di poli
    $stmtSedangPanggil->execute([$antrian['dokter_id'], $tanggalHariIni]);
    $nomorPanggilan = $stmtSedangPanggil->fetchColumn();

    // Menghitung berapa orang yang sedang menunggu di depan nomor antrean pasien
    $stmtSisaDiDepan->execute([$antrian['dokter_id'], $tanggalHariIni, $antrian['nomor']]);
    $antreanDiDepan = (int) $stmtSisaDiDepan->fetchColumn();

    $antrianAktifDiproses[] = [
        'id'             => $antrian['id'],
        'nomor'          => (int) $antrian['nomor'],
        'status'         => $antrian['status'],
        'nama_dokter'    => $antrian['nama_dokter'],
        'spesialis'      => $antrian['spesialis'],
        'waktu_daftar'   => date('H:i', strtotime($antrian['created_at'])),
        'sedang_panggil' => $nomorPanggilan !== false ? (int) $nomorPanggilan : '-',
        'sisa_di_depan'  => $antreanDiDepan,
    ];
}

// 2. QUERY: Mengambil daftar dokter untuk form pendaftaran ambil nomor antrean hari ini
$stmtSemuaDokter = $pdo->query('SELECT id, nama, spesialis, kuota_per_hari, hari_praktik FROM dokter ORDER BY spesialis ASC, nama ASC');
$semuaDokter = $stmtSemuaDokter->fetchAll();

// Menyiapkan prepared statement untuk menghitung jumlah kuota antrean yang telah terisi hari ini
$stmtHitungTerisi = $pdo->prepare('SELECT COUNT(*) FROM antrian WHERE dokter_id = ? AND tanggal = ?');

$daftarPilihanDokter = [];
$daftarSpesialis = [];

foreach ($semuaDokter as $doc) {
    if (!in_array($doc['spesialis'], $daftarSpesialis, true)) {
        $daftarSpesialis[] = $doc['spesialis'];
    }

    // Memeriksa kecocokan jadwal hari praktik dokter dengan hari ini
    $jadwalHariArray = array_map(
        fn($h) => mb_strtolower(trim($h)),
        explode(',', $doc['hari_praktik'])
    );
    $apakahPraktik = in_array(mb_strtolower($hariIni), $jadwalHariArray, true);

    // Menghitung kuota terisi dan sisa kuota hari ini
    $stmtHitungTerisi->execute([$doc['id'], $tanggalHariIni]);
    $kuotaTerpakai = (int) $stmtHitungTerisi->fetchColumn();
    $sisaKuota = max(0, (int) $doc['kuota_per_hari'] - $kuotaTerpakai);

    // Cek apakah pasien ini sudah pernah mengambil nomor ke dokter ini pada hari ini
    $sudahAmbilKeDokterIni = in_array((int) $doc['id'], $dokterSudahDiambil, true);

    $daftarPilihanDokter[] = [
        'id'            => (int) $doc['id'],
        'nama'          => $doc['nama'],
        'spesialis'     => $doc['spesialis'],
        'hari_praktik'  => $doc['hari_praktik'],
        'kuota_total'   => (int) $doc['kuota_per_hari'],
        'sisa_kuota'    => $sisaKuota,
        'apakah_praktik'=> $apakahPraktik,
        'sudah_ambil'   => $sudahAmbilKeDokterIni,
        'bisa_ambil'    => $apakahPraktik && ($sisaKuota > 0) && !$sudahAmbilKeDokterIni,
    ];
}

// 3. QUERY: Mengambil riwayat kunjungan antrean pasien sebelumnya (selesai atau tanggal lampau)
$stmtRiwayat = $pdo->prepare(
    'SELECT a.nomor, a.status, a.tanggal, d.nama AS nama_dokter, d.spesialis
     FROM antrian a
     JOIN dokter d ON a.dokter_id = d.id
     WHERE a.user_id = ? AND a.tanggal < ?
     ORDER BY a.tanggal DESC, a.nomor DESC LIMIT 5'
);
$stmtRiwayat->execute([$userId, $tanggalHariIni]);
$riwayatList = $stmtRiwayat->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pasien & Status Antrean - Klinik FK</title>

    <!-- Font Modern -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS untuk komponen Grid & Alert -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS Kustom Sistem Antrian Klinik -->
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="bg-light">

<!-- Header / Topbar Khusus Pasien -->
<header class="topbar">
    <a href="../index.php" class="brand fw-bold">Klinik FK Unila</a>
    <nav aria-label="Menu pengguna">
        <a href="../index.php">Jadwal Umum</a>
        <a href="pasien.php" class="fw-semibold text-white">Antrean Saya</a>
        <a href="../auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Keluar</a>
    </nav>
</header>

<main class="container py-4">

    <!-- Menampilkan Flash Message jika ada operasi yang baru dilakukan -->
    <?php if ($flashPesan): ?>
        <div class="alert alert-<?= htmlspecialchars($flashTipe, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show shadow-sm" role="alert">
            <strong>Pemberitahuan:</strong> <?= htmlspecialchars($flashPesan, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Banner Selamat Datang -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #0f766e, #115e59); color: #fff;">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-warning text-dark mb-2">Portal Pasien</span>
                <h2 class="h3 fw-bold mb-1">Halo, <?= htmlspecialchars($namaPasien, ENT_QUOTES, 'UTF-8') ?>!</h2>
                <p class="mb-0 text-white-50">Hari ini: <strong><?= htmlspecialchars($tanggalTampil, ENT_QUOTES, 'UTF-8') ?></strong></p>
            </div>
            <div>
                <a href="#form-ambil" class="btn btn-warning fw-semibold px-4 py-2">
                    + Ambil Nomor Baru
                </a>
                <button type="button" class="btn btn-primary ms-2" onclick="location.reload();">
                    Segarkan Status
                </button>
            </div>
        </div>
    </div>

    <!-- BAGIAN 1: STATUS TIKET ANTREAN AKTIF HARI INI -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h4 fw-bold text-dark mb-0">Tiket Antrean Aktif Anda Hari Ini</h3>
            <span class="badge bg-primary"><?= count($antrianAktifDiproses) ?> Tiket Terdaftar</span>
        </div>

        <?php if (empty($antrianAktifDiproses)): ?>
            <!-- Ditampilkan bila pasien belum mengambil antrean hari ini -->
            <div class="card border-0 shadow-sm text-center p-5 bg-white">
                <h4 class="h5 fw-bold text-secondary">Belum Ada Nomor Antrean Aktif</h4>
                <p class="text-muted mb-3">Anda belum mengambil nomor antrean untuk hari ini. Silakan pilih dokter praktik pada daftar di bawah.</p>
                <div>
                    <a href="#form-ambil" class="btn btn-primary px-4 py-2">Pilih Dokter &amp; Ambil Nomor</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Menampilkan kartu tiket antrean yang sedang aktif -->
            <div class="row g-3">
                <?php foreach ($antrianAktifDiproses as $tiket): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 border-top border-4 <?= $tiket['status'] === 'dipanggil' ? 'border-success' : ($tiket['status'] === 'selesai' ? 'border-secondary' : 'border-primary') ?>">
                            <div class="card-body p-4 text-center">
                                <!-- Status Antrean Badge -->
                                <div class="mb-2">
                                    <?php if ($tiket['status'] === 'menunggu'): ?>
                                        <span class="badge bg-warning text-dark px-3 py-2 fs-6">Menunggu Giliran</span>
                                    <?php elseif ($tiket['status'] === 'dipanggil'): ?>
                                        <span class="badge bg-success text-white px-3 py-2 fs-6 animate-pulse">Giliran Anda Dipanggil!</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-white px-3 py-2 fs-6">Selesai Diperiksa</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Tampilan Nomor Antrean Utama -->
                                <p class="text-muted small mb-0">Nomor Antrean Anda</p>
                                <div class="display-3 fw-bold my-2 text-primary">
                                    #<?= $tiket['nomor'] ?>
                                </div>

                                <!-- Informasi Dokter dan Poli -->
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($tiket['nama_dokter'], ENT_QUOTES, 'UTF-8') ?></h5>
                                <p class="text-muted small mb-3"><?= htmlspecialchars($tiket['spesialis'], ENT_QUOTES, 'UTF-8') ?></p>

                                <hr class="my-3">

                                <!-- Status Antrean yang Sedang Berjalan -->
                                <div class="row g-2 text-start small">
                                    <div class="col-6">
                                        <span class="text-muted">Sedang Dilayani:</span>
                                        <p class="fw-bold text-dark fs-6 mb-0">
                                            <?= $tiket['sedang_panggil'] !== '-' ? '#' . $tiket['sedang_panggil'] : 'Belum dimulai' ?>
                                        </p>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted">Antrean di Depan:</span>
                                        <p class="fw-bold text-dark fs-6 mb-0">
                                            <?= $tiket['status'] === 'selesai' ? '-' : (int) $tiket['sisa_di_depan'] . ' orang' ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="text-muted small mt-3 pt-2 border-top">
                                    Didaftarkan pukul: <strong><?= $tiket['waktu_daftar'] ?> WIB</strong>
                                </div>

                                <?php if ($tiket['status'] === 'menunggu'): ?>
                                    <form action="batal_antrian.php" method="POST" class="mt-3" onsubmit="return confirm('Batalkan antrean #<?= (int) $tiket['nomor'] ?> ke <?= htmlspecialchars($tiket['nama_dokter'], ENT_QUOTES, 'UTF-8') ?>?');">
                                        <input type="hidden" name="antrian_id" value="<?= (int) $tiket['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan Antrean</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- BAGIAN 2: FORM DAFTAR DOKTER & AMBIL NOMOR ANTREAN -->
    <div id="form-ambil" class="mb-5 pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h4 fw-bold text-dark mb-1">Ambil Nomor Antrean Dokter</h3>
                <p class="text-muted small mb-0">Pilih dokter yang bertugas hari ini dan konfirmasi pengambilan nomor.</p>
            </div>
            <!-- Filter Spesialis -->
            <div class="d-flex align-items-center gap-2">
                <label for="filter-poli-pasien" class="small text-muted fw-semibold d-none d-md-inline">Poli:</label>
                <select id="filter-poli-pasien" class="form-select form-select-sm" style="min-width: 180px;">
                    <option value="">Semua Poli / Spesialis</option>
                    <?php foreach ($daftarSpesialis as $sp): ?>
                        <option value="<?= htmlspecialchars($sp, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($sp, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabel-dokter-pasien">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Dokter</th>
                            <th>Poli / Spesialis</th>
                            <th>Hari Praktik</th>
                            <th>Sisa Kuota Hari Ini</th>
                            <th>Status Praktik</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftarPilihanDokter as $dok): ?>
                            <tr data-spesialis="<?= htmlspecialchars($dok['spesialis'], ENT_QUOTES, 'UTF-8') ?>">
                                <td>
                                    <strong><?= htmlspecialchars($dok['nama'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($dok['spesialis'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= htmlspecialchars($dok['hari_praktik'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <strong class="text-<?= $dok['sisa_kuota'] > 0 ? 'success' : 'danger' ?>">
                                        <?= (int) $dok['sisa_kuota'] ?>
                                    </strong>
                                    / <?= (int) $dok['kuota_total'] ?>
                                </td>
                                <td>
                                    <?php if (!$dok['apakah_praktik']): ?>
                                        <span class="badge bg-secondary">Tidak Praktik Hari Ini</span>
                                    <?php elseif ($dok['sisa_kuota'] <= 0): ?>
                                        <span class="badge bg-danger">Kuota Penuh</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Praktik (Tersedia)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if ($dok['sudah_ambil']): ?>
                                        <!-- Tombol jika pasien sudah terdaftar hari ini -->
                                        <button type="button" class="btn btn-sm btn-outline-success" disabled>
                                            Sudah Ambil
                                        </button>
                                    <?php elseif (!$dok['bisa_ambil']): ?>
                                        <!-- Tombol nonaktif jika kuota habis atau libur -->
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                            Tidak Tersedia
                                        </button>
                                    <?php else: ?>
                                        <!-- Form POST ke script ambil_antrian.php untuk mengunci antrean via transaksi DB -->
                                        <form action="ambil_antrian.php" method="POST" style="display:inline;" onsubmit="return confirm('Konfirmasi: Ambil nomor antrean untuk <?= htmlspecialchars($dok['nama'], ENT_QUOTES, 'UTF-8') ?> hari ini?');">
                                            <input type="hidden" name="dokter_id" value="<?= (int) $dok['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-primary px-3">
                                                Ambil Nomor
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- BAGIAN 3: RIWAYAT KUNJUNGAN TERDAHULU -->
    <?php if (!empty($riwayatList)): ?>
        <div class="mb-4">
            <h4 class="h5 fw-bold text-secondary mb-3">Riwayat Antrean Sebelumnya</h4>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Dokter</th>
                                <th>Spesialis</th>
                                <th>Nomor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayatList as $rw): ?>
                                <tr>
                                    <td><?= htmlspecialchars($rw['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($rw['nama_dokter'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($rw['spesialis'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>#<?= (int) $rw['nomor'] ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($rw['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

</main>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script Filter Pasien -->
<script src="pasien.js"></script>
</body>
</html>

