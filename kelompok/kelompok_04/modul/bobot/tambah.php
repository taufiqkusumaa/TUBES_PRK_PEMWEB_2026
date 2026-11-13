<?php
include '../../config/koneksi.php';
include '../../views/header.php';

$ternak_query = mysqli_query($koneksi, "SELECT id_ternak, tag_number, jenis_ras FROM ternak ORDER BY tag_number ASC");

// Nilai form (diisi ulang jika ada error)
$id_ternak   = (int)($_POST['id_ternak'] ?? ($_GET['id_ternak'] ?? 0));
$tgl_timbang = $_POST['tgl_timbang'] ?? date('Y-m-d');
$bobot_input = trim($_POST['bobot_kg'] ?? '');
$catatan     = trim($_POST['catatan'] ?? '');

if (isset($_POST['simpan'])) {
    $tgl_obj = DateTime::createFromFormat('Y-m-d', $tgl_timbang);
    // Terima koma sebagai pemisah desimal (mis. 245,5)
    $bobot_kg = str_replace(',', '.', $bobot_input);

    if ($id_ternak <= 0) {
        $error = "Pilih ternak yang ditimbang.";
    } elseif (!$tgl_obj || $tgl_obj->format('Y-m-d') !== $tgl_timbang) {
        $error = "Tanggal timbang tidak valid.";
    } elseif (!is_numeric($bobot_kg) || (float)$bobot_kg <= 0 || (float)$bobot_kg > 9999.99) {
        $error = "Bobot harus berupa angka antara 0,01 dan 9999,99 kg.";
    } elseif (mb_strlen($catatan) > 255) {
        $error = "Catatan maksimal 255 karakter.";
    } else {
        // Pastikan ternak ada
        $cek = mysqli_prepare($koneksi, "SELECT id_ternak FROM ternak WHERE id_ternak = ?");
        mysqli_stmt_bind_param($cek, "i", $id_ternak);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);
        $ternak_ada = mysqli_stmt_num_rows($cek) > 0;
        mysqli_stmt_close($cek);

        if (!$ternak_ada) {
            $error = "Data ternak tidak ditemukan.";
        } else {
            $bobot_db   = (float)$bobot_kg;
            $catatan_db = $catatan !== '' ? $catatan : null;

            $stmt = mysqli_prepare($koneksi, "INSERT INTO riwayat_bobot (id_ternak, tgl_timbang, bobot_kg, catatan) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isds", $id_ternak, $tgl_timbang, $bobot_db, $catatan_db);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                echo "<script>window.location='index.php?status=sukses';</script>";
                exit;
            } else {
                $error = "Gagal menyimpan data: " . mysqli_error($koneksi);
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Penimbangan Ternak</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Ternak</label>
                            <select name="id_ternak" class="form-select" required>
                                <option value="">-- Pilih Ternak --</option>
                                <?php while ($t = mysqli_fetch_assoc($ternak_query)): ?>
                                    <option value="<?= (int)$t['id_ternak']; ?>" <?= $id_ternak === (int)$t['id_ternak'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($t['tag_number']); ?> — <?= htmlspecialchars($t['jenis_ras']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanggal Timbang</label>
                            <input type="date" name="tgl_timbang" class="form-control" value="<?= htmlspecialchars($tgl_timbang); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Bobot (kg)</label>
                        <input type="text" name="bobot_kg" class="form-control" inputmode="decimal" placeholder="Contoh: 245.5" value="<?= htmlspecialchars($bobot_input); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan (Opsional)</label>
                        <input type="text" name="catatan" class="form-control" maxlength="255" placeholder="Contoh: Ditimbang pagi sebelum diberi pakan" value="<?= htmlspecialchars($catatan); ?>">
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                        <button type="submit" name="simpan" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan Penimbangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>
