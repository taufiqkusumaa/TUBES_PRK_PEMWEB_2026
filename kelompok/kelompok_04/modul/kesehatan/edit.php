<?php
include '../../config/koneksi.php';
include '../../views/header.php';

$status_valid = ['Sehat', 'Sakit', 'Karantina'];

// Ambil data rekam medis + ternak terkait
$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($koneksi, "SELECT lk.*, t.tag_number, t.jenis_ras, t.status_kesehatan
                                  FROM log_kesehatan lk
                                  JOIN ternak t ON lk.id_ternak = t.id_ternak
                                  WHERE lk.id_kesehatan = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) {
    echo "<script>alert('Data rekam medis tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

$dokter_query = mysqli_query($koneksi, "SELECT id_user, nama_lengkap FROM users WHERE role = 'dokter' ORDER BY nama_lengkap ASC");

// Nilai form: dari database, atau dari input terakhir jika ada error
$id_dokter        = (int)($_POST['id_dokter'] ?? $data['id_dokter'] ?? 0);
$tgl_periksa      = $_POST['tgl_periksa'] ?? $data['tgl_periksa'];
$diagnosa         = trim($_POST['diagnosa'] ?? $data['diagnosa']);
$tindakan_medis   = trim($_POST['tindakan_medis'] ?? ($data['tindakan_medis'] ?? ''));
$obat_vaksin      = trim($_POST['obat_vaksin'] ?? ($data['obat_vaksin'] ?? ''));
$status_kesehatan = $_POST['status_kesehatan'] ?? $data['status_kesehatan'];

if (isset($_POST['update'])) {
    $tgl_obj = DateTime::createFromFormat('Y-m-d', $tgl_periksa);

    if ($id_dokter <= 0) {
        $error = "Pilih dokter penanggung jawab.";
    } elseif (!$tgl_obj || $tgl_obj->format('Y-m-d') !== $tgl_periksa) {
        $error = "Tanggal periksa tidak valid.";
    } elseif ($diagnosa === '') {
        $error = "Diagnosa wajib diisi.";
    } elseif (!in_array($status_kesehatan, $status_valid, true)) {
        $error = "Pilih status kesehatan ternak.";
    } else {
        // Pastikan dokter ada dan berperan sebagai dokter
        $cek_dokter = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE id_user = ? AND role = 'dokter'");
        mysqli_stmt_bind_param($cek_dokter, "i", $id_dokter);
        mysqli_stmt_execute($cek_dokter);
        mysqli_stmt_store_result($cek_dokter);
        $dokter_ada = mysqli_stmt_num_rows($cek_dokter) > 0;
        mysqli_stmt_close($cek_dokter);

        if (!$dokter_ada) {
            $error = "Data dokter tidak ditemukan.";
        } else {
            $tindakan_db = $tindakan_medis !== '' ? $tindakan_medis : null;
            $obat_db     = $obat_vaksin !== '' ? $obat_vaksin : null;

            // Update rekam medis + status ternak dalam satu transaksi
            mysqli_begin_transaction($koneksi);

            $stmt = mysqli_prepare($koneksi, "UPDATE log_kesehatan
                                              SET id_dokter = ?, tgl_periksa = ?, diagnosa = ?, tindakan_medis = ?, obat_vaksin = ?
                                              WHERE id_kesehatan = ?");
            mysqli_stmt_bind_param($stmt, "issssi", $id_dokter, $tgl_periksa, $diagnosa, $tindakan_db, $obat_db, $id);
            $ok_update = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $ok_status = false;
            if ($ok_update) {
                $stmt2 = mysqli_prepare($koneksi, "UPDATE ternak SET status_kesehatan = ? WHERE id_ternak = ?");
                mysqli_stmt_bind_param($stmt2, "si", $status_kesehatan, $data['id_ternak']);
                $ok_status = mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }

            if ($ok_update && $ok_status) {
                mysqli_commit($koneksi);
                echo "<script>window.location='index.php?status=sukses';</script>";
                exit;
            } else {
                mysqli_rollback($koneksi);
                $error = "Gagal memperbarui data: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Rekam Medis</h5>
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
                            <label class="form-label fw-bold">Dokter Penanggung Jawab</label>
                            <select name="id_dokter" class="form-select" required>
                                <option value="">-- Pilih Dokter --</option>
                                <?php while ($d = mysqli_fetch_assoc($dokter_query)): ?>
                                    <option value="<?= (int)$d['id_user']; ?>" <?= $id_dokter === (int)$d['id_user'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($d['nama_lengkap']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanggal Periksa</label>
                            <input type="date" name="tgl_periksa" class="form-control" value="<?= htmlspecialchars($tgl_periksa); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status Kesehatan Ternak</label>
                            <select name="status_kesehatan" class="form-select" required>
                                <?php foreach ($status_valid as $s): ?>
                                    <option value="<?= $s; ?>" <?= $status_kesehatan === $s ? 'selected' : ''; ?>><?= $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Mengubah pilihan ini juga memperbarui status ternak saat ini.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Diagnosa</label>
                        <textarea name="diagnosa" class="form-control" rows="3" required><?= htmlspecialchars($diagnosa); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tindakan Medis (Opsional)</label>
                        <textarea name="tindakan_medis" class="form-control" rows="2"><?= htmlspecialchars($tindakan_medis); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Obat / Vaksin (Opsional)</label>
                        <input type="text" name="obat_vaksin" class="form-control" value="<?= htmlspecialchars($obat_vaksin); ?>">
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                        <button type="submit" name="update" class="btn btn-success"><i class="bi bi-save me-1"></i>Perbarui Rekam Medis</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>