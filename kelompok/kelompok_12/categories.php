<?php
// ==========================================
// SEHATIN - HALAMAN KATEGORI
// PHP Native + PDO (tanpa framework)
// Tampilan mengikuti halaman Tips (tips.php)
//
// Dua mode dalam satu file:
//   categories.php          -> daftar semua kategori
//   categories.php?id=1     -> artikel dalam kategori tersebut
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

// Escape karakter spesial LIKE (% dan _) supaya pencarian "_" tidak cocok ke semua data
function likeEscape($text) {
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text);
}

// Ikon kategori (sementara, berdasarkan ID)
function kategoriIkon($id) {
    $icons = [
        1 => '🥗',
        2 => '🧠',
        3 => '🏃',
    ];
    return $icons[(int) $id] ?? '📚';
}

// Deskripsi cadangan jika di database masih kosong
function kategoriDeskripsi($id, $description) {
    if (trim((string) $description) !== '') {
        return $description;
    }
    $fallback = [
        1 => 'Informasi seputar makanan bergizi, pola makan sehat, dan kebutuhan nutrisi tubuh.',
        2 => 'Artikel tentang menjaga kesehatan pikiran, mengelola stres, dan keseimbangan emosi.',
        3 => 'Tips aktivitas fisik dan olahraga untuk menjaga tubuh tetap sehat dan bugar.',
    ];
    return $fallback[(int) $id] ?? 'Temukan berbagai informasi kesehatan dalam kategori ini.';
}

// Membuat URL dengan mempertahankan parameter id, pencarian & sortir
function buildUrl($overrides = []) {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null || ($k === 'page' && (int) $v <= 1)) unset($params[$k]);
    }
    return 'categories.php' . (empty($params) ? '' : '?' . http_build_query($params));
}

// Bind semua parameter sekaligus (integer otomatis memakai PARAM_INT)
function bindAll(PDOStatement $stmt, array $params) {
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}

// Navigasi halaman
function renderPagination($page, $totalPages) {
    if ($totalPages <= 1) return;
    ?>
    <nav class="ct-pagination" aria-label="Navigasi halaman">
        <?php if ($page > 1): ?>
            <a href="<?= e(buildUrl(['page' => $page - 1])) ?>" class="ct-page" aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="<?= e(buildUrl(['page' => $p])) ?>" class="ct-page <?= $p === $page ? 'active' : '' ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>><?= $p ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= e(buildUrl(['page' => $page + 1])) ?>" class="ct-page" aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></a>
        <?php endif; ?>
    </nav>
    <?php
}

// ---------- Parameter dari URL ----------
$perPage    = 9;
$q          = (isset($_GET['q']) && is_string($_GET['q'])) ? trim($_GET['q']) : '';
$page       = max(1, (int) ($_GET['page'] ?? 1));
$hasId      = isset($_GET['id']) && $_GET['id'] !== '';
$selectedId = $hasId ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

// ---------- Nilai awal ----------
$dbError          = false;
$totalCategories  = 0;
$totalArticles    = 0;
$totalTips        = 0;

$categories       = [];      // mode daftar
$popular          = null;    // kategori dengan artikel terbanyak
$selectedCategory = null;    // mode detail
$articles         = [];      // mode detail
$catStats         = ['total' => 0, 'views' => 0, 'latest' => null];

$sort         = '';
$totalResults = 0;
$totalPages   = 1;

try {

    // ---------- Statistik (dinamis dari database) ----------
    $totalCategories = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $totalArticles   = (int) $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();

    try {
        $totalTips = (int) $pdo->query("SELECT COUNT(*) FROM tips")->fetchColumn();
    } catch (PDOException $ex) {
        // Jika tabel tips belum ada, statistik dibiarkan 0
    }

    if ($hasId) {

        // ==========================================
        // MODE DETAIL: artikel dalam satu kategori
        // ==========================================
        if ($selectedId !== false && $selectedId > 0) {
            $stmt = $pdo->prepare("SELECT id, name, description FROM categories WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $selectedId]);
            $selectedCategory = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if ($selectedCategory) {
            $catId = (int) $selectedCategory['id'];
            $selectedCategory['icon']        = kategoriIkon($catId);
            $selectedCategory['description'] = kategoriDeskripsi($catId, $selectedCategory['description'] ?? '');

            // Sortir (whitelist)
            $sort = in_array($_GET['sort'] ?? '', ['terbaru', 'terlama', 'populer'], true) ? $_GET['sort'] : 'terbaru';
            $orderMap = [
                'terbaru' => 'created_at DESC',
                'terlama' => 'created_at ASC',
                'populer' => 'views DESC, created_at DESC',
            ];

            // Ringkasan kategori
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS total,
                       COALESCE(SUM(views), 0) AS total_views,
                       MAX(created_at) AS latest
                FROM articles
                WHERE category_id = :cid AND status = 'published'
            ");
            $stmt->execute([':cid' => $catId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $catStats = [
                'total'  => (int) ($row['total'] ?? 0),
                'views'  => (int) ($row['total_views'] ?? 0),
                'latest' => $row['latest'] ?? null,
            ];

            // Pencarian + pagination
            $where  = "WHERE category_id = :cid AND status = 'published'";
            $params = [':cid' => $catId];
            if ($q !== '') {
                $like = '%' . likeEscape($q) . '%';
                $where .= " AND (title LIKE :q1 OR content LIKE :q2)";
                $params[':q1'] = $like;
                $params[':q2'] = $like;
            }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles $where");
            bindAll($countStmt, $params);
            $countStmt->execute();
            $totalResults = (int) $countStmt->fetchColumn();

            $totalPages = max(1, (int) ceil($totalResults / $perPage));
            $page       = min($page, $totalPages);
            $offset     = ($page - 1) * $perPage;

            $listStmt = $pdo->prepare("
                SELECT id, category_id, title, content, views, created_at
                FROM articles
                $where
                ORDER BY {$orderMap[$sort]}
                LIMIT :limit OFFSET :offset
            ");
            $params[':limit']  = $perPage;
            $params[':offset'] = $offset;
            bindAll($listStmt, $params);
            $listStmt->execute();
            $articles = $listStmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } else {

        // ==========================================
        // MODE DAFTAR: semua kategori
        // ==========================================
        $sort = in_array($_GET['sort'] ?? '', ['populer', 'abjad'], true) ? $_GET['sort'] : 'populer';

        $where  = '';
        $params = [];
        if ($q !== '') {
            $like   = '%' . likeEscape($q) . '%';
            $where  = "WHERE c.name LIKE :q1 OR c.description LIKE :q2";
            $params = [':q1' => $like, ':q2' => $like];
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM categories c $where");
        bindAll($countStmt, $params);
        $countStmt->execute();
        $totalResults = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($totalResults / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;
        $orderSql   = $sort === 'abjad' ? 'c.name ASC' : 'article_count DESC, c.name ASC';

        $listStmt = $pdo->prepare("
            SELECT c.id, c.name, c.description, COUNT(a.id) AS article_count
            FROM categories c
            LEFT JOIN articles a
                ON a.category_id = c.id
                AND a.status = 'published'
            $where
            GROUP BY c.id, c.name, c.description
            ORDER BY $orderSql
            LIMIT :limit OFFSET :offset
        ");
        $params[':limit']  = $perPage;
        $params[':offset'] = $offset;
        bindAll($listStmt, $params);
        $listStmt->execute();
        $categories = $listStmt->fetchAll(PDO::FETCH_ASSOC);

        // Kategori pilihan = kategori dengan artikel terbanyak
        $popular = $pdo->query("
            SELECT c.id, c.name, c.description, COUNT(a.id) AS article_count
            FROM categories c
            LEFT JOIN articles a
                ON a.category_id = c.id
                AND a.status = 'published'
            GROUP BY c.id, c.name, c.description
            ORDER BY article_count DESC, c.name ASC
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($popular && (int) $popular['article_count'] < 1) {
            $popular = null;
        }
    }

} catch (PDOException $ex) {
    // Detail error hanya masuk log server, tidak ditampilkan ke pengunjung
    error_log('[Sehatin] categories.php: ' . $ex->getMessage());
    $dbError = true;
}

// Kategori yang diminta tidak ada -> status 404
if ($hasId && !$selectedCategory && !$dbError) {
    http_response_code(404);
}

$cssFile   = 'assets/css/categories_public.css';
$pageTitle = 'Kategori Artikel - Sehatin';
$extraCss  = [
    'assets/css/articles.css',
    $cssFile . '?v=' . (@filemtime(__DIR__ . '/' . $cssFile) ?: time()),
];
$bodyClass = 'page-articles';
include 'includes/header.php';
include 'includes/navbar.php';
?>

    <!-- HERO -->
    <section class="hero">
        <div class="container hero-content">
            <span class="eyebrow">JELAJAHI SEHATIN</span>
            <h1><?= $selectedCategory ? e($selectedCategory['name']) : 'Kategori Artikel' ?></h1>
            <p>
                <?= $selectedCategory
                    ? e($selectedCategory['description'])
                    : 'Temukan berbagai informasi kesehatan berdasarkan topik yang kamu butuhkan.' ?>
            </p>
        </div>
    </section>

    <main class="container ct-main">

    <?php if ($dbError): ?>

        <!-- ERROR DATABASE -->
        <div class="ct-empty" data-reveal>
            <div class="ct-empty-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <h2>Data belum bisa dimuat</h2>
            <p>Terjadi gangguan saat mengambil data kategori. Coba muat ulang halaman beberapa saat lagi.</p>
            <a href="categories.php" class="ct-btn">Muat Ulang</a>
        </div>

    <?php elseif ($hasId && !$selectedCategory): ?>

        <!-- KATEGORI TIDAK DITEMUKAN -->
        <div class="ct-empty" data-reveal>
            <div class="ct-empty-icon"><i class="bi bi-search-heart"></i></div>
            <h2>Kategori tidak ditemukan</h2>
            <p>Kategori yang kamu cari tidak ada atau sudah dihapus.</p>
            <a href="categories.php" class="ct-btn">Lihat Semua Kategori</a>
        </div>

    <?php elseif ($selectedCategory): ?>

        <!-- ==================== DETAIL KATEGORI ==================== -->

        <!-- STATISTIK KATEGORI -->
        <section class="ct-stats" data-reveal>
            <div class="ct-stat">
                <div class="ct-stat-icon"><i class="bi bi-journal-text"></i></div>
                <div>
                    <strong data-count="<?= $catStats['total'] ?>">0</strong>
                    <span>Artikel di Kategori Ini</span>
                </div>
            </div>
            <div class="ct-stat">
                <div class="ct-stat-icon"><i class="bi bi-eye-fill"></i></div>
                <div>
                    <strong data-count="<?= $catStats['views'] ?>">0</strong>
                    <span>Total Dilihat</span>
                </div>
            </div>
            <div class="ct-stat">
                <div class="ct-stat-icon"><i class="bi bi-calendar3"></i></div>
                <div>
                    <strong class="ct-stat-text"><?= $catStats['latest'] ? e(tanggalIndo($catStats['latest'])) : '-' ?></strong>
                    <span>Artikel Terakhir</span>
                </div>
            </div>
        </section>

        <!-- TOOLBAR: PENCARIAN & SORTIR -->
        <section class="ct-toolbar" data-reveal>
            <div class="ct-toolbar-title">
                <a href="categories.php" class="ct-back"><i class="bi bi-arrow-left"></i> Semua Kategori</a>
                <span class="eyebrow">ARTIKEL TERSEDIA</span>
                <h2>Artikel <?= e($selectedCategory['name']) ?></h2>
            </div>

            <form method="get" action="categories.php" class="ct-filter">
                <input type="hidden" name="id" value="<?= (int) $selectedCategory['id'] ?>">
                <div class="ct-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari artikel di kategori ini..." aria-label="Cari artikel">
                </div>
                <select name="sort" aria-label="Urutkan" onchange="this.form.submit()">
                    <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="terlama" <?= $sort === 'terlama' ? 'selected' : '' ?>>Terlama</option>
                    <option value="populer" <?= $sort === 'populer' ? 'selected' : '' ?>>Paling Banyak Dilihat</option>
                </select>
                <button type="submit" class="ct-btn">Cari</button>
            </form>
        </section>

        <?php if ($q !== ''): ?>
            <p class="ct-result-info">
                Menampilkan <strong><?= $totalResults ?></strong> hasil untuk
                "<strong><?= e($q) ?></strong>"
                &middot; <a href="categories.php?id=<?= (int) $selectedCategory['id'] ?>">Reset pencarian</a>
            </p>
        <?php endif; ?>

        <?php if (count($articles) > 0): ?>

            <!-- GRID ARTIKEL -->
            <div class="ct-grid">
                <?php foreach ($articles as $i => $article): ?>
                    <article class="ct-card ct-tone-<?= (int) $selectedCategory['id'] % 4 ?>" data-reveal style="--d: <?= ($i % 3) * 90 ?>ms">
                        <div class="ct-card-media">
                            <span class="ct-emoji" aria-hidden="true"><?= e($selectedCategory['icon']) ?></span>
                            <span class="ct-chip"><?= e($selectedCategory['name']) ?></span>
                        </div>

                        <div class="ct-card-body">
                            <h3><?= e($article['title']) ?></h3>
                            <p><?= e(ringkas($article['content'], 115)) ?></p>

                            <div class="ct-card-foot">
                                <div class="ct-meta">
                                    <span><i class="bi bi-calendar3"></i> <?= e(tanggalIndo($article['created_at'] ?? '')) ?></span>
                                    <span><i class="bi bi-eye"></i> <?= number_format((int) $article['views'], 0, ',', '.') ?></span>
                                </div>
                                <a href="article_detail.php?id=<?= (int) $article['id'] ?>" class="ct-link">
                                    Baca <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <div class="ct-empty" data-reveal>
                <div class="ct-empty-icon"><i class="bi bi-search-heart"></i></div>
                <h2><?= $q !== '' ? 'Artikel tidak ditemukan' : 'Belum Ada Artikel' ?></h2>
                <p><?= $q !== '' ? 'Coba gunakan kata kunci lain.' : 'Belum ada artikel yang dipublikasikan di kategori ini.' ?></p>
                <a href="categories.php" class="ct-btn">Lihat Semua Kategori</a>
            </div>

        <?php endif; ?>

        <?php renderPagination($page, $totalPages); ?>

    <?php else: ?>

        <!-- ==================== DAFTAR KATEGORI ==================== -->

        <!-- STATISTIK -->
        <section class="ct-stats" data-reveal>
            <div class="ct-stat">
                <div class="ct-stat-icon"><i class="bi bi-grid-fill"></i></div>
                <div>
                    <strong data-count="<?= $totalCategories ?>">0</strong>
                    <span>Kategori Topik</span>
                </div>
            </div>
            <div class="ct-stat">
                <div class="ct-stat-icon"><i class="bi bi-journal-medical"></i></div>
                <div>
                    <strong data-count="<?= $totalArticles ?>">0</strong>
                    <span>Artikel Medis</span>
                </div>
            </div>
            <div class="ct-stat">
                <div class="ct-stat-icon"><i class="bi bi-lightbulb-fill"></i></div>
                <div>
                    <strong data-count="<?= $totalTips ?>">0</strong>
                    <span>Tips Kesehatan</span>
                </div>
            </div>
        </section>

        <?php if ($popular && $q === ''): ?>
            <!-- KATEGORI PILIHAN -->
            <section class="ct-today" data-reveal>
                <div class="ct-today-icon" aria-hidden="true"><?= e(kategoriIkon($popular['id'])) ?></div>
                <div class="ct-today-text">
                    <span class="ct-today-label">Kategori Pilihan &middot; <?= (int) $popular['article_count'] ?> artikel</span>
                    <h3><?= e($popular['name']) ?></h3>
                    <p><?= e(ringkas(kategoriDeskripsi($popular['id'], $popular['description']), 170)) ?></p>
                </div>
                <a href="categories.php?id=<?= (int) $popular['id'] ?>" class="ct-btn-light">
                    Lihat Artikel <i class="bi bi-arrow-right"></i>
                </a>
            </section>
        <?php endif; ?>

        <!-- TOOLBAR: PENCARIAN & SORTIR -->
        <section class="ct-toolbar" data-reveal>
            <div class="ct-toolbar-title">
                <span class="eyebrow">PILIH TOPIK</span>
                <h2>Jelajahi Berdasarkan Kategori</h2>
            </div>

            <form method="get" action="categories.php" class="ct-filter">
                <div class="ct-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari kategori..." aria-label="Cari kategori">
                </div>
                <select name="sort" aria-label="Urutkan" onchange="this.form.submit()">
                    <option value="populer" <?= $sort === 'populer' ? 'selected' : '' ?>>Artikel Terbanyak</option>
                    <option value="abjad" <?= $sort === 'abjad' ? 'selected' : '' ?>>A - Z</option>
                </select>
                <button type="submit" class="ct-btn">Cari</button>
            </form>
        </section>

        <?php if ($q !== ''): ?>
            <p class="ct-result-info">
                Menampilkan <strong><?= $totalResults ?></strong> hasil untuk
                "<strong><?= e($q) ?></strong>"
                &middot; <a href="categories.php">Reset pencarian</a>
            </p>
        <?php endif; ?>

        <?php if (count($categories) > 0): ?>

            <!-- GRID KATEGORI -->
            <div class="ct-grid">
                <?php foreach ($categories as $i => $category): ?>
                    <article class="ct-card ct-tone-<?= (int) $category['id'] % 4 ?>" data-reveal style="--d: <?= ($i % 3) * 90 ?>ms">
                        <div class="ct-card-media">
                            <span class="ct-emoji" aria-hidden="true"><?= e(kategoriIkon($category['id'])) ?></span>
                            <span class="ct-chip"><i class="bi bi-grid"></i> Kategori</span>
                        </div>

                        <div class="ct-card-body">
                            <h3><?= e($category['name']) ?></h3>
                            <p><?= e(ringkas(kategoriDeskripsi($category['id'], $category['description']), 115)) ?></p>

                            <div class="ct-card-foot">
                                <span class="ct-count"><i class="bi bi-journal-text"></i> <?= (int) $category['article_count'] ?> Artikel</span>
                                <a href="categories.php?id=<?= (int) $category['id'] ?>" class="ct-link" aria-label="Lihat artikel kategori <?= e($category['name']) ?>">
                                    Lihat <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <div class="ct-empty" data-reveal>
                <div class="ct-empty-icon"><i class="bi bi-search-heart"></i></div>
                <h2><?= $q !== '' ? 'Kategori tidak ditemukan' : 'Belum Ada Kategori' ?></h2>
                <p><?= $q !== '' ? 'Coba gunakan kata kunci lain.' : 'Belum ada kategori yang tersedia.' ?></p>
                <a href="categories.php" class="ct-btn">Lihat Semua Kategori</a>
            </div>

        <?php endif; ?>

        <?php renderPagination($page, $totalPages); ?>

    <?php endif; ?>

    <?php if (!$dbError): ?>
        <!-- CTA -->
        <section class="ct-cta" data-reveal>
            <div class="ct-cta-text">
                <h3>Butuh panduan yang lebih praktis?</h3>
                <p>Lihat tips kesehatan singkat atau baca semua artikel yang sudah dipublikasikan.</p>
            </div>
            <div class="ct-cta-actions">
                <a href="tips.php" class="ct-btn-light">Tips Kesehatan <i class="bi bi-arrow-right"></i></a>
                <a href="articles.php" class="ct-btn-outline">Semua Artikel</a>
            </div>
        </section>
    <?php endif; ?>

    </main>

<script>
(function () {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 1. Animasi muncul saat di-scroll
    var items = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window && !reduceMotion) {
        items.forEach(function (el) { el.classList.add('ct-pre'); });
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('ct-pre');
                    entry.target.classList.add('ct-show');
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