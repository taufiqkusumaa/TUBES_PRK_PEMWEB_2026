<?php
// MODUL ADMIN (Linda): kelola artikel. Daftar, cari, filter status, terbitkan/jadikan draft, hapus.
// Ditulis ulang oleh Rafi (versi sebelumnya memakai $pdo, verify_csrf(), set_flash() yang tidak ada,
// dan redirect ke halaman publik). Form tambah/ubah ada di artikel_form.php.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_role('admin');

$pdo = db();
$kunciFilter = ['q', 'status', 'hal'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $balik = admin_kembali('admin/artikel.php', $_POST, $kunciFilter);
    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        redirect($balik);
    }
    $id   = (int) ($_POST['id'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';
    if ($id > 0 && $aksi === 'hapus') {
        $st = $pdo->prepare('DELETE FROM artikel WHERE id = ?');
        $st->execute([$id]);
        flash($st->rowCount() > 0 ? 'success' : 'warning', $st->rowCount() > 0 ? 'Artikel berhasil dihapus.' : 'Artikel tidak ditemukan.');
    } elseif ($id > 0 && in_array($aksi, ['terbit', 'draft'], true)) {
        $st = $pdo->prepare('UPDATE artikel SET status = ? WHERE id = ?');
        $st->execute([$aksi, $id]);
        flash('success', $aksi === 'terbit' ? 'Artikel diterbitkan dan kini tampil di halaman Artikel.' : 'Artikel dikembalikan menjadi draft.');
    }
    redirect($balik);
}

$q      = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
$status = in_array($_GET['status'] ?? '', ['terbit', 'draft'], true) ? $_GET['status'] : '';
$hal    = max(1, (int) ($_GET['hal'] ?? 1));
$perHal = 15;

$kondisi = ['1 = 1'];
$param   = [];
if ($q !== '') {
    $kondisi[] = '(a.judul LIKE ? OR a.ringkasan LIKE ?)';
    array_push($param, "%$q%", "%$q%");
}
if ($status !== '') {
    $kondisi[] = 'a.status = ?';
    $param[] = $status;
}
$where = implode(' AND ', $kondisi);

$st = $pdo->prepare("SELECT COUNT(*) FROM artikel a WHERE $where");
$st->execute($param);
$total    = (int) $st->fetchColumn();
$totalHal = max(1, (int) ceil($total / $perHal));
$hal      = min($hal, $totalHal);

$st = $pdo->prepare(
    "SELECT a.id, a.judul, a.slug, a.status, a.created_at, b.nama AS bidang, u.nama AS penulis
     FROM artikel a
     LEFT JOIN bidang_hukum b ON b.id = a.bidang_id
     LEFT JOIN users u ON u.id = a.penulis_id
     WHERE $where ORDER BY a.created_at DESC, a.id DESC LIMIT ? OFFSET ?"
);
foreach ($param as $i => $v) {
    $st->bindValue($i + 1, $v);
}
$st->bindValue(count($param) + 1, $perHal, PDO::PARAM_INT);
$st->bindValue(count($param) + 2, ($hal - 1) * $perHal, PDO::PARAM_INT);
$st->execute();
$daftar = $st->fetchAll();

$hitung = ['terbit' => 0, 'draft' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM artikel GROUP BY status')->fetchAll() as $r) {
    $hitung[$r['status']] = (int) $r['n'];
}
$filterAktif = ['q' => $q, 'status' => $status, 'hal' => $hal > 1 ? $hal : ''];

$judul = 'Kelola Artikel';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container ad-wrap">
  <?php admin_kepala('artikel', 'Kelola Artikel', $hitung['terbit'] . ' artikel terbit dan ' . $hitung['draft'] . ' draft. Hanya artikel terbit yang terlihat oleh pengunjung.'); ?>

  <form class="ad-filter" method="get">
    <div class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label" for="a-q">Cari</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span>
          <input class="form-control" id="a-q" name="q" type="search" value="<?= e($q) ?>" placeholder="Judul atau ringkasan"></div>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="a-status">Status</label>
        <select class="form-select" id="a-status" name="status">
          <option value="">Semua</option>
          <option value="terbit" <?= $status === 'terbit' ? 'selected' : '' ?>>Terbit</option>
          <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select>
      </div>
      <div class="col-3 col-md-1 d-grid"><button class="btn btn-navy" type="submit">Cari</button></div>
      <div class="col-3 col-md-1 d-grid"><a class="btn btn-outline-secondary" href="<?= e(url('admin/artikel.php')) ?>" title="Hapus filter"><i class="bi bi-x-lg"></i></a></div>
      <div class="col-12 col-md-2 d-grid"><a class="btn btn-gold" href="<?= e(url('admin/artikel_form.php')) ?>"><i class="bi bi-plus-lg"></i> Tulis Artikel</a></div>
    </div>
  </form>

  <div class="ad-card">
    <?php if (!$daftar): ?>
      <div class="ad-kosong"><i class="bi bi-journal-x"></i><?= ($q !== '' || $status !== '') ? 'Tidak ada artikel yang cocok dengan filter.' : 'Belum ada artikel. Klik "Tulis Artikel" untuk membuat yang pertama.' ?></div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table ad-table">
        <thead><tr><th>Artikel</th><th>Bidang</th><th>Penulis</th><th>Status</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($daftar as $a): ?>
          <tr>
            <td><div class="ad-potong fw-semibold"><?= e($a['judul']) ?></div><span class="ad-sub">/<?= e($a['slug']) ?></span></td>
            <td><?= e($a['bidang'] ?? 'Umum') ?></td>
            <td><?= e($a['penulis'] ?? '-') ?></td>
            <td><?= admin_pil($a['status']) ?></td>
            <td><?= e(tgl($a['created_at'])) ?></td>
            <td class="text-end">
              <span class="ad-aksi">
                <a class="btn btn-outline-secondary btn-ad" href="<?= e(url('artikel_detail.php?slug=' . rawurlencode($a['slug']))) ?>" target="_blank" title="Lihat di situs"><i class="bi bi-box-arrow-up-right"></i></a>
                <a class="btn btn-outline-secondary btn-ad" href="<?= e(url('admin/artikel_form.php?id=' . (int) $a['id'])) ?>">Ubah</a>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                  <input type="hidden" name="aksi" value="<?= $a['status'] === 'terbit' ? 'draft' : 'terbit' ?>">
                  <?php foreach ($filterAktif as $kk => $v): ?><input type="hidden" name="<?= $kk ?>" value="<?= e($v) ?>"><?php endforeach; ?>
                  <button class="btn btn-outline-secondary btn-ad" type="submit"><?= $a['status'] === 'terbit' ? 'Jadikan draft' : 'Terbitkan' ?></button>
                </form>
                <form method="post" data-confirm="Artikel &quot;<?= e($a['judul']) ?>&quot; akan dihapus permanen." data-confirm-judul="Hapus artikel?" data-confirm-ok="Ya, hapus" data-confirm-tipe="danger">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                  <input type="hidden" name="aksi" value="hapus">
                  <?php foreach ($filterAktif as $kk => $v): ?><input type="hidden" name="<?= $kk ?>" value="<?= e($v) ?>"><?php endforeach; ?>
                  <button class="btn btn-outline-danger btn-ad" type="submit" title="Hapus"><i class="bi bi-trash"></i></button>
                </form>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
    <span class="text-muted small">Menampilkan <?= count($daftar) ?> dari <?= $total ?> artikel</span>
    <div><?= art_pagination($hal, $totalHal, 'admin/artikel.php', ['q' => $q, 'status' => $status]) ?></div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
