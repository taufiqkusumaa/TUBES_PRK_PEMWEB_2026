<?php
require_once __DIR__ . '/../config/database.php';

$slug = trim($_GET['slug'] ?? '');
$stmt = $conn->prepare("SELECT a.judul, a.isi, a.gambar, a.dilihat, a.created_at,
                               k.nama_kategori, u.nama AS penulis
                        FROM artikel a
                        JOIN kategori_artikel k ON k.id = a.kategori_id
                        JOIN users u ON u.id = a.penulis_id
                        WHERE a.slug = ? AND a.status = 'publish' LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$artikel = $stmt->get_result()->fetch_assoc();

if (!$artikel) {
    http_response_code(404);
    $judul_halaman = 'Artikel tidak ditemukan | BuyPanen';
    include __DIR__ . '/header.php';
    echo '<main class="container py-5"><h1>Artikel tidak ditemukan</h1>
          <p>Artikel yang Anda cari tidak ada atau belum dipublikasikan.</p>
          <a href="index.php" class="btn btn-panen">Kembali ke daftar artikel</a></main>';
    include __DIR__ . '/footer.php';
    exit;
}

// Tambah jumlah dilihat
$up = $conn->prepare('UPDATE artikel SET dilihat = dilihat + 1 WHERE slug = ?');
$up->bind_param('s', $slug);
$up->execute();

$judul_halaman = $artikel['judul'] . ' | BuyPanen';
include __DIR__ . '/header.php';
?>
<main class="container py-5">
  <a href="index.php" class="text-decoration-none">&larr; Semua artikel</a>
  <span class="badge badge-kategori d-block mt-4 mb-2" style="width:fit-content"><?= htmlspecialchars($artikel['nama_kategori']) ?></span>
  <h1><?= htmlspecialchars($artikel['judul']) ?></h1>
  <p class="text-muted">
    <?= date('d M Y', strtotime($artikel['created_at'])) ?>
    &middot; Ditulis oleh <?= htmlspecialchars($artikel['penulis']) ?>
    &middot; <?= (int)$artikel['dilihat'] + 1 ?> kali dibaca
  </p>
  <?php if (!empty($artikel['gambar'])): ?>
    <img src="../assets/<?= htmlspecialchars($artikel['gambar']) ?>" class="img-fluid rounded mb-4"
         alt="<?= htmlspecialchars($artikel['judul']) ?>" style="max-height:360px;object-fit:cover">
  <?php endif; ?>
  <div class="isi-artikel mt-4"><?= nl2br(htmlspecialchars($artikel['isi'])) ?></div>
</main>
<?php include __DIR__ . '/footer.php'; ?>