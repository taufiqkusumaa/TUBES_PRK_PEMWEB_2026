<?php
include '../../config/koneksi.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Filter log berdasarkan jenis ternak yang menempati kandang.
$filter_log = isset($_GET['jenis_ternak']) && is_string($_GET['jenis_ternak'])
    ? $_GET['jenis_ternak']
    : 'semua';
$jenis_ternak_valid = [
    'sapi' => 'Sapi%',
    'kambing' => 'Kambing%',
    'ayam' => 'Ayam Petelur%'
];
if ($filter_log !== 'semua' && !isset($jenis_ternak_valid[$filter_log])) {
    $filter_log = 'semua';
}
$label_filter_log = [
    'semua' => 'Semua Ternak',
    'sapi' => 'Sapi',
    'kambing' => 'Kambing',
    'ayam' => 'Ayam Petelur'
];

// Daftar log pemberian pakan (terbaru di atas)
$sql = "SELECT lp.id_log_pakan, lp.tgl_pemberian, lp.jumlah_kg,
               p.nama_pakan, p.jenis_pakan, p.satuan,
               k.nama_kandang, u.nama_lengkap AS petugas
        FROM log_pemberian_pakan lp
        JOIN pakan p ON p.id_pakan = lp.id_pakan
        JOIN kandang k ON k.id_kandang = lp.id_kandang
        LEFT JOIN users u ON u.id_user = lp.id_peternak";
$result = false;
try {
    if ($filter_log === 'semua') {
        $result = mysqli_query($koneksi, $sql . " ORDER BY lp.tgl_pemberian DESC");
    } else {
        $sql .= " WHERE EXISTS (
                      SELECT 1 FROM ternak t
                      WHERE t.id_kandang = k.id_kandang AND t.jenis_ras LIKE ?
                  )
                  ORDER BY lp.tgl_pemberian DESC";
        $stmt_log = mysqli_prepare($koneksi, $sql);
        $jenis_ternak = $jenis_ternak_valid[$filter_log];
        mysqli_stmt_bind_param($stmt_log, 's', $jenis_ternak);
        mysqli_stmt_execute($stmt_log);
        $result = mysqli_stmt_get_result($stmt_log);
    }
    if (!$result) {
        throw new RuntimeException(mysqli_error($koneksi));
    }
} catch (Throwable $e) {
    error_log('Gagal memuat log pemberian pakan: ' . $e->getMessage());
}

$filter_pakan_tersering = '';
if ($filter_log !== 'semua') {
    $filter_pakan_tersering = "WHERE EXISTS (
             SELECT 1 FROM ternak t
             WHERE t.id_kandang = lp.id_kandang AND t.jenis_ras LIKE ?
         )";
}
$query_ringkasan = "SELECT
        COALESCE((SELECT SUM(jumlah_kg)
                  FROM log_pemberian_pakan
                  WHERE tgl_pemberian >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                    AND tgl_pemberian < DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY)), 0) AS total_bulan_ini,
        (SELECT COUNT(*) FROM log_pemberian_pakan
         WHERE tgl_pemberian >= CURDATE()
           AND tgl_pemberian < DATE_ADD(CURDATE(), INTERVAL 1 DAY)) AS jumlah_hari_ini,
        (SELECT p.nama_pakan
         FROM log_pemberian_pakan lp
         JOIN pakan p ON p.id_pakan = lp.id_pakan
         $filter_pakan_tersering
         GROUP BY p.id_pakan, p.nama_pakan
         ORDER BY COUNT(*) DESC, p.nama_pakan ASC
         LIMIT 1) AS pakan_tersering";
$ringkasan = false;
try {
    $stmt_ringkasan = mysqli_prepare($koneksi, $query_ringkasan);
    if (!$stmt_ringkasan) {
        throw new RuntimeException(mysqli_error($koneksi));
    }
    if ($filter_log !== 'semua') {
        $jenis_ternak_ringkasan = $jenis_ternak_valid[$filter_log];
        mysqli_stmt_bind_param($stmt_ringkasan, 's', $jenis_ternak_ringkasan);
    }
    if (!mysqli_stmt_execute($stmt_ringkasan)) {
        throw new RuntimeException(mysqli_stmt_error($stmt_ringkasan));
    }
    $hasil_ringkasan = mysqli_stmt_get_result($stmt_ringkasan);
    if (!$hasil_ringkasan) {
        throw new RuntimeException(mysqli_stmt_error($stmt_ringkasan));
    }
    $ringkasan = mysqli_fetch_assoc($hasil_ringkasan);
} catch (Throwable $e) {
    error_log('Gagal memuat ringkasan log pemberian pakan: ' . $e->getMessage());
}
$no = 1;
$pesan = isset($_GET['pesan']) && is_string($_GET['pesan']) ? $_GET['pesan'] : '';
$status_pesan = isset($_GET['status']) && $_GET['status'] === 'danger' ? 'danger' : 'success';

include '../../views/header.php';
?>

<style>
    .dashboard-page {
        --dashboard-green-dark: var(--brand-dark);
        --dashboard-green-soft: var(--brand-soft);
    }

    .dashboard-page .dashboard-title {
        color: var(--bs-body-color);
    }

    .dashboard-page .stat-card {
        border: 1px solid var(--bs-border-color);
        border-left: 4px solid var(--stat-color);
        border-radius: var(--bs-border-radius);
        background: #fff;
        color: var(--bs-body-color);
        box-shadow: var(--bs-box-shadow-sm);
    }

    .dashboard-page .stat-card .card-title {
        color: var(--bs-secondary-color);
    }

    .dashboard-page .stat-card h2 {
        color: var(--stat-color);
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
</style>

<div class="container-fluid my-3 dashboard-page">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h2 class="dashboard-title mb-0"><i class="bi bi-basket me-2"></i>Log Pemberian Pakan</h2>
        <a href="tambah.php" class="btn btn-success align-self-start align-self-md-auto">
            <i class="bi bi-plus-circle me-1"></i> Catat Pemberian Pakan
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card stat-card stat-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Pemberian Bulan Ini</h6>
                        <h2 class="mb-0">
                            <?= $ringkasan ? number_format((float)$ringkasan['total_bulan_ini'], 2, ',', '.') . ' kg' : '-' ?>
                        </h2>
                    </div>
                    <span class="stat-icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card stat-card stat-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Log Hari Ini</h6>
                        <h2 class="mb-0">
                            <?= $ringkasan ? (int)$ringkasan['jumlah_hari_ini'] : '-' ?>
                        </h2>
                    </div>
                    <span class="stat-icon"><i class="bi bi-journal-check" aria-hidden="true"></i></span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card stat-card stat-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Pakan Paling Sering</h6>
                        <h2 class="mb-0 text-break">
                            <?= $ringkasan && $ringkasan['pakan_tersering'] !== null
                                ? htmlspecialchars($ringkasan['pakan_tersering'])
                                : '-' ?>
                        </h2>
                    </div>
                    <span class="stat-icon"><i class="bi bi-trophy" aria-hidden="true"></i></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($ringkasan === false): ?>
        <div class="alert alert-danger" role="alert">Ringkasan pemberian pakan gagal dimuat.</div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-<?= $status_pesan ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($pesan) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!$result): ?>
        <div class="alert alert-danger" role="alert">Data log gagal dimuat. Silakan coba lagi.</div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Log Pemberian Pakan - <?= htmlspecialchars($label_filter_log[$filter_log]) ?></h5>
        </div>
        <div class="card-body border-bottom">
            <div class="nav nav-pills flex-wrap gap-2" aria-label="Filter log berdasarkan jenis ternak">
                <?php foreach ($label_filter_log as $jenis => $label): ?>
                    <a href="index.php?jenis_ternak=<?= urlencode($jenis) ?>"
                       class="nav-link <?= $filter_log === $jenis ? 'active bg-success text-white' : 'text-success' ?>"
                       <?= $filter_log === $jenis ? 'aria-current="page"' : '' ?>>
                        <?php if ($jenis === 'sapi'): ?>
                            <i class="bi bi-clipboard2-pulse me-1" aria-hidden="true"></i>
                        <?php elseif ($jenis === 'kambing'): ?>
                            <i class="bi bi-clipboard2-pulse me-1" aria-hidden="true"></i>
                        <?php elseif ($jenis === 'ayam'): ?>
                            <i class="bi bi-egg me-1" aria-hidden="true"></i>
                        <?php else: ?>
                            <i class="bi bi-grid me-1" aria-hidden="true"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Kandang</th>
                        <th>Pakan</th>
                        <th class="text-end">Jumlah (kg)</th>
                        <th>Petugas</th>
                        <th class="text-center text-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="bi bi-inbox text-muted d-block mb-2" aria-hidden="true"></i>
                            <p class="text-muted mb-3">Belum ada catatan pemberian pakan untuk <?= htmlspecialchars($label_filter_log[$filter_log]) ?>.</p>
                            <a href="tambah.php" class="btn btn-success btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>Buat catatan pertama
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($row['tgl_pemberian'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_kandang']) ?></td>
                        <td>
                            <div><?= htmlspecialchars($row['nama_pakan']) ?></div>
                            <span class="badge text-bg-success"><?= htmlspecialchars($row['jenis_pakan']) ?></span>
                        </td>
                        <td class="text-end text-nowrap"><?= number_format($row['jumlah_kg'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($row['petugas'] ?? '-') ?></td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex gap-1 justify-content-center">
                            <form method="post" action="hapus.php" class="d-inline"
                                  onsubmit="return confirm('Hapus log ini? Stok pakan akan dikembalikan.')">
                                <input type="hidden" name="id" value="<?= (int)$row['id_log_pakan'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus" aria-label="Hapus log">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../../views/footer.php'; ?>