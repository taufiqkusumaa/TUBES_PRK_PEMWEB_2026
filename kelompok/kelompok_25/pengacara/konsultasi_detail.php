<?php
// MODUL PENGACARA (Nday): detail konsultasi + form jawab
require_once __DIR__ . '/../config/app.php';
require_role('pengacara');

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$uid = (int) current_user()['id'];
$pdo = db();
$id  = (int) ($_GET['id'] ?? 0);

// Token CSRF khusus halaman ini
if (empty($_SESSION['csrf_pengacara'])) {
    $_SESSION['csrf_pengacara'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_pengacara'];

// Bidang pengacara yang login
$st = $pdo->prepare('SELECT bidang_id FROM profil_pengacara WHERE user_id = ?');
$st->execute([$uid]);
$bidangId = (int) $st->fetchColumn();

// Ambil konsultasi
$st = $pdo->prepare('SELECT k.*, u.nama AS klien, b.nama AS bidang
                     FROM konsultasi k
                     JOIN users u ON u.id = k.klien_id
                     LEFT JOIN bidang_hukum b ON b.id = k.bidang_id
                     WHERE k.id = ?');
$st->execute([$id]);
$k = $st->fetch();

// Aturan akses: bidangnya sama & belum diambil, atau memang ditangani saya
$boleh = $k && (
    ((int) $k['pengacara_id'] === $uid) ||
    ($k['pengacara_id'] === null && $bidangId > 0 && (int) $k['bidang_id'] === $bidangId)
);

if (!$boleh) {
    http_response_code(403);
    $judul = 'Akses ditolak';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container page-head"><div class="alert alert-danger">Konsultasi tidak ditemukan atau bukan di bidangmu.</div>'
       . '<a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Kembali ke dashboard</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$error = '';

// Proses kirim jawaban
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isi = trim((string) ($_POST['isi'] ?? ''));

    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $error = 'Sesi tidak valid, muat ulang halaman.';
    } elseif ($isi === '') {
        $error = 'Jawaban tidak boleh kosong.';
    } elseif (mb_strlen($isi) > 5000) {
        $error = 'Jawaban terlalu panjang (maksimal 5000 karakter).';
    } else {
        try {
            $pdo->beginTransaction();

            // Kunci baris, cek ulang supaya tidak bentrok dengan pengacara lain
            $st = $pdo->prepare('SELECT pengacara_id, status, bidang_id FROM konsultasi WHERE id = ? FOR UPDATE');
            $st->execute([$id]);
            $now = $st->fetch();

            $masihBoleh = $now
                && !in_array($now['status'], ['selesai', 'ditutup'], true)
                && (
                    ((int) $now['pengacara_id'] === $uid) ||
                    ($now['pengacara_id'] === null && (int) $now['bidang_id'] === $bidangId)
                );

            if (!$masihBoleh) {
                $pdo->rollBack();
                $error = 'Konsultasi ini sudah diambil pengacara lain atau sudah ditutup.';
            } else {
                $pdo->prepare('INSERT INTO balasan (konsultasi_id, pengirim_id, isi) VALUES (?, ?, ?)')
                    ->execute([$id, $uid, $isi]);
                $pdo->prepare("UPDATE konsultasi SET pengacara_id = ?, status = 'dijawab' WHERE id = ?")
                    ->execute([$uid, $id]);
                $pdo->commit();

                header('Location: konsultasi_detail.php?id=' . $id . '&ok=1');
                exit;
            }
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $error = 'Gagal menyimpan jawaban. Coba lagi.';
        }
    }

    // Muat ulang data setelah gagal
    $st = $pdo->prepare('SELECT k.*, u.nama AS klien, b.nama AS bidang
                         FROM konsultasi k
                         JOIN users u ON u.id = k.klien_id
                         LEFT JOIN bidang_hukum b ON b.id = k.bidang_id
                         WHERE k.id = ?');
    $st->execute([$id]);
    $k = $st->fetch();
}

// Percakapan
$st = $pdo->prepare('SELECT b.isi, b.created_at, b.pengirim_id, u.nama
                     FROM balasan b JOIN users u ON u.id = b.pengirim_id
                     WHERE b.konsultasi_id = ?
                     ORDER BY b.created_at ASC, b.id ASC');
$st->execute([$id]);
$chat = $st->fetchAll();

$badge  = ['menunggu' => 'warning', 'dijawab' => 'info', 'selesai' => 'success', 'ditutup' => 'secondary'];
$bisaJawab = $k && !in_array($k['status'], ['selesai', 'ditutup'], true);
$judul  = 'Detail Konsultasi';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container page-head">
  <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">&larr; Kembali</a>

  <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success">Jawaban berhasil dikirim.</div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="card-rh p-3 mb-4">
    <div class="d-flex justify-content-between align-items-start">
      <h3 class="mb-1"><?= e($k['judul']) ?></h3>
      <span class="badge text-bg-<?= e($badge[$k['status']] ?? 'secondary') ?>"><?= e($k['status']) ?></span>
    </div>
    <div class="text-secondary mb-3">
      Dari <?= e($k['klien']) ?> &middot; Bidang: <?= e($k['bidang'] ?? '-') ?> &middot; <?= e($k['created_at']) ?>
    </div>
    <p style="white-space: pre-wrap;"><?= e($k['isi']) ?></p>
    <?php if (!empty($k['lampiran'])): ?>
      <div class="text-secondary small">Lampiran: <?= e($k['lampiran']) ?></div>
    <?php endif; ?>
  </div>

  <h4 class="mb-2">Percakapan</h4>
  <?php foreach ($chat as $c): ?>
    <?php $saya = ((int) $c['pengirim_id'] === $uid); ?>
    <div class="card-rh p-3 mb-2 <?= $saya ? 'border-primary' : '' ?>">
      <div class="small text-secondary mb-1">
        <?= $saya ? 'Saya' : e($c['nama']) ?> &middot; <?= e($c['created_at']) ?>
      </div>
      <div style="white-space: pre-wrap;"><?= e($c['isi']) ?></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$chat): ?>
    <p class="text-secondary">Belum ada balasan.</p>
  <?php endif; ?>

  <?php if ($bisaJawab): ?>
    <h4 class="mt-4 mb-2">Tulis jawaban</h4>
    <form method="post" action="konsultasi_detail.php?id=<?= (int) $id ?>">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <textarea name="isi" class="form-control mb-2" rows="6" maxlength="5000" required></textarea>
      <button type="submit" class="btn btn-primary">Kirim jawaban</button>
    </form>
  <?php else: ?>
    <div class="alert alert-secondary mt-4">Konsultasi ini sudah <?= e($k['status']) ?>, tidak bisa dijawab lagi.</div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>