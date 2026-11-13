<?php
// BERANDA: desain ulang oleh Rafi, berdasarkan struktur halaman dari Nafiza.
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/komponen_publik.php';

$pdo  = db();
$user = current_user();

// Bidang hukum + jumlah pengacara aktif di tiap bidang
$bidang = $pdo->query(
    "SELECT b.id, b.nama, b.deskripsi,
            (SELECT COUNT(*) FROM profil_pengacara p
               JOIN users u ON u.id = p.user_id
              WHERE p.bidang_id = b.id AND u.role = 'pengacara' AND u.status = 'aktif') AS jumlah
     FROM bidang_hukum b
     ORDER BY b.id"
)->fetchAll();

// Semua pengacara aktif beserta rating rata-ratanya (terbaik di urutan pertama)
$semua = $pdo->query(
    "SELECT u.id, u.nama, p.bidang_id, b.nama AS bidang, p.bio, p.pengalaman_tahun, p.kota,
            COALESCE(r.rata, 0) AS rating, COALESCE(r.jumlah, 0) AS jumlah_ulasan
     FROM users u
     JOIN profil_pengacara p ON p.user_id = u.id
     JOIN bidang_hukum b ON b.id = p.bidang_id
     LEFT JOIN (SELECT pengacara_id, AVG(rating) AS rata, COUNT(*) AS jumlah
                  FROM ulasan GROUP BY pengacara_id) r ON r.pengacara_id = u.id
     WHERE u.role = 'pengacara' AND u.status = 'aktif'
     ORDER BY rating DESC, jumlah_ulasan DESC, p.pengalaman_tahun DESC, u.nama ASC"
)->fetchAll();

// Pengacara unggulan: satu yang terbaik di setiap bidang
$unggulan = [];
foreach ($semua as $p) {
    if (!isset($unggulan[$p['bidang_id']])) {
        $unggulan[$p['bidang_id']] = $p;
    }
}
$unggulan = array_slice(array_values($unggulan), 0, 6);

// Angka statistik
$totalPengacara  = count($semua);
$totalBidang     = count($bidang);
$totalKonsultasi = (int) $pdo->query("SELECT COUNT(*) FROM konsultasi WHERE status IN ('dijawab', 'selesai')")->fetchColumn();
$ratingRata      = (float) $pdo->query('SELECT AVG(rating) FROM ulasan')->fetchColumn();

// Testimoni klien (ulasan bagus yang ada komentarnya)
$testimoni = $pdo->query(
    "SELECT ul.rating, ul.komentar, u.nama AS klien, b.nama AS bidang
     FROM ulasan ul
     JOIN users u ON u.id = ul.klien_id
     JOIN konsultasi k ON k.id = ul.konsultasi_id
     JOIN bidang_hukum b ON b.id = k.bidang_id
     WHERE ul.rating >= 4 AND ul.komentar IS NOT NULL AND ul.komentar <> ''
     ORDER BY ul.id DESC
     LIMIT 3"
)->fetchAll();

// Artikel terbaru (tampil hanya kalau sudah ada artikel terbit)
$artikel = $pdo->query(
    "SELECT id, judul, slug, ringkasan, created_at FROM artikel
     WHERE status = 'terbit' ORDER BY created_at DESC, id DESC LIMIT 3"
)->fetchAll();
$adaHalArtikel = is_file(__DIR__ . '/artikel.php');
$adaDetail     = is_file(__DIR__ . '/artikel_detail.php');

// Tombol ajakan sesuai status login
if (!$user) {
    $ctaUrl = url('auth/register.php');
    $ctaTeks = 'Daftar Gratis';
} elseif ($user['role'] === 'klien') {
    $ctaUrl = url('klien/konsultasi_baru.php');
    $ctaTeks = 'Ajukan Konsultasi';
} else {
    $ctaUrl = url(dashboard_path($user['role']));
    $ctaTeks = 'Buka Dashboard';
}

$judul = 'Konsultasi Hukum Online';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/beranda.css')) ?>">

<!-- ============ HERO ============ -->
<section class="bn-hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="bn-eyebrow"><i class="bi bi-shield-check"></i> Konsultasi hukum online yang terpercaya</span>
        <h1 class="mt-3 mb-3">Temukan pengacara tepercaya untuk <span class="emas">masalah hukummu</span></h1>
        <p class="lead mb-4">
          Ceritakan masalahmu, lalu dapatkan jawaban dari pengacara terverifikasi di enam bidang hukum.
          Mudah, jelas, dan terjaga kerahasiaannya.
        </p>

        <form class="bn-cari" method="get" action="<?= e(url('pengacara.php')) ?>">
          <div class="row g-2 align-items-center">
            <div class="col-md-4">
              <label class="visually-hidden" for="cari-bidang">Bidang hukum</label>
              <select class="form-select" id="cari-bidang" name="bidang">
                <option value="">Semua bidang</option>
                <?php foreach ($bidang as $b): ?>
                  <option value="<?= (int) $b['id'] ?>"><?= e($b['nama']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="visually-hidden" for="cari-q">Nama atau kota</label>
              <input class="form-control" id="cari-q" name="q" type="search" placeholder="Nama pengacara atau kota">
            </div>
            <div class="col-md-3 d-grid">
              <button class="btn btn-gold" type="submit"><i class="bi bi-search"></i> Cari</button>
            </div>
          </div>
        </form>

        <ul class="bn-jaminan">
          <li><i class="bi bi-patch-check-fill"></i> Pengacara terverifikasi</li>
          <li><i class="bi bi-lock-fill"></i> Percakapan terjaga</li>
          <li><i class="bi bi-chat-dots-fill"></i> Konsultasi kapan saja</li>
        </ul>
      </div>

      <div class="col-lg-5 d-none d-lg-block">
        <div class="bn-chat" aria-hidden="true">
          <span class="bn-chat-label">Contoh percakapan</span>
          <div class="bn-chat-kepala">
            <div class="pc-avatar pc-avatar-sm av-1">DA</div>
            <div class="lh-sm">
              <strong>Pengacara Hukum Pidana</strong><br>
              <small><i class="bi bi-patch-check-fill"></i> Terverifikasi</small>
            </div>
            <span class="badge-status badge-dijawab ms-auto">Dijawab</span>
          </div>
          <div class="bn-bubble klien">Saya menerima surat panggilan sebagai saksi. Apa yang perlu saya siapkan?</div>
          <div class="bn-bubble pengacara">Anda berhak didampingi. Bawa identitas, catat kronologi singkat, dan jangan menandatangani berita acara sebelum dibaca.</div>
          <div class="bn-chat-kaki"><i class="bi bi-lock"></i> Hanya bisa dibaca Anda dan pengacara</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ STATISTIK ============ -->
<section class="container bn-stats">
  <div class="bn-stats-kartu">
    <div class="row g-0 text-center">
      <div class="col-6 col-md-3 bn-stat">
        <div class="angka"><?= $totalPengacara ?></div>
        <div class="label">Pengacara terverifikasi</div>
      </div>
      <div class="col-6 col-md-3 bn-stat">
        <div class="angka"><?= $totalBidang ?></div>
        <div class="label">Bidang hukum</div>
      </div>
      <div class="col-6 col-md-3 bn-stat">
        <div class="angka"><?= $totalKonsultasi ?></div>
        <div class="label">Konsultasi terjawab</div>
      </div>
      <div class="col-6 col-md-3 bn-stat">
        <div class="angka"><?= $ratingRata > 0 ? e(angka_desimal($ratingRata)) : '-' ?><?php if ($ratingRata > 0): ?><i class="bi bi-star-fill"></i><?php endif; ?></div>
        <div class="label">Rating rata-rata klien</div>
      </div>
    </div>
  </div>
</section>

<!-- ============ BIDANG HUKUM ============ -->
<section class="bn-section">
  <div class="container">
    <div class="bn-kepala tengah">
      <span class="bn-kicker">Bidang hukum</span>
      <h2 class="bn-judul">Apa masalah hukum yang kamu hadapi?</h2>
      <p class="bn-sub">Pilih bidangnya, lalu temukan pengacara yang memang menekuni persoalan tersebut.</p>
    </div>
    <div class="row g-4">
      <?php foreach ($bidang as $b): ?>
        <div class="col-md-6 col-lg-4">
          <a class="bn-bidang card-rh hoverable" href="<?= e(url('pengacara.php?bidang=' . (int) $b['id'])) ?>">
            <span class="bn-ikon"><i class="bi <?= e(ikon_bidang($b['nama'])) ?>"></i></span>
            <h3><?= e($b['nama']) ?></h3>
            <p><?= e($b['deskripsi'] ?? '') ?></p>
            <span class="bn-bidang-kaki">
              <span><?= (int) $b['jumlah'] ?> pengacara</span>
              <i class="bi bi-arrow-right"></i>
            </span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ CARA KERJA ============ -->
<section class="bn-section bn-putih">
  <div class="container">
    <div class="bn-kepala tengah">
      <span class="bn-kicker">Cara kerja</span>
      <h2 class="bn-judul">Tiga langkah menuju jawaban</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="bn-langkah">
          <span class="bn-nomor">1</span>
          <h3>Pilih bidang hukum</h3>
          <p>Tentukan bidang yang paling dekat dengan masalahmu, atau cari pengacara berdasarkan nama dan kota.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="bn-langkah">
          <span class="bn-nomor">2</span>
          <h3>Ceritakan masalahmu</h3>
          <p>Tulis pertanyaan lewat formulir konsultasi. Semakin jelas ceritamu, semakin tepat jawabannya.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="bn-langkah">
          <span class="bn-nomor">3</span>
          <h3>Terima jawaban pengacara</h3>
          <p>Pengacara menjawab di percakapan yang terjaga. Lanjutkan berdiskusi, lalu beri ulasan setelah selesai.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ PENGACARA UNGGULAN ============ -->
<section class="bn-section">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 bn-kepala">
      <div>
        <span class="bn-kicker">Pengacara unggulan</span>
        <h2 class="bn-judul mb-0">Terbaik di setiap bidang</h2>
      </div>
      <a class="btn btn-outline-navy" href="<?= e(url('pengacara.php')) ?>">
        Lihat semua <?= $totalPengacara ?> pengacara <i class="bi bi-arrow-right"></i>
      </a>
    </div>
    <?php if (!$unggulan): ?>
      <div class="card-rh p-4 text-center text-secondary">Belum ada pengacara terdaftar.</div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($unggulan as $p): ?>
          <div class="col-md-6 col-lg-4"><?= kartu_pengacara($p) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ TESTIMONI ============ -->
<?php if ($testimoni): ?>
<section class="bn-section bn-putih">
  <div class="container">
    <div class="bn-kepala tengah">
      <span class="bn-kicker">Testimoni</span>
      <h2 class="bn-judul">Kata mereka yang sudah berkonsultasi</h2>
    </div>
    <div class="row g-4">
      <?php foreach ($testimoni as $t): ?>
        <div class="col-md-4">
          <figure class="bn-testi">
            <i class="bi bi-quote bn-kutip"></i>
            <div class="bintang mb-2"><?= bintang((float) $t['rating']) ?></div>
            <blockquote>&ldquo;<?= e($t['komentar']) ?>&rdquo;</blockquote>
            <figcaption>
              <span class="pc-avatar pc-avatar-sm <?= e(kelas_avatar($t['klien'])) ?>"><?= e(inisial($t['klien'])) ?></span>
              <span><strong><?= e(nama_singkat($t['klien'])) ?></strong><br><small><?= e($t['bidang']) ?></small></span>
            </figcaption>
          </figure>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ ARTIKEL ============ -->
<?php if ($artikel): ?>
<section class="bn-section">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 bn-kepala">
      <div>
        <span class="bn-kicker">Artikel hukum</span>
        <h2 class="bn-judul mb-0">Bacaan terbaru</h2>
      </div>
      <?php if ($adaHalArtikel): ?>
        <a class="btn btn-outline-navy" href="<?= e(url('artikel.php')) ?>">Semua artikel <i class="bi bi-arrow-right"></i></a>
      <?php endif; ?>
    </div>
    <div class="row g-4">
      <?php foreach ($artikel as $a): ?>
        <?php $tautan = $adaDetail ? url('artikel_detail.php?slug=' . urlencode($a['slug'])) : ($adaHalArtikel ? url('artikel.php') : '#'); ?>
        <div class="col-md-4">
          <a class="bn-artikel card-rh hoverable" href="<?= e($tautan) ?>">
            <small class="text-secondary"><?= e(tgl($a['created_at'])) ?></small>
            <h3><?= e($a['judul']) ?></h3>
            <p><?= e($a['ringkasan'] ?? '') ?></p>
            <span class="bn-bidang-kaki"><span>Baca selengkapnya</span><i class="bi bi-arrow-right"></i></span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ AJAKAN ============ -->
<section class="container pb-5 mb-2">
  <div class="bn-cta">
    <div>
      <h2>Siap berkonsultasi dengan pengacara?</h2>
      <p class="mb-0">Daftar gratis dan ajukan pertanyaan pertamamu hari ini.</p>
    </div>
    <a class="btn btn-gold btn-lg" href="<?= e($ctaUrl) ?>"><?= e($ctaTeks) ?> <i class="bi bi-arrow-right"></i></a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
