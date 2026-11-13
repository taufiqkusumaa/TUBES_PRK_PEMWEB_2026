</main>
<?php $tanpaKerangka = $tanpaKerangka ?? false; ?>
<?php if (!$tanpaKerangka): ?>
<?php
// Data footer: kontak dari config/kontak.php, daftar bidang hukum dari database.
$kontak = require __DIR__ . '/../config/kontak.php';
$bidangFooter = [];
try {
    $bidangFooter = db()->query('SELECT id, nama FROM bidang_hukum ORDER BY nama LIMIT 6')->fetchAll();
} catch (Throwable $ex) {
    $bidangFooter = []; // footer tetap tampil walau tabelnya belum ada
}
$userFooter = current_user();
?>
<footer class="rh-footer mt-5">
  <div class="rh-footer-atas">
    <div class="container">
      <div class="row g-4 g-xl-5">

        <!-- Kiri: logo, kontak, media sosial -->
        <div class="col-lg-4">
          <a class="rh-logo" href="<?= url('index.php') ?>" aria-label="Ruang Hukum, ke beranda">
            <span class="rh-logo-lingkar"><i class="bi bi-bank2"></i></span>
            <span class="rh-logo-teks">
              <span class="rh-logo-nama">Ruang<br>Hukum</span>
              <span class="rh-logo-sub">Konsultasi Online</span>
            </span>
          </a>

          <ul class="rh-kontak list-unstyled">
            <li>
              <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
              <address class="mb-0"><?= e($kontak['alamat']) ?></address>
            </li>
            <li>
              <i class="bi bi-telephone-fill" aria-hidden="true"></i>
              <span>Phone: <a href="tel:<?= e($kontak['telepon_tel']) ?>"><?= e($kontak['telepon']) ?></a></span>
            </li>
            <li>
              <i class="bi bi-envelope-fill" aria-hidden="true"></i>
              <a href="mailto:<?= e($kontak['email']) ?>"><?= e($kontak['email']) ?></a>
            </li>
          </ul>

          <div class="rh-sosial" aria-label="Media sosial Ruang Hukum">
            <?php foreach ($kontak['sosial'] as $s): ?>
              <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener noreferrer"
                 aria-label="<?= e($s['nama']) ?> Ruang Hukum" title="<?= e($s['nama']) ?>">
                <i class="bi <?= e($s['ikon']) ?>" aria-hidden="true"></i>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Kanan: kolom tautan, semuanya halaman milik Ruang Hukum sendiri -->
        <nav class="col-lg-8" aria-label="Tautan footer">
          <div class="row g-4">
            <div class="col-6 col-md-3">
              <h2 class="rh-footer-judul">Layanan</h2>
              <ul class="rh-footer-daftar">
                <li><a href="<?= url('klien/konsultasi_baru.php') ?>">Mulai Konsultasi</a></li>
                <li><a href="<?= url('pengacara.php') ?>">Cari Pengacara</a></li>
                <li><a href="<?= url('klien/dashboard.php') ?>">Riwayat Konsultasi</a></li>
              </ul>
            </div>

            <div class="col-6 col-md-3">
              <h2 class="rh-footer-judul">Bidang Hukum</h2>
              <ul class="rh-footer-daftar">
                <?php foreach ($bidangFooter as $b): ?>
                  <li><a href="<?= url('pengacara.php?bidang=' . (int) $b['id']) ?>"><?= e($b['nama']) ?></a></li>
                <?php endforeach; ?>
                <?php if (!$bidangFooter): ?>
                  <li><a href="<?= url('pengacara.php') ?>">Semua bidang</a></li>
                <?php endif; ?>
              </ul>
            </div>

            <div class="col-6 col-md-3">
              <h2 class="rh-footer-judul">Info Hukum</h2>
              <ul class="rh-footer-daftar">
                <li><a href="<?= url('artikel.php') ?>">Artikel Hukum</a></li>
                <li><a href="<?= url('faq.php') ?>">Bantuan &amp; FAQ</a></li>
                <li><a href="<?= url('syarat.php') ?>">Syarat Penggunaan</a></li>
              </ul>
            </div>

            <div class="col-6 col-md-3">
              <h2 class="rh-footer-judul">Ruang Hukum</h2>
              <ul class="rh-footer-daftar">
                <li><a href="<?= url('tentang.php') ?>">Tentang Kami</a></li>
                <li><a href="<?= url('tentang.php#kontak') ?>">Hubungi Kami</a></li>
                <li><a href="<?= url('auth/register.php?role=pengacara') ?>">Gabung sebagai Pengacara</a></li>
                <?php if ($userFooter): ?>
                  <li><a href="<?= url(dashboard_path($userFooter['role'])) ?>">Dashboard Saya</a></li>
                <?php else: ?>
                  <li><a href="<?= url('auth/login.php') ?>">Masuk</a></li>
                  <li><a href="<?= url('auth/register.php') ?>">Daftar</a></li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
        </nav>

      </div>
    </div>
  </div>

  <!-- Bilah bawah -->
  <div class="rh-footer-bawah">
    <div class="container">
      <div class="rh-hak-cipta">
        <span class="rh-c" aria-hidden="true">C</span>
        <span><?= date('Y') ?> Hak Cipta Milik Ruang Hukum</span>
      </div>
      <div class="rh-bawah-tautan">
        <a href="<?= url('syarat.php') ?>">Syarat Penggunaan</a>
        <span class="rh-titik" aria-hidden="true">&bull;</span>
        <a href="<?= url('faq.php') ?>">Bantuan &amp; FAQ</a>
      </div>
    </div>
  </div>
</footer>

<button type="button" class="rh-ke-atas" id="tombolKeAtas" aria-label="Kembali ke atas" hidden>
  <i class="bi bi-chevron-up" aria-hidden="true"></i>
</button>
<?php endif; ?>

<!-- Jendela konfirmasi bersama. Pakai di form: <form method="post" data-confirm="Yakin hapus?"> -->
<div class="modal fade" id="modalKonfirmasi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-rh text-center p-4">
      <div class="modal-ikon" id="konfirmasiIkon"><i class="bi bi-question-lg"></i></div>
      <h4 class="h5 mb-2" id="konfirmasiJudul">Konfirmasi</h4>
      <p class="text-secondary mb-4" id="konfirmasiPesan"></p>
      <div class="d-flex justify-content-center gap-2">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-gold px-4" id="konfirmasiYa">Ya, lanjutkan</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// 1) Notifikasi (flash): hilang sendiri setelah 5 detik, kecuali pesan error.
document.querySelectorAll('.toast-rh').forEach(function (el) {
  function tutup() { el.classList.add('keluar'); setTimeout(function () { el.remove(); }, 350); }
  el.querySelector('.btn-close').addEventListener('click', tutup);
  if (!el.classList.contains('toast-danger')) { setTimeout(tutup, 5000); }
});

// 2) Konfirmasi: form yang punya atribut data-confirm tampil di jendela tengah layar.
//    Atribut opsional: data-confirm-judul, data-confirm-ok, data-confirm-tipe="danger"
(function () {
  var modalEl = document.getElementById('modalKonfirmasi');
  var modal = new bootstrap.Modal(modalEl);
  var tujuan = null;

  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (!f.hasAttribute || !f.hasAttribute('data-confirm')) { return; }
    ev.preventDefault();
    tujuan = f;
    var bahaya = f.getAttribute('data-confirm-tipe') === 'danger';
    document.getElementById('konfirmasiJudul').textContent = f.getAttribute('data-confirm-judul') || 'Konfirmasi';
    document.getElementById('konfirmasiPesan').textContent = f.getAttribute('data-confirm');
    var ya = document.getElementById('konfirmasiYa');
    ya.textContent = f.getAttribute('data-confirm-ok') || 'Ya, lanjutkan';
    ya.className = 'btn px-4 ' + (bahaya ? 'btn-danger' : 'btn-gold');
    document.getElementById('konfirmasiIkon').className = 'modal-ikon' + (bahaya ? ' bahaya' : '');
    modal.show();
  });

  document.getElementById('konfirmasiYa').addEventListener('click', function () {
    if (!tujuan) { return; }
    var f = tujuan; tujuan = null;
    modal.hide();
    f.submit(); // submit() langsung kirim form, tanpa memicu jendela konfirmasi lagi
  });
})();

// 3) Validasi formulir: pesan berbahasa Indonesia tepat di bawah kolom yang salah
//    (menggantikan balon bawaan browser). Berlaku otomatis untuk semua <form>.
//    Pesan wajib-diisi bisa diganti per kolom: <input required data-pesan-wajib="Tulis emailmu.">
(function () {
  function labelDari(el) {
    var l = el.id ? document.querySelector('label[for="' + el.id + '"]') : null;
    return l ? l.textContent.replace('*', '').trim() : '';
  }

  function galat(el) {
    if (!el.willValidate) { return ''; }
    var v = el.validity, tipe = (el.type || '').toLowerCase(), label = labelDari(el);
    var hanyaSpasi = el.required && /^(text|search|tel|url|email|textarea)$/.test(tipe) && el.value.trim() === '';
    if (v.valueMissing || hanyaSpasi) {
      if (el.getAttribute('data-pesan-wajib')) { return el.getAttribute('data-pesan-wajib'); }
      if (el.tagName === 'SELECT') { return 'Silakan pilih ' + (label ? label.toLowerCase() : 'salah satu opsi') + '.'; }
      if (tipe === 'checkbox') { return 'Centang kotak ini untuk melanjutkan.'; }
      if (tipe === 'radio') { return 'Pilih salah satu opsi.'; }
      if (tipe === 'file') { return 'Pilih file terlebih dahulu.'; }
      return (label || 'Kolom ini') + ' wajib diisi.';
    }
    if (v.typeMismatch) { return tipe === 'email' ? 'Format email tidak valid. Contoh: nama@email.com' : 'Format isian tidak valid.'; }
    if (v.tooShort) { return (label || 'Isian') + ' minimal ' + el.minLength + ' karakter.'; }
    if (v.tooLong) { return (label || 'Isian') + ' maksimal ' + el.maxLength + ' karakter.'; }
    if (v.patternMismatch) { return el.title || 'Format isian tidak sesuai.'; }
    if (v.rangeUnderflow) { return 'Nilai minimal ' + el.min + '.'; }
    if (v.rangeOverflow) { return 'Nilai maksimal ' + el.max + '.'; }
    if (v.badInput || v.stepMismatch) { return 'Nilai yang dimasukkan tidak valid.'; }
    if (v.customError) { return el.validationMessage; }
    return '';
  }

  function hapus(el) {
    el.classList.remove('is-invalid');
    el.removeAttribute('aria-invalid');
    if (el._pesan) { el._pesan.remove(); el._pesan = null; }
  }

  function tampil(el, pesan) {
    hapus(el);
    el.classList.add('is-invalid');
    el.setAttribute('aria-invalid', 'true');
    var d = document.createElement('div');
    d.className = 'pesan-validasi';
    d.setAttribute('role', 'alert');
    d.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i><span></span>';
    d.lastChild.textContent = pesan;
    (el.closest('.input-group') || el).insertAdjacentElement('afterend', d);
    el._pesan = d;
    el.classList.remove('field-getar'); void el.offsetWidth; el.classList.add('field-getar');
  }

  // Matikan balon bawaan browser; pemeriksaannya kita lakukan sendiri di bawah.
  document.querySelectorAll('form').forEach(function (f) { f.noValidate = true; });

  // Fase capture: jalan lebih dulu daripada jendela konfirmasi, jadi form yang
  // belum lengkap tidak pernah memunculkan jendela konfirmasi.
  document.addEventListener('submit', function (ev) {
    var f = ev.target, pertama = null;
    if (!f.elements) { return; }
    Array.prototype.forEach.call(f.elements, function (el) {
      var m = galat(el);
      if (m) { tampil(el, m); if (!pertama) { pertama = el; } } else { hapus(el); }
    });
    if (pertama) {
      ev.preventDefault();
      ev.stopPropagation();
      pertama.focus({ preventScroll: true });
      pertama.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, true);

  // Pesan hilang sendiri begitu isiannya sudah benar.
  function ulang(ev) {
    var el = ev.target;
    if (!el._pesan) { return; }
    var m = galat(el);
    if (m) { el._pesan.lastChild.textContent = m; } else { hapus(el); }
  }
  document.addEventListener('input', ulang);
  document.addEventListener('change', ulang);
})();

// 4) Tombol "kembali ke atas": muncul setelah halaman digulir ke bawah.
(function () {
  var tombol = document.getElementById('tombolKeAtas');
  if (!tombol) { return; }
  function atur() { tombol.hidden = (window.pageYOffset || document.documentElement.scrollTop) < 400; }
  window.addEventListener('scroll', atur, { passive: true });
  atur();
  tombol.addEventListener('click', function () {
    var pelan = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    window.scrollTo({ top: 0, behavior: pelan ? 'smooth' : 'auto' });
  });
})();
</script>
</body>
</html>
