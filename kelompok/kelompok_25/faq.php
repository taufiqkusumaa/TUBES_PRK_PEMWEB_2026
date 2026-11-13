<?php
// FAQ (publik): pertanyaan dikelompokkan per kategori, bisa dicari. Isinya dikelola admin lewat admin/faq.php.
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/komponen_artikel.php';

$user     = current_user();
$kategori = faq_kategori();
$grup     = [];
foreach (db()->query('SELECT id, pertanyaan, jawaban, urutan FROM faq ORDER BY urutan, id')->fetchAll() as $f) {
    $grup[faq_kat_id((int) $f['urutan'])][] = $f;
}
ksort($grup);
$total = array_sum(array_map('count', $grup));

$judul = 'FAQ';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/halaman_dalam.css')) ?>">

<section class="hd-hero">
  <div class="container">
    <span class="hd-kicker">Pusat bantuan</span>
    <h1>Pertanyaan yang sering diajukan</h1>
    <p>Jawaban singkat seputar cara kerja Ruang Hukum, konsultasi, akun, dan pendaftaran pengacara.</p>
  </div>
</section>

<div class="container hd-isi">
  <div class="hd-panel">
    <label class="form-label" for="fq-cari">Cari pertanyaan</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input class="form-control" id="fq-cari" type="search" placeholder="Contoh: gratis, verifikasi, status, kata sandi" autocomplete="off">
    </div>
    <?php if ($total > 0): ?>
    <div class="hd-chips" id="fq-chips">
      <button type="button" class="hd-chip aktif" data-kat="0">Semua <span class="jml"><?= $total ?></span></button>
      <?php foreach ($grup as $k => $isi): ?>
        <button type="button" class="hd-chip" data-kat="<?= $k ?>"><i class="bi <?= e($kategori[$k][1]) ?>"></i> <?= e($kategori[$k][0]) ?> <span class="jml"><?= count($isi) ?></span></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="mt-4">
    <?php if ($total === 0): ?>
      <div class="hd-kosong"><i class="bi bi-question-circle"></i><p class="mt-2">Belum ada pertanyaan yang ditambahkan.</p></div>
    <?php endif; ?>

    <?php foreach ($grup as $k => $daftar): ?>
      <section class="fq-grup" data-kat="<?= $k ?>">
        <h2><span class="ikon"><i class="bi <?= e($kategori[$k][1]) ?>"></i></span> <?= e($kategori[$k][0]) ?></h2>
        <div class="accordion" id="fq-acc-<?= $k ?>">
          <?php foreach ($daftar as $f): ?>
            <div class="accordion-item fq-item">
              <h3 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fq-<?= (int) $f['id'] ?>" aria-expanded="false">
                  <?= e($f['pertanyaan']) ?>
                </button>
              </h3>
              <div id="fq-<?= (int) $f['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#fq-acc-<?= $k ?>">
                <div class="accordion-body"><?= nl2br(e($f['jawaban'])) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>

    <div class="hd-kosong" id="fq-kosong" hidden>
      <i class="bi bi-search"></i>
      <h3 class="h5 mt-2">Pertanyaan tidak ditemukan</h3>
      <p>Coba kata kunci lain, atau tanyakan langsung kepada pengacara.</p>
    </div>

    <div class="fq-bantuan">
      <div>
        <h3>Belum menemukan jawabannya?</h3>
        <p>Ceritakan masalahmu kepada pengacara terverifikasi. Konsultasi awal tidak dipungut biaya.</p>
      </div>
      <?php if ($user && $user['role'] === 'klien'): ?>
        <a class="btn btn-gold btn-lg" href="<?= e(url('klien/konsultasi_baru.php')) ?>">Ajukan Konsultasi</a>
      <?php elseif (!$user): ?>
        <a class="btn btn-gold btn-lg" href="<?= e(url('auth/register.php')) ?>">Daftar Gratis</a>
      <?php else: ?>
        <a class="btn btn-gold btn-lg" href="<?= e(url('pengacara.php')) ?>">Lihat Pengacara</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function () {
  var cari = document.getElementById('fq-cari');
  var chips = document.querySelectorAll('#fq-chips .hd-chip');
  var kosong = document.getElementById('fq-kosong');
  var kat = '0';
  function terapkan() {
    var kata = cari.value.trim().toLowerCase();
    var tampilSemua = 0;
    document.querySelectorAll('.fq-grup').forEach(function (g) {
      var cocokKat = kat === '0' || g.getAttribute('data-kat') === kat;
      var tampilGrup = 0;
      g.querySelectorAll('.fq-item').forEach(function (it) {
        var cocok = cocokKat && (kata === '' || it.textContent.toLowerCase().indexOf(kata) !== -1);
        it.hidden = !cocok;
        if (cocok) { tampilGrup++; }
      });
      g.hidden = tampilGrup === 0;
      tampilSemua += tampilGrup;
    });
    kosong.hidden = tampilSemua !== 0 || document.querySelectorAll('.fq-item').length === 0;
  }
  cari.addEventListener('input', terapkan);
  chips.forEach(function (c) {
    c.addEventListener('click', function () {
      chips.forEach(function (x) { x.classList.remove('aktif'); });
      c.classList.add('aktif');
      kat = c.getAttribute('data-kat');
      terapkan();
    });
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
