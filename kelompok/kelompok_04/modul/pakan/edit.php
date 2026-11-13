<?php
include '../../config/koneksi.php';
include '../../views/header.php';

$id = (int) $_GET['id'];
$get_pakan = mysqli_query($koneksi, "SELECT * FROM pakan WHERE id_pakan = '$id'");
$data = mysqli_fetch_assoc($get_pakan);

if (!$data) {
    echo "<script>alert('Data pakan tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

$hasil_jenis_pakan = mysqli_query($koneksi, "SELECT DISTINCT jenis_pakan FROM pakan ORDER BY jenis_pakan");
$daftar_jenis_pakan = [];
while ($jenis = mysqli_fetch_assoc($hasil_jenis_pakan)) {
    $daftar_jenis_pakan[] = $jenis['jenis_pakan'];
}

if (isset($_POST['update'])) {
    $nama_pakan  = trim($_POST['nama_pakan'] ?? '');
    $jenis_pilihan = is_string($_POST['jenis_pakan'] ?? null) ? $_POST['jenis_pakan'] : '';
    $jenis_baru_input = $_POST['jenis_pakan_baru'] ?? '';
    $jenis_baru = is_string($jenis_baru_input) ? trim($jenis_baru_input) : '';
    $jenis_pakan = $jenis_pilihan === '__baru__' ? $jenis_baru : $jenis_pilihan;
    $peruntukan_input = $_POST['peruntukan'] ?? '';
    $peruntukan = is_string($peruntukan_input) ? trim($peruntukan_input) : '';
    $stok_kg     = (float) $_POST['stok_kg'];
    $satuan      = trim($_POST['satuan'] ?? '');

    if ($jenis_pilihan !== '__baru__' && !in_array($jenis_pilihan, $daftar_jenis_pakan, true)) {
        $error = 'Pilih jenis pakan yang tersedia atau tambahkan jenis baru.';
    } elseif ($jenis_pakan === '' || strlen($jenis_pakan) > 50) {
        $error = 'Jenis pakan wajib diisi dan maksimal 50 karakter.';
    } elseif ($peruntukan === '' || strlen($peruntukan) > 100) {
        $error = 'Peruntukan wajib diisi dan maksimal 100 karakter.';
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE pakan SET nama_pakan = ?, jenis_pakan = ?, peruntukan = ?, stok_kg = ?, satuan = ? WHERE id_pakan = ?");
        mysqli_stmt_bind_param($stmt, 'sssdsi', $nama_pakan, $jenis_pakan, $peruntukan, $stok_kg, $satuan, $id);
        $update = mysqli_stmt_execute($stmt);

        if ($update) {
            echo "<script>alert('Data pakan berhasil diperbarui!'); window.location='index.php';</script>";
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
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Data Pakan</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nama Pakan</label>
                            <input type="text" name="nama_pakan" class="form-control" value="<?= htmlspecialchars($data['nama_pakan'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Jenis Pakan</label>
                            <?php
                            $jenis_pilihan_form = is_string($_POST['jenis_pakan'] ?? null)
                                ? $_POST['jenis_pakan']
                                : $data['jenis_pakan'];
                            ?>
                            <select id="jenis_pakan" name="jenis_pakan" class="form-select" required>
                                <?php foreach ($daftar_jenis_pakan as $jenis): ?>
                                    <option value="<?= htmlspecialchars($jenis, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $jenis_pilihan_form === $jenis ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jenis, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__baru__" <?= $jenis_pilihan_form === '__baru__' ? 'selected' : '' ?>>
                                    + Tambah jenis baru
                                </option>
                            </select>
                            <div id="jenis-pakan-baru-wrapper" class="mt-2"
                                 style="<?= $jenis_pilihan_form === '__baru__' ? '' : 'display: none;' ?>">
                                <input type="text" id="jenis_pakan_baru" name="jenis_pakan_baru"
                                       class="form-control" maxlength="50" placeholder="Masukkan jenis pakan baru"
                                       value="<?= htmlspecialchars(is_string($_POST['jenis_pakan_baru'] ?? null) ? $_POST['jenis_pakan_baru'] : '', ENT_QUOTES, 'UTF-8') ?>"
                                       <?= $jenis_pilihan_form === '__baru__' ? 'required' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Peruntukan</label>
                            <input type="text" name="peruntukan" class="form-control" maxlength="100"
                                   value="<?= htmlspecialchars(is_string($_POST['peruntukan'] ?? null) ? $_POST['peruntukan'] : ($data['peruntukan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Stok Saat Ini</label>
                            <input type="number" step="0.01" name="stok_kg" class="form-control" value="<?= $data['stok_kg'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Satuan</label>
                            <input type="text" name="satuan" class="form-control" value="<?= htmlspecialchars($data['satuan'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Batal</a>
                        <button type="submit" name="update" class="btn btn-success"><i class="bi bi-save me-1"></i>Update Data Pakan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('jenis_pakan').addEventListener('change', function () {
        const jenisBaru = this.value === '__baru__';
        const wrapper = document.getElementById('jenis-pakan-baru-wrapper');
        const input = document.getElementById('jenis_pakan_baru');
        wrapper.style.display = jenisBaru ? '' : 'none';
        input.required = jenisBaru;
        if (!jenisBaru) input.value = '';
    });
</script>

<?php include '../../views/footer.php'; ?>