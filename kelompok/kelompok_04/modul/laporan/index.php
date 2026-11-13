<?php
include '../../config/koneksi.php';
include '../../views/header.php';

// --- Filter rentang tanggal (default: bulan ini) ---
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-t');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari))   $dari   = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date('Y-m-t');

$filter_jenis = isset($_GET['jenis_ternak']) && is_string($_GET['jenis_ternak'])
    ? $_GET['jenis_ternak']
    : 'semua';
$jenis_ternak_valid = [
    'sapi' => 'Sapi%',
    'kambing' => 'Kambing%',
    'ayam' => 'Ayam Petelur%'
];
if ($filter_jenis !== 'semua' && !isset($jenis_ternak_valid[$filter_jenis])) {
    $filter_jenis = 'semua';
}
$label_jenis_ternak = [
    'semua' => 'Semua Ternak',
    'sapi' => 'Sapi',
    'kambing' => 'Kambing',
    'ayam' => 'Ayam Petelur'
];

// --- Rekap rekam medis ---
$sql_medis = "SELECT lk.tgl_periksa, t.tag_number, t.jenis_ras, lk.diagnosa,
                     lk.tindakan_medis, lk.obat_vaksin, u.nama_lengkap AS dokter
              FROM log_kesehatan lk
              JOIN ternak t ON t.id_ternak = lk.id_ternak
              LEFT JOIN users u ON u.id_user = lk.id_dokter
              WHERE lk.tgl_periksa BETWEEN ? AND ?";
if ($filter_jenis !== 'semua') {
    $sql_medis .= " AND t.jenis_ras LIKE ?";
}
$sql_medis .= " ORDER BY lk.tgl_periksa DESC";
$stmt = mysqli_prepare($koneksi, $sql_medis);
if ($filter_jenis === 'semua') {
    mysqli_stmt_bind_param($stmt, 'ss', $dari, $sampai);
} else {
    $jenis_ras_filter = $jenis_ternak_valid[$filter_jenis];
    mysqli_stmt_bind_param($stmt, 'sss', $dari, $sampai, $jenis_ras_filter);
}
mysqli_stmt_execute($stmt);
$medis = mysqli_stmt_get_result($stmt);

// --- Rekap penggunaan pakan ---
$sql_pakan = "SELECT DATE(lp.tgl_pemberian) AS tgl, p.nama_pakan, k.nama_kandang,
                     lp.jumlah_kg, u.nama_lengkap AS petugas
              FROM log_pemberian_pakan lp
              JOIN pakan p ON p.id_pakan = lp.id_pakan
              JOIN kandang k ON k.id_kandang = lp.id_kandang
              LEFT JOIN users u ON u.id_user = lp.id_peternak
              WHERE DATE(lp.tgl_pemberian) BETWEEN ? AND ?";
if ($filter_jenis !== 'semua') {
    $sql_pakan .= " AND EXISTS (
                        SELECT 1 FROM ternak t
                        WHERE t.id_kandang = k.id_kandang AND t.jenis_ras LIKE ?
                    )";
}
$sql_pakan .= " ORDER BY lp.tgl_pemberian DESC";
$stmt = mysqli_prepare($koneksi, $sql_pakan);
if ($filter_jenis === 'semua') {
    mysqli_stmt_bind_param($stmt, 'ss', $dari, $sampai);
} else {
    mysqli_stmt_bind_param($stmt, 'sss', $dari, $sampai, $jenis_ras_filter);
}
mysqli_stmt_execute($stmt);
$pakan = mysqli_stmt_get_result($stmt);

// --- Rekap penggunaan pakan per jenis ---
$sql_rekap_jenis = "SELECT p.nama_pakan, p.jenis_pakan, COUNT(*) AS jml_pemberian,
                           SUM(lp.jumlah_kg) AS total_kg
                    FROM log_pemberian_pakan lp
                    JOIN pakan p ON p.id_pakan = lp.id_pakan
                    JOIN kandang k ON k.id_kandang = lp.id_kandang
                    WHERE DATE(lp.tgl_pemberian) BETWEEN ? AND ?";
if ($filter_jenis !== 'semua') {
    $sql_rekap_jenis .= " AND EXISTS (
                              SELECT 1 FROM ternak t
                              WHERE t.id_kandang = k.id_kandang AND t.jenis_ras LIKE ?
                          )";
}
$sql_rekap_jenis .= " GROUP BY p.id_pakan, p.nama_pakan, p.jenis_pakan
                      ORDER BY total_kg DESC";
$stmt = mysqli_prepare($koneksi, $sql_rekap_jenis);
if ($filter_jenis === 'semua') {
    mysqli_stmt_bind_param($stmt, 'ss', $dari, $sampai);
} else {
    mysqli_stmt_bind_param($stmt, 'sss', $dari, $sampai, $jenis_ras_filter);
}
mysqli_stmt_execute($stmt);
$rekap_jenis = mysqli_stmt_get_result($stmt);

$jumlah_medis = mysqli_num_rows($medis);
$jumlah_log_pakan = mysqli_num_rows($pakan);
$total_pakan = 0;
while ($baris_pakan = mysqli_fetch_assoc($pakan)) {
    $total_pakan += $baris_pakan['jumlah_kg'];
}
mysqli_data_seek($pakan, 0);
$zona_waktu_sebelumnya = date_default_timezone_get();
date_default_timezone_set('Asia/Jakarta');
$tanggal_cetak = date('d/m/Y H:i');
date_default_timezone_set($zona_waktu_sebelumnya);
?>

<style>
.report-page {
    --report-green-dark: var(--brand-dark);
    --report-green-soft: var(--brand-soft);
}

.report-page .report-title {
    color: var(--bs-body-color);
}

.report-page .summary-card {
    border: 1px solid var(--bs-border-color);
    border-left: 4px solid var(--summary-color);
    border-radius: var(--bs-border-radius);
    background: #fff;
    color: var(--bs-body-color);
    box-shadow: var(--bs-box-shadow-sm);
}

.report-page .summary-label {
    color: var(--bs-secondary-color);
    font-weight: 600;
}

.report-page .summary-value {
    color: var(--summary-color);
    font-weight: var(--font-weight-title);
}

.report-page .summary-icon {
    display: grid;
    width: 3rem;
    height: 3rem;
    flex: 0 0 3rem;
    place-items: center;
    border-radius: var(--bs-border-radius);
    background: var(--summary-soft);
    color: var(--summary-color);
}

.report-page .summary-primary { --summary-color: var(--brand); --summary-soft: var(--brand-soft); }
.report-page .summary-info { --summary-color: var(--brand); --summary-soft: var(--brand-soft); }
.report-page .summary-warning { --summary-color: var(--brand); --summary-soft: var(--brand-soft); }

.report-page .report-table-card {
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    box-shadow: var(--bs-box-shadow-sm);
}

.report-page .report-table-card .table {
    margin-bottom: 0;
}

.report-page .report-filter-control:focus {
    border-color: var(--brand);
    box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
}

@page {
    size: A4 landscape;
    margin: 1cm;
}

.print-report-header,
.print-signatures {
    display: none;
}

@media print {
    html,
    body {
        background: #fff !important;
        color: #000 !important;
        width: 100% !important;
    }

    .app-shell,
    .app-main,
    .app-content,
    .report-page {
        display: block !important;
        height: auto !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .app-sidebar,
    .app-main > header,
    .app-main > footer,
    body > footer,
    .no-print {
        display: none !important;
    }

    .print-report-header,
    .print-signatures {
        display: block !important;
    }

    .print-report-header {
        margin-bottom: 0.5rem;
        text-align: center;
    }

    .print-report-header h1,
    .print-report-header h2,
    .print-report-header p {
        margin: 0 0 0.2rem !important;
    }

    .report-page .summary-card,
    .report-page .report-table-card {
        border: 1px solid #999 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .report-page .summary-card {
        border-left: 2px solid #666 !important;
    }

    .report-page .summary-card .card-body {
        padding: 0.35rem 0.5rem !important;
    }

    .report-page .summary-row {
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 0.5rem !important;
    }

    .report-page .summary-row > [class*="col-"] {
        flex: 1 1 0 !important;
        max-width: none !important;
        width: auto !important;
    }

    .report-page .summary-icon {
        display: none !important;
    }

    .print-hide {
        display: none !important;
    }

    .report-page .summary-label,
    .report-page .summary-value,
    .report-page .report-title {
        color: #000 !important;
    }

    .report-page .report-table-card .card-header {
        background: #e5e5e5 !important;
        border-bottom: 1px solid #777 !important;
        color: #000 !important;
        padding: 0.4rem 0.6rem !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .report-page .report-table-card .badge {
        background: #fff !important;
        border: 1px solid #777 !important;
        color: #000 !important;
    }

    .report-page .table-responsive {
        overflow: visible !important;
    }

    .report-page table {
        border-collapse: collapse !important;
        width: 100% !important;
        table-layout: auto;
    }

    .report-page thead {
        display: table-header-group;
    }

    .report-page tfoot {
        display: table-footer-group;
    }

    .report-page tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .report-page th,
    .report-page td {
        background: #fff !important;
        border: 0.5px solid #777 !important;
        color: #000 !important;
        padding: 4px !important;
        vertical-align: top;
        overflow-wrap: anywhere;
        word-break: normal;
    }

    .report-page thead th {
        background: #e5e5e5 !important;
        font-weight: 600;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .report-page .card {
        margin-bottom: 0.5rem !important;
    }

    .print-signatures {
        break-inside: avoid;
        page-break-inside: avoid;
        margin-top: 1.5rem;
    }

    .signature-column {
        min-height: 4.5rem;
        text-align: center;
    }

    .signature-name {
        border-top: 1px solid #000;
        display: inline-block;
        margin-top: 2.8rem;
        min-width: 5.5cm;
        padding-top: 0.2rem;
    }
}
</style>

<div class="report-page">
    <div class="print-report-header d-none d-print-block">
        <h1 class="h5 fw-bold">Sistem Log Data Kesehatan &amp; Pakan Ternak - Kelompok 04</h1>
        <h2 class="h6 fw-bold">Rekapitulasi Laporan</h2>
        <p>Jenis ternak: <?= htmlspecialchars($label_jenis_ternak[$filter_jenis]) ?></p>
        <p>Periode: <?= date('d/m/Y', strtotime($dari)) ?> s/d <?= date('d/m/Y', strtotime($sampai)) ?></p>
        <p>Tanggal cetak: <?= $tanggal_cetak ?> WIB</p>
    </div>

    <div class="mb-4 no-print">
        <h2 class="report-title mb-0"><i class="bi bi-file-earmark-bar-graph me-2 text-success"></i>Rekapitulasi Laporan</h2>
    </div>

    <div class="card report-table-card mb-4 no-print">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="jenis_ternak" class="form-label">Jenis Ternak</label>
                    <select id="jenis_ternak" name="jenis_ternak" class="form-select report-filter-control">
                        <?php foreach ($label_jenis_ternak as $jenis => $label): ?>
                            <option value="<?= htmlspecialchars($jenis) ?>" <?= $filter_jenis === $jenis ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="dari" class="form-label">Dari</label>
                    <input id="dari" type="date" name="dari" value="<?= htmlspecialchars($dari) ?>" class="form-control report-filter-control">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="sampai" class="form-label">Sampai</label>
                    <input id="sampai" type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>" class="form-control report-filter-control">
                </div>
                <div class="col-12 col-lg-auto d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-search me-1"></i>Tampilkan
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-outline-success">
                        <i class="bi bi-printer me-1"></i>Cetak Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4 summary-row">
        <div class="col-12 col-md-6">
            <div class="card summary-card summary-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="summary-label">Pemeriksaan pada Periode</div>
                        <div class="summary-value"><?= $jumlah_medis ?></div>
                    </div>
                    <span class="summary-icon"><i class="bi bi-heart-pulse" aria-hidden="true"></i></span>
                </div>
            </div>

        </div>
        <div class="col-12 col-md-6">
            <div class="card summary-card summary-info h-100">
                <div class="card-body d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="summary-label">Total Penggunaan Pakan</div>
                        <div class="summary-value"><?= number_format($total_pakan, 2, ',', '.') ?> kg</div>
                    </div>
                    <span class="summary-icon"><i class="bi bi-basket" aria-hidden="true"></i></span>
                </div>
            </div>

        </div>
    </div>

    <div class="card report-table-card mb-4">
        <div class="card-header bg-success text-white d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1">
            <h5 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>Rekam Medis</h5>
            <span class="badge text-bg-light text-success print-hide"><?= $jumlah_medis ?> data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover bg-white align-middle">
                <thead class="table-success">
                    <tr>
                        <th>Tanggal</th><th>Tag</th><th>Ras</th><th>Diagnosa</th>
                        <th>Tindakan</th><th>Obat/Vaksin</th><th>Dokter</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($jumlah_medis === 0): ?>
                    <tr><td colspan="7" class="text-center py-5">
                        <i class="bi bi-inbox text-muted d-block mb-2" aria-hidden="true"></i>
                        <span class="text-muted">Belum ada data rekam medis pada periode ini.</span>
                    </td></tr>
                <?php endif; ?>
                <?php while ($m = mysqli_fetch_assoc($medis)): ?>
                    <tr>
                        <td class="text-nowrap"><?= date('d/m/Y', strtotime($m['tgl_periksa'])) ?></td>
                        <td><?= htmlspecialchars($m['tag_number']) ?></td>
                        <td><?= htmlspecialchars($m['jenis_ras']) ?></td>
                        <td><?= htmlspecialchars($m['diagnosa']) ?></td>
                        <td><?= htmlspecialchars($m['tindakan_medis'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['obat_vaksin'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['dokter'] ?? '-') ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card report-table-card mb-4">
        <div class="card-header bg-success text-white d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1">
            <h5 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Rekap per Jenis Pakan</h5>
            <span class="badge text-bg-light text-success print-hide"><?= htmlspecialchars((string)mysqli_num_rows($rekap_jenis), ENT_QUOTES, 'UTF-8') ?> data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover bg-white align-middle">
                <thead class="table-success">
                    <tr>
                        <th>Pakan</th>
                        <th>Jenis</th>
                        <th class="text-end">Jumlah Pemberian</th>
                        <th class="text-end">Total (kg)</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $total_rekap_pakan = 0;
                if (mysqli_num_rows($rekap_jenis) === 0):
                ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Tidak ada data pada periode ini.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($r = mysqli_fetch_assoc($rekap_jenis)):
                        $total_rekap_pakan += (float)$r['total_kg'];
                        $jumlah_pemberian = (int)$r['jml_pemberian'] . 'x';
                        $total_jenis_kg = number_format((float)$r['total_kg'], 2, ',', '.');
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nama_pakan'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['jenis_pakan'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= htmlspecialchars($jumlah_pemberian, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= htmlspecialchars($total_jenis_kg, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">Total Keseluruhan</td>
                        <td class="text-end">
                            <?= htmlspecialchars(number_format($total_rekap_pakan, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card report-table-card">
        <div class="card-header bg-success text-white d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1">
            <h5 class="mb-0"><i class="bi bi-basket me-2"></i>Penggunaan Pakan</h5>
            <span class="badge text-bg-light text-success print-hide"><?= $jumlah_log_pakan ?> data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover bg-white align-middle">
                <thead class="table-success">
                    <tr>
                        <th>Tanggal</th><th>Pakan</th><th>Kandang</th>
                        <th class="text-end">Jumlah (kg)</th><th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($jumlah_log_pakan === 0): ?>
                    <tr><td colspan="5" class="text-center py-5">
                        <i class="bi bi-inbox text-muted d-block mb-2" aria-hidden="true"></i>
                        <span class="text-muted">Belum ada penggunaan pakan pada periode ini.</span>
                    </td></tr>
                <?php endif; ?>
                <?php while ($p = mysqli_fetch_assoc($pakan)): ?>
                    <tr>
                        <td class="text-nowrap"><?= date('d/m/Y', strtotime($p['tgl'])) ?></td>
                        <td><?= htmlspecialchars($p['nama_pakan']) ?></td>
                        <td><?= htmlspecialchars($p['nama_kandang']) ?></td>
                        <td class="text-end text-nowrap"><?= number_format($p['jumlah_kg'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($p['petugas'] ?? '-') ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">Total</td>
                        <td class="text-end"><?= number_format($total_pakan, 2, ',', '.') ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="print-signatures d-none d-print-block">
        <div class="row justify-content-between">
            <div class="col-4 signature-column">
                <div>Mengetahui</div>
                <div class="signature-name"></div>
            </div>
            <div class="col-4 signature-column">
                <div>Petugas</div>
                <div class="signature-name"></div>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>