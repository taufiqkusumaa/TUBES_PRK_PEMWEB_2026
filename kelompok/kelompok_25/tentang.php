<?php
// TENTANG KAMI: dibuat ulang agar sejalan dengan Beranda (hero, statistik, kartu, ajakan).
// Teks visi, misi, dan nilai ada langsung di bawah; ubah seperlunya. Angka statistik diambil dari database.
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/komponen_publik.php';

$pdo     = db();
$user    = current_user();
$kontak  = require __DIR__ . '/config/kontak.php';

// Bidang hukum + jumlah pengacara aktif di tiap bidang
$bidang = $pdo->query(
    "SELECT b.id, b.nama, b.deskripsi,
            (SELECT COUNT(*) FROM profil_pengacara p
               JOIN users u ON u.id = p.user_id
              WHERE p.bidang_id = b.id AND u.role = 'pengacara' AND u.status = 'aktif') AS jumlah
     FROM bidang_hukum b
     ORDER BY b.id"
)->fetchAll();

// Angka statistik (sama dengan Beranda)
$totalPengacara  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'pengacara' AND status = 'aktif'")->fetchColumn();
$totalBidang     = count($bidang);
$totalKonsultasi = (int) $pdo->query("SELECT COUNT(*) FROM konsultasi WHERE status IN ('dijawab', 'selesai')")->fetchColumn();
$ratingRata      = (float) $pdo->query('SELECT AVG(rating) FROM ulasan')->fetchColumn();

// Tombol ajakan sesuai status login
if (!$user) {
    $ctaUrl  = url('auth/register.php');
    $ctaTeks = 'Daftar Gratis';
} elseif ($user['role'] === 'klien') {
    $ctaUrl  = url('klien/konsultasi_baru.php');
    $ctaTeks = 'Ajukan Konsultasi';
} else {
    $ctaUrl  = url(dashboard_path($user['role']));
    $ctaTeks = 'Buka Dashboard';
}

// Tim di balik Ruang Hukum (nama saja; urutan tampil: baris atas satu orang, baris bawah empat orang)
$tim = [
    'Rafi Zakwan Ekaputra',
    'Nafiza Fadhila Hasanah',
    'Aziz Muhammad Sultan Ghani',
    'Nadya Putri Gusnarni',
    'Linda Syahrun Putri',
];

$alamatPeta = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($kontak['alamat']);

$judul = 'Tentang Kami';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/beranda.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/tentang.css')) ?>">

<!-- ============ HERO ============ -->
<section class="bn-hero tt-hero">
  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-8">
        <span class="bn-eyebrow"><i class="bi bi-bank2"></i> Tentang Ruang Hukum</span>
        <h1 class="mt-3 mb-3">Akses hukum yang <span class="emas">mudah dipahami</span> oleh siapa saja</h1>
        <p class="lead mb-4">
          Ruang Hukum adalah platform konsultasi hukum online yang menghubungkan masyarakat dengan
          pengacara terverifikasi. Ajukan pertanyaan, dapatkan jawaban yang jelas, dan pahami hakmu
          tanpa harus datang ke kantor.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-gold btn-lg" href="<?= e($ctaUrl) ?>"><?= e($ctaTeks) ?> <i class="bi bi-arrow-right"></i></a>
          <a class="btn btn-outline-light btn-lg" href="<?= e(url('pengacara.php')) ?>">Lihat Pengacara</a>
        </div>
      </div>
      <div class="col-lg-4 d-none d-lg-flex justify-content-center" aria-hidden="true">
        <div class="tt-lambang"><i class="bi bi-bank2"></i></div>
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

<!-- ============ SIAPA KAMI, VISI, MISI ============ -->
<section class="bn-section">
  <div class="container">
    <div class="row g-4 g-lg-5 align-items-start">
      <div class="col-lg-6">
        <span class="bn-kicker">Siapa kami</span>
        <h2 class="bn-judul">Pintu pertama menuju bantuan hukum</h2>
        <p class="tt-isi">
          Banyak orang menunda mencari bantuan hukum karena bingung harus mulai dari mana, atau segan
          datang langsung ke kantor pengacara. Ruang Hukum hadir sebagai tempat bertanya yang sederhana:
          tulis masalahmu dengan bahasa sehari-hari, pilih bidang yang sesuai, lalu pengacara di bidang
          itu menjawab lewat percakapan yang tercatat.
        </p>
        <p class="tt-isi mb-0">
          Kamu bisa membaca ulang jawabannya kapan saja, bertanya lanjutan sampai paham, dan memberi
          ulasan setelah konsultasi selesai. Untuk bekal awal, tersedia juga
          <a href="<?= e(url('artikel.php')) ?>">artikel hukum</a> dan
          <a href="<?= e(url('faq.php')) ?>">pertanyaan yang sering diajukan</a>.
        </p>
      </div>
      <div class="col-lg-6">
        <div class="card-rh tt-visi p-4 mb-4">
          <span class="bn-ikon"><i class="bi bi-eye"></i></span>
          <h3 class="tt-h">Visi</h3>
          <p class="mb-0">Masyarakat yang paham haknya dan tahu langkah awal yang tepat saat menghadapi masalah hukum.</p>
        </div>
        <div class="card-rh tt-visi p-4">
          <span class="bn-ikon"><i class="bi bi-bullseye"></i></span>
          <h3 class="tt-h">Misi</h3>
          <ul class="tt-daftar mb-0">
            <li>Mempertemukan klien dengan pengacara yang sesuai bidang masalahnya.</li>
            <li>Menyajikan jawaban yang jelas dan mudah dipahami orang awam.</li>
            <li>Menjaga kerahasiaan percakapan konsultasi.</li>
            <li>Membantu pemahaman dasar lewat artikel hukum dan FAQ.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ NILAI ============ -->
<section class="bn-section bn-putih">
  <div class="container">
    <div class="bn-kepala tengah">
      <span class="bn-kicker">Nilai kami</span>
      <h2 class="bn-judul">Yang kami jaga di setiap konsultasi</h2>
    </div>
    <div class="row g-4">
      <div class="col-sm-6 col-lg-3">
        <div class="card-rh hoverable tt-nilai p-4 h-100">
          <span class="bn-ikon"><i class="bi bi-patch-check"></i></span>
          <h3>Terverifikasi</h3>
          <p>Akun pengacara baru aktif setelah admin memeriksa nomor izin advokatnya.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card-rh hoverable tt-nilai p-4 h-100">
          <span class="bn-ikon"><i class="bi bi-chat-heart"></i></span>
          <h3>Bahasa yang jelas</h3>
          <p>Jawaban disusun agar mudah dipahami, bukan hanya oleh sesama sarjana hukum.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card-rh hoverable tt-nilai p-4 h-100">
          <span class="bn-ikon"><i class="bi bi-lock"></i></span>
          <h3>Rahasia terjaga</h3>
          <p>Percakapan tidak ditampilkan ke publik, dan kata sandi disimpan dalam bentuk hash.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card-rh hoverable tt-nilai p-4 h-100">
          <span class="bn-ikon"><i class="bi bi-star"></i></span>
          <h3>Ulasan jujur</h3>
          <p>Rating hanya bisa diberikan klien setelah konsultasinya selesai, satu kali per konsultasi.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ BIDANG HUKUM ============ -->
<?php if ($bidang): ?>
<section class="bn-section">
  <div class="container">
    <div class="bn-kepala tengah">
      <span class="bn-kicker">Bidang yang kami layani</span>
      <h2 class="bn-judul"><?= $totalBidang ?> bidang hukum, satu tempat bertanya</h2>
      <p class="bn-sub">Pilih bidangnya untuk melihat pengacara yang menekuni persoalan tersebut.</p>
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
<?php endif; ?>

<!-- ============ CARA KERJA ============ -->
<section class="bn-section bn-putih">
  <div class="container">
    <div class="bn-kepala tengah">
      <span class="bn-kicker">Cara kerja</span>
      <h2 class="bn-judul">Dari bertanya sampai mendapat jawaban</h2>
    </div>
    <div class="row g-4">
      <div class="col-sm-6 col-lg-3">
        <div class="bn-langkah">
          <span class="bn-nomor">1</span>
          <h3>Buat akun</h3>
          <p>Daftar gratis sebagai klien dengan email dan nomor handphone.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="bn-langkah">
          <span class="bn-nomor">2</span>
          <h3>Ceritakan masalahmu</h3>
          <p>Pilih bidang hukum, tulis pertanyaanmu, lalu kirim.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="bn-langkah">
          <span class="bn-nomor">3</span>
          <h3>Pengacara menjawab</h3>
          <p>Pengacara di bidang yang sesuai membalas lewat percakapan konsultasi.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="bn-langkah">
          <span class="bn-nomor">4</span>
          <h3>Selesai dan beri ulasan</h3>
          <p>Tandai selesai bila sudah cukup, lalu beri rating untuk pengacaranya.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ UNTUK KLIEN DAN PENGACARA ============ -->
<section class="bn-section">
  <div class="container">
    <div class="bn-kepala tengah">
      <span class="bn-kicker">Untuk siapa</span>
      <h2 class="bn-judul">Ruang Hukum untuk klien dan pengacara</h2>
    </div>
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card-rh tt-peran p-4 p-md-5 h-100">
          <span class="bn-ikon"><i class="bi bi-person-heart"></i></span>
          <h3>Untuk klien</h3>
          <ul class="tt-daftar">
            <li>Bertanya kepada pengacara tanpa harus datang ke kantor.</li>
            <li>Pilih pengacara berdasarkan bidang, kota, pengalaman, dan rating.</li>
            <li>Riwayat konsultasi tersimpan rapi di dashboard.</li>
            <li>Pantau status konsultasi: menunggu, dijawab, atau selesai.</li>
          </ul>
          <a class="btn btn-gold mt-auto align-self-start" href="<?= e($ctaUrl) ?>"><?= e($ctaTeks) ?> <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card-rh tt-peran p-4 p-md-5 h-100">
          <span class="bn-ikon"><i class="bi bi-briefcase"></i></span>
          <h3>Untuk pengacara</h3>
          <ul class="tt-daftar">
            <li>Profil publik yang memuat bidang, pengalaman, dan rating.</li>
            <li>Menerima pertanyaan sesuai bidang keahlianmu.</li>
            <li>Menjawab kapan saja lewat dashboard pengacara.</li>
            <li>Akun aktif setelah nomor izin advokatmu diverifikasi admin.</li>
          </ul>
          <?php if (!$user): ?>
            <a class="btn btn-navy mt-auto align-self-start" href="<?= e(url('auth/register.php?role=pengacara')) ?>">Gabung sebagai Pengacara <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ CATATAN PENTING ============ -->
<section class="container pb-5">
  <div class="tt-penting" role="note">
    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
    <div>
      <h3 class="tt-h">Penting diketahui</h3>
      <p class="mb-2">
        Jawaban pengacara di Ruang Hukum bersifat informasi dan arahan awal. Jawaban itu bukan pemberian
        kuasa dan tidak menggantikan pendampingan hukum resmi di kantor atau pengadilan.
      </p>
      <p class="mb-0">
        Untuk perkara yang sudah berjalan atau mendesak, segera temui pengacara atau lembaga bantuan hukum
        secara langsung. Selengkapnya ada di <a href="<?= e(url('syarat.php')) ?>">Syarat Penggunaan Layanan</a>.
      </p>
    </div>
  </div>
</section>

<!-- ============ TIM ============ -->
<section class="bn-section bn-putih" id="tim">
  <div class="container">
    <div class="bn-kepala tengah">
      <span class="bn-kicker">Tim kami</span>
      <h2 class="bn-judul">Orang-orang di balik Ruang Hukum</h2>
      <p class="bn-sub">
        Ruang Hukum merupakan platform konsultasi hukum online yang dikelola oleh tim yang percaya bahwa
        pemahaman hukum harus dapat dijangkau oleh siapa saja.
      </p>
    </div>
    <div class="tt-tim">
      <div class="tt-tim-baris">
        <?php $utama = $tim[0]; ?>
        <div class="tt-anggota">
          <span class="pc-avatar <?= e(kelas_avatar($utama)) ?>"><?= e(inisial($utama)) ?></span>
          <strong><?= e($utama) ?></strong>
        </div>
      </div>
      <div class="tt-tim-baris">
        <?php foreach (array_slice($tim, 1) as $namaAnggota): ?>
          <div class="tt-anggota">
            <span class="pc-avatar <?= e(kelas_avatar($namaAnggota)) ?>"><?= e(inisial($namaAnggota)) ?></span>
            <strong><?= e($namaAnggota) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ============ KONTAK ============ -->
<section class="bn-section" id="kontak">
  <div class="container">
    <div class="bn-kepala tengah">
      <span class="bn-kicker">Hubungi kami</span>
      <h2 class="bn-judul">Ada pertanyaan atau masukan?</h2>
      <p class="bn-sub">Tim kami senang mendengar dari kamu. Hubungi lewat kanal di bawah ini.</p>
    </div>
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card-rh p-4 p-md-5 h-100">
          <ul class="tt-kontak list-unstyled mb-0">
            <li>
              <span class="bn-ikon"><i class="bi bi-geo-alt"></i></span>
              <div>
                <strong>Alamat</strong>
                <address class="mb-1"><?= e($kontak['alamat']) ?></address>
                <a href="<?= e($alamatPeta) ?>" target="_blank" rel="noopener noreferrer">Buka di Google Maps <i class="bi bi-box-arrow-up-right"></i></a>
              </div>
            </li>
            <li>
              <span class="bn-ikon"><i class="bi bi-telephone"></i></span>
              <div>
                <strong>Telepon</strong>
                <a href="tel:<?= e($kontak['telepon_tel']) ?>"><?= e($kontak['telepon']) ?></a>
              </div>
            </li>
            <li>
              <span class="bn-ikon"><i class="bi bi-envelope"></i></span>
              <div>
                <strong>Email</strong>
                <a href="mailto:<?= e($kontak['email']) ?>"><?= e($kontak['email']) ?></a>
              </div>
            </li>
          </ul>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card-rh tt-ikuti p-4 p-md-5 h-100">
          <h3 class="tt-h">Ikuti kami</h3>
          <p class="text-secondary">Kabar terbaru dan informasi hukum singkat kami bagikan di media sosial.</p>
          <div class="d-flex flex-column gap-2 mb-4">
            <?php foreach ($kontak['sosial'] as $s): ?>
              <a class="tt-sosial" href="<?= e($s['url']) ?>" target="_blank" rel="noopener noreferrer">
                <i class="bi <?= e($s['ikon']) ?>" aria-hidden="true"></i>
                <span><?= e($s['nama']) ?></span>
                <i class="bi bi-box-arrow-up-right ms-auto" aria-hidden="true"></i>
              </a>
            <?php endforeach; ?>
          </div>
          <p class="small text-secondary mb-0">Pertanyaan seputar cara memakai Ruang Hukum? Lihat <a href="<?= e(url('faq.php')) ?>">Bantuan &amp; FAQ</a> lebih dulu.</p>
        </div>
      </div>
    </div>
  </div>
</section>

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
