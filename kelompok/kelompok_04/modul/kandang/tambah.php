<?php
include '../../config/koneksi.php';
include '../../views/header.php';

if (isset($_POST['simpan'])) {
    $nama_kandang = mysqli_real_escape_string($koneksi, $_POST['nama_kandang']);
    $kapasitas    = (int) $_POST['kapasitas'];
    $lokasi       = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $keterangan   = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    $insert = mysqli_query($koneksi, "INSERT INTO kandang (nama_kandang, kapasitas, lokasi, keterangan) 
                                      VALUES ('$nama_kandang', '$kapasitas', '$lokasi', '$keterangan')");

    if ($insert) {
        echo "<script>alert('Data kandang berhasil ditambahkan!'); window.location='index.php';</script>";
    } else {
        $error = "Gagal menyimpan data: " . mysqli_error($koneksi);
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Kandang Baru</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Kandang</label>
                        <input type="text" name="nama_kandang" class="form-control" placeholder="Contoh: Kandang Sapi A1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kapasitas (Ekor)</label>
                        <input type="number" name="kapasitas" class="form-control" placeholder="Contoh: 20" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Lokasi Kandang</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Blok Barat Sektor 2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan tambahan (opsional)"></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                        <button type="submit" name="simpan" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>