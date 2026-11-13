<?php
require_once __DIR__ . '/guard.php';
$judul_halaman = 'Kelola Artikel | BUYPANEN';

$stmt = $conn->prepare(
    "SELECT a.id, a.judul, a.slug, a.status, a.dilihat, a.created_at, k.nama_kategori
     FROM artikel a
     JOIN kategori_artikel k ON k.id = a.kategori_id
     WHERE a.penulis_id = ?
     ORDER BY a.created_at DESC"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$daftar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

include __DIR__ . '/../header.php';
?>
<main class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
      <h1 class="mb-1">Kelola Artikel</h1>
      <p class="text-muted mb-0">Artikel milik <?= htmlspecialchars($_SESSION['nama']) ?></p>
    </div>
    <div class="d-flex gap-2">
      <a href="../index.php" class="btn btn-outline-success">Lihat Halaman Edukasi</a>
      <a href="form.php" class="btn btn-panen">+ Tambah Artikel</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <?php if (!$daftar): ?>
    <div class="alert alert-light border">Belum ada artikel. Klik <strong>Tambah Artikel</strong> untuk mulai menulis.</div>
  <?php else: ?>
    <div class="table-responsive bg-white rounded shadow-sm">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr><th>Judul</th><th>Kategori</th><th>Status</th><th>Dibaca</th><th>Dibuat</th><th class="text-end">Aksi</th></tr>
        </thead>
        <tbody>
          <?php foreach ($daftar as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['judul']) ?></td>
              <td><?= htmlspecialchars($a['nama_kategori']) ?></td>
              <td>
                <span class="badge <?= $a['status'] === 'publish' ? 'bg-success' : 'bg-secondary' ?>">
                  <?= $a['status'] === 'publish' ? 'Publish' : 'Draft' ?>
                </span>
              </td>
              <td><?= (int) $a['dilihat'] ?></td>
              <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
              <td class="text-end text-nowrap">
                <?php if ($a['status'] === 'publish'): ?>
                  <a href="../detail.php?slug=<?= urlencode($a['slug']) ?>" class="btn btn-sm btn-outline-secondary">Lihat</a>
                <?php endif; ?>
                <a href="form.php?id=<?= (int) $a['id'] ?>" class="btn btn-sm btn-outline-success">Edit</a>
                <form method="post" action="hapus.php" class="d-inline"
                      onsubmit="return confirm('Hapus artikel ini? Tindakan ini tidak bisa dibatalkan.')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../footer.php'; ?>