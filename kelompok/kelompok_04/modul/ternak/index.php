<?php
include '../../config/koneksi.php';
include '../../views/header.php';

// Filter berdasarkan Kandang
$filter_kandang = isset($_GET['id_kandang']) ? (int)$_GET['id_kandang'] : 0;
$where = $filter_kandang > 0 ? "WHERE t.id_kandang = '$filter_kandang'" : "";

$query_str = "SELECT t.*, k.nama_kandang 
              FROM ternak t 
              LEFT JOIN kandang k ON t.id_kandang = k.id_kandang 
              $where 
              ORDER BY t.id_ternak DESC";
$query = mysqli_query($koneksi, $query_str);

// Ambil list kandang untuk dropdown filter
$kandang_list = mysqli_query($koneksi, "SELECT * FROM kandang ORDER BY nama_kandang ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-card-checklist me-2"></i>Data Hewan Ternak</h2>
    <a href="tambah.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Tambah Ternak</a>
</div>

<!-- Filter Kandang -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-bold">Filter Kandang:</label>
            </div>
            <div class="col-auto">
                <select name="id_kandang" class="form-select" onchange="this.form.submit()">
                    <option value="0">-- Semua Kandang --</option>
                    <?php while ($k = mysqli_fetch_assoc($kandang_list)): ?>
                        <option value="<?= $k['id_kandang']; ?>" <?= $filter_kandang == $k['id_kandang'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($k['nama_kandang']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php if ($filter_kandang > 0): ?>
                <div class="col-auto">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset Filter</a>
                </div>
            <?php endif; ?>
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
                        <th>Foto</th>
                        <th>No Tag</th>
                        <th>Jenis / Ras</th>
                        <th>Kandang</th>
                        <th>Kelamin</th>
                        <th>Status Kesehatan</th>
                        <th class="text-center text-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if (mysqli_num_rows($query) > 0):
                        while ($row = mysqli_fetch_assoc($query)): 
                            $badge_status = 'bg-success-subtle text-success-emphasis border-success-subtle';
                            if ($row['status_kesehatan'] == 'Sakit') $badge_status = 'bg-danger-subtle text-danger-emphasis border-danger-subtle';
                            if ($row['status_kesehatan'] == 'Karantina') $badge_status = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td>
                            <?php if (!empty($row['foto']) && file_exists('../../assets/images/' . $row['foto'])): ?>
                                <img src="../../assets/images/<?= $row['foto']; ?>" class="rounded" width="50" height="50" style="object-fit: cover;">
                            <?php else: ?>
                                <span class="badge bg-secondary">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($row['tag_number']); ?></strong></td>
                        <td><?= htmlspecialchars($row['jenis_ras']); ?></td>
                        <td><?= htmlspecialchars($row['nama_kandang'] ?? 'Belum Diatur'); ?></td>
                        <td><?= $row['jenis_kelamin'] == 'Jantan' ? '♂ Jantan' : '♀ Betina'; ?></td>
                        <td><span class="badge health-status-badge <?= $badge_status; ?>"><?= $row['status_kesehatan']; ?></span></td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="<?= $base_url; ?>/modul/kesehatan/riwayat.php?id_ternak=<?= (int)$row['id_ternak']; ?>" class="btn btn-outline-secondary btn-sm" title="Detail"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                <a href="edit.php?id=<?= $row['id_ternak']; ?>" class="btn btn-outline-success btn-sm" title="Edit"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <a href="hapus.php?id=<?= $row['id_ternak']; ?>" class="btn btn-outline-danger btn-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus ternak ini?')"><i class="bi bi-trash" aria-hidden="true"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada data ternak. Klik tombol tambah di atas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>