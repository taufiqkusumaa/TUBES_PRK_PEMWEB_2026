<?php
// MODUL ADMIN: manajemen pengguna (cari, filter, aktifkan/nonaktifkan).
// Ditulis ulang oleh Rafi (versi sebelumnya memakai $pdo, verify_csrf(), set_flash() yang tidak ada).
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_role('admin');

$pdo = db();
$saya = (int) current_user()['id'];
$kunciFilter = ['q', 'peran', 'status', 'hal'];

// ---- Aksi: aktifkan / nonaktifkan ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $balik = admin_kembali('admin/pengguna.php', $_POST, $kunciFilter);
    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        redirect($balik);
    }
    $id   = (int) ($_POST['user_id'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';
    if ($id === $saya) {
        flash('warning', 'Kamu tidak bisa mengubah status akunmu sendiri.');
        redirect($balik);
    }
    if ($id > 0 && in_array($aksi, ['aktif', 'nonaktif'], true)) {
        $st = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $st->execute([$aksi, $id]);
        if ($st->rowCount() > 0) {
            flash('success', $aksi === 'aktif' ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.');
        } else {
            flash('warning', 'Status akun sudah sesuai, tidak ada yang berubah.');
        }
    }
    redirect($balik);
}

// ---- Filter ----
$q      = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
$peran  = in_array($_GET['peran'] ?? '', ['klien', 'pengacara', 'admin'], true) ? $_GET['peran'] : '';
$status = in_array($_GET['status'] ?? '', ['aktif', 'menunggu', 'nonaktif', 'ditolak'], true) ? $_GET['status'] : '';
$hal    = max(1, (int) ($_GET['hal'] ?? 1));
$perHal = 15;

$kondisi = ['1 = 1'];
$param   = [];
if ($q !== '') {
    $kondisi[] = '(nama LIKE ? OR email LIKE ?)';
    array_push($param, "%$q%", "%$q%");
}
if ($peran !== '') {
    $kondisi[] = 'role = ?';
    $param[] = $peran;
}
if ($status !== '') {
    $kondisi[] = 'status = ?';
    $param[] = $status;
}
$where = implode(' AND ', $kondisi);

$st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
$st->execute($param);
$total    = (int) $st->fetchColumn();
$totalHal = max(1, (int) ceil($total / $perHal));
$hal      = min($hal, $totalHal);

$st = $pdo->prepare("SELECT id, nama, email, role, status, created_at FROM users WHERE $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?");
foreach ($param as $i => $v) {
    $st->bindValue($i + 1, $v);
}
$st->bindValue(count($param) + 1, $perHal, PDO::PARAM_INT);
$st->bindValue(count($param) + 2, ($hal - 1) * $perHal, PDO::PARAM_INT);
$st->execute();
$users = $st->fetchAll();

$hitung = [];
foreach ($pdo->query('SELECT role, COUNT(*) AS n FROM users GROUP BY role')->fetchAll() as $r) {
    $hitung[$r['role']] = (int) $r['n'];
}
$filterAktif = ['q' => $q, 'peran' => $peran, 'status' => $status, 'hal' => $hal > 1 ? $hal : ''];

$judul = 'Kelola Pengguna';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container ad-wrap">
  <?php admin_kepala('pengguna', 'Manajemen Pengguna', 'Cari akun dan atur status aktif atau nonaktif. Total ' . array_sum($hitung) . ' akun: ' . ($hitung['klien'] ?? 0) . ' klien, ' . ($hitung['pengacara'] ?? 0) . ' pengacara, ' . ($hitung['admin'] ?? 0) . ' admin.'); ?>

  <form class="ad-filter" method="get">
    <div class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label" for="u-q">Cari</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span>
          <input class="form-control" id="u-q" name="q" type="search" value="<?= e($q) ?>" placeholder="Nama atau email"></div>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="u-peran">Peran</label>
        <select class="form-select" id="u-peran" name="peran">
          <option value="">Semua</option>
          <?php foreach (['klien' => 'Klien', 'pengacara' => 'Pengacara', 'admin' => 'Admin'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $peran === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="u-status">Status</label>
        <select class="form-select" id="u-status" name="status">
          <option value="">Semua</option>
          <?php foreach (['aktif' => 'Aktif', 'menunggu' => 'Menunggu', 'nonaktif' => 'Nonaktif', 'ditolak' => 'Ditolak'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $status === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2 d-grid"><button class="btn btn-navy" type="submit"><i class="bi bi-funnel"></i> Terapkan</button></div>
      <div class="col-6 col-md-1 d-grid"><a class="btn btn-outline-secondary" href="<?= e(url('admin/pengguna.php')) ?>" title="Hapus filter"><i class="bi bi-x-lg"></i></a></div>
    </div>
  </form>

  <div class="ad-card">
    <?php if (!$users): ?>
      <div class="ad-kosong"><i class="bi bi-person-x"></i>Tidak ada akun yang cocok dengan filter.</div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table ad-table">
        <thead><tr><th>Pengguna</th><th>Peran</th><th>Status</th><th>Terdaftar</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="ad-ava ad-ava-<?= ui_warna($u['nama']) ?>"><?= e(ui_inisial($u['nama'])) ?></span>
                <div><strong><?= e($u['nama']) ?></strong><span class="ad-sub"><?= e($u['email']) ?></span></div>
              </div>
            </td>
            <td><?= admin_pil($u['role']) ?></td>
            <td><?= admin_pil($u['status']) ?></td>
            <td><?= e(tgl($u['created_at'])) ?></td>
            <td class="text-end">
              <?php if ((int) $u['id'] === $saya): ?>
                <span class="text-muted small">Akun kamu</span>
              <?php else: ?>
                <form method="post" class="d-inline"
                      data-confirm="<?= $u['status'] === 'aktif' ? 'Akun ' . e($u['nama']) . ' tidak akan bisa masuk sampai diaktifkan lagi.' : 'Akun ' . e($u['nama']) . ' akan bisa masuk dan dipakai kembali.' ?>"
                      data-confirm-judul="<?= $u['status'] === 'aktif' ? 'Nonaktifkan akun?' : 'Aktifkan akun?' ?>"
                      data-confirm-ok="<?= $u['status'] === 'aktif' ? 'Ya, nonaktifkan' : 'Ya, aktifkan' ?>"
                      <?= $u['status'] === 'aktif' ? 'data-confirm-tipe="danger"' : '' ?>>
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                  <input type="hidden" name="aksi" value="<?= $u['status'] === 'aktif' ? 'nonaktif' : 'aktif' ?>">
                  <?php foreach ($filterAktif as $k => $v): ?><input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>"><?php endforeach; ?>
                  <?php if ($u['status'] === 'aktif'): ?>
                    <button class="btn btn-outline-danger btn-ad" type="submit">Nonaktifkan</button>
                  <?php else: ?>
                    <button class="btn btn-outline-success btn-ad" type="submit">Aktifkan</button>
                  <?php endif; ?>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
    <span class="text-muted small">Menampilkan <?= count($users) ?> dari <?= $total ?> akun</span>
    <div><?= art_pagination($hal, $totalHal, 'admin/pengguna.php', ['q' => $q, 'peran' => $peran, 'status' => $status]) ?></div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
