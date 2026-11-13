<?php
include 'config/koneksi.php';
include 'views/header.php';

$batas_sedikit = 10;
$batas_banyak = 50;

// Statistik dashboard
$total_ternak  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM ternak"))['jml'];
$ternak_sakit  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM ternak WHERE status_kesehatan = 'Sakit'"))['jml'];
$total_kandang = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM kandang"))['jml'];
$kategori_pakan = [];
$hasil_kategori_pakan = mysqli_query($koneksi, "SELECT DISTINCT jenis_pakan FROM pakan WHERE jenis_pakan IS NOT NULL AND jenis_pakan <> '' ORDER BY jenis_pakan");
while ($kategori = mysqli_fetch_assoc($hasil_kategori_pakan)) {
    $kategori_pakan[] = $kategori['jenis_pakan'];
}
$filter_stok_pakan = isset($_GET['jenis_stok']) && is_string($_GET['jenis_stok'])
    ? $_GET['jenis_stok']
    : 'semua';
if ($filter_stok_pakan !== 'semua' && !in_array($filter_stok_pakan, $kategori_pakan, true)) {
    $filter_stok_pakan = 'semua';
}
if ($filter_stok_pakan === 'semua') {
    $ringkasan_stok = mysqli_query(
        $koneksi,
        "SELECT COALESCE(SUM(stok_kg), 0) AS total,
                COUNT(IF(stok_kg < 10, 1, NULL)) AS menipis
         FROM pakan"
    );
} else {
    $ringkasan_stok = mysqli_prepare(
        $koneksi,
        "SELECT COALESCE(SUM(stok_kg), 0) AS total,
                COUNT(IF(stok_kg < 10, 1, NULL)) AS menipis
         FROM pakan
         WHERE jenis_pakan = ?"
    );
    mysqli_stmt_bind_param($ringkasan_stok, 's', $filter_stok_pakan);
    mysqli_stmt_execute($ringkasan_stok);
    $ringkasan_stok = mysqli_stmt_get_result($ringkasan_stok);
}
$ringkasan_stok = mysqli_fetch_assoc($ringkasan_stok);
$total_stok_pakan = (float) $ringkasan_stok['total'];
$jumlah_stok_menipis = (int) $ringkasan_stok['menipis'];
$level_stok_pakan = $total_stok_pakan < $batas_sedikit
    ? 'Sedikit'
    : ($total_stok_pakan <= $batas_banyak ? 'Sedang' : 'Banyak');
$kelas_level_stok = [
    'Sedikit' => 'text-bg-warning',
    'Sedang' => 'text-bg-secondary',
    'Banyak' => 'text-bg-success'
][$level_stok_pakan];
$opsi_filter_stok = ['semua' => 'Semua kategori'];
foreach ($kategori_pakan as $kategori) {
    $opsi_filter_stok[$kategori] = $kategori;
}
$stok_menipis  = mysqli_query($koneksi, "SELECT nama_pakan, stok_kg FROM pakan WHERE stok_kg < 10 ORDER BY stok_kg ASC");
$jml_menipis   = mysqli_num_rows($stok_menipis);
$distribusi_status = ['Sehat' => 0, 'Sakit' => 0, 'Karantina' => 0];
$filter_status_jenis = isset($_GET['jenis_status']) && is_string($_GET['jenis_status'])
    ? $_GET['jenis_status']
    : 'semua';
$jenis_status_valid = [
    'sapi' => 'Sapi%',
    'kambing' => 'Kambing%',
    'ayam' => 'Ayam Petelur%'
];
if ($filter_status_jenis !== 'semua' && !isset($jenis_status_valid[$filter_status_jenis])) {
    $filter_status_jenis = 'semua';
}
$label_status_jenis = [
    'semua' => 'Semua Ternak',
    'sapi' => 'Sapi',
    'kambing' => 'Kambing',
    'ayam' => 'Ayam Petelur'
];
if ($filter_status_jenis === 'semua') {
    $hasil_status = mysqli_query($koneksi, "SELECT status_kesehatan, COUNT(*) AS jumlah FROM ternak GROUP BY status_kesehatan");
} else {
    $hasil_status = mysqli_prepare(
        $koneksi,
        "SELECT status_kesehatan, COUNT(*) AS jumlah
         FROM ternak
         WHERE jenis_ras LIKE ?
         GROUP BY status_kesehatan"
    );
    $jenis_ras_status = $jenis_status_valid[$filter_status_jenis];
    mysqli_stmt_bind_param($hasil_status, 's', $jenis_ras_status);
    mysqli_stmt_execute($hasil_status);
    $hasil_status = mysqli_stmt_get_result($hasil_status);
}
while ($status = mysqli_fetch_assoc($hasil_status)) {
    $distribusi_status[$status['status_kesehatan']] = (int) $status['jumlah'];
}

$ternak_per_kandang = mysqli_query(
    $koneksi,
    "SELECT k.nama_kandang, k.kapasitas, COUNT(t.id_ternak) AS jumlah_ternak
     FROM kandang k
     LEFT JOIN ternak t ON t.id_kandang = k.id_kandang
     GROUP BY k.id_kandang, k.nama_kandang, k.kapasitas
     ORDER BY k.nama_kandang"
);
$pemeriksaan_terbaru = mysqli_query(
    $koneksi,
    "SELECT lk.tgl_periksa, lk.diagnosa, t.tag_number, t.jenis_ras, u.nama_lengkap AS dokter
     FROM log_kesehatan lk
     JOIN ternak t ON t.id_ternak = lk.id_ternak
     LEFT JOIN users u ON u.id_user = lk.id_dokter
     ORDER BY lk.tgl_periksa DESC, lk.id_kesehatan DESC
     LIMIT 5"
);
$penimbangan_terbaru = mysqli_query(
    $koneksi,
    "SELECT rb.tgl_timbang, rb.bobot_kg, rb.catatan, t.tag_number, t.jenis_ras
     FROM riwayat_bobot rb
     JOIN ternak t ON t.id_ternak = rb.id_ternak
     ORDER BY rb.tgl_timbang DESC, rb.id_bobot DESC
     LIMIT 5"
);
$zona_waktu = new DateTimeZone('Asia/Jakarta');
$hari_ini = new DateTimeImmutable('today', $zona_waktu);
$tanggal_awal_default = $hari_ini->modify('first day of this month');
$tanggal_akhir_default = $hari_ini->modify('last day of this month');
if (isset($_GET['periode_pakan']) && $_GET['periode_pakan'] === 'mingguan') {
    $tanggal_awal_default = $hari_ini->modify('monday this week');
    $tanggal_akhir_default = $tanggal_awal_default->modify('+6 days');
}
$dari_pakan = isset($_GET['dari_pakan']) && is_string($_GET['dari_pakan'])
    ? $_GET['dari_pakan']
    : $tanggal_awal_default->format('Y-m-d');
$sampai_pakan = isset($_GET['sampai_pakan']) && is_string($_GET['sampai_pakan'])
    ? $_GET['sampai_pakan']
    : $tanggal_akhir_default->format('Y-m-d');
$tanggal_awal = DateTimeImmutable::createFromFormat('!Y-m-d', $dari_pakan, $zona_waktu);
$tanggal_akhir = DateTimeImmutable::createFromFormat('!Y-m-d', $sampai_pakan, $zona_waktu);
if (
    !$tanggal_awal || $tanggal_awal->format('Y-m-d') !== $dari_pakan
    || !$tanggal_akhir || $tanggal_akhir->format('Y-m-d') !== $sampai_pakan
    || $tanggal_awal > $tanggal_akhir
) {
    $tanggal_awal = $tanggal_awal_default;
    $tanggal_akhir = $tanggal_akhir_default;
    $dari_pakan = $tanggal_awal->format('Y-m-d');
    $sampai_pakan = $tanggal_akhir->format('Y-m-d');
}
$awal_pakan_sql = $tanggal_awal->format('Y-m-d 00:00:00');
$akhir_pakan_sql = $tanggal_akhir->modify('+1 day')->format('Y-m-d 00:00:00');
$label_periode_pakan = $tanggal_awal->format('d/m/Y') . ' - ' . $tanggal_akhir->format('d/m/Y');
// Satu batang per nama pakan; EXISTS mengelompokkan pemakaian kandang tanpa menggandakan jumlah per ekor.
$sql_pakan_per_jenis = "SELECT p.id_pakan, p.nama_pakan, p.peruntukan,
                               COALESCE(SUM(lp.jumlah_kg), 0) AS total_kg,
                               COALESCE(SUM(CASE WHEN EXISTS (
                                   SELECT 1 FROM ternak t
                                   WHERE t.id_kandang = lp.id_kandang AND t.jenis_ras LIKE 'Sapi%'
                               ) THEN lp.jumlah_kg ELSE 0 END), 0) AS total_sapi,
                               COALESCE(SUM(CASE WHEN EXISTS (
                                   SELECT 1 FROM ternak t
                                   WHERE t.id_kandang = lp.id_kandang AND t.jenis_ras LIKE 'Kambing%'
                               ) THEN lp.jumlah_kg ELSE 0 END), 0) AS total_kambing,
                               COALESCE(SUM(CASE WHEN EXISTS (
                                   SELECT 1 FROM ternak t
                                   WHERE t.id_kandang = lp.id_kandang AND t.jenis_ras LIKE 'Ayam Petelur%'
                               ) THEN lp.jumlah_kg ELSE 0 END), 0) AS total_ayam
                        FROM pakan p
                        LEFT JOIN log_pemberian_pakan lp
                            ON lp.id_pakan = p.id_pakan
                            AND lp.tgl_pemberian >= ? AND lp.tgl_pemberian < ?
                        GROUP BY p.id_pakan, p.nama_pakan, p.peruntukan
                        ORDER BY total_kg DESC, p.nama_pakan";
$stmt_pakan_per_jenis = mysqli_prepare($koneksi, $sql_pakan_per_jenis);
mysqli_stmt_bind_param($stmt_pakan_per_jenis, 'ss', $awal_pakan_sql, $akhir_pakan_sql);
mysqli_stmt_execute($stmt_pakan_per_jenis);
$penggunaan_per_jenis = mysqli_stmt_get_result($stmt_pakan_per_jenis);
$label_pakan_per_jenis = [];
$data_pakan_per_jenis = [];
$pengguna_pakan_per_item = [];
while ($pemakaian = mysqli_fetch_assoc($penggunaan_per_jenis)) {
    $label_pakan_per_jenis[] = $pemakaian['nama_pakan'];
    $data_pakan_per_jenis[] = (float) $pemakaian['total_kg'];
    $pengguna = [];
    foreach ([
        'Sapi' => (float) $pemakaian['total_sapi'],
        'Kambing' => (float) $pemakaian['total_kambing'],
        'Ayam Petelur' => (float) $pemakaian['total_ayam']
    ] as $jenis_ternak => $jumlah_kg) {
        if ($jumlah_kg > 0) {
            $pengguna[] = [
                'jenis' => $jenis_ternak,
                'jumlah_kg' => $jumlah_kg
            ];
        }
    }
    $pengguna_pakan_per_item[] = $pengguna;
}
$total_pakan_periode = array_sum($data_pakan_per_jenis);
?>

<style>
    .dashboard-page {
        --dashboard-green-dark: var(--brand-dark);
        --dashboard-green-soft: var(--brand-soft);
    }

    .dashboard-page .dashboard-title {
        color: var(--bs-body-color);
    }

    .dashboard-page .stat-card,
    .dashboard-page .dashboard-panel {
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        box-shadow: var(--bs-box-shadow-sm);
    }

    .dashboard-page .stat-card {
        border-left: 4px solid var(--stat-color);
        background: #fff;
        color: var(--bs-body-color);
    }

    .dashboard-page .stat-card .card-title {
        color: var(--bs-secondary-color);
    }

    .dashboard-page .stat-card h2 {
        color: var(--brand-dark);
        white-space: nowrap;
    }

    .dashboard-page .stat-card-content {
        min-width: 0;
    }

    .dashboard-page .stat-icon {
        display: grid;
        width: 3rem;
        height: 3rem;
        place-items: center;
        border-radius: var(--bs-border-radius);
        background: var(--stat-soft);
        color: var(--stat-color);
    }

    .dashboard-page .stat-primary { --stat-color: var(--brand); --stat-soft: var(--brand-soft); }
    .dashboard-page .stat-danger { --stat-color: #dc3545; --stat-soft: #f8d7da; }
    .dashboard-page .stat-warning { --stat-color: #ffc107; --stat-soft: #fff3cd; }
    .dashboard-page .stat-info { --stat-color: var(--brand); --stat-soft: var(--brand-soft); }

    .dashboard-page .panel-heading {
        border-bottom: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius) var(--bs-border-radius) 0 0;
        background: var(--dashboard-green-soft);
        color: var(--dashboard-green-dark);
    }

    .dashboard-page .table thead th {
        background: var(--brand-soft);
        color: var(--brand-dark);
        font-weight: 600;
        white-space: nowrap;
    }

    .dashboard-page .table > :not(caption) > * > * {
        padding: 0.8rem 1rem;
    }

    .dashboard-page .alert-warning {
        border-color: #f0dfb5;
        background: #fff9eb;
        color: #70551d;
    }

    .dashboard-page .feed-chart-period,
    .dashboard-page .feed-chart-period .form-label,
    .dashboard-page .feed-chart-period .form-control,
    .dashboard-page .feed-chart-summary {
        font-family: inherit;
    }

    .dashboard-page .feed-chart-period .form-control:focus,
    .dashboard-page .feed-chart-control:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.2);
    }
</style>

<div class="container-fluid my-3 dashboard-page">
    <div class="mb-4">
        <h2 class="dashboard-title mb-1">Dashboard</h2>
        <p class="text-muted mb-0">Ringkasan kondisi ternak, kandang, dan pakan.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card stat-card stat-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Ternak</h6>
                        <h2 class="mb-0"><?= $total_ternak ?></h2>
                    </div>
                    <span class="stat-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card stat-danger h-100">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Ternak Sakit</h6>
                        <h2 class="mb-0"><?= $ternak_sakit ?></h2>
                    </div>
                    <span class="stat-icon"><i class="bi bi-heart-pulse" aria-hidden="true"></i></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card stat-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Jumlah Kandang</h6>
                        <h2 class="mb-0"><?= $total_kandang ?></h2>
                    </div>
                    <span class="stat-icon"><i class="bi bi-house-door" aria-hidden="true"></i></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card stat-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div class="stat-card-content">
                        <h6 class="card-title mb-1">Total Stok Pakan</h6>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h2 class="mb-0"><?= number_format($total_stok_pakan, 2, ',', '.') ?> kg</h2>
                            <span class="badge <?= $kelas_level_stok ?>"><?= $level_stok_pakan ?></span>
                        </div>
                        <div class="stat-card-caption <?= $jumlah_stok_menipis > 0 ? 'text-warning-emphasis' : 'text-muted' ?>">
                            <?= $jumlah_stok_menipis ?> jenis menipis
                        </div>
                        <div class="d-flex align-items-center gap-1 mt-1">
                            <small class="text-muted text-truncate">
                                <?= $filter_stok_pakan === 'semua' ? 'Semua kategori' : htmlspecialchars($filter_stok_pakan, ENT_QUOTES, 'UTF-8') ?>
                            </small>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-success py-0 px-1" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false"
                                        aria-label="Pilih kategori pakan">
                                    <i class="bi bi-funnel" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach ($opsi_filter_stok as $nilai_kategori => $label_kategori): ?>
                                        <?php
                                        $parameter_stok = [
                                            'jenis_stok' => $nilai_kategori,
                                            'jenis_status' => $filter_status_jenis,
                                            'dari_pakan' => $dari_pakan,
                                            'sampai_pakan' => $sampai_pakan
                                        ];
                                        ?>
                                        <li>
                                            <a class="dropdown-item <?= $filter_stok_pakan === $nilai_kategori ? 'active' : '' ?>"
                                               href="?<?= htmlspecialchars(http_build_query($parameter_stok), ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($label_kategori, ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <span class="stat-icon flex-shrink-0"><i class="bi bi-basket" aria-hidden="true"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6 col-lg-5">
            <div class="card dashboard-panel h-100">
                <div class="card-header panel-heading">
                    <h5 class="mb-0">Distribusi Status - <?= htmlspecialchars($label_status_jenis[$filter_status_jenis], ENT_QUOTES, 'UTF-8') ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="chartStatusTernak" aria-label="Chart distribusi status ternak" role="img"></canvas>
                    <form method="get" class="mt-3">
                        <input type="hidden" name="dari_pakan" value="<?= htmlspecialchars($dari_pakan, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="sampai_pakan" value="<?= htmlspecialchars($sampai_pakan, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="jenis_stok" value="<?= htmlspecialchars($filter_stok_pakan, ENT_QUOTES, 'UTF-8') ?>">
                        <label for="jenis_status" class="form-label fw-semibold">Jenis ternak</label>
                        <div class="input-group">
                            <select id="jenis_status" name="jenis_status" class="form-select feed-chart-control">
                                <?php foreach ($label_status_jenis as $jenis => $label): ?>
                                    <option value="<?= htmlspecialchars($jenis, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $filter_status_jenis === $jenis ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline-success">Tampilkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-7 mt-4 mt-md-0">
            <div class="card dashboard-panel h-100">
                <div class="card-header panel-heading">
                    <h5 class="mb-0">Penggunaan Pakan per Nama Pakan</h5>
                </div>
                <div class="card-body">
                    <div style="height: <?= max(220, count($label_pakan_per_jenis) * 34 + 60) ?>px">
                        <canvas id="chartPakanHarian" aria-label="Chart penggunaan untuk setiap nama pakan dan jenis ternak yang menggunakannya pada periode terpilih" role="img"></canvas>
                    </div>
                    <form method="get" class="row g-3 align-items-end mt-2 feed-chart-period">
                        <input type="hidden" name="jenis_status" value="<?= htmlspecialchars($filter_status_jenis, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="jenis_stok" value="<?= htmlspecialchars($filter_stok_pakan, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="col-12 col-sm-6">
                            <label for="dari_pakan" class="form-label fw-semibold">Dari</label>
                            <input type="date" id="dari_pakan" name="dari_pakan"
                                   value="<?= htmlspecialchars($dari_pakan, ENT_QUOTES, 'UTF-8') ?>"
                                   class="form-control" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="sampai_pakan" class="form-label fw-semibold">Sampai</label>
                            <input type="date" id="sampai_pakan" name="sampai_pakan"
                                   value="<?= htmlspecialchars($sampai_pakan, ENT_QUOTES, 'UTF-8') ?>"
                                   class="form-control" required>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-outline-success">
                                <i class="bi bi-calendar-range me-1" aria-hidden="true"></i>Tampilkan
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                    <p class="text-muted small mt-3 mb-0 feed-chart-summary">
                        Periode grafik: <strong><?= htmlspecialchars($label_periode_pakan, ENT_QUOTES, 'UTF-8') ?></strong>.
                        Total pemakaian pakan: <strong><?= number_format($total_pakan_periode, 2, ',', '.') ?> kg</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-panel mt-4">
        <div class="card-header panel-heading">
            <h5 class="mb-0">Ternak per Kandang</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nama Kandang</th>
                        <th>Jumlah Ternak</th>
                        <th>Kapasitas</th>
                        <th>Keterisian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($ternak_per_kandang) > 0): ?>
                        <?php while ($kandang = mysqli_fetch_assoc($ternak_per_kandang)):
                            $persentase = (int) $kandang['kapasitas'] > 0
                                ? ((int) $kandang['jumlah_ternak'] / (int) $kandang['kapasitas']) * 100
                                : 0;
                            $lebar_bar = min($persentase, 100);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($kandang['nama_kandang']) ?></td>
                                <td><?= (int) $kandang['jumlah_ternak'] ?></td>
                                <td><?= (int) $kandang['kapasitas'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" role="progressbar"
                                             aria-label="Keterisian kandang"
                                             aria-valuenow="<?= (int) $lebar_bar ?>"
                                             aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar <?= $persentase > 100 ? 'bg-danger' : 'bg-success' ?>"
                                                 style="width: <?= $lebar_bar ?>%"></div>
                                        </div>
                                        <span><?= number_format($persentase, 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">Belum ada data kandang.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card dashboard-panel mt-4">
        <div class="card-header panel-heading d-flex justify-content-between align-items-center">
            <h5 class="mb-0">5 Pemeriksaan Kesehatan Terbaru</h5>
            <a href="modul/laporan/index.php" class="btn btn-outline-success btn-sm">Lihat Laporan</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Tag</th>
                        <th>Jenis / Ras</th>
                        <th>Diagnosa</th>
                        <th>Dokter</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($pemeriksaan_terbaru) > 0): ?>
                        <?php while ($pemeriksaan = mysqli_fetch_assoc($pemeriksaan_terbaru)): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($pemeriksaan['tgl_periksa'])) ?></td>
                                <td><?= htmlspecialchars($pemeriksaan['tag_number']) ?></td>
                                <td><?= htmlspecialchars($pemeriksaan['jenis_ras']) ?></td>
                                <td><?= htmlspecialchars($pemeriksaan['diagnosa']) ?></td>
                                <td><?= htmlspecialchars($pemeriksaan['dokter'] ?? '-') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada pemeriksaan kesehatan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card dashboard-panel mt-4">
        <div class="card-header panel-heading d-flex justify-content-between align-items-center">
            <h5 class="mb-0">5 Penimbangan Bobot Terbaru</h5>
            <a href="modul/bobot/index.php" class="btn btn-outline-success btn-sm">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Tag</th>
                        <th>Jenis / Ras</th>
                        <th class="text-end">Bobot (kg)</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($penimbangan_terbaru && mysqli_num_rows($penimbangan_terbaru) > 0): ?>
                        <?php while ($penimbangan = mysqli_fetch_assoc($penimbangan_terbaru)): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($penimbangan['tgl_timbang'])) ?></td>
                                <td><strong><?= htmlspecialchars($penimbangan['tag_number']) ?></strong></td>
                                <td><?= htmlspecialchars($penimbangan['jenis_ras']) ?></td>
                                <td class="text-end text-nowrap"><?= number_format((float)$penimbangan['bobot_kg'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($penimbangan['catatan'] ?? '-') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada data penimbangan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($jml_menipis > 0): ?>
    <div class="alert alert-warning mt-4 shadow-sm">
        <strong>Peringatan stok pakan menipis:</strong>
        <ul class="mb-0">
            <?php while ($p = mysqli_fetch_assoc($stok_menipis)): ?>
                <li><?= htmlspecialchars($p['nama_pakan']) ?> (sisa <?= $p['stok_kg'] ?> kg)</li>
            <?php endwhile; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const dataStatusTernak = <?= json_encode(array_values($distribusi_status), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    new Chart(document.getElementById('chartStatusTernak'), {
        type: 'doughnut',
        data: {
            labels: ['Sehat', 'Sakit', 'Karantina'],
            datasets: [{
                data: dataStatusTernak,
                backgroundColor: ['#198754', '#dc3545', '#ffc107'],
                borderColor: '#fff',
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    new Chart(document.getElementById('chartPakanHarian'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($label_pakan_per_jenis, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            datasets: [{
                label: 'Penggunaan (kg)',
                data: <?= json_encode($data_pakan_per_jenis, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                backgroundColor: '#198754',
                borderColor: '#198754',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            font: {
                family: getComputedStyle(document.body).fontFamily
            },
            datasets: {
                bar: {
                    categoryPercentage: 0.72,
                    barPercentage: 0.82,
                    maxBarThickness: 32
                }
            },
            scales: {
                y: {
                    grid: { display: false }
                },
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'Kilogram' }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return Number(context.raw).toLocaleString('id-ID', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }) + ' kg';
                        },
                        afterLabel: function(context) {
                            const pengguna = <?= json_encode($pengguna_pakan_per_item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>[context.dataIndex];
                            if (!pengguna || pengguna.length === 0) {
                                return 'Belum ada ternak yang menggunakan pada periode ini';
                            }
                            return pengguna.map(function(item) {
                                return item.jenis + ': ' + Number(item.jumlah_kg).toLocaleString('id-ID', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }) + ' kg';
                            });
                        }
                    }
                }
            }
        }
    });
</script>

<?php include 'views/footer.php'; ?>