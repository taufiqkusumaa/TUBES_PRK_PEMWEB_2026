<?php
include '../../config/koneksi.php';
include '../../views/header.php';

$kandang_query = mysqli_query($koneksi, "SELECT * FROM kandang ORDER BY nama_kandang ASC");

if (isset($_POST['simpan'])) {
    // Tangkap input berdasarkan name="tag_number"
    $tag_number       = mysqli_real_escape_string($koneksi, $_POST['tag_number']);
    $id_kandang       = (int) $_POST['id_kandang'];
    $jenis_ras        = mysqli_real_escape_string($koneksi, $_POST['jenis_ras']);
    $jenis_kelamin    = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $tgl_lahir        = $_POST['tgl_lahir'];
    $status_kesehatan = mysqli_real_escape_string($koneksi, $_POST['status_kesehatan']);

    // Upload Foto
    $foto_nama = '';
    if (!empty($_FILES['foto']['name'])) {
        $ext        = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_nama  = 'ternak_' . time() . '.' . $ext;
        $target_dir = '../../assets/images/' . $foto_nama;

        move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir);
    }

    // Query INSERT dengan kolom tag_number
    $insert = mysqli_query($koneksi, "INSERT INTO ternak (tag_number, id_kandang, jenis_ras, jenis_kelamin, tgl_lahir, status_kesehatan, foto) 
                                      VALUES ('$tag_number', '$id_kandang', '$jenis_ras', '$jenis_kelamin', '$tgl_lahir', '$status_kesehatan', '$foto_nama')");

    if ($insert) {
        echo "<script>alert('Data ternak berhasil ditambahkan!'); window.location='index.php';</script>";
    } else {
        $error = "Gagal menyimpan data: " . mysqli_error($koneksi);
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Data Ternak Baru</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nomor Tag / Eartag</label>
                            <input type="text" name="tag_number" class="form-control" placeholder="Contoh: SP-001" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Kandang</label>
                            <select name="id_kandang" class="form-select" required>
                                <option value="">-- Pilih Kandang --</option>
                                <?php while ($k = mysqli_fetch_assoc($kandang_query)): ?>
                                    <option value="<?= $k['id_kandang']; ?>"><?= htmlspecialchars($k['nama_kandang']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Jenis / Ras</label>
                            <input type="text" name="jenis_ras" class="form-control" placeholder="Contoh: Sapi Limosin / Kambing Etawa" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="Jantan">Jantan</option>
                                <option value="Betina">Betina</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanggal Lahir / Masuk</label>
                            <input type="date" name="tgl_lahir" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status Kesehatan</label>
                            <select name="status_kesehatan" class="form-select" required>
                                <option value="Sehat">Sehat</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Karantina">Karantina</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Foto Ternak (Opsional)</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                        <button type="submit" name="simpan" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan Data Ternak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>