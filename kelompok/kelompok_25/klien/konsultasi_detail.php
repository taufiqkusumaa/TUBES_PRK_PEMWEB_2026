<?php
// MODUL KLIEN (Ghani): detail konsultasi, balas, tandai selesai, beri ulasan (rating bintang)
require_once __DIR__ . '/../config/app.php';
require_role('klien');

$user = current_user();
$pdo  = db();
$id   = (int) ($_GET['id'] ?? 0);

// Klien hanya boleh membuka konsultasi miliknya sendiri
$stmt = $pdo->prepare(
    'SELECT k.*, b.nama AS bidang, u.nama AS pengacara
     FROM konsultasi k
     LEFT JOIN bidang_hukum b ON b.id = k.bidang_id
     LEFT JOIN users u ON u.id = k.pengacara_id
     WHERE k.id = ? AND k.klien_id = ?'
);
$stmt->execute([$id, $user['id']]);
$k = $stmt->fetch();

if (!$k) {
    http_response_code(404);
    $judul = 'Konsultasi tidak ditemukan';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container page-head"><div class="card-rh p-4">Konsultasi tidak ditemukan atau bukan milikmu. '
       . '<a href="' . e(url('klien/dashboard.php')) . '">Kembali ke dashboard</a></div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$errors = [];
// Aturan status: klien baru boleh membalas / menandai selesai SETELAH pengacara menjawab.
$menunggu   = ($k['status'] === 'menunggu');
$bisa_balas = ($k['status'] === 'dijawab');
// Ulasan (rating) baru bisa diberikan setelah konsultasi selesai, dan hanya satu kali per konsultasi.
$bisa_ulas  = ($k['status'] === 'selesai' && !empty($k['pengacara_id']));

$st = $pdo->prepare('SELECT rating, komentar, created_at FROM ulasan WHERE konsultasi_id = ?');
$st->execute([$k['id']]);
$ulasan = $st->fetch() ?: null;

$rating_isi   = 0;
$komentar_isi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($aksi === 'ulas') {
        if (!$bisa_ulas) {
            $errors[] = 'Ulasan hanya bisa diberikan setelah konsultasi ditandai selesai.';
        } elseif ($ulasan) {
            $errors[] = 'Kamu sudah memberi ulasan untuk konsultasi ini.';
        } else {
            $rating_isi   = (int) ($_POST['rating'] ?? 0);
            $komentar_isi = trim($_POST['komentar'] ?? '');
            if ($rating_isi < 1 || $rating_isi > 5) {
                $errors[] = 'Pilih jumlah bintang dulu (1 sampai 5).';
            }
            if (mb_strlen($komentar_isi) > 500) {
                $errors[] = 'Komentar maksimal 500 karakter.';
            }
            if (!$errors) {
                try {
                    $pdo->prepare('INSERT INTO ulasan (konsultasi_id, klien_id, pengacara_id, rating, komentar) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$k['id'], $user['id'], $k['pengacara_id'], $rating_isi, $komentar_isi !== '' ? $komentar_isi : null]);
                } catch (PDOException $ex) {
                    if ($ex->getCode() !== '23000') { throw $ex; }   // 23000 = ulasan ganda (sudah ada)
                    flash('warning', 'Kamu sudah memberi ulasan untuk konsultasi ini.');
                    redirect('klien/konsultasi_detail.php?id=' . $k['id'] . '#ulasan');
                }
                flash('success', 'Terima kasih! Ulasanmu sudah tersimpan dan membantu klien lain memilih pengacara.');
                redirect('klien/konsultasi_detail.php?id=' . $k['id'] . '#ulasan');
            }
        }
    } elseif (!$bisa_balas) {
        $errors[] = $menunggu
            ? 'Pengacara belum menjawab. Kamu bisa membalas setelah ada jawaban.'
            : 'Konsultasi ini sudah ditutup, tidak bisa dibalas lagi.';
    } else {
        if ($aksi === 'balas') {
            $isi = trim($_POST['isi'] ?? '');
            if (mb_strlen($isi) < 2) {
                $errors[] = 'Isi balasan tidak boleh kosong.';
            } else {
                $ins = $pdo->prepare('INSERT INTO balasan (konsultasi_id, pengirim_id, isi) VALUES (?, ?, ?)');
                $ins->execute([$k['id'], $user['id'], $isi]);
                flash('success', 'Balasan terkirim.');
                redirect('klien/konsultasi_detail.php?id=' . $k['id']);
            }
        } elseif ($aksi === 'selesai') {
            $upd = $pdo->prepare("UPDATE konsultasi SET status = 'selesai' WHERE id = ? AND klien_id = ? AND status = 'dijawab'");
            $upd->execute([$k['id'], $user['id']]);
            if (!empty($k['pengacara_id'])) {
                flash('success', 'Konsultasi ditandai selesai. Yuk beri ulasan untuk pengacaramu.');
                redirect('klien/konsultasi_detail.php?id=' . $k['id'] . '#ulasan');
            }
            flash('success', 'Konsultasi ditandai selesai.');
            redirect('klien/konsultasi_detail.php?id=' . $k['id']);
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT bl.id, bl.isi, bl.pengirim_id, u.nama, u.role
     FROM balasan bl JOIN users u ON u.id = bl.pengirim_id
     WHERE bl.konsultasi_id = ? ORDER BY bl.id ASC'
);
$stmt->execute([$k['id']]);
$balasan = $stmt->fetchAll();

$judul = 'Detail Konsultasi';
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/ulasan.css')) ?>">

<div class="container page-head">
  <a href="<?= e(url('klien/dashboard.php')) ?>" class="text-decoration-none">&larr; Kembali ke dashboard</a>
  <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-3 gap-2">
    <h2 class="mb-0"><?= e($k['judul']) ?></h2>
    <span class="badge-status badge-<?= e($k['status']) ?>"><?= e(ucfirst($k['status'])) ?></span>
  </div>
  <p class="text-secondary">
    Bidang: <?= e($k['bidang'] ?? '-') ?> &middot; Pengacara: <?= e($k['pengacara'] ?? 'Belum ada') ?>
  </p>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="card-rh p-4 mb-3">
    <strong>Pertanyaanmu</strong>
    <p class="mb-0 mt-2"><?= nl2br(e($k['isi'])) ?></p>
  </div>

  <?php foreach ($balasan as $b): ?>
    <?php $saya = ((int) $b['pengirim_id'] === (int) $user['id']); ?>
    <div class="d-flex <?= $saya ? 'justify-content-end' : 'justify-content-start' ?> mb-3">
      <div class="card-rh p-3" style="max-width: 80%;">
        <small class="text-secondary">
          <?= $saya ? 'Kamu' : e($b['nama']) . ' (' . e($b['role']) . ')' ?>
        </small>
        <div class="mt-1"><?= nl2br(e($b['isi'])) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$balasan): ?>
    <p class="text-secondary">Belum ada balasan.</p>
  <?php endif; ?>

  <?php if ($bisa_balas): ?>
    <div class="card-rh p-4 mt-4">
      <form method="post" class="mb-3">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="balas">
        <label class="form-label" for="isi">Balas</label>
        <textarea class="form-control mb-3" id="isi" name="isi" rows="4" required></textarea>
        <button class="btn btn-gold" type="submit">Kirim Balasan</button>
      </form>
      <form method="post"
            data-confirm="Setelah ditandai selesai, konsultasi ini tidak bisa dibalas lagi."
            data-confirm-judul="Tandai konsultasi selesai?"
            data-confirm-ok="Ya, tandai selesai">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="selesai">
        <button class="btn btn-navy" type="submit">Tandai selesai</button>
      </form>
    </div>
  <?php elseif ($menunggu): ?>
    <div class="card-rh p-4 mt-4 text-center">
      <i class="bi bi-hourglass-split fs-2" style="color: var(--gold-600);"></i>
      <p class="mb-0 mt-2">Pertanyaanmu sudah terkirim dan sedang menunggu pengacara menjawab.<br>
        Kotak balasan akan muncul setelah ada jawaban.</p>
    </div>
  <?php else: ?>
    <div class="alert alert-secondary mt-4">Konsultasi ini sudah <?= e($k['status']) ?>.</div>

    <?php if ($ulasan): ?>
      <div class="card-rh ulasan-kartu p-4 mt-3" id="ulasan">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
          <strong><i class="bi bi-chat-heart"></i> Ulasanmu</strong>
          <span class="text-secondary small"><?= e(tgl($ulasan['created_at'])) ?></span>
        </div>
        <div class="bintang-tampil my-2" aria-label="Rating <?= (int) $ulasan['rating'] ?> dari 5">
          <?php for ($i = 1; $i <= 5; $i++): ?><i class="bi <?= $i <= (int) $ulasan['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i><?php endfor; ?>
          <span class="ms-2 fw-semibold"><?= (int) $ulasan['rating'] ?>/5</span>
        </div>
        <?php if (trim((string) $ulasan['komentar']) !== ''): ?>
          <p class="mb-0">&ldquo;<?= nl2br(e($ulasan['komentar'])) ?>&rdquo;</p>
        <?php else: ?>
          <p class="text-secondary mb-0">Kamu tidak menulis komentar.</p>
        <?php endif; ?>
      </div>
    <?php elseif ($bisa_ulas): ?>
      <div class="card-rh ulasan-kartu p-4 mt-3" id="ulasan">
        <h3 class="h5 mb-1">Beri ulasan untuk <?= e($k['pengacara'] ?? 'pengacara') ?></h3>
        <p class="text-secondary mb-3">Ulasanmu membantu klien lain memilih pengacara yang tepat. Rating akan tampil di profil pengacara dan Beranda.</p>
        <form method="post" id="formUlasan" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="ulas">
          <div class="bintang" role="radiogroup" aria-label="Rating bintang">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" name="rating" id="bintang<?= $i ?>" value="<?= $i ?>" <?= $rating_isi === $i ? 'checked' : '' ?>>
              <label for="bintang<?= $i ?>" title="<?= $i ?> bintang"><i class="bi bi-star-fill"></i><span class="visually-hidden"><?= $i ?> bintang</span></label>
            <?php endfor; ?>
          </div>
          <div class="bintang-teks" id="bintangTeks" aria-live="polite">Pilih jumlah bintang</div>
          <div id="galatBintang"></div>

          <label class="form-label mt-3" for="komentar">Komentar <span class="text-secondary fw-normal">(boleh dikosongkan)</span></label>
          <textarea class="form-control" id="komentar" name="komentar" rows="4" maxlength="500"
                    placeholder="Ceritakan pengalamanmu: apakah jawabannya jelas, cepat, dan membantu?"><?= e($komentar_isi) ?></textarea>
          <div class="text-end small text-secondary mt-1"><span id="hitungKomentar">0</span>/500</div>

          <button class="btn btn-gold mt-2" type="submit"><i class="bi bi-send"></i> Kirim ulasan</button>
        </form>
      </div>
      <script>
      (function () {
        var form = document.getElementById('formUlasan');
        var teks = document.getElementById('bintangTeks');
        var nama = {1: 'Kurang puas', 2: 'Cukup', 3: 'Baik', 4: 'Sangat baik', 5: 'Luar biasa'};
        function perbarui() {
          var r = form.querySelector('input[name=rating]:checked');
          teks.textContent = r ? r.value + ' dari 5: ' + nama[r.value] : 'Pilih jumlah bintang';
          if (r) { var g = document.getElementById('galatBintang'); g.innerHTML = ''; }
        }
        form.addEventListener('change', perbarui);
        perbarui();

        var ta = document.getElementById('komentar'), hit = document.getElementById('hitungKomentar');
        function hitung() { hit.textContent = ta.value.length; }
        ta.addEventListener('input', hitung); hitung();

        form.addEventListener('submit', function (ev) {
          if (form.querySelector('input[name=rating]:checked')) { return; }
          ev.preventDefault();
          var g = document.getElementById('galatBintang');
          g.innerHTML = '<div class="pesan-validasi" role="alert"><i class="bi bi-exclamation-circle-fill"></i><span>Pilih jumlah bintang dulu sebelum mengirim.</span></div>';
        });
      })();
      </script>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
