<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION["user_id"];

$query = mysqli_prepare(
    $conn,
    "SELECT 
        users.nama,
        users.email,
        petani.no_whatsapp,
        petani.lokasi_kebun
     FROM users
     INNER JOIN petani ON users.id = petani.user_id
     WHERE users.id = ?"
);

mysqli_stmt_bind_param($query, "i", $user_id);
mysqli_stmt_execute($query);

$result = mysqli_stmt_get_result($query);
$petani = mysqli_fetch_assoc($result);

if (!$petani) {
    exit("Data profil petani tidak ditemukan.");
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Petani - BUYPANEN</title>

    <!-- Bootstrap 5 & Icons (khusus untuk tampilan dan ukuran font Navbar) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- CSS Asli Halaman Profil -->
    <link rel="stylesheet" href="../assets/style.css">

    <style>
  
        body {
            margin: 0 !important;
            padding: 0 !important;
        }

        .navbar {
            background-color: #218838 !important;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 1rem;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
        }

        .navbar .nav-link {
            font-size: 1rem;
            font-weight: 400;
        }

        .profile-container {
            margin-top: 30px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

<!-- NAVBAR DASHBOARD PETANI -->


<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            🌾 BUYPANEN
        </a>

        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link text-white" href="dashboard.php#hero">
                        Beranda
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="dashboard.php#katalog">
                        Katalog
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="../produk/index.php">
                        Produk
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="../petani/profil.php">
                        Profil
                    </a>
                </li>

                <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                    <a class="btn btn-light btn-sm px-3"
                       href="../config/logout.php"
                       onclick="return confirm('Yakin ingin logout?')">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>


<div class="profile-container">

    <div class="profile-header">
        <h1>🌱 BUYPANEN</h1>
        <p>Platform pemasaran hasil panen petani Indonesia.</p>
        <br>
        <h2 style="color:white;">Halo, <?= htmlspecialchars($petani["nama"]) ?>!</h2>
        <p>Selamat datang di dashboard petani.</p>
    </div>

    <div class="profile-card">

        <h2>Profil Petani</h2>
        <p><strong>Nama Lengkap</strong><br>
            <?= htmlspecialchars($petani["nama"]) ?>
        </p>

        <p><strong>Email</strong><br>
            <?= htmlspecialchars($petani["email"]) ?>
        </p>

        <p><strong>Nomor WhatsApp</strong><br>
            <?= htmlspecialchars($petani["no_whatsapp"]) ?>
        </p>

        <p><strong>Lokasi Kebun</strong><br>
            <?= nl2br(htmlspecialchars($petani["lokasi_kebun"])) ?>
        </p>

        <div class="profile-actions">
            <a href="update_profil.php">
                <button type="button">✏️ Edit Profil</button>
            </a>

            <a href="../auth/logout.php"
               onclick="return confirm('Yakin ingin logout?')">
                <button type="button" class="btn-secondary">
                    Logout
                </button>
            </a>
        </div>

    </div>

    <p style="text-align:center; color:#718078; margin-top:25px; font-size:12px;">
        © 2026 BUYPANEN - Mendukung Petani Indonesia
    </p>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>