<?php
// ==========================================
// SEHATIN - HALAMAN TIPS KESEHATAN
// PHP Native + PDO (tanpa framework)
// ==========================================

require_once 'config/database.php';

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

// Membuat URL dengan mempertahankan parameter pencarian & sortir
function buildUrl($overrides = []) {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null || ($k === 'page' && (int) $v <= 1)) unset($params[$k]);
    }
    return 'tips.php' . (empty($params) ? '' : '?' . http_build_query($params));
}

// ---------- Parameter dari URL ----------
$perPage = 9;
$q       = trim($_GET['q'] ?? '');
$sort    = (($_GET['sort'] ?? 'terbaru') === 'terlama') ? 'terlama' : 'terbaru';
$page    = max(1, (int) ($_GET['page'] ?? 1));

// ---------- Statistik (dinamis dari database) ----------
$totalAllTips   = (int) $pdo->query("SELECT COUNT(*) FROM tips")->fetchColumn();
$totalArticles  = 0;
$totalCategories = 0;

try {
    $totalArticles   = (int) $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
    $totalCategories = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
} catch (PDOException $ex) {
    // Jika tabel belum ada, statistik dibiarkan 0
}

// ---------- Tips Hari Ini (berganti otomatis tiap hari) ----------
$tipOfTheDay = null;
if ($totalAllTips > 0) {
    $offset = ((int) date('z')) % $totalAllTips;
    $stmt = $pdo->prepare("SELECT * FROM tips ORDER BY created_at ASC LIMIT 1 OFFSET :o");
    $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tipOfTheDay = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ---------- Daftar tips: pencarian + sortir + pagination ----------
$where  = '';
$params = [];
if ($q !== '') {
    $where = "WHERE title LIKE :q1 OR description LIKE :q2";
    $params = ['q1' => "%$q%", 'q2' => "%$q%"];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM tips $where");
$countStmt->execute($params);
$totalResults = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalResults / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;
$orderSql   = $sort === 'terlama' ? 'ASC' : 'DESC';

$listStmt = $pdo->prepare("SELECT * FROM tips $where ORDER BY created_at $orderSql LIMIT :limit OFFSET :offset");
foreach ($params as $key => $val) {
    $listStmt->bindValue(':' . $key, $val);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$tips = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// Tips pilihan hanya tampil di halaman 1 tanpa pencarian
$featured = null;
if ($page === 1 && $q === '' && $sort === 'terbaru' && count($tips) > 0) {
    $featured = array_shift($tips);
}

$pageTitle = 'Tips Kesehatan - Sehatin';
$extraCss = ['assets/css/articles.css', 'assets/css/tips_public.css?v=' . time()];
$bodyClass = 'page-articles';
include 'includes/header.php';
include 'includes/navbar.php';
?>

    <!-- HERO -->
    <section class="hero">
        <div class="container hero-content">
            <span class="eyebrow">JELAJAHI SEHATIN</span>
            <h1>Tips Kesehatan Sehatin</h1>
            <p>Temukan berbagai tips sederhana untuk menjaga kesehatan dan pola hidup sehat.</p>
        </div>
    </section>

    <main class="container tp-main">

        <!-- STATISTIK -->
        <section class="tp-stats" data-reveal>
            <div class="tp-stat">
                <div class="tp-stat-icon"><i class="bi bi-lightbulb-fill"></i></div>
                <div>
                    <strong data-count="<?= $totalAllTips ?>">0</strong>
                    <span>Tips Kesehatan</span>
                </div>
            </div>
            <div class="tp-stat">
                <div class="tp-stat-icon"><i class="bi bi-journal-medical"></i></div>
                <div>
                    <strong data-count="<?= $totalArticles ?>">0</strong>
                    <span>Artikel Medis</span>
                </div>
            </div>
            <div class="tp-stat">
                <div class="tp-stat-icon"><i class="bi bi-grid-fill"></i></div>
                <div>
                    <strong data-count="<?= $totalCategories ?>">0</strong>
                    <span>Kategori Topik</span>
                </div>
            </div>
        </section>

        <?php if ($tipOfTheDay): ?>
            <!-- TIPS HARI INI -->
            <section class="tp-today" data-reveal>
                <div class="tp-today-icon"><i class="bi bi-sun-fill"></i></div>
                <div class="tp-today-text">
                    <span class="tp-today-label">Tips Hari Ini &middot; <?= e(date('d/m/Y')) ?></span>
                    <h3><?= e($tipOfTheDay['title']) ?></h3>
                    <p><?= e(ringkas($tipOfTheDay['description'], 170)) ?></p>
                </div>
                <button
                    type="button"
                    class="tp-btn-light"
                    data-bs-toggle="modal"
                    data-bs-target="#tipModal"
                    data-title="<?= e($tipOfTheDay['title']) ?>"
                    data-date="<?= e(tanggalIndo($tipOfTheDay['created_at'] ?? '')) ?>"
                    data-image="<?= !empty($tipOfTheDay['image']) ? e('assets/uploads/tips/' . $tipOfTheDay['image']) : '' ?>"
                    data-body="<?= e(nl2br(e($tipOfTheDay['description']))) ?>"
                >
                    Baca Tips <i class="bi bi-arrow-right"></i>
                </button>
            </section>
        <?php endif; ?>

        <!-- TOOLBAR: PENCARIAN & SORTIR -->
        <section class="tp-toolbar" data-reveal>
            <div class="tp-toolbar-title">
                <span class="eyebrow">KUMPULAN TIPS</span>
                <h2>Tips untuk Hidup Lebih Sehat</h2>
            </div>

            <form method="get" action="tips.php" class="tp-filter">
                <div class="tp-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari tips kesehatan..." aria-label="Cari tips">
                </div>
                <select name="sort" aria-label="Urutkan" onchange="this.form.submit()">
                    <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="terlama" <?= $sort === 'terlama' ? 'selected' : '' ?>>Terlama</option>
                </select>
                <button type="submit" class="tp-btn">Cari</button>
            </form>
        </section>

        <?php if ($q !== ''): ?>
            <p class="tp-result-info">
                Menampilkan <strong><?= $totalResults ?></strong> hasil untuk
                "<strong><?= e($q) ?></strong>"
                &middot; <a href="tips.php">Reset pencarian</a>
            </p>
        <?php endif; ?>

        <?php if ($featured): ?>
            <!-- TIPS PILIHAN -->
            <article class="tp-featured" data-reveal>
                <div class="tp-featured-media">
                    <?php if (!empty($featured['image'])): ?>
                        <img src="assets/uploads/tips/<?= e($featured['image']) ?>" alt="<?= e($featured['title']) ?>">
                    <?php else: ?>
                        <div class="tp-placeholder"><i class="bi bi-heart-pulse-fill"></i></div>
                    <?php endif; ?>
                </div>
                <div class="tp-featured-body">
                    <span class="tp-badge"><i class="bi bi-stars"></i> Tips Terbaru</span>
                    <h3><?= e($featured['title']) ?></h3>
                    <p><?= e(ringkas($featured['description'], 260)) ?></p>
                    <div class="tp-featured-foot">
                        <span class="tp-date"><i class="bi bi-calendar3"></i> <?= e(tanggalIndo($featured['created_at'] ?? '')) ?></span>
                        <button
                            type="button"
                            class="tp-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#tipModal"
                            data-title="<?= e($featured['title']) ?>"
                            data-date="<?= e(tanggalIndo($featured['created_at'] ?? '')) ?>"
                            data-image="<?= !empty($featured['image']) ? e('assets/uploads/tips/' . $featured['image']) : '' ?>"
                            data-body="<?= e(nl2br(e($featured['description']))) ?>"
                        >
                            Baca Selengkapnya <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </article>
        <?php endif; ?>

        <?php if (count($tips) > 0): ?>

            <!-- GRID TIPS -->
            <div class="tp-grid">
                <?php foreach ($tips as $i => $tip): ?>
                    <article class="tp-card" data-reveal style="--d: <?= ($i % 3) * 90 ?>ms">
                        <div class="tp-card-media">
                            <?php if (!empty($tip['image'])): ?>
                                <img src="assets/uploads/tips/<?= e($tip['image']) ?>" alt="<?= e($tip['title']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="tp-placeholder"><i class="bi bi-heart-pulse-fill"></i></div>
                            <?php endif; ?>
                            <span class="tp-chip"><i class="bi bi-lightbulb"></i> Tips</span>
                        </div>

                        <div class="tp-card-body">
                            <h3><?= e($tip['title']) ?></h3>
                            <p><?= e(ringkas($tip['description'], 115)) ?></p>

                            <div class="tp-card-foot">
                                <span class="tp-date"><i class="bi bi-calendar3"></i> <?= e(tanggalIndo($tip['created_at'] ?? '')) ?></span>
                                <button
                                    type="button"
                                    class="tp-link"
                                    data-bs-toggle="modal"
                                    data-bs-target="#tipModal"
                                    data-title="<?= e($tip['title']) ?>"
                                    data-date="<?= e(tanggalIndo($tip['created_at'] ?? '')) ?>"
                                    data-image="<?= !empty($tip['image']) ? e('assets/uploads/tips/' . $tip['image']) : '' ?>"
                                    data-body="<?= e(nl2br(e($tip['description']))) ?>"
                                >
                                    Baca <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php elseif (!$featured): ?>

            <div class="tp-empty" data-reveal>
                <div class="tp-empty-icon"><i class="bi bi-search-heart"></i></div>
                <h2><?= $q !== '' ? 'Tips tidak ditemukan' : 'Belum Ada Tips' ?></h2>
                <p><?= $q !== '' ? 'Coba gunakan kata kunci lain.' : 'Belum ada tips kesehatan tersedia.' ?></p>
                <a href="tips.php" class="tp-btn">Lihat Semua Tips</a>
            </div>

        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <!-- PAGINATION -->
            <nav class="tp-pagination" aria-label="Navigasi halaman">
                <?php if ($page > 1): ?>
                    <a href="<?= e(buildUrl(['page' => $page - 1])) ?>" class="tp-page"><i class="bi bi-chevron-left"></i></a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="<?= e(buildUrl(['page' => $p])) ?>" class="tp-page <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= e(buildUrl(['page' => $page + 1])) ?>" class="tp-page"><i class="bi bi-chevron-right"></i></a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        <!-- CTA -->
        <section class="tp-cta" data-reveal>
            <div class="tp-cta-text">
                <h3>Ingin belajar lebih dalam?</h3>
                <p>Baca artikel kesehatan lengkap yang ditulis dengan bahasa yang mudah dipahami.</p>
            </div>
            <div class="tp-cta-actions">
                <a href="articles.php" class="tp-btn-light">Semua Artikel <i class="bi bi-arrow-right"></i></a>
                <a href="categories.php" class="tp-btn-outline">Jelajahi Kategori</a>
            </div>
        </section>

    </main>

    <!-- MODAL BACA TIPS -->
    <div class="modal fade tp-modal" id="tipModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="tp-modal-media" id="tipModalMedia"><img src="" alt=""></div>
                <div class="modal-body">
                    <span class="tp-badge"><i class="bi bi-lightbulb-fill"></i> Tips Kesehatan</span>
                    <h3 id="tipModalTitle"></h3>
                    <div class="tp-date mb-3"><i class="bi bi-calendar3"></i> <span id="tipModalDate"></span></div>
                    <div class="tp-modal-text" id="tipModalBody"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tp-btn" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

<script>
(function () {
    // 1. Isi modal dari atribut data-* tombol yang diklik
    var modal = document.getElementById('tipModal');
    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;
        document.getElementById('tipModalTitle').textContent = btn.dataset.title || '';
        document.getElementById('tipModalDate').textContent  = btn.dataset.date || '';
        document.getElementById('tipModalBody').innerHTML    = btn.dataset.body || '';

        var media = document.getElementById('tipModalMedia');
        var img = media.querySelector('img');
        if (btn.dataset.image) {
            img.src = btn.dataset.image;
            media.style.display = 'block';
        } else {
            media.style.display = 'none';
        }
    });

    // 2. Animasi muncul saat di-scroll
    var items = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window) {
        items.forEach(function (el) { el.classList.add('tp-pre'); });
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('tp-pre');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        items.forEach(function (el) { io.observe(el); });
    }

    // 3. Animasi angka statistik
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseInt(el.dataset.count, 10) || 0;
        var duration = 900;
        var start = null;
        function step(ts) {
            if (!start) start = ts;
            var progress = Math.min((ts - start) / duration, 1);
            el.textContent = Math.floor(progress * target);
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target;
        }
        requestAnimationFrame(step);
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>