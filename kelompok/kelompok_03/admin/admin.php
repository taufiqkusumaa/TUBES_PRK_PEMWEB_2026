<?php
require_once __DIR__ . '/../includes/cek_login.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

$pageTitle = 'Dashboard Admin - Sistem Antrian Klinik';
require_once '../includes/header.php';
?>

<div class="mb-4">
    <h2 class="text-primary fw-bold mb-1">Dashboard Admin</h2>
    <p class="text-muted small mb-0">Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>. Silakan pilih menu kelola di bawah ini.</p>
</div>

<div class="row g-4">
    <!-- Card Kelola Antrian -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="badge bg-primary mb-2">Antrian Pasien</div>
                    <h4 class="fw-bold text-dark mb-2">Daftar Antrian Hari Ini</h4>
                    <p class="text-muted small">Pantau daftar antrian pasien yang masuk hari ini, lakukan panggil pasien atau selesaikan antrian.</p>
                </div>
                <div class="mt-3">
                    <a href="antrian_list.php" class="btn btn-primary px-4 shadow-sm">
                        Buka Antrian 
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Kelola Dokter (Sudah Diseragamkan Warnanya) -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="badge bg-primary mb-2">Data Dokter</div>
                    <h4 class="fw-bold text-dark mb-2">Kelola Data Dokter</h4>
                    <p class="text-muted small">Tambah dokter baru, ubah spesialisasi, atur kuota harian pasien, serta jadwal hari praktik.</p>
                </div>
                <div class="mt-3">
                    <a href="dokter_list.php" class="btn btn-primary px-4 shadow-sm">
                        Kelola Dokter 
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php'; 
?>