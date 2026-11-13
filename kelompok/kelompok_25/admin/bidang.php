<?php
// MODUL ADMIN: kelola bidang hukum (kategori). Tambah, ubah nama/deskripsi, hapus bila belum dipakai.
// Bidang yang sudah dipakai pengacara, konsultasi, atau artikel tidak bisa dihapus agar data tetap utuh.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_role('admin');

$pdo  = db();
$edit = (int) ($_GET['edit'] ?? 0);

$data  = ['nama' => '', 'deskripsi' => ''];
$galat = [];

if ($edit > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $st = $pdo->prepare('SELECT nama, deskripsi FROM bidang_hukum WHERE id = ?');
    $st->execute([$edit]);
    $b = $st->fetch();
    if (!$b) {
        flash('warning', 'Bidang hukum tidak ditemukan.');
        redirect('admin/bidang.php');
    }
    $data = ['nama' => $b['nama'], 'deskripsi' => (string) $b['deskripsi']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        redirect('admin/bidang.php');
    }
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($aksi === 'hapus' && $id > 0) {
        $pakai = $pdo->prepare(
            'SELECT (SELECT COUNT(*) FROM profil_pengacara WHERE bidang_id = :a)
                  + (SELECT COUNT(*) FROM konsultasi WHERE bidang_id = :b)
                  + (SELECT COUNT(*) FROM artikel WHERE bidang_id = :c)'
        );
        $pakai->execute([':a' => $id, ':b' => $id, ':c' => $id]);
        if ((int) $pakai->fetchColumn() > 0) {
            flash('danger', 'Bidang ini masih dipakai oleh pengacara, konsultasi, atau artikel, sehingga tidak bisa dihapus.');
        } else {
            $pdo->prepare('DELETE FROM bidang_hukum WHERE id = ?')->execute([$id]);
            flash('success', 'Bidang hukum dihapus.');
        }
        redirect('admin/bidang.php');
    }

    if ($aksi === 'simpan') {
        $edit = $id;
        $data = [
            'nama'      => trim((string) ($_POST['nama'] ?? '')),
            'deskripsi' => trim((string) ($_POST['deskripsi'] ?? '')),
        ];
        if (mb_strlen($data['nama']) < 3 || mb_strlen($data['nama']) > 80) {
            $galat['nama'] = 'Nama bidang harus 3 sampai 80 karakter.';
        } else {
            $dobel = $pdo->prepare('SELECT COUNT(*) FROM bidang_hukum WHERE nama = ? AND id <> ?');
            $dobel->execute([$data['nama'], $id]);
            if ((int) $dobel->fetchColumn() > 0) {
                $galat['nama'] = 'Nama bidang ini sudah ada.';
            }
        }
        if (mb_strlen($data['deskripsi']) > 255) {
            $galat['deskripsi'] = 'Deskripsi maksimal 255 karakter.';
        }

        if (!$galat) {
            $deskripsi = $data['deskripsi'] !== '' ? $data['deskripsi'] : null;
            if ($id > 0) {
                $pdo->prepare('UPDATE bidang_hukum SET nama = ?, deskripsi = ? WHERE id = ?')->execute([$data['nama'], $deskripsi, $id]);
                flash('success', 'Bidang hukum diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO bidang_hukum (nama, deskripsi) VALUES (?, ?)')->execute([$data['nama'], $deskripsi]);
                flash('success', 'Bidang hukum baru ditambahkan.');
            }
            redirect('admin/bidang.php');
        }
        flash('danger', 'Bidang hukum belum bisa disimpan. Periksa kolom yang ditandai.');
    }
}

$daftar = $pdo->query(
    'SELECT b.id, b.nama, b.deskripsi,
            (SELECT COUNT(*) FROM profil_pengacara p WHERE p.bidang_id = b.id) AS pengacara,
            (SELECT COUNT(*) FROM konsultasi k WHERE k.bidang_id = b.id) AS konsultasi,
            (SELECT COUNT(*) FROM artikel a WHERE a.bidang_id = b.id) AS artikel
     FROM bidang_hukum b ORDER BY b.id'
)->fetchAll();

$judul = 'Kelola Bidang Hukum';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container ad-wrap">
  <?php admin_kepala('bidang', 'Kelola Bidang Hukum', count($daftar) . ' bidang hukum. Bidang menentukan pilihan saat klien mengajukan konsultasi dan saat pengacara mendaftar.'); ?>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="ad-card" id="form-bidang">
        <div class="ad-card-h"><h2><?= $edit ? 'Ubah bidang' : 'Tambah bidang' ?></h2>
          <?php if ($edit): ?><a class="btn btn-outline-secondary btn-ad" href="<?= e(url('admin/bidang.php')) ?>">Batal</a><?php endif; ?></div>
        <div class="ad-card-b">
          <form method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id" value="<?= (int) $edit ?>">
            <div class="mb-3">
              <label class="form-label fw-semibold" for="nama">Nama bidang</label>
              <input class="form-control <?= isset($galat['nama']) ? 'is-invalid' : '' ?>" id="nama" name="nama" maxlength="80" required
                     value="<?= e($data['nama']) ?>" placeholder="Contoh: Hukum Pajak" data-pesan-wajib="Nama bidang wajib diisi.">
              <?php if (isset($galat['nama'])): ?><div class="invalid-feedback d-block"><?= e($galat['nama']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" for="deskripsi">Deskripsi singkat</label>
              <textarea class="form-control <?= isset($galat['deskripsi']) ? 'is-invalid' : '' ?>" id="deskripsi" name="deskripsi" rows="3" maxlength="255"
                        placeholder="Contoh: Sengketa pajak, keberatan, dan banding"><?= e($data['deskripsi']) ?></textarea>
              <?php if (isset($galat['deskripsi'])): ?><div class="invalid-feedback d-block"><?= e($galat['deskripsi']) ?></div><?php endif; ?>
            </div>
            <button class="btn btn-navy w-100" type="submit"><i class="bi bi-check2-circle"></i> <?= $edit ? 'Simpan perubahan' : 'Tambah bidang' ?></button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="ad-card">
        <div class="table-responsive">
          <table class="table ad-table">
            <thead><tr><th>Bidang</th><th class="text-center">Pengacara</th><th class="text-center">Konsultasi</th><th class="text-center">Artikel</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $b): ?>
              <?php $dipakai = (int) $b['pengacara'] + (int) $b['konsultasi'] + (int) $b['artikel']; ?>
              <tr>
                <td><strong><?= e($b['nama']) ?></strong><span class="ad-sub"><?= e($b['deskripsi'] ?? '') ?></span></td>
                <td class="text-center"><?= (int) $b['pengacara'] ?></td>
                <td class="text-center"><?= (int) $b['konsultasi'] ?></td>
                <td class="text-center"><?= (int) $b['artikel'] ?></td>
                <td class="text-end">
                  <span class="ad-aksi">
                    <a class="btn btn-outline-secondary btn-ad" href="<?= e(url('admin/bidang.php?edit=' . (int) $b['id'])) ?>#form-bidang">Ubah</a>
                    <?php if ($dipakai > 0): ?>
                      <button class="btn btn-outline-secondary btn-ad" type="button" disabled title="Masih dipakai, tidak bisa dihapus"><i class="bi bi-lock"></i></button>
                    <?php else: ?>
                      <form method="post" data-confirm="Bidang &quot;<?= e($b['nama']) ?>&quot; akan dihapus." data-confirm-judul="Hapus bidang?" data-confirm-ok="Ya, hapus" data-confirm-tipe="danger">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                        <button class="btn btn-outline-danger btn-ad" type="submit" title="Hapus"><i class="bi bi-trash"></i></button>
                      </form>
                    <?php endif; ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <p class="text-muted small mt-2"><i class="bi bi-lock"></i> Bidang yang sudah dipakai pengacara, konsultasi, atau artikel dikunci agar data tidak rusak.</p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
