<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

wajibAdmin();

$tgl_mulai = isset($_GET['tgl_mulai']) ? trim($_GET['tgl_mulai']) : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? trim($_GET['tgl_selesai']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$jenis = isset($_GET['jenis']) ? trim($_GET['jenis']) : '';

$where = " WHERE 1=1";
$params = [];

if ($tgl_mulai !== '') {
    $where .= " AND DATE(l.created_at) >= :tgl_mulai";
    $params[':tgl_mulai'] = $tgl_mulai;
}

if ($tgl_selesai !== '') {
    $where .= " AND DATE(l.created_at) <= :tgl_selesai";
    $params[':tgl_selesai'] = $tgl_selesai;
}

if ($status !== '' && in_array($status, ['menunggu', 'diproses', 'selesai'])) {
    $where .= " AND l.status = :status";
    $params[':status'] = $status;
}

if ($jenis !== '' && in_array($jenis, ['pengaduan', 'aspirasi'])) {
    $where .= " AND l.jenis = :jenis";
    $params[':jenis'] = $jenis;
}

$sql = "
    SELECT l.*, k.nama_kategori, u.nama AS nama_pelapor
    FROM laporan l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN kategori k ON l.kategori_id = k.id
    " . $where . "
    ORDER BY l.created_at DESC, l.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarLaporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalData = count($daftarLaporan);
$totalPengaduan = 0;
$totalAspirasi = 0;
$countMenunggu = 0;
$countDiproses = 0;
$countSelesai = 0;

foreach ($daftarLaporan as $row) {
    if ($row['jenis'] === 'pengaduan') $totalPengaduan++;
    if ($row['jenis'] === 'aspirasi') $totalAspirasi++;
    if ($row['status'] === 'menunggu') $countMenunggu++;
    if ($row['status'] === 'diproses') $countDiproses++;
    if ($row['status'] === 'selesai') $countSelesai++;
}

$pageTitle = 'Rekap Laporan';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
@media print {
    .site-header,
    .site-footer,
    .no-print,
    .alert {
        display: none !important;
    }
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
        font-size: 11pt !important;
    }
    .main-content {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .print-only {
        display: block !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        background: transparent !important;
    }
    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    th, td {
        border: 1px solid #333333 !important;
        padding: 6px 8px !important;
        font-size: 10pt !important;
    }
    th {
        background-color: #f2f2f2 !important;
        color: #000000 !important;
    }
}

.print-only {
    display: none;
}

.stat-box-rekap {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.stat-item-rekap {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: 0.85rem;
    text-align: center;
}
</style>

<div class="print-only" style="text-align: center; margin-bottom: 1.5rem; border-bottom: 2px solid #000; padding-bottom: 0.75rem;">
    <h2 style="margin: 0; font-size: 14pt; text-transform: uppercase;">FAKULTAS ILMU SOSIAL DAN ILMU POLITIK</h2>
    <h3 style="margin: 4px 0 0 0; font-size: 12pt; font-weight: normal;">Laporan Rekapitulasi Aspirasi dan Pengaduan Publik</h3>
    <p style="margin: 4px 0 0 0; font-size: 9pt; color: #555;">
        Periode: <?= $tgl_mulai ? sanitize($tgl_mulai) : 'Awal' ?> s/d <?= $tgl_selesai ? sanitize($tgl_selesai) : 'Sekarang' ?> &bull; Dicetak pada: <?= date('d/m/Y H:i') ?> WIB
    </p>
</div>

<div class="no-print" style="margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.75rem; color: var(--color-primary); margin: 0 0 0.35rem 0;">
                Rekap &amp; Cetak Laporan
            </h1>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
                Rekapitulasi berkala data pengaduan dan aspirasi kampus dalam format siap cetak.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/admin/dashboard.php" class="btn btn-secondary btn-sm">&larr; Dashboard</a>
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">
                Cetak Laporan (Print / PDF)
            </button>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <form method="GET" action="/admin/rekap.php">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: flex-end;">
                <div>
                    <label for="tgl_mulai" class="form-label">Tanggal Mulai</label>
                    <input type="date" id="tgl_mulai" name="tgl_mulai" class="form-control" value="<?= sanitize($tgl_mulai) ?>">
                </div>
                <div>
                    <label for="tgl_selesai" class="form-label">Tanggal Selesai</label>
                    <input type="date" id="tgl_selesai" name="tgl_selesai" class="form-control" value="<?= sanitize($tgl_selesai) ?>">
                </div>
                <div>
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="menunggu" <?= $status === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="diproses" <?= $status === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <div>
                    <label for="jenis" class="form-label">Jenis</label>
                    <select id="jenis" name="jenis" class="form-control">
                        <option value="">Semua Jenis</option>
                        <option value="pengaduan" <?= $jenis === 'pengaduan' ? 'selected' : '' ?>>Pengaduan</option>
                        <option value="aspirasi" <?= $jenis === 'aspirasi' ? 'selected' : '' ?>>Aspirasi</option>
                    </select>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
                    <a href="/admin/rekap.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="stat-box-rekap">
    <div class="stat-item-rekap">
        <div style="font-size: 1.4rem; font-weight: 700; color: var(--color-primary);"><?= $totalData ?></div>
        <div style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase;">Total Data</div>
    </div>
    <div class="stat-item-rekap">
        <div style="font-size: 1.4rem; font-weight: 700; color: var(--color-primary);"><?= $totalPengaduan ?></div>
        <div style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase;">Pengaduan</div>
    </div>
    <div class="stat-item-rekap">
        <div style="font-size: 1.4rem; font-weight: 700; color: var(--color-primary);"><?= $totalAspirasi ?></div>
        <div style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase;">Aspirasi</div>
    </div>
    <div class="stat-item-rekap">
        <div style="font-size: 1.4rem; font-weight: 700; color: #6B5420;"><?= $countMenunggu ?></div>
        <div style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase;">Menunggu</div>
    </div>
    <div class="stat-item-rekap">
        <div style="font-size: 1.4rem; font-weight: 700; color: #34402A;"><?= $countDiproses ?></div>
        <div style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase;">Diproses</div>
    </div>
    <div class="stat-item-rekap">
        <div style="font-size: 1.4rem; font-weight: 700; color: var(--color-primary);"><?= $countSelesai ?></div>
        <div style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase;">Selesai</div>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <?php if (empty($daftarLaporan)): ?>
        <div style="text-align: center; padding: 2.5rem 1rem; color: var(--color-text-muted);">
            Tidak ada data laporan pada periode yang dipilih.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--color-border); background-color: var(--color-bg);">
                        <th style="padding: 0.75rem; text-align: center; width: 45px;">No</th>
                        <th style="padding: 0.75rem; text-align: left; width: 105px;">Tanggal</th>
                        <th style="padding: 0.75rem; text-align: left; width: 105px;">Jenis</th>
                        <th style="padding: 0.75rem; text-align: left;">Judul &amp; Lokasi</th>
                        <th style="padding: 0.75rem; text-align: left; width: 130px;">Kategori</th>
                        <th style="padding: 0.75rem; text-align: left; width: 130px;">Pelapor</th>
                        <th style="padding: 0.75rem; text-align: center; width: 105px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($daftarLaporan as $lap): ?>
                        <tr style="border-bottom: 1px solid var(--color-border);">
                            <td style="padding: 0.65rem 0.75rem; text-align: center;"><?= $no++ ?></td>
                            <td style="padding: 0.65rem 0.75rem; white-space: nowrap; font-size: 0.85rem;">
                                <?= date('d/m/Y', strtotime($lap['created_at'])) ?>
                            </td>
                            <td style="padding: 0.65rem 0.75rem; text-transform: capitalize; font-size: 0.85rem; font-weight: 600;">
                                <?= sanitize($lap['jenis']) ?>
                            </td>
                            <td style="padding: 0.65rem 0.75rem;">
                                <div style="font-weight: 600; color: var(--color-primary);"><?= sanitize($lap['judul']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 2px;">
                                    Lokasi: <?= sanitize($lap['lokasi'] ?? '-') ?>
                                </div>
                            </td>
                            <td style="padding: 0.65rem 0.75rem; font-size: 0.85rem;">
                                <?= sanitize($lap['nama_kategori'] ?? 'Umum') ?>
                            </td>
                            <td style="padding: 0.65rem 0.75rem; font-size: 0.85rem;">
                                <?= sanitize($lap['nama_pelapor']) ?>
                            </td>
                            <td style="padding: 0.65rem 0.75rem; text-align: center;">
                                <?= badgeStatus($lap['status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="print-only" style="margin-top: 3rem; display: flex; justify-content: flex-end;">
    <div style="text-align: center; width: 220px;">
        <p style="margin-bottom: 4rem;">Mengetahui,<br>Pengelola Layanan Aspiraku</p>
        <p style="border-bottom: 1px solid #000; margin: 0; font-weight: bold;">( <?= sanitize($_SESSION['nama'] ?? 'Administrator') ?> )</p>
        <p style="margin: 2px 0 0 0; font-size: 9pt;">NIP/ID: Admin FISIP</p>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

