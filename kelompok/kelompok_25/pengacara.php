<?php
// DAFTAR PENGACARA: pencarian, filter, urutan, dan halaman (desain ulang oleh Rafi, dasar dari Nafiza).
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/komponen_publik.php';

$pdo = db();

// ---- Masukan dari URL (semuanya divalidasi) ----
$q         = trim($_GET['q'] ?? '');
$bidang_id = (int) ($_GET['bidang'] ?? 0);
$kota      = trim($_GET['kota'] ?? '');
$urut      = $_GET['urut'] ?? 'rating';
$hal       = max(1, (int) ($_GET['hal'] ?? 1));
$perHal    = 12;

$urutan = [
    'rating'     => 'rating DESC, jumlah_ulasan DESC, p.pengalaman_tahun DESC, u.nama ASC',
    'pengalaman' => 'p.pengalaman_tahun DESC, rating DESC, u.nama ASC',
    'nama'       => 'u.nama ASC',
];
if (!isset($urutan[$urut])) {
    $urut = 'rating';
}

// ---- Kondisi pencarian ----
$kondisi = ["u.role = 'pengacara'", "u.status = 'aktif'"];
$param   = [];
if ($q !== '') {
    $kondisi[] = '(u.nama LIKE ? OR p.kota LIKE ? OR p.bio LIKE ?)';
    $like = '%' . $q . '%';
    array_push($param, $like, $like, $like);
}
if ($bidang_id > 0) {
    $kondisi[] = 'p.bidang_id = ?';
    $param[] = $bidang_id;
}
if ($kota !== '') {
    $kondisi[] = 'p.kota = ?';
    $param[] = $kota;
}
$where = implode(' AND ', $kondisi);

// ---- Jumlah hasil ----
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN profil_pengacara p ON p.user_id = u.id WHERE $where");
$stmt->execute($param);
$total     = (int) $stmt->fetchColumn();
$totalHal  = max(1, (int) ceil($total / $perHal));
$hal       = min($hal, $totalHal);
$offset    = ($hal - 1) * $perHal;

// ---- Data satu halaman ----
$stmt = $pdo->prepare(
    "SELECT u.id, u.nama, p.bidang_id, b.nama AS bidang, p.bio, p.pengalaman_tahun, p.kota,
            COALESCE(r.rata, 0) AS rating, COALESCE(r.jumlah, 0) AS jumlah_ulasan
     FROM users u
     JOIN profil_pengacara p ON p.user_id = u.id
     JOIN bidang_hukum b ON b.id = p.bidang_id
     LEFT JOIN (SELECT pengacara_id, AVG(rating) AS rata, COUNT(*) AS jumlah
                  FROM ulasan GROUP BY pengacara_id) r ON r.pengacara_id = u.id
     WHERE $where
     ORDER BY {$urutan[$urut]}
     LIMIT ? OFFSET ?"
);
foreach ($param as $i => $nilai) {
    $stmt->bindValue($i + 1, $nilai);
}
$stmt->bindValue(count($param) + 1, $perHal, PDO::PARAM_INT);
$stmt->bindValue(count($param) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$daftar = $stmt->fetchAll();

// ---- Pilihan untuk filter ----
$bidangList = $pdo->query('SELECT id, nama FROM bidang_hukum ORDER BY nama')->fetchAll();
$kotaList = $pdo->query(
    "SELECT DISTINCT p.kota FROM profil_pengacara p JOIN users u ON u.id = p.user_id
     WHERE u.role = 'pengacara' AND u.status = 'aktif' AND p.kota IS NOT NULL AND p.kota <> ''
     ORDER BY p.kota"
)->fetchAll(PDO::FETCH_COLUMN);

$bidangAktif = '';
foreach ($bidangList as $b) {
    if ((int) $b['id'] === $bidang_id) {
        $bidangAktif = $b['nama'];
    }
}
$adaFilter = ($q !== '' || $bidang_id > 0 || $kota !== '');

/** Alamat halaman dengan filter yang sama, hanya nomor halaman yang beda. */
function alamat_halaman(int $nomor, string $q, int $bidang_id, string $kota, string $urut): string
{
    $p = ['q' => $q, 'bidang' => $bidang_id ?: '', 'kota' => $kota, 'urut' => $urut !== 'rating' ? $urut : '', 'hal' => $nomor > 1 ? $nomor : ''];
    return '?' . http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== null));
}

// Nomor halaman yang ditampilkan: 1 ... (sekitar halaman aktif) ... terakhir
$nomorTampil = [];
for ($i = 1; $i <= $totalHal; $i++) {
    if ($i === 1 || $i === $totalHal || abs($i - $hal) <= 1) {
        $nomorTampil[] = $i;
    }
}

$judul = 'Daftar Pengacara';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/beranda.css')) ?>">

<section class="pg-hero">
  <div class="container">
    <span class="bn-kicker">Direktori pengacara</span>
    <h1>Temukan pengacara yang tepat</h1>
    <p>
      <?php if ($bidangAktif !== ''): ?>
        Menampilkan pengacara <?= e($bidangAktif) ?>.
      <?php else: ?>
        Pengacara terverifikasi di enam bidang hukum, dengan keahlian dan pengalaman yang berbeda-beda.
      <?php endif; ?>
    </p>
  </div>
</section>

<div class="container pg-isi">
  <form class="pg-filter" method="get" action="<?= e(url('pengacara.php')) ?>">
    <div class="row g-2 align-items-end">
      <div class="col-lg-4">
        <label class="form-label" for="f-q">Cari</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input class="form-control" id="f-q" name="q" type="search" value="<?= e($q) ?>" placeholder="Nama, kota, atau keahlian">
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <label class="form-label" for="f-bidang">Bidang</label>
        <select class="form-select" id="f-bidang" name="bidang">
          <option value="">Semua</option>
          <?php foreach ($bidangList as $b): ?>
            <option value="<?= (int) $b['id'] ?>" <?= (int) $b['id'] === $bidang_id ? 'selected' : '' ?>><?= e($b['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-lg-2">
        <label class="form-label" for="f-kota">Kota</label>
        <select class="form-select" id="f-kota" name="kota">
          <option value="">Semua</option>
          <?php foreach ($kotaList as $k): ?>
            <option value="<?= e($k) ?>" <?= $k === $kota ? 'selected' : '' ?>><?= e($k) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-lg-2">
        <label class="form-label" for="f-urut">Urutkan</label>
        <select class="form-select" id="f-urut" name="urut">
          <option value="rating" <?= $urut === 'rating' ? 'selected' : '' ?>>Rating tertinggi</option>
          <option value="pengalaman" <?= $urut === 'pengalaman' ? 'selected' : '' ?>>Paling berpengalaman</option>
          <option value="nama" <?= $urut === 'nama' ? 'selected' : '' ?>>Nama (A-Z)</option>
        </select>
      </div>
      <div class="col-6 col-lg-2 d-grid">
        <button class="btn btn-gold" type="submit"><i class="bi bi-funnel"></i> Terapkan</button>
      </div>
    </div>
  </form>

  <div class="pg-info">
    <span>
      Menampilkan <strong><?= count($daftar) ?></strong> dari <strong><?= $total ?></strong> pengacara
      <?php if ($totalHal > 1): ?> &middot; halaman <?= $hal ?> dari <?= $totalHal ?><?php endif; ?>
    </span>
    <?php if ($adaFilter): ?>
      <a href="<?= e(url('pengacara.php')) ?>" class="text-decoration-none"><i class="bi bi-x-circle"></i> Hapus filter</a>
    <?php endif; ?>
  </div>

  <?php if (!$daftar): ?>
    <div class="pg-kosong">
      <i class="bi bi-search"></i>
      <h3>Pengacara tidak ditemukan</h3>
      <p>Coba kata kunci lain atau hapus sebagian filter.</p>
      <a class="btn btn-navy" href="<?= e(url('pengacara.php')) ?>">Tampilkan semua pengacara</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($daftar as $p): ?>
        <div class="col-md-6 col-lg-4"><?= kartu_pengacara($p) ?></div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalHal > 1): ?>
      <nav class="mt-5" aria-label="Halaman daftar pengacara">
        <ul class="pagination justify-content-center">
          <li class="page-item <?= $hal <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e(alamat_halaman($hal - 1, $q, $bidang_id, $kota, $urut)) ?>" aria-label="Sebelumnya">&laquo;</a>
          </li>
          <?php $sebelum = 0; foreach ($nomorTampil as $n): ?>
            <?php if ($sebelum && $n - $sebelum > 1): ?>
              <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
            <?php endif; ?>
            <li class="page-item <?= $n === $hal ? 'active' : '' ?>">
              <a class="page-link" href="<?= e(alamat_halaman($n, $q, $bidang_id, $kota, $urut)) ?>"><?= $n ?></a>
            </li>
            <?php $sebelum = $n; ?>
          <?php endforeach; ?>
          <li class="page-item <?= $hal >= $totalHal ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e(alamat_halaman($hal + 1, $q, $bidang_id, $kota, $urut)) ?>" aria-label="Berikutnya">&raquo;</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
