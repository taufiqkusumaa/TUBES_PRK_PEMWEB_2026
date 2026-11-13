<?php

// ==========================================
// SEHATIN - HALAMAN DETAIL ARTIKEL
// Terhubung dengan database MySQL
// ==========================================

require_once __DIR__ . '/config/database.php';

// Mengamankan output HTML
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Membuat ringkasan dari isi artikel
function makeExcerpt($content, $length = 150) {
    $content = trim(strip_tags($content));

    if (function_exists('mb_strlen')) {
        return mb_strlen($content) > $length
            ? mb_substr($content, 0, $length) . '...'
            : $content;
    }

    return strlen($content) > $length
        ? substr($content, 0, $length) . '...'
        : $content;
}

// Mengambil ID artikel dari URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$article = null;
$recommendations = [];

try {

    if ($id && $id > 0) {

        // Menambah jumlah pembaca artikel
        $updateViews = $pdo->prepare("
            UPDATE articles
            SET views = views + 1
            WHERE id = :id
            AND status = 'published'
        ");

        $updateViews->execute([
            'id' => $id
        ]);

        // Mengambil detail artikel
        $articleQuery = $pdo->prepare("
            SELECT
                a.id,
                a.category_id,
                a.title,
                a.content,
                a.image,
                a.author,
                a.views,
                a.created_at AS date,
                c.name AS category
            FROM articles a
            INNER JOIN categories c ON a.category_id = c.id
            WHERE a.id = :id
            AND a.status = 'published'
            LIMIT 1
        ");

        $articleQuery->execute([
            'id' => $id
        ]);

        $article = $articleQuery->fetch(PDO::FETCH_ASSOC);

        // Membuat ringkasan artikel
        if ($article) {
            $article['excerpt'] = makeExcerpt($article['content']);

            // Mengambil rekomendasi dari kategori yang sama
            $recommendationQuery = $pdo->prepare("
                SELECT
                    a.id,
                    a.title,
                    a.content,
                    a.views,
                    a.created_at AS date,
                    c.name AS category
                FROM articles a
                INNER JOIN categories c ON a.category_id = c.id
                WHERE a.category_id = :category_id
                AND a.id != :id
                AND a.status = 'published'
                ORDER BY a.created_at DESC
                LIMIT 2
            ");

            $recommendationQuery->execute([
                'category_id' => $article['category_id'],
                'id' => $article['id']
            ]);

            $recommendations = $recommendationQuery->fetchAll(PDO::FETCH_ASSOC);

            // Membuat ringkasan rekomendasi
            foreach ($recommendations as &$recommendation) {
                $recommendation['excerpt'] = makeExcerpt(
                    $recommendation['content'],
                    110
                );
            }

            unset($recommendation);
        }
    }

} catch (PDOException $e) {
    die("Gagal mengambil detail artikel: " . $e->getMessage());
}

?>

<?php
$pageTitle = ($article ? $article['title'] : 'Artikel Tidak Ditemukan') . ' | Sehatin';
$extraCss  = ['assets/css/articles.css'];
$bodyClass = 'page-articles';
include 'includes/header.php';
include 'includes/navbar.php';
?>


    <main class="detail-section">
        <div class="container">

            <?php if ($article): ?>

                <a href="articles.php" class="back-link">
                    &larr; Kembali ke Artikel
                </a>

                <!-- DETAIL ARTIKEL -->
                <article class="detail-article">

                    <div class="detail-header">

                        <span class="detail-category">
                            <?= e($article['category']) ?>
                        </span>

                        <h1><?= e($article['title']) ?></h1>

                        <div class="detail-meta">
                            <span>Oleh <?= e($article['author']) ?></span>
                            <span>•</span>
                            <span>
                                <?= date('d M Y', strtotime($article['date'])) ?>
                            </span>
                            <span>•</span>
                            <span>
                                <?= number_format($article['views']) ?> dibaca
                            </span>
                        </div>

                    </div>

                    <div class="detail-image">
                        <span>Ilustrasi Artikel Kesehatan</span>
                    </div>

                    <!-- ISI ARTIKEL -->
                    <div class="detail-content">

                        <p class="detail-excerpt">
                            <?= e($article['excerpt']) ?>
                        </p>

                        <p>
                            <?= nl2br(e($article['content'])) ?>
                        </p>

                    </div>

                </article>

                <!-- ARTIKEL REKOMENDASI -->
                <?php if (!empty($recommendations)): ?>

                    <section class="recommendation-section">

                        <h2>Artikel Terkait</h2>

                        <div class="recommendation-grid">

                            <?php foreach ($recommendations as $rec): ?>

                                <article class="recommendation-card">

                                    <span class="detail-category">
                                        <?= e($rec['category']) ?>
                                    </span>

                                    <h3><?= e($rec['title']) ?></h3>

                                    <p><?= e($rec['excerpt']) ?></p>

                                    <a href="article_detail.php?id=<?= (int) $rec['id'] ?>">
                                        Baca selengkapnya &rarr;
                                    </a>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    </section>

                <?php endif; ?>

            <?php else: ?>

                <!-- JIKA ARTIKEL TIDAK DITEMUKAN -->
                <div class="empty-state">

                    <h2>Artikel Tidak Ditemukan</h2>

                    <p>
                        Maaf, artikel yang kamu cari tidak tersedia.
                        Silakan kembali ke halaman daftar artikel.
                    </p>

                    <a href="articles.php" class="filter-button">
                        Kembali ke Artikel
                    </a>

                </div>

            <?php endif; ?>

        </div>
    </main>

<?php include 'includes/footer.php'; ?>


</body>
</html>