<?php
include '../../config/koneksi.php';
include '../../views/header.php';

// Halaman ini dibuka dengan: riwayat.php?id_ternak=1
$id_ternak = (int)($_GET['id_ternak'] ?? 0);

// Data ternak + nama kandang
$stmt = mysqli_prepare($koneksi, "SELECT t.*, k.nama_kandang
                                  FROM ternak t
                                  LEFT JOIN kandang k ON t.id_kandang = k.id_kandang
                                  WHERE t.id_ternak = ?");
mysqli_stmt_bind_param($stmt, "i", $id_ternak);
mysqli_stmt_execute($stmt);
$ternak = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$ternak) {
    echo "<script>alert('Data ternak tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Riwayat kesehatan ternak ini
$stmt = mysqli_prepare($koneksi, "SELECT lk.id_kesehatan, lk.tgl_periksa, lk.diagnosa, lk.tindakan_medis, lk.obat_vaksin,
                                         u.nama_lengkap AS nama_dokter
                                  FROM log_kesehatan lk
                                  LEFT JOIN users u ON lk.id_dokter = u.id_user
                                  WHERE lk.id_ternak = ?
                                  ORDER BY lk.tgl_periksa DESC, lk.id_kesehatan DESC");
mysqli_stmt_bind_param($stmt, "i", $id_ternak);
mysqli_stmt_execute($stmt);
$riwayat_kesehatan = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Riwayat bobot ternak ini
$stmt = mysqli_prepare($koneksi, "SELECT id_bobot, tgl_timbang, bobot_kg, catatan
                                  FROM riwayat_bobot
                                  WHERE id_ternak = ?
                                  ORDER BY tgl_timbang DESC, id_bobot DESC");
mysqli_stmt_bind_param($stmt, "i", $id_ternak);
mysqli_stmt_execute($stmt);
$riwayat_bobot = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Ringkasan bobot: terakhir dan selisih dari penimbangan sebelumnya
$bobot_terakhir = $riwayat_bobot[0]['bobot_kg'] ?? null;
$selisih = isset($riwayat_bobot[1]) ? (float)$riwayat_bobot[0]['bobot_kg'] - (float)$riwayat_bobot[1]['bobot_kg'] : null;

$badge_status = 'bg-success-subtle text-success-emphasis border-success-subtle';
if ($ternak['status_kesehatan'] == 'Sakit') $badge_status = 'bg-danger-subtle text-danger-emphasis border-danger-subtle';
if ($ternak['status_kesehatan'] == 'Karantina') $badge_status = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard2-pulse me-2"></i>Riwayat Ternak <?= htmlspecialchars($ternak['tag_number']); ?></h2>
    <a href="../ternak/index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<!-- Info Ternak -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <?php if (!empty($ternak['foto']) && file_exists('../../assets/images/' . $ternak['foto'])): ?>
                    <img src="../../assets/images/<?= htmlspecialchars($ternak['foto']); ?>" class="rounded" width="90" height="90" style="object-fit: cover;">
                <?php else: ?>
                    <span class="badge bg-secondary">No Image</span>
                <?php endif; ?>
            </div>
            <div class="col">
                <div class="row">
                    <div class="col-md-3 mb-2"><small class="text-muted d-block">No Tag</small><strong><?= htmlspecialchars($ternak['tag_number']); ?></strong></div>
                    <div class="col-md-3 mb-2"><small class="text-muted d-block">Jenis / Ras</small><?= htmlspecialchars($ternak['jenis_ras']); ?></div>
                    <div class="col-md-3 mb-2"><small class="text-muted d-block">Kelamin</small><?= $ternak['jenis_kelamin'] == 'Jantan' ? '♂ Jantan' : '♀ Betina'; ?></div>
                    <div class="col-md-3 mb-2"><small class="text-muted d-block">Kandang</small><?= htmlspecialchars($ternak['nama_kandang'] ?? 'Belum Diatur'); ?></div>
                    <div class="col-md-3 mb-2"><small class="text-muted d-block">Tanggal Lahir</small><?= htmlspecialchars($ternak['tgl_lahir'] ?? '-'); ?></div>
                    <div class="col-md-3 mb-2"><small class="text-muted d-block">Status Kesehatan</small><span class="badge health-status-badge <?= $badge_status; ?>"><?= htmlspecialchars($ternak['status_kesehatan']); ?></span></div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted d-block">Bobot Terakhir</small>
                        <?php if ($bobot_terakhir !== null): ?>
                            <?= number_format((float)$bobot_terakhir, 2, ',', '.'); ?> kg
                            <?php if ($selisih !== null): ?>
                                <small class="<?= $selisih >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    (<?= $selisih >= 0 ? '+' : '−'; ?><?= number_format(abs($selisih), 2, ',', '.'); ?> kg)
                                </small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">Belum ditimbang</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Kesehatan -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Riwayat Kesehatan</h4>
    <a href="tambah.php?id_ternak=<?= $id_ternak; ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i>Tambah Pemeriksaan</a>
</div>
<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Dokter</th>
                        <th>Diagnosa</th>
                        <th>Tindakan Medis</th>
                        <th>Obat / Vaksin</th>
                        <th class="text-center text-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($riwayat_kesehatan) > 0): ?>
                        <?php foreach ($riwayat_kesehatan as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1; ?></td>
                            <td><?= htmlspecialchars($row['tgl_periksa']); ?></td>
                            <td><?= htmlspecialchars($row['nama_dokter'] ?? 'Tidak diketahui'); ?></td>
                            <td><?= htmlspecialchars($row['diagnosa']); ?></td>
                            <td><?= htmlspecialchars($row['tindakan_medis'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($row['obat_vaksin'] ?? '-'); ?></td>
                            <td class="text-center text-nowrap">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="edit.php?id=<?= (int)$row['id_kesehatan']; ?>" class="btn btn-outline-success btn-sm" title="Edit"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                    <a href="hapus.php?id=<?= (int)$row['id_kesehatan']; ?>" class="btn btn-outline-danger btn-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus rekam medis ini?')"><i class="bi bi-trash" aria-hidden="true"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada riwayat kesehatan untuk ternak ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Riwayat Bobot -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="mb-0"><i class="bi bi-speedometer me-2"></i>Riwayat Bobot</h4>
    <a href="../bobot/tambah.php?id_ternak=<?= $id_ternak; ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i>Tambah Penimbangan</a>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>No</th>
                        <th>Tanggal Timbang</th>
                        <th>Bobot (kg)</th>
                        <th>Catatan</th>
                        <th class="text-center text-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($riwayat_bobot) > 0): ?>
                        <?php foreach ($riwayat_bobot as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1; ?></td>
                            <td><?= htmlspecialchars($row['tgl_timbang']); ?></td>
                            <td><?= number_format((float)$row['bobot_kg'], 2, ',', '.'); ?></td>
                            <td><?= htmlspecialchars($row['catatan'] ?? '-'); ?></td>
                            <td class="text-center text-nowrap">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="../bobot/edit.php?id=<?= (int)$row['id_bobot']; ?>" class="btn btn-outline-success btn-sm" title="Edit"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                    <a href="../bobot/hapus.php?id=<?= (int)$row['id_bobot']; ?>" class="btn btn-outline-danger btn-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus data penimbangan ini?')"><i class="bi bi-trash" aria-hidden="true"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat bobot untuk ternak ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>