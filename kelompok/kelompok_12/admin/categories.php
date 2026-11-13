
<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$e = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

// Mengambil kategori dan jumlah artikel
$query = "SELECT
            c.id,
            c.name,
            c.description,
            COUNT(a.id) AS total_articles
          FROM categories c
          LEFT JOIN articles a ON c.id = a.category_id
          GROUP BY c.id, c.name, c.description
          ORDER BY c.name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - SehatIn</title>

    <!-- CSS Halaman Kategori -->
    <link rel="stylesheet" href="../assets/css/categories_admin.css?v=3">
</head>

<body>

<!-- NAVBAR -->
<nav class="admin-navbar">

    <a href="dashboard.php" class="brand">
        <span class="brand-mark">S</span>
        <span class="brand-name">Sehat<span>In</span></span>
        <small>ADMIN</small>
    </a>

    <div class="admin-menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="articles.php">Artikel</a>
        <a href="categories.php" class="active">Kategori</a>
        <a href="tips.php">Tips Kesehatan</a>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>

</nav>

<!-- MAIN CONTENT -->
<main class="admin-container">

    <!-- HEADER -->
    <section class="welcome-section">

        <div>
            <span class="welcome-label">MANAJEMEN KONTEN</span>

            <h1>Kelola Kategori</h1>

            <p>
                Atur dan kelola kategori untuk mengorganisasi artikel kesehatan SehatIn.
            </p>
        </div>

        <div class="welcome-date">
            <?= date('d M Y') ?>
        </div>

    </section>

    <!-- PANEL KATEGORI -->
    <section class="panel">

        <div class="panel-heading">

            <div>
                <h2>Daftar Kategori</h2>
                <p>Semua kategori yang tersedia dalam sistem.</p>
            </div>

            <span class="count-badge">
                <?= count($categories) ?> Kategori
            </span>

        </div>

        <!-- TOMBOL TAMBAH -->
        <div class="panel-toolbar">

            <a href="category-add.php" class="add-button">
                <span>+</span>
                Tambah Kategori
            </a>

        </div>

        <!-- TABEL -->
        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Jumlah Artikel</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($categories)): ?>

                    <?php foreach ($categories as $index => $category): ?>

                        <tr>

                            <td class="number-column">
                                <?= $index + 1 ?>
                            </td>

                            <td class="category-name">
                                <?= $e($category['name']) ?>
                            </td>

                            <td class="category-description">
                                <?= $e($category['description'] ?: '-') ?>
                            </td>

                            <td>
                                <span class="article-count">
                                    <?= (int)$category['total_articles'] ?> Artikel
                                </span>
                            </td>

                            <td>

                                <div class="action-buttons">

                                    <a
                                        class="edit-button"
                                        href="category-edit.php?id=<?= (int)$category['id'] ?>">
                                        Edit
                                    </a>

                                    <a
                                        class="delete-button"
                                        href="category-delete.php?id=<?= (int)$category['id'] ?>"
                                        onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                        Hapus
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="empty">
                            Belum ada kategori.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- FOOTER -->
    <footer class="admin-footer">
        <span>SehatIn Admin Panel</span>
        <span>Pengelolaan Informasi Kesehatan</span>
    </footer>

</main>

</body>
</html>
