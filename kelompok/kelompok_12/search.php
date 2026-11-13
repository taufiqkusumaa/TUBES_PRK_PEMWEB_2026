<?php
require_once 'config/database.php';

$keyword = trim($_GET['keyword'] ?? '');
$articles = [];

if ($keyword !== '') {
    $stmt = $pdo->prepare("SELECT articles.*, categories.name AS category_name 
                           FROM articles 
                           LEFT JOIN categories ON articles.category_id = categories.id 
                           WHERE articles.status = 'published' 
                           AND (articles.title LIKE :keyword OR articles.content LIKE :keyword)
                           ORDER BY articles.created_at DESC");
    $stmt->execute([':keyword' => '%' . $keyword . '%']);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Pencarian - Sehatin';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container my-5">
    <h2 class="mb-4">Hasil Pencarian untuk: "<?= escape($keyword) ?>"</h2>

    <?php if ($keyword === ''): ?>
        <div class="alert alert-warning">Masukkan kata kunci pencarian terlebih dahulu pada kolom di atas.</div>
    <?php elseif (count($articles) > 0): ?>
        <p class="text-muted mb-4">Ditemukan <?= count($articles) ?> artikel yang sesuai.</p>
        <div class="row">
            <?php foreach ($articles as $article): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if (!empty($article['image'])): ?>
                            <img src="<?= escape($article['image']) ?>" class="card-img-top" style="height: 180px; object-fit: cover;" alt="Gambar">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-success mb-2 align-self-start"><?= escape($article['category_name'] ?? 'Umum') ?></span>
                            <h5 class="card-title fw-bold"><?= escape($article['title']) ?></h5>
                            <p class="card-text text-muted small flex-grow-1"><?= escape(substr(strip_tags($article['content']), 0, 90)) ?>...</p>
                            <a href="article_detail.php?id=<?= (int)$article['id'] ?>" class="btn btn-outline-success btn-sm mt-auto">Baca Selengkapnya</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Maaf, artikel yang kamu cari tidak ditemukan.</div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="index.php" class="btn btn-secondary">&larr; Kembali ke Beranda</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>