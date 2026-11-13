<?php
// Hubungkan dengan konfigurasi database
require_once 'config/database.php';

try {
    // Ambil data artikel terbaru
    $stmtLatest = $pdo->query("
        SELECT a.*, c.name AS category_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        ORDER BY a.created_at DESC
        LIMIT 4
    ");
    $latestArticles = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);

    // Ambil maksimal 6 kategori untuk menu fitur
    $stmtCategories = $pdo->query("SELECT * FROM categories LIMIT 6");
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $latestArticles = [];
    $categories = [];
}

$pageTitle = 'Sehatin - Solusi Kesehatan Terpercaya';
$extraCss  = ['assets/css/index.css'];

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="container my-4 home-page">

    <!-- HERO / BANNER UTAMA -->
    <section class="home-hero row g-3 mb-5">
        <div class="col-lg-8">
            <div class="promo-banner-left h-100">
                <div class="hero-content">
                    <span class="hero-eyebrow"><span class="eyebrow-dot"></span> Teman sehat setiap hari</span>
                    <h1>Mulai Hidup Lebih Sehat,<br><span>Mulai dari Sehatin.</span></h1>
                    <p>Temukan informasi kesehatan yang mudah dipahami, tips sederhana, dan wawasan untuk membantu kamu menjaga kesehatan.</p>
                    <div class="hero-actions">
                        <a href="articles.php" class="btn btn-hero-primary">Jelajahi Artikel <span aria-hidden="true">→</span></a>
                        <a href="#home-features" class="btn btn-hero-ghost">Jelajahi Sehatin</a>
                    </div>
                    <div class="hero-note"><span aria-hidden="true">✦</span> Informasi kesehatan dalam satu tempat</div>
                </div>
                <div class="hero-decoration hero-decoration-one"></div>
                <div class="hero-decoration hero-decoration-two"></div>
                <div class="hero-leaf" aria-hidden="true">✳</div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="promo-banner-right h-100">
                <div class="tips-icon" aria-hidden="true">✦</div>
                <span class="tips-label">Ruang inspirasi</span>
                <h2>Langkah kecil,<br>hidup lebih sehat.</h2>
                <p>Bangun kebiasaan baik lewat tips kebugaran, pola makan, dan perawatan diri yang bisa kamu terapkan setiap hari.</p>
                <a href="tips.php" class="btn btn-tips">Temukan Tips <span aria-hidden="true">↗</span></a>
                <div class="tips-decoration" aria-hidden="true">+</div>
            </div>
        </div>
    </section>

    <!-- FITUR DAN KATEGORI -->
    <section class="home-feature-section mb-5" id="home-features">
        <div class="section-heading">
            <div>
                <span class="section-kicker">Jelajahi Sehatin</span>
                <h2>Solusi Kesehatan di Tanganmu</h2>
                <p>Akses informasi dan topik kesehatan yang kamu butuhkan.</p>
            </div>
            <span class="section-heading-mark" aria-hidden="true">✳</span>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
            <div class="col">
                <a href="articles.php" class="feature-card feature-card-mint h-100">
                    <span class="feature-icon" aria-hidden="true">▤</span>
                    <span class="feature-card-arrow" aria-hidden="true">↗</span>
                    <h3>Daftar Artikel Lengkap</h3>
                    <p>Baca berbagai informasi dan wawasan kesehatan terkini.</p>
                    <span class="feature-link-label">Jelajahi artikel</span>
                </a>
            </div>

            <div class="col">
                <a href="tips.php" class="feature-card feature-card-peach h-100">
                    <span class="feature-icon" aria-hidden="true">☀</span>
                    <span class="feature-card-arrow" aria-hidden="true">↗</span>
                    <h3>Tips &amp; Gaya Hidup Sehat</h3>
                    <p>Temukan panduan praktis untuk menjaga kebugaran tubuh.</p>
                    <span class="feature-link-label">Lihat tips sehat</span>
                </a>
            </div>

            <div class="col">
                <a href="search.php" class="feature-card feature-card-lilac h-100">
                    <span class="feature-icon" aria-hidden="true">⌕</span>
                    <span class="feature-card-arrow" aria-hidden="true">↗</span>
                    <h3>Pencarian Informasi</h3>
                    <p>Cari topik kesehatan yang ingin kamu ketahui dengan mudah.</p>
                    <span class="feature-link-label">Mulai mencari</span>
                </a>
            </div>

            <?php foreach ($categories as $index => $cat): ?>
                <?php
                    $categoryThemes = ['feature-card-sage', 'feature-card-blue', 'feature-card-cream'];
                    $categoryTheme = $categoryThemes[$index % count($categoryThemes)];
                ?>
                <div class="col">
                    <a href="categories.php?id=<?= (int)$cat['id'] ?>" class="feature-card <?= $categoryTheme ?> h-100">
                        <span class="feature-icon" aria-hidden="true">＋</span>
                        <span class="feature-card-arrow" aria-hidden="true">↗</span>
                        <h3><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p>Jelajahi artikel dan informasi seputar topik ini.</p>
                        <span class="feature-link-label">Lihat kategori</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ARTIKEL TERBARU -->
    <section class="home-articles-section mb-5" id="articles">
        <div class="section-heading articles-heading">
            <div>
                <span class="section-kicker">Bacaan pilihan</span>
                <h2>Artikel Kesehatan Terbaru</h2>
                <p>Perluas wawasan dan temukan informasi yang bermanfaat untukmu.</p>
            </div>
            <a href="articles.php" class="view-all-link">Lihat Semua <span aria-hidden="true">→</span></a>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">
            <?php if (count($latestArticles) > 0): ?>
                <?php foreach ($latestArticles as $article): ?>
                    <div class="col">
                        <article class="card card-article h-100">
                            <a class="article-image-wrap" href="article_detail.php?id=<?= (int)$article['id'] ?>" aria-label="Baca <?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (!empty($article['image'])): ?>
                                    <img src="<?= htmlspecialchars($article['image'], ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=800&q=85" class="card-img-top" alt="Ilustrasi artikel kesehatan" loading="lazy">
                                <?php endif; ?>
                            </a>

                            <div class="card-body d-flex flex-column">
                                <span class="article-category">
                                    <?= htmlspecialchars($article['category_name'] ?? 'Umum', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <h3 class="card-title">
                                    <a href="article_detail.php?id=<?= (int)$article['id'] ?>">
                                        <?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </h3>
                                <p class="card-text">
                                    <?= htmlspecialchars(mb_substr(strip_tags($article['content'] ?? ''), 0, 95), ENT_QUOTES, 'UTF-8') ?>...
                                </p>
                                <a href="article_detail.php?id=<?= (int)$article['id'] ?>" class="article-read-link mt-auto">
                                    Baca selengkapnya <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-articles">
                        <span aria-hidden="true">▤</span>
                        <h3>Belum ada artikel terbaru</h3>
                        <p>Artikel yang dipublikasikan akan muncul di bagian ini.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA PENUTUP -->
    <section class="home-bottom-cta">
        <div>
            <span class="section-kicker">Jaga kesehatan mulai sekarang</span>
            <h2>Pengetahuan yang baik adalah langkah awal hidup sehat.</h2>
            <p>Luangkan waktu sejenak untuk membaca dan mengenali kebutuhan kesehatanmu.</p>
        </div>
        <a href="articles.php" class="btn btn-cta-bottom">Temukan Informasi <span aria-hidden="true">→</span></a>
        <span class="cta-decoration" aria-hidden="true">✳</span>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/script.js"></script>
</body>
</html>
