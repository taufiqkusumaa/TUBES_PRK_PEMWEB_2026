
<?php
require_once '../includes/header.php';
require_once '../config/koneksi.php';

// Validasi role admin 
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

try {
    $stmt = $pdo->query('SELECT id, nama, spesialis, kuota_per_hari, hari_praktik FROM dokter ORDER BY nama');
    $dokterList = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('[Dokter List] ' . $e->getMessage());
    $dokterList = [];
    $error = 'Gagal memuat data dokter.';
}
?>

<!-- Breadcrumb Navigasi -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small">
         <li class="breadcrumb-item"><a href="admin.php" class="text-decoration-underline fw-medium" style="color: #0f766e;">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Kelola Dokter</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="text-primary fw-bold mb-1">Kelola Dokter</h2>
        <p class="text-muted small mb-0">Kelola daftar dokter praktik, spesialisasi, kuota harian, dan hari praktik.</p>
    </div>
    <a href="dokter_form.php" class="btn btn-primary px-3 shadow-sm">
        + Tambah Dokter
    </a>
</div>

<!-- Alert Notifikasi Sesuai Tema -->
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Container Card Tabel -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($dokterList)): ?>
            <p class="text-muted p-4 text-center mb-0">Belum ada data dokter.</p>
        <?php else: ?>
        <!-- Kotak Pencarian & Filter Dokter -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <input type="text" id="cari-admin" class="form-control form-control-sm" style="max-width: 280px;" placeholder="Cari nama dokter atau spesialis...">
            <select id="filter-status-admin" class="form-select form-select-sm" style="max-width: 180px;">
                 <option value="">Semua Hari Praktik</option>
                 <option value="senin">Senin</option>
                 <option value="selasa">Selasa</option>
                 <option value="rabu">Rabu</option>
                 <option value="kamis">Kamis</option>
                 <option value="jumat">Jumat</option>
                 <option value="sabtu">Sabtu</option>
                 <option value="minggu">Minggu</option>
            </select>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Nama</th>
                            <th>Spesialis</th>
                            <th>Kuota/Hari</th>
                            <th>Hari Praktik</th>
                            <th class="text-center" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dokterList as $dokter): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($dokter['nama']) ?></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?= htmlspecialchars($dokter['spesialis']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold"><?= htmlspecialchars($dokter['kuota_per_hari']) ?></span> Pasien
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars(str_replace(',', ', ', $dokter['hari_praktik'])) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="dokter_form.php?id=<?= $dokter['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                    <a href="dokter_hapus.php?id=<?= $dokter['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Yakin ingin menghapus dokter ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="admin.js"></script>
<?php 
require_once '../includes/footer.php'; 
?>