
<?php
// admin/dashboard.php
require_once '../includes/auth.php';
require_once '../config/database.php';

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$adminName = $_SESSION['admin_username'] ?? 'Admin';

// Statistik
$stat = [
    ['Total Artikel', $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn()],
    ['Published', $pdo->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn()],
    ['Draft', $pdo->query("SELECT COUNT(*) FROM articles WHERE status='draft'")->fetchColumn()],
    ['Kategori', $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn()],
    ['Tips Sehat', $pdo->query("SELECT COUNT(*) FROM tips")->fetchColumn()],
    ['Total Views', $pdo->query("SELECT COALESCE(SUM(views),0) FROM articles")->fetchColumn()],
];

// Artikel draft
$draft = $pdo->query(
    "SELECT a.title, a.created_at, c.name AS kategori
     FROM articles a
     JOIN categories c ON a.category_id = c.id
     WHERE a.status = 'draft'
     ORDER BY a.created_at DESC
     LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// Artikel terbaru
$terbaru = $pdo->query(
    "SELECT a.title, a.status, a.views, a.created_at,
            c.name AS kategori
     FROM articles a
     JOIN categories c ON a.category_id = c.id
     ORDER BY a.created_at DESC
     LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

// Artikel per kategori
$per_kategori = $pdo->query(
    "SELECT c.name, COUNT(a.id) AS total
     FROM categories c
     LEFT JOIN articles a ON a.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY total DESC, c.name"
)->fetchAll(PDO::FETCH_ASSOC);

$maks = max(
    1,
    ...array_map(
        fn($k) => (int)$k['total'],
        $per_kategori ?: [['total' => 1]]
    )
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - SehatIn</title>

    <link rel="stylesheet" href="../assets/css/admin-theme.css?v=2">
</head>

<body>

<nav class="admin-navbar">
    <a href="dashboard.php" class="brand">
        <span class="brand-mark">S</span>
        <span>Sehat<span>In</span></span>
        <small>ADMIN</small>
    </a>

    <div class="admin-menu">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="articles.php">Artikel</a>
        <a href="categories.php">Kategori</a>
        <a href="tips.php">Tips Kesehatan</a>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>
</nav>

<main class="admin-container">

    <!-- Header -->
    <section class="welcome-section">
        <div>
            <span class="welcome-label">ADMINISTRATOR</span>
            <h1>Selamat datang, <?= $e($adminName) ?></h1>
            <p>Berikut ringkasan aktivitas pengelolaan konten SehatIn.</p>
        </div>
        <div class="welcome-date">
            <?= date('d M Y') ?>
        </div>
    </section>

    <!-- Statistik -->
    <section class="stats-grid">

        <?php foreach ($stat as [$label, $n]): ?>
            <div class="stat-card">
                <div class="stat-label"><?= $e($label) ?></div>
                <div class="stat-number">
                    <?= number_format((int)$n, 0, ',', '.') ?>
                </div>
                <div class="stat-caption">Data saat ini</div>
            </div>
        <?php endforeach; ?>

    </section>

    <!-- Konten Dashboard -->
    <section class="dashboard-grid">

        <!-- Artikel Terbaru -->
        <div class="panel article-panel">
            <div class="panel-heading">
                <div>
                    <h2>Artikel Terbaru</h2>
                    <p>Daftar artikel yang terakhir ditambahkan.</p>
                </div>
                <a href="articles.php" class="text-link">Lihat semua</a>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Judul Artikel</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!$terbaru): ?>
                        <tr>
                            <td colspan="5" class="empty">
                                Belum ada artikel.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($terbaru as $a): ?>
                            <tr>
                                <td class="article-title">
                                    <?= $e($a['title']) ?>
                                </td>

                                <td><?= $e($a['kategori']) ?></td>

                                <td>
                                    <span class="status-badge <?= $e($a['status']) ?>">
                                        <?= $e(ucfirst($a['status'])) ?>
                                    </span>
                                </td>

                                <td><?= number_format((int)$a['views'], 0, ',', '.') ?></td>

                                <td>
                                    <?= date('d M Y', strtotime($a['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Artikel Draft -->
        <div class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Artikel Draft</h2>
                    <p>Artikel yang belum dipublikasikan.</p>
                </div>
                <span class="count-badge"><?= count($draft) ?></span>
            </div>

            <div class="draft-list">
                <?php if (!$draft): ?>
                    <div class="empty">
                        Tidak ada artikel draft.
                    </div>
                <?php else: ?>
                    <?php foreach ($draft as $d): ?>
                        <div class="draft-item">
                            <div class="draft-indicator"></div>

                            <div class="draft-content">
                                <strong><?= $e($d['title']) ?></strong>
                                <span>
                                    <?= $e($d['kategori']) ?>
                                    ·
                                    <?= date('d M Y', strtotime($d['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Artikel per Kategori -->
        <div class="panel category-panel">
            <div class="panel-heading">
                <div>
                    <h2>Artikel per Kategori</h2>
                    <p>Jumlah artikel berdasarkan kategori.</p>
                </div>
            </div>

            <?php if (!$per_kategori): ?>
                <div class="empty">Belum ada kategori.</div>
            <?php else: ?>
                <div class="category-list">
                    <?php foreach ($per_kategori as $k): ?>
                        <?php
                        $total = (int)$k['total'];
                        $persen = round(($total / $maks) * 100);
                        ?>

                        <div class="category-item">
                            <div class="category-info">
                                <span><?= $e($k['name']) ?></span>
                                <strong><?= $total ?></strong>
                            </div>

                            <div class="progress-track">
                                <div
                                    class="progress-fill"
                                    style="width: <?= $persen ?>%"
                                ></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </section>

    <footer class="admin-footer">
        <span>SehatIn Admin Panel</span>
        <span>Pengelolaan Informasi Kesehatan</span>
    </footer>

</main>

</body>
</html>
