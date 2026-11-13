<?php
include '../../config/koneksi.php';
include '../../views/header.php';

$id = (int) $_GET['id'];
$get = mysqli_query($koneksi, "SELECT * FROM kandang WHERE id_kandang ='$id'");
$data = mysqli_fetch_assoc($get);

if(!$data){
    echo "<script>alert('Data tidak ditemukan'); window.location='index.php';</script>";
    exit;
}

if(isset($_POST['update'])){
    $nama_kandang = mysqli_real_escape_string($koneksi, $_POST['nama_kandang']);
    $kapasitas    = (int) $_POST['kapasitas'];
    $lokasi       = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $keterangan   = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    $update = mysqli_query($koneksi, "UPDATE kandang SET 
                nama_kandang = '$nama_kandang',
                kapasitas    = '$kapasitas',
                lokasi       = '$lokasi',
                keterangan   = '$keterangan'
                WHERE id_kandang = '$id'");
    if ($update) {
        echo "<script>alert('Data kandang berhasil diubah!'); window.location='index.php';</script>";
    } else {
        $error = "Gagal memperbarui data: " . mysqli_error($koneksi);
    }
}
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Data Kandang</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Kandang</label>
                        <input type="text" name="nama_kandang" class="form-control" value="<?= htmlspecialchars($data['nama_kandang']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kapasitas (Ekor)</label>
                        <input type="number" name="kapasitas" class="form-control" value="<?= $data['kapasitas']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Lokasi Kandang</label>
                        <input type="text" name="lokasi" class="form-control" value="<?= htmlspecialchars($data['lokasi']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($data['keterangan']); ?></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Batal</a>
                        <button type="submit" name="update" class="btn btn-success"><i class="bi bi-save me-1"></i>Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>