
<?php
// kelompok/kelompok_12/admin/tips.php

require_once '../includes/auth.php';
require_once '../config/database.php';

// Mengambil semua data tips
$stmt = $pdo->query(
    "SELECT * FROM tips ORDER BY created_at DESC"
);

$tips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pesan sukses
$success = '';

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Fungsi keamanan output
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tips Kesehatan</title>

    <!-- CSS Tips -->
    <link rel="stylesheet" href="../assets/css/tips.css?v=3">
</head>
<body>

    <!-- NAVBAR -->
    <nav class="admin-navbar">
        <div class="brand">
            <div class="brand-mark">S</div>
            <span class="brand-name">SehatIn</span>
        </div>

        <div class="admin-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="categories.php">Kategori</a>
            <a href="articles.php">Artikel</a>
            <a href="tips.php" class="active">Tips Kesehatan</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <!-- CONTAINER -->
    <main class="admin-container">

        <!-- WELCOME SECTION -->
        <section class="welcome-section">
            <div>
                <span class="welcome-label">SEHATIN ADMIN</span>
                <h1>Kelola Tips Kesehatan</h1>
                <p>
                    Selamat datang, <?= $e($_SESSION['admin_username']) ?>!
                    Kelola informasi dan tips kesehatan di sini.
                </p>
            </div>
        </section>

        <!-- NOTIFIKASI -->
        <?php if ($success !== ''): ?>
            <div class="pesan-sukses">
                <?= $e($success) ?>
            </div>
        <?php endif; ?>

        <!-- PANEL TIPS -->
        <section class="panel">

            <div class="panel-heading">
                <div>
                    <h2>Daftar Tips Kesehatan</h2>
                    <p>Kelola seluruh tips kesehatan yang tersedia.</p>
                </div>

                <span class="count-badge">
                    <?= count($tips) ?> Tips
                </span>
            </div>

            <div class="panel-toolbar">
                <span class="toolbar-description">
                    Data tips kesehatan
                </span>

                <a href="tip-add.php" class="add-button">
                    + Tambah Tips
                </a>
            </div>

            <!-- TABEL -->
            <?php if (count($tips) > 0): ?>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th class="number-column">No</th>
                                <th>Judul Tips</th>
                                <th>Deskripsi</th>
                                <th>Gambar</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $no = 1; ?>

                            <?php foreach ($tips as $tip): ?>
                                <tr>
                                    <td class="number-column">
                                        <?= $no++ ?>
                                    </td>

                                    <td class="category-name">
                                        <?= $e($tip['title']) ?>
                                    </td>

                                    <td class="category-description">
                                        <?= $e($tip['description']) ?>
                                    </td>

                                    <td>
                                        <?= $e($tip['image'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= $e($tip['created_at']) ?>
                                    </td>

                                    <td>
                                        <div class="action-buttons">

                                            <a
                                                href="tip-edit.php?id=<?= (int) $tip['id'] ?>"
                                                class="edit-button">
                                                Edit
                                            </a>

                                            <form
                                                action="tip-delete.php"
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus tips ini?');">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $tip['id'] ?>">

                                                <button
                                                    type="submit"
                                                    class="delete-button">
                                                    Hapus
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="empty">
                    <h3>Belum Ada Tips Kesehatan</h3>
                    <p>
                        Belum ada tips yang ditambahkan.
                        Klik tombol Tambah Tips untuk membuat tips baru.
                    </p>
                </div>

            <?php endif; ?>

        </section>

        <!-- FOOTER -->
        <footer class="admin-footer">
            <p>© <?= date('Y') ?> SehatIn. Admin Panel.</p>
        </footer>

    </main>

</body>
</html>
