<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Mengambil data pencarian dan filter
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';

// Pengaturan pagination
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

// Mengambil data kategori
$categoryQuery = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Menyiapkan kondisi pencarian dan filter
$where = " WHERE 1=1 ";
$params = [];

if ($search !== '') {
    $where .= " AND articles.title LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if ($category_id !== '' && ctype_digit((string) $category_id)) {
    $where .= " AND articles.category_id = :category_id";
    $params[':category_id'] = (int) $category_id;
}

if (in_array($status, ['draft', 'published', 'archived'], true)) {
    $where .= " AND articles.status = :status";
    $params[':status'] = $status;
}

// Menghitung seluruh artikel sesuai pencarian dan filter
$countQuery = "SELECT COUNT(*) FROM articles" . $where;
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);

$totalArticles = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalArticles / $limit));

// Memastikan halaman tidak melebihi jumlah halaman
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// Mengambil artikel berdasarkan halaman
$sql = "SELECT
            articles.id,
            articles.title,
            articles.content,
            articles.image,
            articles.author,
            articles.status,
            articles.created_at,
            categories.name AS category_name
        FROM articles
        LEFT JOIN categories
        ON articles.category_id = categories.id
        $where
        ORDER BY articles.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Memasukkan parameter pencarian dan filter
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Memasukkan batas jumlah data dan posisi awal
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fungsi untuk menampilkan teks dengan aman
function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Membuat URL pagination dengan tetap membawa filter
function pageUrl($pageNumber, $search, $category_id, $status) {
    return '?' . http_build_query([
        'search' => $search,
        'category_id' => $category_id,
        'status' => $status,
        'page' => $pageNumber
    ]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel - Sehatin</title>
    <link rel="stylesheet" href="../assets/css/articles.css?v=3">
</head>
<body class="admin-articles-page">

<nav class="admin-navbar">
    <a href="dashboard.php" class="brand">
        <span class="brand-mark">S</span>
        <span>Sehat<span>In</span></span>
        <small>ADMIN</small>
    </a>

    <div class="admin-menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="articles.php" class="active" aria-current="page">Artikel</a>
        <a href="categories.php">Kategori</a>
        <a href="tips.php">Tips Kesehatan</a>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>
</nav>

<main class="admin-container">
    <section class="page-heading">
        <div>
            <span class="eyebrow">MANAJEMEN KONTEN</span>
            <h1>Kelola Artikel</h1>
            <p>Atur, cari, dan pantau artikel kesehatan yang tersedia di SehatIn.</p>
        </div>
        <a href="article-add.php" class="primary-button">+ Tambah Artikel</a>
    </section>

    <section class="content-panel">
        <div class="panel-heading">
            <div>
                <h2>Daftar Artikel</h2>
                <p>Artikel terbaru ditampilkan terlebih dahulu.</p>
            </div>
            <span class="total-pill"><?= number_format($totalArticles, 0, ',', '.') ?> artikel</span>
        </div>

        <form method="GET" class="filter-form">
            <label class="search-field">
                <span class="field-icon">⌕</span>
                <input type="text" name="search" placeholder="Cari judul artikel..." value="<?= escape($search) ?>" aria-label="Cari judul artikel">
            </label>

            <select name="category_id" aria-label="Filter kategori">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= (string) $category_id === (string) $category['id'] ? 'selected' : '' ?>><?= escape($category['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status" aria-label="Filter status">
                <option value="">Semua Status</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>

            <button type="submit" class="filter-button">Terapkan</button>
            <a href="articles.php" class="reset-button">Reset</a>
        </form>

        <div class="table-wrap">
            <table class="articles-table">
                <thead>
                    <tr>
                        <th class="number-col">No.</th>
                        <th>Artikel</th>
                        <th>Kategori</th>
                        <th>Penulis</th>
                        <th>Ringkasan</th>
                        <th>Status</th>
                        <th>Tanggal dibuat</th>
                        <th class="action-col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($articles) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($articles as $article): ?>
                            <tr>
                                <td class="number-cell"><?= $no++ ?></td>
                                <td>
                                    <div class="article-cell">
                                        <?php if (!empty($article['image'])): ?>
                                            <img class="article-thumb" src="../<?= escape($article['image']) ?>" alt="Gambar artikel">
                                        <?php else: ?>
                                            <div class="article-thumb thumb-placeholder">S</div>
                                        <?php endif; ?>
                                        <div class="article-title">
                                            <strong><?= escape($article['title']) ?></strong>
                                            <small>ID Artikel: #<?= (int) $article['id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="category-text"><?= escape($article['category_name'] ?? '-') ?></span></td>
                                <td><?= escape($article['author']) ?></td>
                                <td class="excerpt-cell"><?= escape(substr(strip_tags($article['content']), 0, 100)) ?><?= strlen(strip_tags($article['content'])) > 100 ? '...' : '' ?></td>
                                <td>
                                    <span class="status-badge status-<?= escape($article['status']) ?>">
                                        <span class="status-dot"></span><?= escape(ucfirst($article['status'])) ?>
                                    </span>
                                </td>
                                <td class="date-cell"><?= escape($article['created_at']) ?></td>
                                <td>
                                    <div class="action-links">
                                        <a class="edit-link" href="article-edit.php?id=<?= (int) $article['id'] ?>">Edit</a>
                                        <a class="delete-link" href="article-delete.php?id=<?= (int) $article['id'] ?>" onclick="return confirm('Yakin ingin menghapus artikel ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <span class="empty-symbol">⌕</span>
                                    <strong>Artikel tidak ditemukan</strong>
                                    <p>Coba ubah kata kunci atau filter yang digunakan.</p>
                                    <a href="articles.php">Tampilkan semua artikel</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p class="table-info">
                Menampilkan <strong><?= count($articles) ?></strong> dari <strong><?= $totalArticles ?></strong> artikel
                <span class="info-separator">•</span> Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong>
            </p>
            <nav class="pagination" aria-label="Navigasi halaman">
                <?php if ($page > 1): ?>
                    <a href="<?= escape(pageUrl($page - 1, $search, $category_id, $status)) ?>" aria-label="Halaman sebelumnya">‹</a>
                <?php else: ?>
                    <span class="disabled" aria-disabled="true">‹</span>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active" aria-current="page"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= escape(pageUrl($i, $search, $category_id, $status)) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="<?= escape(pageUrl($page + 1, $search, $category_id, $status)) ?>" aria-label="Halaman berikutnya">›</a>
                <?php else: ?>
                    <span class="disabled" aria-disabled="true">›</span>
                <?php endif; ?>
            </nav>
        </div>
    </section>

    <footer class="admin-footer">
        <span>© <?= date('Y') ?> SehatIn Admin Panel</span>
        <span>Pengelolaan Informasi Kesehatan</span>
    </footer>
</main>
</body>
</html>
