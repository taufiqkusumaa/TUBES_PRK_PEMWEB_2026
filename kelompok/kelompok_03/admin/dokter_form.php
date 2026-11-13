
<?php
require_once __DIR__ . '/../includes/cek_login.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

$id = $_GET['id'] ?? null;
$isEdit = !empty($id);

$dokter = [
    'id' => '',
    'nama' => '',
    'spesialis' => '',
    'kuota_per_hari' => '',
    'hari_praktik' => ''
];

$error = '';

if ($isEdit) {
    try {
        $stmt = $pdo->prepare('SELECT id, nama, spesialis, kuota_per_hari, hari_praktik FROM dokter WHERE id = ?');
        $stmt->execute([$id]);
        $fetchedDokter = $stmt->fetch();
        
        if ($fetchedDokter) {
            $dokter = $fetchedDokter;
        } else {
            header('Location: dokter_list.php?error=' . urlencode('Dokter tidak ditemukan.'));
            exit;
        }
    } catch (PDOException $e) {
        error_log('[Dokter Form] ' . $e->getMessage());
        $error = 'Gagal memuat data dokter.';
    }
}

$hariOptions = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$selectedHari = $isEdit ? explode(',', $dokter['hari_praktik']) : [];

// Memanggil Header Global (sudah include CSS & Nav)
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        
        <!-- Header Judul Form -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="text-primary fw-bold mb-1"><?= $isEdit ? 'Edit' : 'Tambah' ?> Dokter</h2>
                <p class="text-muted small mb-0">Silakan isi data dokter di bawah ini.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Card Container Form -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form id="form-dokter" method="POST" action="dokter_simpan.php">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($dokter['id']) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="nama" class="form-label fw-semibold text-dark">Nama Dokter</label>
                        <input type="text" class="form-control" id="nama" name="nama" 
                               value="<?= htmlspecialchars($dokter['nama']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="spesialis" class="form-label fw-semibold text-dark">Spesialis</label>
                        <input type="text" class="form-control" id="spesialis" name="spesialis" 
                               value="<?= htmlspecialchars($dokter['spesialis']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="kuota_per_hari" class="form-label fw-semibold text-dark">Kuota Per Hari</label>
                        <input type="number" class="form-control" id="kuota_per_hari" name="kuota_per_hari" 
                               value="<?= htmlspecialchars($dokter['kuota_per_hari']) ?>" min="1" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-dark d-block">Hari Praktik</label>
                        <div class="p-3 bg-light rounded border d-flex flex-wrap gap-3">
                            <?php foreach ($hariOptions as $hari): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="hari_praktik[]" 
                                           id="hari_<?= $hari ?>" 
                                           value="<?= $hari ?>"
                                           <?= in_array($hari, $selectedHari) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-medium text-dark" for="hari_<?= $hari ?>">
                                        <?= $hari ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <hr class="my-4 text-muted opacity-25">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="dokter_list.php" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script src="admin.js"></script>
<?php 
require_once '../includes/footer.php'; 
?>