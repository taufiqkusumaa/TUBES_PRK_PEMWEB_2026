<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUYPANEN - Hasil Panen Segar</title>
    <!-- Memanggil Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        /* Mengatur gradasi dan gambar background untuk hero banner */
.hero-bg-image {
    /* Gradasi hijau transparan dipadu dengan gambar background */
    background: linear-gradient(rgba(20, 83, 45, 0.35), rgba(33, 136, 56, 0.75)), url('assets/hero-bg.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 8px 30px rgba(30, 80, 40, 0.15);
}

        /* Mengatur ukuran gambar produk agar seragam */
.img-produk {
    width: 100%;
    height: 200px;
    object-fit: cover;
    object-position: center;
}

/* Background Hero Banner */
.hero-section {
    background:
        linear-gradient(
            rgba(20, 83, 45, 0.40),
            rgba(33, 136, 56, 0.40)
        ),
        url('assets/background.jpg');

    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    padding: 40px 0;
}

/* Mengatur ukuran gambar produk */
.img-produk {
    width: 100%;
    height: 200px;
    object-fit: cover;
    object-position: center;
}

/* Tombol Login Petani */
.btn-login-petani {
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 6px;
    background-color: transparent;
    padding: 6px 14px !important;
    transition: all 0.2s ease;
}

/* Efek hover */
.btn-login-petani:hover {
    color: #ffffff !important;
    background-color: rgba(255, 255, 255, 0.15);
    border-color: #ffffff;
}

/* Efek hover pada kartu produk */
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Ketika kursor berada di atas kartu */
.card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
}
    </style>
</head>
<body class="bg-light">

    <!-- KOMPONEN: NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🌾 BUYPANEN</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
    <li class="nav-item"><a class="nav-link text-white" href="#hero">Beranda</a></li>
    <li class="nav-item"><a class="nav-link text-white" href="#katalog">Katalog</a></li>
    <!-- Ini tambahan link Edukasi-nya -->
    <li class="nav-item"><a class="nav-link text-white" href="edukasi/index.php">Edukasi</a></li>
    
    <li class="nav-item"><a class="btn btn-light ms-lg-3 px-3 mt-2 mt-lg-0" style="color: var(--dark-green); font-weight: 600; font-size: 13px;" href="auth/login.php">Login Petani</a></li>
</ul>
            </div>
        </div>
    </nav>


<!-- KOMPONEN: HERO BANNER DENGAN BACKGROUND GAMBAR -->
<section id="hero" class="hero-section text-white text-center">
    <div class="container py-5">
        <h1 class="display-4 fw-bold mb-3 text-white">
            Panen Segar Langsung dari Petani
        </h1>

    <p class="lead fw-medium mb-4" style="color: #e0f2e3;">
    Dapatkan komoditas sayur, buah, dan pangan berkualitas tanpa perantara.
</p>

        <a href="#katalog" class="btn btn-warning btn-lg fw-semibold px-4 shadow">
            Mulai Belanja
        </a>
    </div>
</section>

    <!-- HALAMAN UTAMA: KATALOG PRODUK -->
    <section id="katalog" class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Katalog Hasil Panen</h2>
            <p class="text-muted">Pilih produk segar dan hubungi petaninya secara langsung</p>
        </div>

        <!-- FITUR PENCARIAN DAN FILTER PRODUK -->
<?php include 'produk/filter-search.php'; ?>

        <!-- TAMPILAN PRODUK (Kartu/Card Bootstrap) -->
        <div class="row g-4">
            
            <?php
            // Catatan: Jika Elan sudah menyiapkan koneksi database, kode ini bisa diaktifkan nanti
            /*
            require_once 'config/database.php';
            $query = "SELECT p.*, u.nama AS nama_petani, pt.lokasi_kebun, pt.no_whatsapp, k.nama_kategori 
                      FROM produk p
                      JOIN users u ON p.user_id = u.id
                      JOIN petani pt ON u.id = pt.user_id
                      JOIN kategori k ON p.kategori_id = k.id
                      WHERE p.status = 'tayang'";
            $result = mysqli_query($conn, $query);

            while ($row = mysqli_fetch_assoc($result)) {
            */
            ?>

            <!-- CONTOH KARTU PRODUK 1 (Dummy Data untuk Tes Tampilan) -->
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="card h-100 shadow-sm border-0">
                    <img src="assets/cabai.jpg" class="img-produk" alt="Cabai Merah Keriting">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="badge bg-danger">Sayur</span>
                        </div>
                        <h5 class="card-title fw-bold">Cabai Merah Keriting</h5>
                        <p class="text-success fw-bold fs-5 mb-1">Rp 35.000 <small class="text-muted fs-6">/ kg</small></p>
                        <p class="text-muted small mb-2">Stok: 50 kg</p>
                        
                        <hr class="my-2">
                        
                        <!-- Informasi Petani -->
                        <p class="small text-secondary mb-1"><i class="bi bi-person-fill"></i> Pak Sutrisno</p>
                        <p class="small text-secondary mb-3"><i class="bi bi-geo-alt-fill"></i> Lampung Selatan</p>
                        
                        <!-- Tombol WA (Nanti dimodifikasi oleh Zahra juga untuk detail) -->
                        <a href="https://wa.me/6281234567890?text=Halo%20Pak%20Sutrisno,%20saya%20tertarik%20membeli%20Cabai%20Merah%20Keriting" target="_blank" class="btn btn-outline-success w-100 mt-auto mb-2">
                            <i class="bi bi-whatsapp"></i> Hubungi Petani
                        </a>
                        <!-- Tombol Detail Produk untuk tugas Zahra -->
                        <a href="#" class="btn btn-outline-secondary w-100">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            </div>

            <!-- CONTOH KARTU PRODUK 2 -->
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="card h-100 shadow-sm border-0">
                   <img src="assets/pisang.jpg" class="img-produk" alt="Pisang Cavendish">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="badge bg-warning text-dark">Buah</span>
                        </div>
                        <h5 class="card-title fw-bold">Pisang Cavendish</h5>
                        <p class="text-success fw-bold fs-5 mb-1">Rp 15.000 <small class="text-muted fs-6">/ sisir</small></p>
                        <p class="text-muted small mb-2">Stok: 20 sisir</p>
                        
                        <hr class="my-2">
                        
                        <p class="small text-secondary mb-1"><i class="bi bi-person-fill"></i> Budi Tani</p>
                        <p class="small text-secondary mb-3"><i class="bi bi-geo-alt-fill"></i> Pesawaran</p>
                        
                        <a href="#" target="_blank" class="btn btn-outline-success w-100 mt-auto mb-2">
                            <i class="bi bi-whatsapp"></i> Hubungi Petani
                        </a>
                        <a href="#" class="btn btn-outline-secondary w-100">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            </div>

            <?php 
            // } // Tutup kurung while jika database sudah jalan
            ?>

        </div>
    </section>

    <!-- KOMPONEN: FOOTER -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2026 BUYPANEN - Tugas Besar Pemrograman Web Kelompok 08</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>