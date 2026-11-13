<?php
// MODUL ADMIN: ringkasan sistem. Ditulis ulang oleh Rafi (versi sebelumnya memakai $pdo yang tidak ada).
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_role('admin');

$pdo = db();

// Jumlah akun per peran dan status
$akun = [];
foreach ($pdo->query('SELECT role, status, COUNT(*) AS n FROM users GROUP BY role, status')->fetchAll() as $r) {
    $akun[$r['role']][$r['status']] = (int) $r['n'];
}
$klienAktif     = $akun['klien']['aktif'] ?? 0;
$pengacaraAktif = $akun['pengacara']['aktif'] ?? 0;
$menunggu       = $akun['pengacara']['menunggu'] ?? 0;

// Konsultasi per status
$status = ['menunggu' => 0, 'dijawab' => 0, 'selesai' => 0, 'ditutup' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM konsultasi GROUP BY status')->fetchAll() as $r) {
    $status[$r['status']] = (int) $r['n'];
}
$totalKonsultasi = array_sum($status);

// Rating, artikel, FAQ
$ulasan = $pdo->query('SELECT COUNT(*) AS jml, AVG(rating) AS rata FROM ulasan')->fetch();
$artikel = ['terbit' => 0, 'draft' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM artikel GROUP BY status')->fetchAll() as $r) {
    $artikel[$r['status']] = (int) $r['n'];
}
$jumlahFaq = (int) $pdo->query('SELECT COUNT(*) FROM faq')->fetchColumn();

// Konsultasi per bidang
$perBidang = $pdo->query(
    'SELECT b.nama, COUNT(k.id) AS n
     FROM bidang_hukum b LEFT JOIN konsultasi k ON k.bidang_id = b.id
     GROUP BY b.id, b.nama ORDER BY n DESC, b.id'
)->fetchAll();
$maksBidang = max(1, (int) ($perBidang[0]['n'] ?? 1));

// Pengacara menunggu verifikasi (5 teratas)
$calon = $pdo->query(
    "SELECT u.id, u.nama, u.created_at, p.no_izin, b.nama AS bidang
     FROM users u
     LEFT JOIN profil_pengacara p ON p.user_id = u.id
     LEFT JOIN bidang_hukum b ON b.id = p.bidang_id
     WHERE u.role = 'pengacara' AND u.status = 'menunggu'
     ORDER BY u.created_at DESC, u.id DESC LIMIT 5"
)->fetchAll();

// Konsultasi terbaru
$terbaru = $pdo->query(
    'SELECT k.id, k.judul, k.status, k.created_at, uk.nama AS klien, b.nama AS bidang
     FROM konsultasi k
     JOIN users uk ON uk.id = k.klien_id
     JOIN bidang_hukum b ON b.id = k.bidang_id
     ORDER BY k.created_at DESC, k.id DESC LIMIT 6'
)->fetchAll();

$judul = 'Dashboard Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container ad-wrap">
  <?php admin_kepala('dashboard', 'Halo, ' . explode(' ', current_user()['nama'])[0] . '!', 'Ringkasan aktivitas Ruang Hukum hari ini.'); ?>

  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="ad-stat"><span class="ikon ik-0"><i class="bi bi-people"></i></span>
        <div><div class="angka"><?= $klienAktif ?></div><div class="label">Klien aktif</div></div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="ad-stat"><span class="ikon ik-1"><i class="bi bi-briefcase"></i></span>
        <div><div class="angka"><?= $pengacaraAktif ?></div><div class="label">Pengacara aktif</div>
          <?php if ($menunggu): ?><a class="sub" href="<?= e(url('admin/verifikasi.php')) ?>"><?= $menunggu ?> menunggu verifikasi</a><?php endif; ?></div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="ad-stat"><span class="ikon ik-2"><i class="bi bi-chat-square-text"></i></span>
        <div><div class="angka"><?= $totalKonsultasi ?></div><div class="label">Total konsultasi</div>
          <?php if ($status['menunggu']): ?><a class="sub" href="<?= e(url('admin/konsultasi.php?status=menunggu')) ?>"><?= $status['menunggu'] ?> belum dijawab</a><?php endif; ?></div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="ad-stat"><span class="ikon ik-3"><i class="bi bi-star-fill"></i></span>
        <div><div class="angka"><?= $ulasan['jml'] ? number_format((float) $ulasan['rata'], 1, ',', '') : '-' ?></div><div class="label">Rating rata-rata (<?= (int) $ulasan['jml'] ?> ulasan)</div></div></div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="ad-card mb-4">
        <div class="ad-card-h"><h2><i class="bi bi-patch-check text-warning"></i> Perlu tindakan</h2>
          <a class="btn btn-outline-secondary btn-ad" href="<?= e(url('admin/verifikasi.php')) ?>">Buka verifikasi</a></div>
        <?php if (!$calon): ?>
          <div class="ad-kosong"><i class="bi bi-check2-circle"></i>Tidak ada pengacara yang menunggu verifikasi.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table ad-table">
              <thead><tr><th>Calon pengacara</th><th>Bidang</th><th>No. izin</th><th>Mendaftar</th></tr></thead>
              <tbody>
              <?php foreach ($calon as $c): ?>
                <tr>
                  <td><strong><?= e($c['nama']) ?></strong></td>
                  <td><?= e($c['bidang'] ?? '-') ?></td>
                  <td><?= e($c['no_izin'] ?? '-') ?></td>
                  <td><?= e(tgl($c['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="ad-card">
        <div class="ad-card-h"><h2><i class="bi bi-clock-history text-secondary"></i> Konsultasi terbaru</h2>
          <a class="btn btn-outline-secondary btn-ad" href="<?= e(url('admin/konsultasi.php')) ?>">Lihat semua</a></div>
        <?php if (!$terbaru): ?>
          <div class="ad-kosong"><i class="bi bi-chat-square"></i>Belum ada konsultasi.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table ad-table">
              <thead><tr><th>Judul</th><th>Klien</th><th>Bidang</th><th>Status</th><th>Tanggal</th></tr></thead>
              <tbody>
              <?php foreach ($terbaru as $k): ?>
                <tr>
                  <td class="ad-potong"><a class="text-decoration-none fw-semibold" href="<?= e(url('admin/konsultasi.php?id=' . (int) $k['id'])) ?>"><?= e($k['judul']) ?></a></td>
                  <td><?= e($k['klien']) ?></td>
                  <td><?= e($k['bidang']) ?></td>
                  <td><?= admin_pil_konsultasi($k['status']) ?></td>
                  <td><?= e(tgl($k['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="ad-card mb-4">
        <div class="ad-card-h"><h3>Status konsultasi</h3></div>
        <div class="ad-card-b">
          <?php foreach ($status as $nama => $n): ?>
            <div class="ad-baris">
              <div class="atas"><span><?= admin_pil_konsultasi($nama) ?></span><b><?= $n ?></b></div>
              <div class="ad-bar"><span style="width: <?= $totalKonsultasi ? round($n / $totalKonsultasi * 100) : 0 ?>%"></span></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="ad-card mb-4">
        <div class="ad-card-h"><h3>Konsultasi per bidang</h3></div>
        <div class="ad-card-b">
          <?php foreach ($perBidang as $b): ?>
            <div class="ad-baris">
              <div class="atas"><span><?= e($b['nama']) ?></span><b><?= (int) $b['n'] ?></b></div>
              <div class="ad-bar emas"><span style="width: <?= round((int) $b['n'] / $maksBidang * 100) ?>%"></span></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="ad-card">
        <div class="ad-card-h"><h3>Konten</h3></div>
        <div class="ad-card-b">
          <div class="d-flex justify-content-between mb-2"><span>Artikel terbit</span><b><?= $artikel['terbit'] ?></b></div>
          <div class="d-flex justify-content-between mb-2"><span>Artikel draft</span><b><?= $artikel['draft'] ?></b></div>
          <div class="d-flex justify-content-between mb-3"><span>Pertanyaan FAQ</span><b><?= $jumlahFaq ?></b></div>
          <div class="d-flex gap-2">
            <a class="btn btn-navy btn-ad flex-fill" href="<?= e(url('admin/artikel_form.php')) ?>"><i class="bi bi-plus-lg"></i> Artikel</a>
            <a class="btn btn-outline-secondary btn-ad flex-fill" href="<?= e(url('admin/faq.php')) ?>">Kelola FAQ</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
