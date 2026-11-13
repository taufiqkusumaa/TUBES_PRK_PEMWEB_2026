<?php
$halaman = $_SERVER['SCRIPT_NAME'] ?? '';

$aktifDashboard = basename(dirname($halaman)) === 'admin';
$aktifObat      = strpos($halaman, '/admin/obat/') !== false;
$aktifKategori  = strpos($halaman, '/admin/kategori/') !== false;

if (!isset($judulHalaman)) {
    if ($aktifObat) {
        $judulHalaman = "Data Obat";
    } elseif ($aktifKategori) {
        $judulHalaman = "Data Kategori";
    } else {
        $judulHalaman = "Dashboard";
    }
}

$baseAdmin = "/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judulHalaman) ?> | Admin KlikObat</title>
    <link rel="stylesheet" href="<?= $baseAdmin ?>/frontend/assets/css/admin.css">
</head>
<body>

<div class="admin-container">

    <?php require_once __DIR__ . "/sidebar.php"; ?>

    <main class="admin-main">

        <header class="admin-header">
            <div class="header-title">
                <h1><?= htmlspecialchars($judulHalaman) ?></h1>
            </div>

            <div class="admin-user">
                <span class="user-name">Admin KlikObat</span>
            </div>
        </header>

        <div class="admin-content">