<?php
// MODUL PENGACARA (Nday): dashboard
require_once __DIR__ . '/../config/app.php';
require_role('pengacara');
 
$uid   = (int) current_user()['id'];
$pdo   = db();
$judul = 'Dashboard Pengacara';
 
// Bidang hukum milik pengacara yang login
$st = $pdo->prepare('SELECT bidang_id FROM profil_pengacara WHERE user_id = ?');
$st->execute([$uid]);
$bidangId = (int) $st->fetchColumn();
 
// Kartu angka
$st = $pdo->prepare("SELECT COUNT(*) FROM konsultasi
                     WHERE status = 'menunggu' AND pengacara_id IS NULL AND bidang_id = ?");
$st->execute([$bidangId]);
$jmlMasuk = (int) $st->fetchColumn();
 
$st = $pdo->prepare("SELECT COUNT(*) FROM konsultasi WHERE pengacara_id = ? AND status = 'dijawab'");
$st->execute([$uid]);
$jmlDijawab = (int) $st->fetchColumn();
 
$st = $pdo->prepare("SELECT COUNT(*) FROM konsultasi WHERE pengacara_id = ? AND status = 'selesai'");
$st->execute([$uid]);
$jmlSelesai = (int) $st->fetchColumn();
 
// Rating rata-rata dan jumlah ulasan
$st = $pdo->prepare('SELECT COUNT(*) AS jml, AVG(rating) AS rata FROM ulasan WHERE pengacara_id = ?');
$st->execute([$uid]);
$ul = $st->fetch();
$jmlUlasan  = (int) $ul['jml'];
$ratingRata = $jmlUlasan > 0 ? number_format((float) $ul['rata'], 1) : null;
 
// Pertanyaan masuk
$st = $pdo->prepare("SELECT k.id, k.judul, k.status, u.nama AS klien
                     FROM konsultasi k JOIN users u ON u.id = k.klien_id
                     WHERE k.status = 'menunggu' AND k.pengacara_id IS NULL AND k.bidang_id = ?
                     ORDER BY k.id DESC");
$st->execute([$bidangId]);
$masuk = $st->fetchAll();
 
// Konsultasi yang saya tangani
$st = $pdo->prepare("SELECT k.id, k.judul, k.status, u.nama AS klien
                     FROM konsultasi k JOIN users u ON u.id = k.klien_id
                     WHERE k.pengacara_id = ?
                     ORDER BY k.id DESC");
$st->execute([$uid]);
$saya = $st->fetchAll();
 
$badge = ['menunggu' => 'warning', 'dijawab' => 'info', 'selesai' => 'success', 'ditutup' => 'secondary'];
 
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container page-head">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-0">Dashboard Pengacara</h2>
      <p class="text-secondary mb-0">Halo, <?= e(current_user()['nama']) ?></p>
    </div>
    <a href="profil.php" class="btn btn-outline-secondary btn-sm">Profil saya</a>
  </div>
 
  <?php if (!$bidangId): ?>
    <div class="alert alert-warning">Bidang hukummu belum diisi. Hubungi admin atau lengkapi di <a href="profil.php">Profil saya</a>.</div>
  <?php endif; ?>
 
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="card-rh p-3 text-center">
      <div class="fs-2 fw-bold"><?= $jmlMasuk ?></div><div class="text-secondary">Pertanyaan masuk</div></div></div>
    <div class="col-md-3 col-6"><div class="card-rh p-3 text-center">
      <div class="fs-2 fw-bold"><?= $jmlDijawab ?></div><div class="text-secondary">Sudah saya jawab</div></div></div>
    <div class="col-md-3 col-6"><div class="card-rh p-3 text-center">
      <div class="fs-2 fw-bold"><?= $jmlSelesai ?></div><div class="text-secondary">Selesai</div></div></div>
    <div class="col-md-3 col-6"><div class="card-rh p-3 text-center">
      <?php if ($ratingRata !== null): ?>
        <div class="fs-2 fw-bold">&#9733; <?= e($ratingRata) ?></div>
        <div class="text-secondary">Rating (<?= $jmlUlasan ?> ulasan)</div>
      <?php else: ?>
        <div class="fs-2 fw-bold">-</div>
        <div class="text-secondary">Belum ada ulasan</div>
      <?php endif; ?>
    </div></div>
  </div>
 
  <h4 class="mb-2">Pertanyaan masuk</h4>
  <div class="table-responsive mb-4">
    <table class="table table-hover align-middle">
      <thead><tr><th>Judul</th><th>Klien</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($masuk as $r): ?>
        <tr>
          <td><?= e($r['judul']) ?></td>
          <td><?= e($r['klien']) ?></td>
          <td><span class="badge text-bg-<?= e($badge[$r['status']] ?? 'secondary') ?>"><?= e($r['status']) ?></span></td>
          <td class="text-end"><a class="btn btn-sm btn-primary" href="konsultasi_detail.php?id=<?= (int) $r['id'] ?>">Buka</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$masuk): ?><tr><td colspan="4" class="text-secondary">Belum ada pertanyaan masuk.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
 
  <h4 class="mb-2">Konsultasi saya</h4>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th>Judul</th><th>Klien</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($saya as $r): ?>
        <tr>
          <td><?= e($r['judul']) ?></td>
          <td><?= e($r['klien']) ?></td>
          <td><span class="badge text-bg-<?= e($badge[$r['status']] ?? 'secondary') ?>"><?= e($r['status']) ?></span></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="konsultasi_detail.php?id=<?= (int) $r['id'] ?>">Buka</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$saya): ?><tr><td colspan="4" class="text-secondary">Belum ada konsultasi yang kamu tangani.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
 