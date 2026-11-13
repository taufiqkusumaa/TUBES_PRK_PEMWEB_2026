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

$pageTitle = 'Manajemen Pengguna';
$pengguna = [];

try {
    $stmt = $pdo->prepare('CALL sp_admin_pengguna()');
    $stmt->execute();
    $pengguna = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = 'Gagal mengambil data pengguna.';
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - SaveSmart</title>
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
                    <h1>Manajemen Pengguna</h1>
                    <p>Kelola akun pengguna yang terdaftar di SaveSmart.</p>
                </div>
            </section>

            <?php if (isset($_SESSION['success'])): ?>
                <p class="admin-alert success-alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </p>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <p class="admin-alert error-alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </p>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <p class="admin-alert error-alert">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <section class="admin-table-card">

                <div class="admin-table-heading">
                    <div>
                        <h3>Daftar Pengguna</h3>
                        <p>Total <?= count($pengguna) ?> pengguna terdaftar.</p>
                    </div>
                </div>

                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Keluarga</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($pengguna as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>

                                    <td><?= htmlspecialchars($user['email']) ?></td>

                                    <td><?= htmlspecialchars($user['nama_keluarga'] ?? '-') ?></td>

                                    <td>
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['role']))) ?>
                                    </td>

                                    <td>
                                        <span class="admin-status <?= $user['status'] === 'aktif' ? 'status-active' : 'status-inactive' ?>">
                                            <?= htmlspecialchars(ucfirst($user['status'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ((int) $user['id_pengguna'] !== (int) $_SESSION['user']['id_pengguna']): ?>

                                            <form action="../backend/admin/pengguna_status_process.php"
                                                  method="POST"
                                                  onsubmit="return confirm('Yakin ingin mengubah status pengguna ini?')">

                                                <input type="hidden" name="id_pengguna"
                                                       value="<?= (int) $user['id_pengguna'] ?>">

                                                <input type="hidden" name="status"
                                                       value="<?= $user['status'] === 'aktif' ? 'nonaktif' : 'aktif' ?>">

                                                <button type="submit"
                                                        class="<?= $user['status'] === 'aktif' ? 'admin-delete-button' : 'admin-edit-button' ?>">
                                                    <?= $user['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </button>

                                            </form>

                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($pengguna)): ?>
                                <tr>
                                    <td colspan="6" class="admin-empty">
                                        Belum ada pengguna.
                                    </td>
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