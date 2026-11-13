<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';

if ($role !== 'kepala_keluarga') {
    header('Location: dashboard.php');
    exit;
}

$idKeluarga = $_SESSION['user']['id_keluarga'] ?? null;

if (!$idKeluarga) {
    die('Data keluarga tidak ditemukan.');
}

$pageTitle = 'Anggota Keluarga';

// Mengambil daftar anggota dalam keluarga yang sama
$stmt = $pdo->prepare("
    SELECT id_pengguna, nama_lengkap, email, status, created_at
    FROM pengguna
    WHERE id_keluarga = ?
      AND role = 'anggota_keluarga'
    ORDER BY nama_lengkap ASC
");

$stmt->execute([$idKeluarga]);
$anggota = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalAnggota = count($anggota);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Anggota Keluarga - SaveSmart</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/anggota_keluarga.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>

<body>

<div class="app-container">

    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../components/navbar.php'; ?>

        <div class="anggota-content">

            <section class="anggota-welcome">
                <div>
                    <h2>Kelola Anggota Keluarga</h2>
                    <p>Tambahkan dan pantau anggota yang tergabung dalam keluarga Anda.</p>
                </div>
            </section>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <section class="anggota-summary">
                <div class="anggota-summary-icon">♧</div>
                <div>
                    <p>Total Anggota Keluarga</p>
                    <h3><?= $totalAnggota ?> <span>orang</span></h3>
                </div>
            </section>

            <section class="anggota-card">

                <div class="anggota-card-header">
                    <div>
                        <h3>Daftar Anggota</h3>
                        <p>Anggota yang terdaftar dalam keluarga Anda.</p>
                    </div>
                </div>

                <?php if ($totalAnggota > 0): ?>

                    <div class="table-wrapper">
                        <table class="anggota-table">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Nama Lengkap</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($anggota as $index => $user): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <div class="anggota-name">
                                                <div class="anggota-avatar">
                                                    <?= htmlspecialchars(strtoupper(substr($user['nama_lengkap'], 0, 1))) ?>
                                                </div>
                                                <strong>
                                                    <?= htmlspecialchars($user['nama_lengkap']) ?>
                                                </strong>
                                            </div>
                                        </td>

                                        <td><?= htmlspecialchars($user['email']) ?></td>

                                        <td>
                                            <span class="status-badge <?= $user['status'] === 'aktif' ? 'status-active' : 'status-inactive' ?>">
                                                <?= htmlspecialchars(ucfirst($user['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>

                    <div class="empty-state">
                        <div class="empty-icon">♧</div>
                        <h4>Belum Ada Anggota</h4>
                        <p>Tambahkan anggota keluarga melalui formulir di bawah.</p>
                    </div>

                <?php endif; ?>

            </section>

            <section class="anggota-card form-anggota-card">

                <div class="anggota-card-header">
                    <div>
                        <h3>Tambah Anggota Baru</h3>
                        <p>Buat akun untuk anggota keluarga Anda.</p>
                    </div>
                </div>

                <form action="../backend/auth/tambah_anggota_process.php" method="POST">

                    <div class="form-grid">

                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input
                                type="text"
                                id="nama_lengkap"
                                name="nama_lengkap"
                                placeholder="Masukkan nama lengkap"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="contoh@email.com"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Minimal 8 karakter"
                                minlength="8"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="konfirmasi_password">Konfirmasi Password</label>
                            <input
                                type="password"
                                id="konfirmasi_password"
                                name="konfirmasi_password"
                                placeholder="Masukkan ulang password"
                                minlength="8"
                                required
                            >
                        </div>

                    </div>

                    <div class="form-footer">
                        <p>Akun baru otomatis terhubung dengan keluarga Anda.</p>
                        <button type="submit" class="btn-tambah-anggota">
                            + Tambah Anggota
                        </button>
                    </div>

                </form>

            </section>

        </div>

    </main>

</div>

<script src="../assets/js/script.js"></script>

</body>
</html>