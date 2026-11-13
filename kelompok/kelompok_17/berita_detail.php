<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php'; // sekalian start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_berita = $_GET['id'] ?? null;
$komentarError = null;

if (!$id_berita || !is_numeric($id_berita)) {
    
    header('Location: index.php', true, 303);
    exit();
}


//simpan komen berita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_komentar'])) {
    csrf_verify();
    $idUser = $_SESSION['id_user'] ?? null;
    $isi    = trim($_POST['isi_komentar'] ?? '');
    $anonim = isset($_POST['is_anonim']) ? 1 : 0;

    if ($idUser && $isi !== '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO komentar (id_berita, id_user, isi_komentar, is_anonim) VALUES (?, ?, ?, ?)");
            $stmt->execute([(int)$id_berita, $idUser, $isi, $anonim]);
            header('Location: berita_detail.php?id=' . (int)$id_berita . '#komentar', true, 303);
            exit();
        } catch (PDOException $e) {
            error_log('Gagal menyimpan komentar berita: ' . $e->getMessage());
            $komentarError = 'Komentar gagal disimpan. Silakan coba lagi.';
        }
    }
}

$berita = null;
$komentarList = [];
try {
    // Ambil Detail Berita
    $stmt = $pdo->prepare("SELECT berita.*, kategori.nama_kategori, users.nama_lengkap AS penulis 
                           FROM berita 
                           LEFT JOIN kategori ON berita.id_kategori = kategori.id_kategori
                           LEFT JOIN users ON berita.id_user = users.id_user
                           WHERE berita.id_berita = ?");
    $stmt->execute([$id_berita]);
    $berita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$berita) {
        header('Location: index.php', true, 303);
        exit();
    }

    // Ambil List Komentar Berita
    $stmtKomentar = $pdo->prepare("SELECT k.*, u.nama_lengkap
                                   FROM komentar k
                                   LEFT JOIN users u ON k.id_user = u.id_user
                                   WHERE k.id_berita = ?
                                   ORDER BY k.tanggal_komentar ASC");
    $stmtKomentar->execute([$id_berita]);
    $komentarList = $stmtKomentar->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $komentarList = [];
    error_log('Gagal mengambil komentar berita: ' . $e->getMessage());
    $komentarError = 'Komentar belum dapat ditampilkan. Silakan muat ulang halaman.';
}

if (!$berita) {
    header('Location: index.php', true, 303);
    exit();
}

$pageStylesheet = 'css/berita_detail.css';
include_once __DIR__ . '/components/header.php';
?>

<div class="news-detail-page">
    <a href="index.php" class="news-back-link"> Kembali ke Beranda</a>
    
    <!-- CARD DETAIL BERITA -->
    <article class="news-article">
        <span class="news-category">
            <?= htmlspecialchars($berita['nama_kategori'] ?? 'Umum'); ?>
        </span>
        
        <h1 class="news-title">
            <?= htmlspecialchars($berita['judul_berita']); ?>
        </h1>
        
        <div class="news-meta">
            <span>Diterbitkan oleh: <strong><?= htmlspecialchars($berita['penulis'] ?? 'Admin Kampus'); ?></strong></span>
            <span class="news-meta-separator">&bull;</span>
            <time><?= date('d M Y, H:i', strtotime($berita['tanggal_publikasi'])); ?> WIB</time>
        </div>

        <?php if (!empty($berita['gambar_url'])): ?>
            <img src="uploads/<?= htmlspecialchars($berita['gambar_url']); ?>" alt="Gambar Berita" class="news-image">
        <?php endif; ?>

        <div class="news-content">
            <?= nl2br(htmlspecialchars($berita['isi_berita'])); ?>
        </div>
    </article>

    <!-- SECTION KOMENTAR BERITA -->
    <section id="komentar" class="news-comments">
        <?php if ($komentarError): ?>
            <p class="news-error" role="alert">
                <?= htmlspecialchars($komentarError); ?>
            </p>
        <?php endif; ?>
        <div class="news-comments-heading">
            <h2>Komentar</h2>
            <span class="news-comment-count">
                <?= count($komentarList) ?> Komentar
            </span>
        </div>

        <!-- DAFTAR KOMENTAR -->
        <div class="news-comment-list">
            <?php if (!empty($komentarList)): ?>
                <?php foreach ($komentarList as $k): ?>
                    <article class="news-comment">
                        <div class="news-comment-meta">
                            <span class="news-comment-author">
                                <?= $k['is_anonim'] ? '👤 Anonim' : '🎓 ' . htmlspecialchars($k['nama_lengkap'] ?? 'Mahasiswa') ?>
                            </span>
                            <time>
                                <?= date('d M Y, H:i', strtotime($k['tanggal_komentar'])) ?> WIB
                            </time>
                        </div>
                        <p>
                            <?= nl2br(htmlspecialchars($k['isi_komentar'])); ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="news-empty-comments">
                    Belum ada komentar untuk berita ini. Berikan pandangan Anda pertama kali!
                </div>
            <?php endif; ?>
        </div>


        <!-- FORM INPUT KOMENTAR -->
        <?php if (isset($_SESSION['id_user'])): ?>
            <form action="berita_detail.php?id=<?= (int)$id_berita ?>" method="POST" class="news-comment-form">
                <?= csrf_field() ?>
                <label class="news-anonymous-option">
                    <input type="checkbox" name="is_anonim" value="1">
                    Komentar sebagai Anonim
                </label>
                <textarea name="isi_komentar" rows="4" required
                    placeholder="Tuliskan komentar atau tanggapan mengenai berita ini..."></textarea>
                <button type="submit" name="submit_komentar">Kirim Komentar</button>
            </form>


        <?php else: ?>
            <div class="news-login-notice">
                <span>Silakan login terlebih dahulu untuk memberikan komentar.</span>
                <a href="auth/login.php">
                    Login Sekarang
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>