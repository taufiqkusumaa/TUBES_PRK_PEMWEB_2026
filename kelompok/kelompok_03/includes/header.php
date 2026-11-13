<?php
require_once __DIR__ . '/init.php';

$pageTitle = $pageTitle ?? 'Sistem Antrian Klinik';
$namaPengguna = $_SESSION['nama'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Google Fonts: Bricolage Grotesque -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- CSS Utama Aplikasi -->
    <link rel="stylesheet" href="../assets/style.css?">
</head>

<body class="bg-light d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm topbar">
    <div class="container d-flex justify-content-between align-items-center">

        <a class="brand" href="admin.php">
            Klinik FK Unila
        </a>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="d-flex align-items-center gap-3">
                <span class="navbar-text text-white fw-semibold">
                    <?= htmlspecialchars($namaPengguna, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm px-3" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                    Keluar
                </a>
            </div>
        <?php endif; ?>

    </div>
</nav>

<main class="container py-4 flex-grow-1">