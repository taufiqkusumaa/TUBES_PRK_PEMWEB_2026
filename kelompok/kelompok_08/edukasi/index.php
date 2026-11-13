<?php
// Daftar artikel edukasi. Koneksi memakai $conn (mysqli) dari config/database.php.
require_once __DIR__ . '/../config/database.php';

$judul_halaman = 'Edukasi Pertanian | BuyPanen';
$kategori = trim($_GET['kategori'] ?? '');

$sql = "SELECT a.judul, a.slug, a.ringkasan, a.gambar, a.created_at, k.nama_kategori
        FROM artikel a
        JOIN kategori_artikel k ON k.id = a.kategori_id
        WHERE a.status = 'publish'";
if ($kategori !== '') {
    $sql .= " AND k.nama_kategori = ?";
}
$sql .= " ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
if ($kategori !== '') {
    $stmt->bind_param('s', $kategori);
}
$stmt->execute();
$hasil = $stmt->get_result();

$daftar_kategori = $conn->query('SELECT nama_kategori FROM kategori_artikel ORDER BY nama_kategori');

include __DIR__ . '/header.php';
?>
<main class="container py-5">
  <h1 class="mb-1">Edukasi</h1>
  <p class="text-muted mb-4">Tips bertani dan cara memilih serta menyimpan bahan pangan segar.</p>

  <div class="mb-4 d-flex flex-wrap gap-2">
    <a href="index.php" class="btn btn-sm <?= $kategori === '' ? 'btn-panen' : 'btn-outline-success' ?>">Semua</a>
    <?php while ($k = $daftar_kategori->fetch_assoc()): ?>
      <a href="?kategori=<?= urlencode($k['nama_kategori']) ?>"
         class="btn btn-sm <?= $kategori === $k['nama_kategori'] ? 'btn-panen' : 'btn-outline-success' ?>">
        <?= htmlspecialchars($k['nama_kategori']) ?>
      </a>
    <?php endwhile; ?>
  </div>

  <div class="row g-4">
    <?php if ($hasil->num_rows === 0): ?>
      <p class="text-muted">Belum ada artikel di kategori ini. Coba pilih kategori lain.</p>
    <?php endif; ?>
    <?php while ($a = $hasil->fetch_assoc()): ?>
      <div class="col-md-6 col-lg-4">
        <article class="card h-100 border-0 shadow-sm">
          <?php if (!empty($a['gambar'])): ?>
            <img src="../assets/<?= htmlspecialchars($a['gambar']) ?>" class="card-img-top"
                 alt="<?= htmlspecialchars($a['judul']) ?>" style="height:180px;object-fit:cover">
          <?php endif; ?>
          <div class="card-body d-flex flex-column">
            <span class="badge badge-kategori align-self-start mb-2"><?= htmlspecialchars($a['nama_kategori']) ?></span>
            <h2 class="h5"><?= htmlspecialchars($a['judul']) ?></h2>
            <p class="text-muted flex-grow-1"><?= htmlspecialchars($a['ringkasan']) ?></p>
            <small class="text-muted mb-3"><?= date('d M Y', strtotime($a['created_at'])) ?></small>
            <a href="detail.php?slug=<?= urlencode($a['slug']) ?>" class="btn btn-panen btn-sm">Baca artikel</a>
          </div>
        </article>
      </div>
    <?php endwhile; ?>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>