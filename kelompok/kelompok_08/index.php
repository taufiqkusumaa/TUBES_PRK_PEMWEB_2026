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
        body {
            overflow-y: scroll;
        }

        /* Mengatur gradasi dan gambar background untuk hero banner */
        .hero-bg-image {
            background: linear-gradient(rgba(20, 83, 45, 0.35), rgba(33, 136, 56, 0.75)), url('assets/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(30, 80, 40, 0.15);
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

        /* Mengatur ukuran gambar produk agar seragam */
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

        .btn-login-petani:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.15);
            border-color: #ffffff;
        }

        /* Efek hover pada kartu produk */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

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
                    <li class="nav-item"><a class="nav-link text-white" href="edukasi/index.php">Edukasi</a></li>
                    <li class="nav-item"><a class="btn btn-light ms-lg-3 px-3 mt-2 mt-lg-0" style="color: #14532d; font-weight: 600; font-size: 13px;" href="auth/login.php">Login Petani</a></li>
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

        <!-- FITUR PENCARIAN, FILTER, DAN DAFTAR KARTU PRODUK -->
        <?php include 'produk/filter-search.php'; ?>
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