<?php
include '../../config/koneksi.php';
include '../../views/header.php';

$id = (int) $_GET['id'];
$get_ternak = mysqli_query($koneksi, "SELECT * FROM ternak WHERE id_ternak = '$id'");
$data = mysqli_fetch_assoc($get_ternak);

if (!$data) {
    echo "<script>alert('Data ternak tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

$kandang_query = mysqli_query($koneksi, "SELECT * FROM kandang ORDER BY nama_kandang ASC");

if (isset($_POST['update'])) {
    $tag_number       = mysqli_real_escape_string($koneksi, $_POST['tag_number']);
    $id_kandang       = (int) $_POST['id_kandang'];
    $jenis_ras        = mysqli_real_escape_string($koneksi, $_POST['jenis_ras']);
    $jenis_kelamin    = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $tgl_lahir        = $_POST['tgl_lahir'];
    $status_kesehatan = mysqli_real_escape_string($koneksi, $_POST['status_kesehatan']);

    // Cek apakah tag_number sudah dipakai oleh ternak LAIN
    $cek_tag = mysqli_query($koneksi, "SELECT id_ternak FROM ternak WHERE tag_number = '$tag_number' AND id_ternak != '$id'");

    if (mysqli_num_rows($cek_tag) > 0) {
        $error = "Nomor Tag <strong>$tag_number</strong> sudah digunakan oleh ternak lain! Silakan pakai nomor lain.";
    } else {
        $foto_nama = $data['foto']; // Gunakan foto lama sebagai default

        // Jika mengunggah foto baru
        if (!empty($_FILES['foto']['name'])) {
            $ext        = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_baru  = 'ternak_' . time() . '.' . $ext;
            $target_dir = '../../assets/images/' . $foto_baru;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir)) {
                // Hapus foto lama jika ada
                if (!empty($data['foto']) && file_exists('../../assets/images/' . $data['foto'])) {
                    unlink('../../assets/images/' . $data['foto']);
                }
                $foto_nama = $foto_baru;
            }
        }

        $update = mysqli_query($koneksi, "UPDATE ternak SET 
                    tag_number       = '$tag_number',
                    id_kandang       = '$id_kandang',
                    jenis_ras        = '$jenis_ras',
                    jenis_kelamin    = '$jenis_kelamin',
                    tgl_lahir        = '$tgl_lahir',
                    status_kesehatan = '$status_kesehatan',
                    foto             = '$foto_nama'
                    WHERE id_ternak  = '$id'");

        if ($update) {
            echo "<script>alert('Data ternak berhasil diperbarui!'); window.location='index.php';</script>";
        } else {
            $error = "Gagal memperbarui data: " . mysqli_error($koneksi);
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Data Ternak</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nomor Tag / Eartag</label>
                            <input type="text" name="tag_number" class="form-control" value="<?= htmlspecialchars($data['tag_number'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Kandang</label>
                            <select name="id_kandang" class="form-select" required>
                                <option value="">-- Pilih Kandang --</option>
                                <?php while ($k = mysqli_fetch_assoc($kandang_query)): ?>
                                    <option value="<?= $k['id_kandang']; ?>" <?= $data['id_kandang'] == $k['id_kandang'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($k['nama_kandang']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Jenis / Ras</label>
                            <input type="text" name="jenis_ras" class="form-control" value="<?= htmlspecialchars($data['jenis_ras'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="Jantan" <?= ($data['jenis_kelamin'] ?? '') == 'Jantan' ? 'selected' : ''; ?>>Jantan</option>
                                <option value="Betina" <?= ($data['jenis_kelamin'] ?? '') == 'Betina' ? 'selected' : ''; ?>>Betina</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanggal Lahir / Masuk</label>
                            <input type="date" name="tgl_lahir" class="form-control" value="<?= $data['tgl_lahir'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status Kesehatan</label>
                            <select name="status_kesehatan" class="form-select" required>
                                <option value="Sehat" <?= ($data['status_kesehatan'] ?? '') == 'Sehat' ? 'selected' : ''; ?>>Sehat</option>
                                <option value="Sakit" <?= ($data['status_kesehatan'] ?? '') == 'Sakit' ? 'selected' : ''; ?>>Sakit</option>
                                <option value="Karantina" <?= ($data['status_kesehatan'] ?? '') == 'Karantina' ? 'selected' : ''; ?>>Karantina</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Foto Ternak Saat Ini</label><br>
                        <?php if (!empty($data['foto']) && file_exists('../../assets/images/' . $data['foto'])): ?>
                            <img src="../../assets/images/<?= $data['foto']; ?>" class="rounded mb-2" width="100" height="100" style="object-fit: cover;">
                        <?php else: ?>
                            <p class="text-muted small">Belum ada foto.</p>
                        <?php endif; ?>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <small class="text-muted">Pilih foto baru jika ingin mengganti foto lama.</small>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Batal</a>
                        <button type="submit" name="update" class="btn btn-success"><i class="bi bi-save me-1"></i>Update Data Ternak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>