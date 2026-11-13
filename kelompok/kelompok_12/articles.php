<?php
// ==========================================
// SEHATIN - HALAMAN DAFTAR ARTIKEL
// PHP Native + PDO (tanpa framework)
// Tampilan mengikuti halaman Tips & Kategori
// ==========================================

require_once __DIR__ . '/config/database.php';

// ---------- Helper ----------
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function tanggalIndo($datetime) {
    if (empty($datetime)) return '';
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($datetime);
    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function ringkas($text, $length = 110) {
    $text = trim(strip_tags((string) $text));
    if (function_exists('mb_strlen')) {
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
    }
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

// Escape karakter spesial LIKE (% dan _)
function likeEscape($text) {
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text);
}

// Ikon kategori (sama seperti di categories.php)
function kategoriIkon($id) {
    $icons = [1 => '🥗', 2 => '🧠', 3 => '🏃'];
    return $icons[(int) $id] ?? '📚';
}

// URL dengan mempertahankan pencarian, kategori & sortir
function buildUrl($overrides = []) {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null || ($k === 'page' && (int) $v <= 1)) unset($params[$k]);
    }
    return 'articles.php' . (empty($params) ? '' : '?' . http_build_query($params));
}

// Bind semua parameter sekaligus (integer otomatis PARAM_INT)
function bindAll(PDOStatement $stmt, array $params) {
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}

// Navigasi halaman
function renderPagination($page, $totalPages) {
    if ($totalPages <= 1) return;
    ?>
    <nav class="ar-pagination" aria-label="Navigasi halaman">
        <?php if ($page > 1): ?>
            <a href="<?= e(buildUrl(['page' => $page - 1])) ?>" class="ar-page" aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="<?= e(buildUrl(['page' => $p])) ?>" class="ar-page <?= $p === $page ? 'active' : '' ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>><?= $p ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= e(buildUrl(['page' => $page + 1])) ?>" class="ar-page" aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></a>
        <?php endif; ?>
    </nav>
    <?php
}

// ---------- Parameter dari URL ----------
$perPage = 9;

$search = (isset($_GET['q']) && is_string($_GET['q']))
    ? trim($_GET['q'])
    : '';

$selectedCategory = (isset($_GET['category']) && is_string($_GET['category']))
    ? trim($_GET['category'])
    : '';

$sort = (isset($_GET['sort']) && is_string($_GET['sort'])) ? $_GET['sort'] : 'latest';
if (!in_array($sort, ['latest', 'popular'], true)) {
    $sort = 'latest';
}

$page = max(1, (int) ($_GET['page'] ?? 1));

$isFiltered = $search !== '' || $selectedCategory !== '' || $sort !== 'latest';

// ---------- Nilai awal ----------
$dbError         = false;
$categories      = [];
$articles        = [];
$featured        = null;
$totalArticles   = 0;
$totalCategories = 0;
$totalViews      = 0;
$totalResults    = 0;
$totalPages      = 1;

try {

    // ---------- Daftar kategori & statistik ----------
    $categories      = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
    $totalCategories = count($categories);

    $statRow = $pdo->query("
        SELECT COUNT(*) AS total, COALESCE(SUM(views), 0) AS total_views
        FROM articles
        WHERE status = 'published'
    ")->fetch(PDO::FETCH_ASSOC);

    $totalArticles = (int) ($statRow['total'] ?? 0);
    $totalViews    = (int) ($statRow['total_views'] ?? 0);

    // ---------- Filter pencarian + kategori ----------
    $from = "
        FROM articles a
        INNER JOIN categories c ON a.category_id = c.id
        WHERE a.status = 'published'
    ";
    $params = [];

    if ($search !== '') {
        $like  = '%' . likeEscape($search) . '%';
        $from .= " AND (a.title LIKE :search_title OR a.content LIKE :search_content)";
        $params[':search_title']   = $like;
        $params[':search_content'] = $like;
    }

    if ($selectedCategory !== '') {
        $from .= " AND c.name = :category";
        $params[':category'] = $selectedCategory;
    }

    // ---------- Pagination ----------
    $countStmt = $pdo->prepare("SELECT COUNT(*) $from");
    bindAll($countStmt, $params);
    $countStmt->execute();
    $totalResults = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($totalResults / $perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    $orderSql = $sort === 'popular'
        ? 'a.views DESC, a.created_at DESC'
        : 'a.created_at DESC';

    $listStmt = $pdo->prepare("
        SELECT
            a.id,
            a.title,
            a.content,
            c.id   AS category_id,
            c.name AS category,
            a.author,
            a.created_at AS date,
            a.views
        $from
        ORDER BY $orderSql
        LIMIT :limit OFFSET :offset
    ");
    $params[':limit']  = $perPage;
    $params[':offset'] = $offset;
    bindAll($listStmt, $params);
    $listStmt->execute();
    $articles = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    // Artikel pilihan hanya tampil di halaman 1 tanpa filter
    if ($page === 1 && !$isFiltered && count($articles) > 0) {
        $featured = array_shift($articles);
    }

} catch (PDOException $ex) {
    // Detail error hanya masuk log server, tidak ditampilkan ke pengunjung
    error_log('[Sehatin] articles.php: ' . $ex->getMessage());
    $dbError = true;
}

$cssFile   = 'assets/css/articles_public.css';
$pageTitle = 'Artikel Kesehatan | Sehatin';
$extraCss  = [
    'assets/css/articles.css',
    $cssFile . '?v=' . (@filemtime(__DIR__ . '/' . $cssFile) ?: time()),
];
$bodyClass = 'page-articles';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main>

    <!-- HERO -->
    <section class="hero">
        <div class="container hero-content">
            <span class="eyebrow">INFORMASI KESEHATAN TERPERCAYA</span>

            <h1>Temukan Informasi untuk<br>
                Hidup Lebih <span>Sehat.</span>
            </h1>

            <p>
                Baca berbagai artikel kesehatan, temukan wawasan baru,
                dan mulai langkah kecil menuju kehidupan yang lebih baik.
            </p>
        </div>
    </section>

    <div class="container ar-main">

    <?php if ($dbError): ?>

        <!-- ERROR DATABASE -->
        <div class="ar-empty" data-reveal>
            <div class="ar-empty-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <h2>Data belum bisa dimuat</h2>
            <p>Terjadi gangguan saat mengambil data artikel. Coba muat ulang halaman beberapa saat lagi.</p>
            <a href="articles.php" class="ar-btn">Muat Ulang</a>
        </div>

    <?php else: ?>

        <!-- STATISTIK -->
        <section class="ar-stats" data-reveal>
            <div class="ar-stat">
                <div class="ar-stat-icon"><i class="bi bi-journal-medical"></i></div>
                <div>
                    <strong data-count="<?= $totalArticles ?>">0</strong>
                    <span>Artikel Medis</span>
                </div>
            </div>
            <div class="ar-stat">
                <div class="ar-stat-icon"><i class="bi bi-grid-fill"></i></div>
                <div>
                    <strong data-count="<?= $totalCategories ?>">0</strong>
                    <span>Kategori Topik</span>
                </div>
            </div>
            <div class="ar-stat">
                <div class="ar-stat-icon"><i class="bi bi-eye-fill"></i></div>
                <div>
                    <strong data-count="<?= $totalViews ?>">0</strong>
                    <span>Total Dibaca</span>
                </div>
            </div>
        </section>

        <!-- TOOLBAR: PENCARIAN, KATEGORI & SORTIR -->
        <section class="ar-toolbar" data-reveal>
            <div class="ar-toolbar-title">
                <span class="eyebrow">JELAJAHI PENGETAHUAN</span>
                <h2>Artikel Kesehatan</h2>
            </div>

            <form action="articles.php" method="get" class="ar-filter">
                <div class="ar-input-wrap">
                    <i class="bi bi-search"></i>
                    <input
                        type="search"
                        name="q"
                        value="<?= e($search) ?>"
                        placeholder="Cari artikel kesehatan..."
                        aria-label="Cari artikel"
                    >
                </div>

                <select name="category" aria-label="Kategori" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>" <?= $selectedCategory === $category ? 'selected' : '' ?>>
                            <?= e($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="sort" aria-label="Urutkan" onchange="this.form.submit()">
                    <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Terpopuler</option>
                </select>

                <button type="submit" class="ar-btn">Cari</button>
            </form>
        </section>

        <?php if ($isFiltered): ?>
            <p class="ar-result-info">
                Menampilkan <strong><?= $totalResults ?></strong> artikel
                <?php if ($search !== ''): ?>
                    untuk "<strong><?= e($search) ?></strong>"
                <?php endif; ?>
                <?php if ($selectedCategory !== ''): ?>
                    di kategori <strong><?= e($selectedCategory) ?></strong>
                <?php endif; ?>
                &middot; <a href="articles.php">Reset filter</a>
            </p>
        <?php endif; ?>

        <?php if ($featured): ?>
            <!-- ARTIKEL PILIHAN -->
            <article class="ar-featured ar-tone-<?= (int) $featured['category_id'] % 4 ?>" data-reveal>
                <div class="ar-featured-media">
                    <span class="ar-emoji" aria-hidden="true"><?= e(kategoriIkon($featured['category_id'])) ?></span>
                    <span class="ar-chip"><?= e($featured['category']) ?></span>
                </div>
                <div class="ar-featured-body">
                    <span class="ar-badge"><i class="bi bi-stars"></i> Artikel Terbaru</span>
                    <h3><?= e($featured['title']) ?></h3>
                    <p><?= e(ringkas($featured['content'], 260)) ?></p>
                    <div class="ar-featured-foot">
                        <div class="ar-meta">
                            <span><i class="bi bi-calendar3"></i> <?= e(tanggalIndo($featured['date'])) ?></span>
                            <?php if (!empty($featured['author'])): ?>
                                <span><i class="bi bi-person"></i> <?= e($featured['author']) ?></span>
                            <?php endif; ?>
                            <span><i class="bi bi-eye"></i> <?= number_format((int) $featured['views'], 0, ',', '.') ?> dibaca</span>
                        </div>
                        <a href="article_detail.php?id=<?= (int) $featured['id'] ?>" class="ar-btn">
                            Baca Selengkapnya <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>
        <?php endif; ?>

        <?php if (count($articles) > 0): ?>

            <!-- GRID ARTIKEL -->
            <div class="ar-grid">
                <?php foreach ($articles as $i => $article): ?>
                    <article class="ar-card ar-tone-<?= (int) $article['category_id'] % 4 ?>" data-reveal style="--d: <?= ($i % 3) * 90 ?>ms">
                        <div class="ar-card-media">
                            <span class="ar-emoji" aria-hidden="true"><?= e(kategoriIkon($article['category_id'])) ?></span>
                            <span class="ar-chip"><?= e($article['category']) ?></span>
                        </div>

                        <div class="ar-card-body">
                            <div class="ar-meta">
                                <span><i class="bi bi-calendar3"></i> <?= e(tanggalIndo($article['date'])) ?></span>
                                <?php if (!empty($article['author'])): ?>
                                    <span><i class="bi bi-person"></i> <?= e($article['author']) ?></span>
                                <?php endif; ?>
                            </div>

                            <h3><?= e($article['title']) ?></h3>
                            <p><?= e(ringkas($article['content'], 115)) ?></p>

                            <div class="ar-card-foot">
                                <span class="ar-views"><i class="bi bi-eye"></i> <?= number_format((int) $article['views'], 0, ',', '.') ?> dibaca</span>
                                <a href="article_detail.php?id=<?= (int) $article['id'] ?>" class="ar-link">
                                    Baca <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php elseif (!$featured): ?>

            <div class="ar-empty" data-reveal>
                <div class="ar-empty-icon"><i class="bi bi-search-heart"></i></div>
                <h2>Artikel tidak ditemukan</h2>
                <p>Coba gunakan kata kunci atau kategori yang berbeda.</p>
                <a href="articles.php" class="ar-btn">Lihat Semua Artikel</a>
            </div>

        <?php endif; ?>

        <?php renderPagination($page, $totalPages); ?>

        <!-- CTA -->
        <section class="ar-cta" data-reveal>
            <div class="ar-cta-text">
                <h3>Cari topik yang lebih spesifik?</h3>
                <p>Telusuri artikel berdasarkan kategori, atau baca tips kesehatan singkat yang mudah dipraktikkan.</p>
            </div>
            <div class="ar-cta-actions">
                <a href="categories.php" class="ar-btn-light">Jelajahi Kategori <i class="bi bi-arrow-right"></i></a>
                <a href="tips.php" class="ar-btn-outline">Tips Kesehatan</a>
            </div>
        </section>

    <?php endif; ?>

    </div>
</main>

<script>
(function () {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 1. Animasi muncul saat di-scroll
    var items = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window && !reduceMotion) {
        items.forEach(function (el) { el.classList.add('ar-pre'); });
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('ar-pre');
                    entry.target.classList.add('ar-show');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        items.forEach(function (el) { io.observe(el); });
    }

    // 2. Animasi angka statistik
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseInt(el.dataset.count, 10) || 0;
        var fmt = function (n) { return n.toLocaleString('id-ID'); };
        if (reduceMotion) { el.textContent = fmt(target); return; }

        var duration = 900;
        var start = null;
        function step(ts) {
            if (!start) start = ts;
            var progress = Math.min((ts - start) / duration, 1);
            el.textContent = fmt(Math.floor(progress * target));
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = fmt(target);
        }
        requestAnimationFrame(step);
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>