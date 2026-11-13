<?php
include '../../config/koneksi.php';
include '../../views/header.php';

$query = mysqli_query($koneksi, "SELECT * FROM kandang ORDER BY id_kandang DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-house-door me-2"></i>Data Kandang</h2>
    <a href="tambah.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Tambah Kandang</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-success">
                    <tr>
                        <th>No</th>
                        <th>Nama Kandang</th>
                        <th>Kapasitas</th>
                        <th>Lokasi</th>
                        <th>Keterangan</th>
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
                        <td><strong><?= htmlspecialchars($row['nama_kandang']); ?></strong></td>
                        <td><?= $row['kapasitas']; ?> ekor</td>
                        <td><?= htmlspecialchars($row['lokasi']); ?></td>
                        <td><?= htmlspecialchars($row['keterangan']); ?></td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="edit.php?id=<?= $row['id_kandang']; ?>" class="btn btn-outline-success btn-sm" title="Edit"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <a href="hapus.php?id=<?= $row['id_kandang']; ?>" class="btn btn-outline-danger btn-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus kandang ini?')"><i class="bi bi-trash" aria-hidden="true"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data kandang. Klik tombol tambah di atas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>