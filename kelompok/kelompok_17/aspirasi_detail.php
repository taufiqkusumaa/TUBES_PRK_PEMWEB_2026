<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';


$id_aspirasi = $_GET['id'] ?? null;
$isAdmin = strtolower($_SESSION['role'] ?? '') === 'admin';

if (!$id_aspirasi || !is_numeric($id_aspirasi)) {
    header('Location: index.php', true, 303);
    exit();
}

// PROSES SIMPAN TANGGAPAN / KOMENTAR
$komentarError = null;

try {
    // Ambil Detail Aspirasi
    $stmt = $pdo->prepare("SELECT aspirasi.*, kategori.nama_kategori, users.nama_lengkap 
                           FROM aspirasi 
                           LEFT JOIN kategori ON aspirasi.id_kategori = kategori.id_kategori
                           LEFT JOIN users ON aspirasi.id_user = users.id_user
                           WHERE aspirasi.id_aspirasi = ?");
    $stmt->execute([$id_aspirasi]);
    $aspirasi = $stmt->fetch(PDO::FETCH_ASSOC);

    $isOwner = $aspirasi && isset($_SESSION['id_user'])
        && (int) $_SESSION['id_user'] === (int) $aspirasi['id_user'];
    if (!$aspirasi || ($aspirasi['status'] !== 'publikasi' && !$isAdmin && !$isOwner)) {
        header('Location: index.php', true, 303);
        exit();
    }

    // Ambil List Tanggapan
    $stmtTanggapan = $pdo->prepare("SELECT k.*, u.nama_lengkap, u.role
                                    FROM komentar k
                                    LEFT JOIN users u ON k.id_user = u.id_user
                                    WHERE k.id_aspirasi = ?
                                    ORDER BY k.tanggal_komentar ASC");
    $stmtTanggapan->execute([$id_aspirasi]);
    $tanggapanList = $stmtTanggapan->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Gagal mengambil aspirasi: ' . $e->getMessage());
    header('Location: index.php', true, 303);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tanggapan'])) {
    csrf_verify();
    $idUser = $_SESSION['id_user'] ?? null;
    $isi    = trim($_POST['isi_tanggapan'] ?? '');
    $anonim = isset($_POST['is_anonim']) ? 1 : 0;

    if ($idUser && $isi !== '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO komentar (id_aspirasi, id_user, isi_komentar, is_anonim) VALUES (?, ?, ?, ?)");
            $stmt->execute([(int)$id_aspirasi, $idUser, $isi, $anonim]);
            header('Location: aspirasi_detail.php?id=' . (int)$id_aspirasi . '#diskusi', true, 303);
            exit();
        } catch (PDOException $e) {
            error_log('Gagal menyimpan tanggapan aspirasi: ' . $e->getMessage());
            $komentarError = 'Tanggapan gagal disimpan. Silakan coba lagi.';
        }
    }
}

$pageStylesheet = 'css/aspirasi_detail.css';
include_once __DIR__ . '/components/header.php';
?>

<div class="aspiration-detail-page">
    <a href="<?= $isAdmin ? 'admin/aspirasi/aspirasi.php' : 'index.php' ?>" class="aspiration-back-link">
        &larr; <?= $isAdmin ? 'Kembali ke Kelola Aspirasi' : 'Kembali ke Beranda' ?>
    </a>

    <!-- CARD DETAIL ASPIRASI -->
    <article class="aspiration-article">
        <div class="aspiration-badges">
            <span class="aspiration-category">
                <?= htmlspecialchars($aspirasi['nama_kategori'] ?? 'Umum'); ?>
            </span>
            
            <?php
            $statusClass = match(strtolower($aspirasi['status'] ?? '')) {
                'menunggu' => 'aspiration-status-pending',
                'publikasi' => 'aspiration-status-published',
                default => 'aspiration-status-default',
            };
            ?>
            <span class="aspiration-status <?= $statusClass; ?>">
                Status: <?= htmlspecialchars($aspirasi['status'] ?? 'Menunggu'); ?>
            </span>
        </div>

        <h1 class="aspiration-title">
            <?= htmlspecialchars($aspirasi['judul_aspirasi']); ?>
        </h1>

        <div class="aspiration-meta">
            <span>Oleh: <strong><?= ($aspirasi['is_anonim'] == 1) ? 'Anonim' : htmlspecialchars($aspirasi['nama_lengkap'] ?? 'Mahasiswa'); ?></strong></span>
            <span class="aspiration-meta-separator" aria-hidden="true">&bull;</span>
            <time><?= date('d M Y, H:i', strtotime($aspirasi['tanggal_kirim'])); ?> WIB</time>
        </div>

        <div class="aspiration-content">
            <?= nl2br(htmlspecialchars($aspirasi['isi_aspirasi'])); ?>
        </div>
    </article>

    <!-- SECTION DISKUSI & TANGGAPAN -->
    <section id="diskusi" class="aspiration-discussion">
        <?php if ($komentarError): ?>
            <p class="aspiration-error" role="alert">
                <?= htmlspecialchars($komentarError); ?>
            </p>
        <?php endif; ?>
        <div class="aspiration-discussion-heading">
            <h2>Diskusi / Tanggapan</h2>
            <span class="aspiration-comment-count">
                <?= count($tanggapanList) ?> Tanggapan
            </span>
        </div>

        <!-- DAFTAR KOMENTAR/TANGGAPAN -->
        <div class="aspiration-comment-list">
            <?php if (!empty($tanggapanList)): ?>
                <?php foreach ($tanggapanList as $t): ?>
                    <article class="aspiration-comment">
                        <div class="aspiration-comment-meta">
                            <span class="aspiration-comment-author">
                                <?= (isset($t['role']) && strtolower($t['role']) === 'admin')
                                    ? '<span class="aspiration-admin-badge">Admin FISIP</span>'
                                    : (($t['is_anonim'] ?? 0) ? 'Anonim' : htmlspecialchars($t['nama_lengkap'] ?? 'Mahasiswa')) ?>
                            </span>
                            <time>
                                <?= date('d M Y, H:i', strtotime($t['tanggal_komentar'])) ?> WIB
                            </time>
                        </div>
                        <p class="aspiration-comment-content">
                            <?= nl2br(htmlspecialchars($t['isi_komentar'])); ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="aspiration-empty">
                    Belum ada diskusi atau tanggapan. Jadilah yang pertama memberikan masukan!
                </div>
            <?php endif; ?>
        </div>

        <!-- FORM INPUT TANGGAPAN -->
        <?php if (isset($_SESSION['id_user'])): ?>
            <form action="aspirasi_detail.php?id=<?= (int)$id_aspirasi ?>" method="POST" class="aspiration-comment-form">
                <div>
                    <?= csrf_field() ?>
                    <label class="aspiration-form-label" for="isi-tanggapan">Berikan Tanggapan Anda:</label>
                    <textarea name="isi_tanggapan" rows="3" required
                        id="isi-tanggapan"
                        placeholder="Tuliskan pendapat atau masukan tambahan mengenai aspirasi ini..."></textarea>
                    <label class="aspiration-anonymous-option">
                        <input type="checkbox" name="is_anonim" value="1"> Tanggapan sebagai Anonim
                    </label>
                </div>

                <button type="submit" name="submit_tanggapan">
                    Kirim Tanggapan
                </button>
            </form>
        <?php else: ?>
            <div class="aspiration-login-notice">
                <span>Silakan login terlebih dahulu untuk memberikan tanggapan.</span>
                <a href="auth/login.php">
                    Login Sekarang
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>