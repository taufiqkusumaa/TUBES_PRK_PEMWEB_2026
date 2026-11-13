<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Admin Dashboard';

$totalPengguna = 0;
$penggunaAktif = 0;
$penggunaNonaktif = 0;
$totalKategori = 0;
$kategoriPemasukan = 0;
$kategoriPengeluaran = 0;
$penggunaTerbaru = [];

try {
    $totalPengguna = $pdo->query("SELECT COUNT(*) FROM pengguna")->fetchColumn();

    $penggunaAktif = $pdo->query("SELECT COUNT(*) FROM pengguna WHERE status = 'aktif'")->fetchColumn();

    $penggunaNonaktif = $pdo->query("SELECT COUNT(*) FROM pengguna WHERE status = 'nonaktif'")->fetchColumn();

    $totalKategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();

    $kategoriPemasukan = $pdo->query("SELECT COUNT(*) FROM kategori WHERE jenis = 'pemasukan'")->fetchColumn();

    $kategoriPengeluaran = $pdo->query("SELECT COUNT(*) FROM kategori WHERE jenis = 'pengeluaran'")->fetchColumn();

    $stmt = $pdo->query("
        SELECT nama_lengkap, email, role, status, created_at
        FROM pengguna
        ORDER BY created_at DESC
        LIMIT 5
    ");

    $penggunaTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'Gagal mengambil data dashboard.';
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SaveSmart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

<div class="app-container">

    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../components/navbar.php'; ?>

        <div class="dashboard-content admin-content">

            <section class="admin-welcome">
                <div>
                    <span class="admin-eyebrow">ADMINISTRATOR</span>
                    <h1>Dashboard Admin</h1>
                    <p>Selamat datang, <?= htmlspecialchars($_SESSION['user']['nama_lengkap']) ?>! Kelola SaveSmart dari sini.</p>
                </div>
                <div class="admin-date">
                    <?= date('d F Y') ?>
                </div>
            </section>

            <?php if (isset($error)): ?>
                <p class="admin-alert error-alert"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <section class="admin-stats">

                <div class="admin-stat-card">
                    <div class="admin-stat-icon users-icon">♙</div>
                    <div>
                        <p>Total Pengguna</p>
                        <h2><?= number_format((int) $totalPengguna, 0, ',', '.') ?></h2>
                        <small>Seluruh akun terdaftar</small>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-icon users-icon">✓</div>
                    <div>
                        <p>Pengguna Aktif</p>
                        <h2><?= number_format((int) $penggunaAktif, 0, ',', '.') ?></h2>
                        <small>Akun berstatus aktif</small>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-icon category-icon">×</div>
                    <div>
                        <p>Pengguna Nonaktif</p>
                        <h2><?= number_format((int) $penggunaNonaktif, 0, ',', '.') ?></h2>
                        <small>Akun berstatus nonaktif</small>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-icon category-icon">▤</div>
                    <div>
                        <p>Total Kategori</p>
                        <h2><?= number_format((int) $totalKategori, 0, ',', '.') ?></h2>
                        <small>Kategori transaksi</small>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-icon category-icon">↙</div>
                    <div>
                        <p>Kategori Pemasukan</p>
                        <h2><?= number_format((int) $kategoriPemasukan, 0, ',', '.') ?></h2>
                        <small>Kategori uang masuk</small>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-icon category-icon">↗</div>
                    <div>
                        <p>Kategori Pengeluaran</p>
                        <h2><?= number_format((int) $kategoriPengeluaran, 0, ',', '.') ?></h2>
                        <small>Kategori uang keluar</small>
                    </div>
                </div>

            </section>

            <section class="admin-table-card">

                <div class="admin-table-heading">
                    <div>
                        <h3>Pengguna Terbaru</h3>
                        <p>Daftar lima akun yang terakhir terdaftar.</p>
                    </div>
                    <a href="admin_pengguna.php">Lihat Semua →</a>
                </div>

                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nama Pengguna</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($penggunaTerbaru as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['role']))) ?></td>
                                    <td>
                                        <span class="admin-status <?= $user['status'] === 'aktif' ? 'status-active' : 'status-inactive' ?>">
                                            <?= htmlspecialchars(ucfirst($user['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($penggunaTerbaru)): ?>
                                <tr>
                                    <td colspan="5" class="admin-empty">Belum ada pengguna.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </section>

        </div>
    </main>
</div>

<script src="../assets/js/script.js"></script>

</body>
</html>