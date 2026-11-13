  <?php
  require_once __DIR__ . '/config/database.php';
  include_once __DIR__ . '/components/header.php';

  // ===== PENGATURAN KAMPUS =====
  $namaPortal = 'KabarSi FISIP';
  $namaKampus = 'Universitas Lampung';
  $singkatan  = 'FISIP';

  // ===== DATA BERITA =====
  $stmt = $pdo->prepare("SELECT b.*, k.nama_kategori
          FROM berita b
          LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
          ORDER BY b.tanggal_publikasi DESC
          LIMIT 7");
  $stmt->execute();
  $beritaList   = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $heroBerita   = $beritaList[0] ?? null;
  $daftarBerita = array_slice($beritaList, 1);

  // Kategori unik untuk filter berita
  $kategoriBerita = [];
  foreach ($daftarBerita as $b) { $kategoriBerita[$b['nama_kategori'] ?? 'Umum'] = true; }
  $kategoriBerita = array_keys($kategoriBerita);

  // ===== DATA ASPIRASI =====
$stmt = $pdo->prepare("SELECT a.*, k.nama_kategori, u.nama_lengkap
        FROM aspirasi a
        LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
        LEFT JOIN users u ON a.id_user = u.id_user
        WHERE a.status = 'publikasi'
        ORDER BY a.tanggal_kirim DESC
        LIMIT 6");
$stmt->execute();
$aspirasiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

  function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
  $ticker = ['Transparansi Informasi', 'Suara Aspirasi Mahasiswa', 'Respon Terintegrasi', 'Layanan Publikasi Resmi', 'FISIP Universitas Lampung'];
  ?>

<style>
:root { 
  --ink: #141414; 
  --paper: #F6F2E9; 
  --card: #FFFDF8; 
  --or: #FF5A1F; 
  --or-soft: #FFD9C7; 
  --muted: #6B655B; 
  --border-line: #141414;
  --stone-200: #e7e5e4;
  --stone-300: #d6d3d1;
  --stone-400: #a8a29e;
  --stone-500: #78716c;
  --stone-600: #57534e;
  --stone-700: #44403c;
  --slate-500: #64748b;
  --slate-700: #334155;
  --amber-100: #fef3c7;
}

  .kb * { box-sizing: border-box; }
  .kb { 
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
    color: var(--ink); 
    background: var(--paper); 
    overflow-x: hidden; 
  }

  .kb a { color: inherit; text-decoration: none; }
  .kb a:focus-visible, .kb button:focus-visible { outline: 2px solid var(--or); outline-offset: 2px; }

.clamp2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.clamp3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.hide { display: none !important; }

/* Container Utama */
.ed-container {
  max-width: 72rem;
  margin-left: auto;
  margin-right: auto;
  padding-left: 1rem;
  padding-right: 1rem;
}

/* Hero Section Layout */
.hero-sec {
  padding-top: 3.5rem;
  padding-bottom: 4rem;
  display: grid;
  grid-template-columns: 1fr;
  gap: 3rem;
  align-items: center;
}
@media (min-width: 768px) {
  .hero-sec { padding-top: 5rem; }
}
@media (min-width: 1024px) {
  .hero-sec { grid-template-columns: repeat(12, minmax(0, 1fr)); }
  .hero-left { grid-column: span 7 / span 7; }
  .hero-right { grid-column: span 5 / span 5; }
}

.hero-kicker {
  font-size: 0.75rem;
  line-height: 1rem;
  text-transform: uppercase;
  font-weight: 700;
  color: var(--slate-500);
  margin-bottom: 0.75rem;
  letter-spacing: 0.05em;
}
.hero-title {
  font-size: clamp(2.8rem, 7vw, 5.2rem);
  line-height: 1.05;
  font-weight: 800;
  letter-spacing: -0.03em;
  margin: 0;
}
.hero-title span { color: var(--or); }
.hero-desc {
  margin-top: 1.5rem;
  margin-bottom: 0;
  max-width: 28rem;
  color: var(--slate-700);
  line-height: 1.625;
  font-size: 1rem;
  font-weight: 400;
}
.hero-actions {
  margin-top: 2rem;
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

/* Clean Border Card */
.ed-card {
  background: var(--card);
  border: 1px solid var(--border-line);
  border-radius: 0.75rem;
  transition: border-color 0.2s ease, transform 0.2s ease;
}
.ed-card:hover { border-color: var(--or); }
.ed-card:hover .ttl-card { color: var(--or); }

.hero-card {
  display: block;
  padding: 1.75rem;
}
.hero-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  border-bottom: 1px solid var(--stone-200);
  padding-bottom: 0.75rem;
}
.hero-card-title {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.375;
  margin: 0 0 0.75rem 0;
  transition: color 0.15s ease;
}
.hero-card-excerpt {
  font-size: 0.75rem;
  color: var(--stone-600);
  line-height: 1.625;
  margin: 0 0 1.5rem 0;
}
.hero-card-foot {
  font-size: 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 0.75rem;
  border-top: 1px solid var(--stone-200);
}

  /* Badge Kategori */
  .ed-tag {
    display: inline-block;
    font-weight: 700;
    font-size: 0.68rem;
    padding: 0.2rem 0.55rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: 1px solid var(--border-line);
    background: var(--card);
    color: var(--ink);
    border-radius: 4px;
  }
  .ed-tag-or {
    background: var(--or);
    color: #ffffff;
    border-color: var(--or);
  }

/* Tombol Standard */
.btn-ed {
  display: inline-block;
  font-weight: 700;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  padding: 0.75rem 1.4rem;
  border: 1px solid var(--border-line);
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s ease;
}
.btn-ed-or { background: var(--or); color: #ffffff !important; border-color: var(--or); }
.btn-ed-or:hover { background: var(--ink); border-color: var(--ink); }
.btn-ed-light { background: var(--card); color: var(--ink) !important; }
.btn-ed-light:hover { background: var(--or-soft); }
.btn-ed-ink { background: var(--ink); color: var(--paper) !important; }
.btn-ed-ink:hover { background: var(--or); border-color: var(--or); }

/* Ticker running line */
.ticker { border-top: 1px solid var(--border-line); border-bottom: 1px solid var(--border-line); background: var(--card); color: var(--ink); overflow: hidden; white-space: nowrap; }
.ticker-track { display: inline-flex; gap: 2.5rem; padding: 0.75rem 0; animation: marq 30s linear infinite; }
.ticker-track span { font-weight: 600; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 2.5rem; text-transform: uppercase; letter-spacing: 0.04em; }
.ticker-track span::after { content: "•"; color: var(--or); }
@keyframes marq { to { transform: translateX(-50%); } }

/* Main Content Area */
.main-wrap {
  padding-top: 4rem;
  padding-bottom: 4rem;
  display: flex;
  flex-direction: column;
  gap: 5rem;
}
.sec-anchor { scroll-margin-top: 1.5rem; }

/* Section Header */
.sec-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
  border-bottom: 1px solid var(--stone-300);
  padding-bottom: 1rem;
}
.sec-title {
  font-size: clamp(1.8rem, 4vw, 2.6rem);
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -0.02em;
  margin: 0;
}
.sec-title span {
  font-weight: 400;
  color: var(--muted);
}

/* Filter Chips */
.chip-group {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.chip {
  font-weight: 700;
  font-size: 0.7rem;
  text-transform: uppercase;
  border: 1px solid var(--border-line);
  background: var(--card);
  padding: 0.4rem 0.85rem;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.15s;
}
.chip:hover { background: var(--or-soft); }
.chip[aria-pressed="true"] { background: var(--ink); color: var(--paper); }

/* Kartu Kosong */
.empty-box {
  padding: 2.5rem;
  text-align: center;
  font-size: 0.75rem;
  color: var(--stone-500);
}
.empty-box-sm { padding: 1.5rem; }

/* Grid Berita & Aspirasi */
.grid-berita {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
}
@media (min-width: 768px) {
  .grid-berita { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

.grid-aspirasi {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
}
@media (min-width: 768px) {
  .grid-aspirasi { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

/* Kartu Berita */
.card-item {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.card-berita-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--stone-200);
  padding-bottom: 0.5rem;
}
.card-berita-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--stone-400);
}
.card-item-title {
  font-size: 1.125rem;
  font-weight: 700;
  line-height: 1.375;
  margin: 0;
  transition: color 0.15s ease;
}
.card-item-body {
  font-size: 0.75rem;
  color: var(--stone-600);
  line-height: 1.625;
  margin: 0;
}
.card-berita-foot {
  font-size: 0.75rem;
  display: flex;
  justify-content: space-between;
  margin-top: auto;
  padding-top: 0.75rem;
  border-top: 1px solid var(--stone-200);
}

/* Kartu Aspirasi */
.card-asp-top {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  border-bottom: 1px solid var(--stone-200);
  padding-bottom: 0.75rem;
}
.card-asp-author {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--stone-700);
}
.card-asp-date {
  font-size: 0.75rem;
  margin-left: auto;
  color: var(--stone-500);
}
.card-asp-main { flex: 1 1 0%; }
.card-asp-title {
  font-size: 1.125rem;
  font-weight: 700;
  line-height: 1.375;
  margin: 0 0 0.5rem 0;
}
.card-asp-foot {
  font-size: 0.75rem;
  display: flex;
  justify-content: flex-end;
  padding-top: 0.75rem;
  border-top: 1px solid var(--stone-200);
}

.more-btn { 
  background: none; 
  border: 0; 
  padding: 0; 
  margin-top: 0.5rem;
  font-weight: 700; 
  font-size: 0.75rem; 
  text-decoration: underline; 
  cursor: pointer; 
  color: var(--ink); 
}

/* Typography Helpers */
.txt-muted { font-size: 0.75rem; color: var(--stone-500); font-weight: 500; }
.txt-orange-bold { font-weight: 700; color: var(--or); }

/* CTA Banner */
.cta-banner {
  background: var(--or);
  border-color: var(--or);
  padding: 2rem;
}
@media (min-width: 768px) {
  .cta-banner { padding: 3rem; }
}
.cta-title {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1.25;
  max-width: 42rem;
  color: #ffffff;
  margin: 0;
}
@media (min-width: 768px) {
  .cta-title { font-size: 2.25rem; }
}
.cta-desc {
  margin-top: 0.75rem;
  margin-bottom: 0;
  max-width: 32rem;
  color: var(--amber-100);
  font-size: 0.875rem;
  line-height: 1.625;
}
.cta-actions {
  margin-top: 2rem;
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}
</style>

  <div class="kb">

<!-- ===== HERO SECTION ===== -->
<section class="ed-container hero-sec">
  <div class="hero-left">
    <div class="hero-kicker">Portal Resmi FISIP Unila</div>
    <h1 class="hero-title">
      Suara <span>Kritis.</span><br>
      Kabar Terbuka.
    </h1>
    <p class="hero-desc">
      Wadah resmi civitas akademika untuk menyampaikan aspirasi dan mengakses publikasi berita kampus secara transparan dan terintegrasi.
    </p>
    <div class="hero-actions">
      <a href="user/dashboard.php" class="btn-ed btn-ed-or">Sampaikan Aspirasi &rarr;</a>
      <a href="#aspirasi" class="btn-ed btn-ed-light">Pantau Isu Kampus</a>
    </div>
  </div>

  <div class="hero-right">
    <?php if ($heroBerita): ?>
      <a href="berita_detail.php?id=<?= (int)$heroBerita['id_berita'] ?>" class="ed-card hero-card">
        <div class="hero-card-top">
          <span class="ed-tag ed-tag-or"><?= e($heroBerita['nama_kategori'] ?? 'Umum') ?></span>
          <span class="txt-muted"><?= date('d M Y', strtotime($heroBerita['tanggal_publikasi'])) ?></span>
        </div>
        <h2 class="ttl-card hero-card-title clamp3"><?= e($heroBerita['judul_berita']) ?></h2>
        <p class="hero-card-excerpt clamp3"><?= e(strip_tags($heroBerita['isi_berita'])) ?></p>
        <div class="hero-card-foot">
          <span class="txt-muted">Berita Terbaru</span>
          <b class="txt-orange-bold">Baca Selengkapnya &rarr;</b>
        </div>
      </a>
    <?php else: ?>
      <div class="ed-card empty-box empty-box-sm">Belum ada berita yang diterbitkan.</div>
    <?php endif; ?>
  </div>
</section>

  <!-- ===== RUNNING TICKER ===== -->
  <div class="ticker" aria-hidden="true">
    <div class="ticker-track">
      <?php for ($r = 0; $r < 4; $r++) foreach ($ticker as $t): ?><span><?= e($t) ?></span><?php endforeach; ?>
    </div>
  </div>

<main class="ed-container main-wrap">

  <!-- ===== BERITA ===== -->
  <section id="berita" class="sec-anchor">
    <div class="sec-head">
      <div>
        <h2 class="sec-title">Kabar Kilat <span><?= e($singkatan) ?></span></h2>
      </div>
      <?php if (count($kategoriBerita) > 1): ?>
        <div class="chip-group" role="group" aria-label="Filter kategori berita" data-group="berita">
          <button class="chip" data-val="all" aria-pressed="true">Semua Kategori</button>
          <?php foreach ($kategoriBerita as $kat): ?>
            <button class="chip" data-val="<?= e($kat) ?>" aria-pressed="false"><?= e($kat) ?></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if (empty($daftarBerita)): ?>
      <div class="ed-card empty-box">Belum ada publikasi berita lainnya.</div>
    <?php else: ?>
      <div class="grid-berita">
        <?php foreach ($daftarBerita as $berita): ?>
          <a href="berita_detail.php?id=<?= (int)$berita['id_berita'] ?>"
            class="ed-card card-item item" data-group="berita" data-val="<?= e($berita['nama_kategori'] ?? 'Umum') ?>">
            <div class="card-berita-top">
              <span class="card-berita-label">BERITA</span>
              <span class="ed-tag"><?= e($berita['nama_kategori'] ?? 'Umum') ?></span>
            </div>
            <h3 class="ttl-card card-item-title clamp2"><?= e($berita['judul_berita']) ?></h3>
            <p class="card-item-body clamp3"><?= e(strip_tags($berita['isi_berita'])) ?></p>
            <div class="card-berita-foot">
              <span class="txt-muted"><?= date('d/m/Y', strtotime($berita['tanggal_publikasi'])) ?></span>
              <span class="txt-orange-bold">&rarr;</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ===== ASPIRASI ===== -->
  <section id="aspirasi" class="sec-anchor">
    <div class="sec-head">
      <div>
        <h2 class="sec-title">Daftar Aspirasi <span>Mahasiswa</span></h2>
      </div>
      <a href="user/dashboard.php" class="btn-ed btn-ed-ink">+ Buat Laporan</a>
    </div>

    <?php if (empty($aspirasiList)): ?>
      <div class="ed-card empty-box">Belum ada data aspirasi yang masuk. Silakan sampaikan aspirasi pertama Anda.</div>
    <?php else: ?>
      <div class="grid-aspirasi">
        <?php foreach ($aspirasiList as $aspirasi):
            $status = $aspirasi['status'];
            $isi    = strip_tags($aspirasi['isi_aspirasi']);
            $idAsp  = (int)($aspirasi['id_aspirasi'] ?? 0);
        ?>
          <article class="ed-card card-item item" data-group="asp" data-val="<?= e($status) ?>">
            <div class="card-asp-top">
              <span class="ed-tag"><?= e($aspirasi['nama_kategori'] ?? 'Umum') ?></span>
              <span class="card-asp-author"><?= $aspirasi['is_anonim'] ? '👤 Anonim' : '🎓 ' . e($aspirasi['nama_lengkap'] ?? 'Mahasiswa') ?></span>
              <span class="card-asp-date"><?= date('d/m/Y', strtotime($aspirasi['tanggal_kirim'])) ?></span>
            </div>

            <div class="card-asp-main">
              <h3 class="ttl-card card-asp-title clamp2">
                <a href="aspirasi_detail.php?id=<?= $idAsp ?>"><?= e($aspirasi['judul_aspirasi']) ?></a>
              </h3>
              <p class="card-item-body clamp3 body"><?= e($isi) ?></p>
              <?php if (mb_strlen($isi) > 140): ?>
                  <button type="button" class="more-btn" aria-expanded="false">Tampilkan Selengkapnya</button>
              <?php endif; ?>
            </div>

            <div class="card-asp-foot">
                <a href="aspirasi_detail.php?id=<?= $idAsp ?>" class="txt-orange-bold">Lihat Detail &rarr;</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ===== CTA BANNER ===== -->
  <section class="ed-card cta-banner">
    <h2 class="cta-title">Ingin Menyampaikan Aspirasi atau Pengaduan Kampus?</h2>
    <p class="cta-desc">Sampaikan masukan Anda secara rinci. Laporan dapat disampaikan dengan nama terang maupun secara anonim, dan status tindak lanjutnya dapat dipantau berkala.</p>
    <div class="cta-actions">
      <a href="user/dashboard.php" class="btn-ed btn-ed-ink">Sampaikan Aspirasi Sekarang &rarr;</a>
      <a href="#berita" class="btn-ed btn-ed-light">Pelajari Berita Kampus</a>
    </div>
  </section>

  </main>
  </div>

<script>
(function () {
  // Filter generik kategori
  document.querySelectorAll('[data-group][role="group"]').forEach(function (bar) {
    const group = bar.dataset.group;
    const chips = bar.querySelectorAll('.chip');
    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        const val = chip.dataset.val;
        chips.forEach(function (c) { c.setAttribute('aria-pressed', c === chip); });
        document.querySelectorAll('.item[data-group="' + group + '"]').forEach(function (it) {
          const ok = val === 'all' || it.dataset.val === val;
          it.classList.toggle('hide', !ok);
        });
      });
    });
  });

    // Buka/tutup ringkasan isi aspirasi
    document.querySelectorAll('.more-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const body = btn.parentElement.querySelector('.body');
        const open = !body.classList.toggle('clamp3');
        btn.setAttribute('aria-expanded', open);
        btn.textContent = open ? 'Ringkas' : 'Tampilkan Selengkapnya';
      });
    });
  })();
  </script>

  <?php include_once __DIR__ . '/components/footer.php'; ?>