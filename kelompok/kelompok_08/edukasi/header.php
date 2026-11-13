<?php
$judul_halaman = $judul_halaman ?? 'Edukasi | BuyPanen';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($judul_halaman) ?></title>
    
    <!-- Memanggil Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        /* ==========================================
           1. STYLE ASLI DARI INDEX.PHP (MILIK PANDU)
           ========================================== */
        .hero-bg-image {
            background: linear-gradient(rgba(20, 83, 45, 0.35), rgba(33, 136, 56, 0.75)), url('../assets/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(30, 80, 40, 0.15);
        }

        .hero-section {
            background: linear-gradient(rgba(20, 83, 45, 0.40), rgba(33, 136, 56, 0.40)), url('../assets/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 40px 0;
        }

        .img-produk {
            width: 100%;
            height: 200px;
            object-fit: cover;
            object-position: center;
        }

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

        .card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important; }

        /* ==========================================
           2. PENGAMAN PADDING & ARTIKEL EDUKASI
           ========================================== */
        body { padding: 0 !important; color: #26302a; }
        :root { --hijau: #2f6b3a; --hijau-muda: #e7f1e4; --tanah: #4a3426; }
        .btn-panen { background: var(--hijau); color: #fff; }
        .btn-panen:hover { background: #24542d; color: #fff; }
        .badge-kategori { background: var(--hijau-muda); color: var(--hijau); }
        .isi-artikel { max-width: 70ch; line-height: 1.8; font-size: 1.05rem; }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- KOMPONEN: NAVBAR SAMA PERSIS DENGAN INDEX.PHP -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">🌾 BUYPANEN</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link text-white" href="../index.php#hero">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="../index.php#katalog">Katalog</a></li>
                    <li class="nav-item"><a class="nav-link text-white active" href="index.php">Edukasi</a></li>
                    
                    <!-- Warna tombol dikunci ke #14532d agar tidak bergantung pada variabel css eksternal -->
                    <li class="nav-item"><a class="btn btn-light ms-lg-3 px-3 mt-2 mt-lg-0" style="color: #14532d; font-weight: 600; font-size: 13px;" href="../auth/login.php">Login Petani</a></li>
                </ul>
            </div>
        </div>
    </nav>