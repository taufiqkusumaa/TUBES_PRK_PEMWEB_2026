<?php
include '../../config/koneksi.php';
include '../../views/header.php';

$status_valid = ['Sehat', 'Sakit', 'Karantina'];

// Data untuk dropdown
$ternak_query = mysqli_query($koneksi, "SELECT id_ternak, tag_number, jenis_ras, status_kesehatan FROM ternak ORDER BY tag_number ASC");
$dokter_query = mysqli_query($koneksi, "SELECT id_user, nama_lengkap FROM users WHERE role = 'dokter' ORDER BY nama_lengkap ASC");

// Nilai form (diisi ulang jika ada error)
$id_ternak        = (int)($_POST['id_ternak'] ?? ($_GET['id_ternak'] ?? 0));
$id_dokter        = (int)($_POST['id_dokter'] ?? 0);
$tgl_periksa      = $_POST['tgl_periksa'] ?? date('Y-m-d');
$diagnosa         = trim($_POST['diagnosa'] ?? '');
$tindakan_medis   = trim($_POST['tindakan_medis'] ?? '');
$obat_vaksin      = trim($_POST['obat_vaksin'] ?? '');
$status_kesehatan = $_POST['status_kesehatan'] ?? '';

if (isset($_POST['simpan'])) {
    // Validasi input
    $tgl_obj = DateTime::createFromFormat('Y-m-d', $tgl_periksa);

    if ($id_ternak <= 0) {
        $error = "Pilih ternak yang diperiksa.";
    } elseif ($id_dokter <= 0) {
        $error = "Pilih dokter penanggung jawab.";
    } elseif (!$tgl_obj || $tgl_obj->format('Y-m-d') !== $tgl_periksa) {
        $error = "Tanggal periksa tidak valid.";
    } elseif ($diagnosa === '') {
        $error = "Diagnosa wajib diisi.";
    } elseif (!in_array($status_kesehatan, $status_valid, true)) {
        $error = "Pilih status kesehatan ternak.";
    } else {
        // Pastikan ternak dan dokter benar-benar ada
        $cek_ternak = mysqli_prepare($koneksi, "SELECT id_ternak FROM ternak WHERE id_ternak = ?");
        mysqli_stmt_bind_param($cek_ternak, "i", $id_ternak);
        mysqli_stmt_execute($cek_ternak);
        mysqli_stmt_store_result($cek_ternak);
        $ternak_ada = mysqli_stmt_num_rows($cek_ternak) > 0;
        mysqli_stmt_close($cek_ternak);

        $cek_dokter = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE id_user = ? AND role = 'dokter'");
        mysqli_stmt_bind_param($cek_dokter, "i", $id_dokter);
        mysqli_stmt_execute($cek_dokter);
        mysqli_stmt_store_result($cek_dokter);
        $dokter_ada = mysqli_stmt_num_rows($cek_dokter) > 0;
        mysqli_stmt_close($cek_dokter);

        if (!$ternak_ada) {
            $error = "Data ternak tidak ditemukan.";
        } elseif (!$dokter_ada) {
            $error = "Data dokter tidak ditemukan.";
        } else {
            // Kolom opsional: kosong disimpan sebagai NULL
            $tindakan_db = $tindakan_medis !== '' ? $tindakan_medis : null;
            $obat_db     = $obat_vaksin !== '' ? $obat_vaksin : null;

            // Simpan rekam medis + update status ternak dalam satu transaksi
            mysqli_begin_transaction($koneksi);

            $stmt = mysqli_prepare($koneksi, "INSERT INTO log_kesehatan (id_ternak, id_dokter, tgl_periksa, diagnosa, tindakan_medis, obat_vaksin)
                                              VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iissss", $id_ternak, $id_dokter, $tgl_periksa, $diagnosa, $tindakan_db, $obat_db);
            $ok_insert = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $ok_update = false;
            if ($ok_insert) {
                $stmt2 = mysqli_prepare($koneksi, "UPDATE ternak SET status_kesehatan = ? WHERE id_ternak = ?");
                mysqli_stmt_bind_param($stmt2, "si", $status_kesehatan, $id_ternak);
                $ok_update = mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }

            if ($ok_insert && $ok_update) {
                mysqli_commit($koneksi);
                echo "<script>window.location='index.php?status=sukses';</script>";
                exit;
            } else {
                mysqli_rollback($koneksi);
                $error = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Pemeriksaan Ternak</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Ternak</label>
                            <select name="id_ternak" id="id_ternak" class="form-select" required>
                                <option value="">-- Pilih Ternak --</option>
                                <?php while ($t = mysqli_fetch_assoc($ternak_query)): ?>
                                    <option value="<?= (int)$t['id_ternak']; ?>"
                                            data-status="<?= htmlspecialchars($t['status_kesehatan']); ?>"
                                            <?= $id_ternak === (int)$t['id_ternak'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($t['tag_number']); ?> — <?= htmlspecialchars($t['jenis_ras']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
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
                            <label class="form-label fw-bold">Status Kesehatan Setelah Pemeriksaan</label>
                            <select name="status_kesehatan" id="status_kesehatan" class="form-select" required>
                                <option value="">-- Pilih Status --</option>
                                <?php foreach ($status_valid as $s): ?>
                                    <option value="<?= $s; ?>" <?= $status_kesehatan === $s ? 'selected' : ''; ?>><?= $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Diagnosa</label>
                        <textarea name="diagnosa" class="form-control" rows="3" placeholder="Contoh: Demam, nafsu makan menurun" required><?= htmlspecialchars($diagnosa); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tindakan Medis (Opsional)</label>
                        <textarea name="tindakan_medis" class="form-control" rows="2" placeholder="Contoh: Pemberian antibiotik, isolasi sementara"><?= htmlspecialchars($tindakan_medis); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Obat / Vaksin (Opsional)</label>
                        <input type="text" name="obat_vaksin" class="form-control" placeholder="Contoh: Amoxicillin 10 ml" value="<?= htmlspecialchars($obat_vaksin); ?>">
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                        <button type="submit" name="simpan" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan Rekam Medis</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Saat ternak dipilih, status otomatis terisi dengan status ternak saat ini (bisa diubah dokter)
function isiStatusTernak() {
    var sel = document.getElementById('id_ternak');
    var status = sel.options[sel.selectedIndex].getAttribute('data-status');
    if (status) {
        document.getElementById('status_kesehatan').value = status;
    }
}
document.getElementById('id_ternak').addEventListener('change', isiStatusTernak);

// Jika ternak sudah terpilih dari alamat (?id_ternak=...) dan status belum diisi, isi otomatis
if (document.getElementById('id_ternak').value && !document.getElementById('status_kesehatan').value) {
    isiStatusTernak();
}
</script>

<?php include '../../views/footer.php'; ?>