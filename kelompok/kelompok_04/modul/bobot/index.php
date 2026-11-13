<?php
include '../../config/koneksi.php';
include '../../views/header.php';

// Filter berdasarkan jenis ternak dan pencarian kode ternak
$filter_jenis = isset($_GET['jenis_ternak']) && is_string($_GET['jenis_ternak'])
    ? $_GET['jenis_ternak']
    : 'semua';
$kode_ternak = isset($_GET['kode_ternak']) && is_string($_GET['kode_ternak'])
    ? trim($_GET['kode_ternak'])
    : '';
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

$sql = "SELECT rb.id_bobot, rb.tgl_timbang, rb.bobot_kg, rb.catatan,
               t.tag_number, t.jenis_ras
        FROM riwayat_bobot rb
        JOIN ternak t ON rb.id_ternak = t.id_ternak";
$where = [];
$types = '';
if ($filter_jenis !== 'semua') {
    $where[] = "t.jenis_ras LIKE ?";
    $types .= 's';
}
if ($kode_ternak !== '') {
    $where[] = "t.tag_number LIKE ?";
    $types .= 's';
    $kode_ternak_filter = '%' . $kode_ternak . '%';
}
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY rb.tgl_timbang DESC, rb.id_bobot DESC";

$stmt = mysqli_prepare($koneksi, $sql);
if ($types === 's') {
    if ($filter_jenis !== 'semua') {
        $jenis_ras_filter = $jenis_ternak_valid[$filter_jenis];
        mysqli_stmt_bind_param($stmt, 's', $jenis_ras_filter);
    } else {
        mysqli_stmt_bind_param($stmt, 's', $kode_ternak_filter);
    }
} elseif ($types === 'ss') {
    $jenis_ras_filter = $jenis_ternak_valid[$filter_jenis];
    mysqli_stmt_bind_param($stmt, 'ss', $jenis_ras_filter, $kode_ternak_filter);
}
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer me-2"></i>Riwayat Bobot Ternak</h2>
    <a href="tambah.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Tambah Penimbangan</a>
</div>

<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] === 'sukses'): ?>
        <div class="alert alert-success">Data berhasil disimpan.</div>
    <?php elseif ($_GET['status'] === 'hapus'): ?>
        <div class="alert alert-success">Data berhasil dihapus.</div>
    <?php elseif ($_GET['status'] === 'gagal'): ?>
        <div class="alert alert-danger">Terjadi kesalahan, data tidak tersimpan.</div>
    <?php endif; ?>
<?php endif; ?>

<style>
    .bobot-filter-input:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
    }
</style>

<!-- Filter Ternak -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <div class="nav nav-pills flex-wrap gap-2 mb-3" aria-label="Filter bobot berdasarkan jenis ternak">
            <?php foreach ($label_jenis_ternak as $jenis => $label): ?>
                <a href="index.php?jenis_ternak=<?= urlencode($jenis); ?>"
                   class="nav-link <?= $filter_jenis === $jenis ? 'active bg-success text-white' : 'text-success'; ?>"
                   <?= $filter_jenis === $jenis ? 'aria-current="page"' : ''; ?>>
                    <?php if ($jenis === 'ayam'): ?>
                        <i class="bi bi-egg me-1" aria-hidden="true"></i>
                    <?php elseif ($jenis === 'semua'): ?>
                        <i class="bi bi-grid me-1" aria-hidden="true"></i>
                    <?php else: ?>
                        <i class="bi bi-speedometer me-1" aria-hidden="true"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($label); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="jenis_ternak" value="<?= htmlspecialchars($filter_jenis); ?>">
            <div class="col-12 col-md-6 col-lg-4">
                <label for="kode_ternak" class="form-label fw-bold">Cari Kode Ternak</label>
                <input type="search" id="kode_ternak" name="kode_ternak"
                       class="form-control bobot-filter-input" value="<?= htmlspecialchars($kode_ternak); ?>"
                       placeholder="Contoh: AY-001">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-search me-1"></i>Cari
                </button>
                <?php if ($kode_ternak !== ''): ?>
                    <a href="index.php?jenis_ternak=<?= urlencode($filter_jenis); ?>" class="btn btn-outline-success">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>No</th>
                        <th>Tanggal Timbang</th>
                        <th>No Tag</th>
                        <th>Jenis / Ras</th>
                        <th>Bobot (kg)</th>
                        <th>Catatan</th>
                        <th class="text-center text-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if (mysqli_num_rows($query) > 0):
                        while ($row = mysqli_fetch_assoc($query)):
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['tgl_timbang']); ?></td>
                        <td><strong><?= htmlspecialchars($row['tag_number']); ?></strong></td>
                        <td><?= htmlspecialchars($row['jenis_ras']); ?></td>
                        <td><?= number_format((float)$row['bobot_kg'], 2, ',', '.'); ?></td>
                        <td><?= htmlspecialchars($row['catatan'] ?? '-'); ?></td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="edit.php?id=<?= (int)$row['id_bobot']; ?>" class="btn btn-outline-success btn-sm" title="Edit"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <a href="hapus.php?id=<?= (int)$row['id_bobot']; ?>" class="btn btn-outline-danger btn-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus data penimbangan ini?')"><i class="bi bi-trash" aria-hidden="true"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <?php if ($kode_ternak !== ''): ?>
                                Tidak ditemukan data bobot dengan kode "<?= htmlspecialchars($kode_ternak); ?>" pada filter <?= htmlspecialchars($label_jenis_ternak[$filter_jenis]); ?>.
                            <?php else: ?>
                                Belum ada data penimbangan untuk <?= htmlspecialchars($label_jenis_ternak[$filter_jenis]); ?>. Klik tombol tambah di atas.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>
