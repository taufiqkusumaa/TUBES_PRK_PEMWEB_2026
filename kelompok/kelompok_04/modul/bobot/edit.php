<?php
include '../../config/koneksi.php';
include '../../views/header.php';

// Ambil data penimbangan + ternak terkait
$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($koneksi, "SELECT rb.*, t.tag_number, t.jenis_ras
                                  FROM riwayat_bobot rb
                                  JOIN ternak t ON rb.id_ternak = t.id_ternak
                                  WHERE rb.id_bobot = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) {
    echo "<script>alert('Data penimbangan tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Nilai form: dari database, atau dari input terakhir jika ada error
$tgl_timbang = $_POST['tgl_timbang'] ?? $data['tgl_timbang'];
$bobot_input = trim($_POST['bobot_kg'] ?? $data['bobot_kg']);
$catatan     = trim($_POST['catatan'] ?? ($data['catatan'] ?? ''));

if (isset($_POST['update'])) {
    $tgl_obj  = DateTime::createFromFormat('Y-m-d', $tgl_timbang);
    $bobot_kg = str_replace(',', '.', $bobot_input);

    if (!$tgl_obj || $tgl_obj->format('Y-m-d') !== $tgl_timbang) {
        $error = "Tanggal timbang tidak valid.";
    } elseif (!is_numeric($bobot_kg) || (float)$bobot_kg <= 0 || (float)$bobot_kg > 9999.99) {
        $error = "Bobot harus berupa angka antara 0,01 dan 9999,99 kg.";
    } elseif (mb_strlen($catatan) > 255) {
        $error = "Catatan maksimal 255 karakter.";
    } else {
        $bobot_db   = (float)$bobot_kg;
        $catatan_db = $catatan !== '' ? $catatan : null;

        $stmt = mysqli_prepare($koneksi, "UPDATE riwayat_bobot SET tgl_timbang = ?, bobot_kg = ?, catatan = ? WHERE id_bobot = ?");
        mysqli_stmt_bind_param($stmt, "sdsi", $tgl_timbang, $bobot_db, $catatan_db, $id);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            echo "<script>window.location='index.php?status=sukses';</script>";
            exit;
        } else {
            $error = "Gagal memperbarui data: " . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Penimbangan Ternak</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Ternak</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['tag_number'] . ' — ' . $data['jenis_ras']); ?>" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanggal Timbang</label>
                            <input type="date" name="tgl_timbang" class="form-control" value="<?= htmlspecialchars($tgl_timbang); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Bobot (kg)</label>
                        <input type="text" name="bobot_kg" class="form-control" inputmode="decimal" value="<?= htmlspecialchars($bobot_input); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan (Opsional)</label>
                        <input type="text" name="catatan" class="form-control" maxlength="255" value="<?= htmlspecialchars($catatan); ?>">
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                        <button type="submit" name="update" class="btn btn-success"><i class="bi bi-save me-1"></i>Perbarui Penimbangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>
