<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$prefix = $prefix ?? '../';
$judul_halaman = $judul_halaman ?? 'Edukasi | BUYPANEN';
$sudah_login = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($judul_halaman) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    :root { --hijau: #198754; --hijau-muda: #e7f1e4; }
    h1, h2 { color: var(--hijau); font-weight: 700; }
    .btn-panen { background: var(--hijau); color: #fff; }
    .btn-panen:hover { background: #157347; color: #fff; }
    .badge-kategori { background: var(--hijau-muda); color: var(--hijau); }
    .isi-artikel { max-width: 70ch; line-height: 1.8; font-size: 1.05rem; }
    main { flex: 1 0 auto; }

    .btn-login-petani {
      color: #ffffff !important;
      border: 1px solid rgba(255, 255, 255, 0.7);
      border-radius: 6px;
      background-color: transparent;
      padding: 6px 14px !important;
      transition: all 0.2s ease;
    }
    .btn-login-petani:hover {
      background-color: rgba(255, 255, 255, 0.15);
      border-color: #ffffff;
    }
    .card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .card:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
    }
  </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $prefix ?>index.php">🌾 BUYPANEN</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php
        $tujuan_beranda = $sudah_login ? $prefix . 'petani/dashboard.php' : $prefix . 'index.php';
        ?>
<li class="nav-item"><a class="nav-link" href="<?= $tujuan_beranda ?>#hero">Beranda</a></li>
<li class="nav-item"><a class="nav-link" href="<?= $tujuan_beranda ?>#katalog">Katalog</a></li>
<?php if ($sudah_login): ?>
  <li class="nav-item"><a class="nav-link" href="<?= $prefix ?>produk/index.php">Produk</a></li>
<?php endif; ?>
<li class="nav-item"><a class="nav-link active" href="<?= $prefix ?>edukasi/index.php">Edukasi</a></li>
<?php if ($sudah_login): ?>
  <li class="nav-item"><a class="nav-link" href="<?= $prefix ?>petani/profil.php">Profil</a></li>
<?php endif; ?>
        <?php if ($sudah_login): ?>
          <li class="nav-item">
            <a class="nav-link btn-login-petani ms-lg-3 px-3 mt-2 mt-lg-0" href="<?= $prefix ?>auth/logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link btn-login-petani ms-lg-3 px-3 mt-2 mt-lg-0" href="<?= $prefix ?>auth/login.php">Login Petani</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>