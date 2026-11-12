<?php
require_once __DIR__ . '/config/db.php';
$pageTitle = 'Beranda';
require __DIR__ . '/includes/header.php';
?>
<div class="hero shadow mb-5">
    <div class="row align-items-center">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold">Servis Kendaraan Jadi Lebih Mudah</h1>
            <p class="lead opacity-75">Pesan servis secara online, pilih paket sesuai kebutuhan, dan pantau progres kendaraan Anda kapan saja.</p>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="booking.php" class="btn btn-warning btn-lg"><i class="bi bi-calendar-plus"></i> Pesan Servis</a>
                <a href="tracking.php" class="btn btn-outline-light btn-lg"><i class="bi bi-geo-alt"></i> Lacak Kendaraan</a>
            </div>
        </div>
        <div class="col-lg-5 text-center d-none d-lg-block">
            <i class="bi bi-car-front-fill" style="font-size: 9rem; color: #f59e0b;"></i>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100 p-4 text-center">
            <i class="bi bi-pencil-square fs-1 text-warning"></i>
            <h5 class="fw-bold mt-3">Pesan Online</h5>
            <p class="text-muted mb-0">Isi plat nomor, keluhan, dan pilih paket servis dalam satu formulir.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100 p-4 text-center">
            <i class="bi bi-clock-history fs-1 text-warning"></i>
            <h5 class="fw-bold mt-3">Riwayat Servis</h5>
            <p class="text-muted mb-0">Lihat semua riwayat servis kendaraan Anda dalam satu halaman.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100 p-4 text-center">
            <i class="bi bi-diagram-3 fs-1 text-warning"></i>
            <h5 class="fw-bold mt-3">Pantau Progres</h5>
            <p class="text-muted mb-0">Ikuti tahap servis dari Pending, Antre, Dikerjakan, hingga Siap Diambil.</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
