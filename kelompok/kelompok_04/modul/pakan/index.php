<?php
include '../../config/koneksi.php';
include '../../views/header.php';

$query = mysqli_query($koneksi, "SELECT * FROM pakan ORDER BY id_pakan DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam me-2"></i>Data Pakan Ternak</h2>
    <a href="tambah.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Tambah Pakan</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>No</th>
                        <th>Nama Pakan</th>
                        <th>Jenis Pakan</th>
                        <th>Peruntukan</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                        <th class="text-center text-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if (mysqli_num_rows($query) > 0):
                        while ($row = mysqli_fetch_assoc($query)): 
                            // Peringatan badge jika stok pakan < 10 kg
                            $badge_stok = 'bg-success';
                            if ($row['stok_kg'] < 10) $badge_stok = 'bg-danger';
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><strong><?= htmlspecialchars($row['nama_pakan']); ?></strong></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['jenis_pakan']); ?></span></td>
                        <td><?= htmlspecialchars($row['peruntukan']); ?></td>
                        <td><span class="badge <?= $badge_stok; ?>"><?= number_format($row['stok_kg'], 2); ?></span></td>
                        <td><?= htmlspecialchars($row['satuan']); ?></td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="edit.php?id=<?= $row['id_pakan']; ?>" class="btn btn-outline-success btn-sm" title="Edit"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <a href="hapus.php?id=<?= $row['id_pakan']; ?>" class="btn btn-outline-danger btn-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus pakan ini?')"><i class="bi bi-trash" aria-hidden="true"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data pakan. Klik tombol tambah di atas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>