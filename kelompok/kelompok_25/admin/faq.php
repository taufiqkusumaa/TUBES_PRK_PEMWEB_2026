<?php
// MODUL ADMIN: kelola FAQ (tambah, ubah, hapus). Pertanyaan tampil di halaman faq.php.
// Kategori disimpan di kolom "urutan" (urutan = nomor kategori x 100 + nomor urut), jadi tidak perlu mengubah schema.sql.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_role('admin');

$pdo      = db();
$kategori = faq_kategori();
$edit     = (int) ($_GET['edit'] ?? 0);

$data  = ['kategori' => 1, 'nomor' => '', 'pertanyaan' => '', 'jawaban' => ''];
$galat = [];

// ---- Muat data untuk diubah ----
if ($edit > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $st = $pdo->prepare('SELECT pertanyaan, jawaban, urutan FROM faq WHERE id = ?');
    $st->execute([$edit]);
    $f = $st->fetch();
    if (!$f) {
        flash('warning', 'Pertanyaan tidak ditemukan.');
        redirect('admin/faq.php');
    }
    $data = ['kategori' => faq_kat_id((int) $f['urutan']), 'nomor' => (int) $f['urutan'] % 100 ?: '', 'pertanyaan' => $f['pertanyaan'], 'jawaban' => $f['jawaban']];
}

// ---- Aksi ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        redirect('admin/faq.php');
    }
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($aksi === 'hapus' && $id > 0) {
        $pdo->prepare('DELETE FROM faq WHERE id = ?')->execute([$id]);
        flash('success', 'Pertanyaan dihapus.');
        redirect('admin/faq.php');
    }

    if ($aksi === 'simpan') {
        $edit = $id;
        $data = [
            'kategori'   => (int) ($_POST['kategori'] ?? 1),
            'nomor'      => trim((string) ($_POST['nomor'] ?? '')),
            'pertanyaan' => trim((string) ($_POST['pertanyaan'] ?? '')),
            'jawaban'    => trim((string) ($_POST['jawaban'] ?? '')),
        ];
        if (!isset($kategori[$data['kategori']])) {
            $galat['kategori'] = 'Pilih kategori yang tersedia.';
        }
        if (mb_strlen($data['pertanyaan']) < 5 || mb_strlen($data['pertanyaan']) > 255) {
            $galat['pertanyaan'] = 'Pertanyaan harus 5 sampai 255 karakter.';
        }
        if (mb_strlen($data['jawaban']) < 10) {
            $galat['jawaban'] = 'Jawaban minimal 10 karakter.';
        }
        if ($data['nomor'] !== '' && (!ctype_digit($data['nomor']) || (int) $data['nomor'] < 1 || (int) $data['nomor'] > 99)) {
            $galat['nomor'] = 'Nomor urut harus angka 1 sampai 99.';
        }

        if (!$galat) {
            $dasar = $data['kategori'] * 100;
            if ($data['nomor'] === '') {
                // Kosong = taruh di akhir kategori
                $st = $pdo->prepare('SELECT MAX(urutan) FROM faq WHERE urutan > ? AND urutan < ?');
                $st->execute([$dasar, $dasar + 100]);
                $nomor = min(99, ((int) $st->fetchColumn() ?: $dasar) - $dasar + 1);
            } else {
                $nomor = (int) $data['nomor'];
            }
            $urutan = $dasar + $nomor;
            if ($id > 0) {
                $pdo->prepare('UPDATE faq SET pertanyaan = ?, jawaban = ?, urutan = ? WHERE id = ?')
                    ->execute([$data['pertanyaan'], $data['jawaban'], $urutan, $id]);
                flash('success', 'Pertanyaan berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO faq (pertanyaan, jawaban, urutan) VALUES (?, ?, ?)')
                    ->execute([$data['pertanyaan'], $data['jawaban'], $urutan]);
                flash('success', 'Pertanyaan baru ditambahkan.');
            }
            redirect('admin/faq.php');
        }
        flash('danger', 'Pertanyaan belum bisa disimpan. Periksa kolom yang ditandai.');
    }
}

$grup = [];
foreach ($pdo->query('SELECT id, pertanyaan, jawaban, urutan FROM faq ORDER BY urutan, id')->fetchAll() as $f) {
    $grup[faq_kat_id((int) $f['urutan'])][] = $f;
}
ksort($grup);
$total = array_sum(array_map('count', $grup));

$judul = 'Kelola FAQ';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container ad-wrap">
  <?php admin_kepala('faq', 'Kelola FAQ', $total . ' pertanyaan tampil di halaman FAQ publik, dikelompokkan per kategori.'); ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="ad-card" id="form-faq">
        <div class="ad-card-h"><h2><?= $edit ? 'Ubah pertanyaan' : 'Tambah pertanyaan' ?></h2>
          <?php if ($edit): ?><a class="btn btn-outline-secondary btn-ad" href="<?= e(url('admin/faq.php')) ?>">Batal</a><?php endif; ?></div>
        <div class="ad-card-b">
          <form method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id" value="<?= (int) $edit ?>">
            <div class="row g-2 mb-3">
              <div class="col-8">
                <label class="form-label fw-semibold" for="kategori">Kategori</label>
                <select class="form-select <?= isset($galat['kategori']) ? 'is-invalid' : '' ?>" id="kategori" name="kategori">
                  <?php foreach ($kategori as $k => [$nama]): ?>
                    <option value="<?= $k ?>" <?= (int) $data['kategori'] === $k ? 'selected' : '' ?>><?= e($nama) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($galat['kategori'])): ?><div class="invalid-feedback d-block"><?= e($galat['kategori']) ?></div><?php endif; ?>
              </div>
              <div class="col-4">
                <label class="form-label fw-semibold" for="nomor">Urutan</label>
                <input class="form-control <?= isset($galat['nomor']) ? 'is-invalid' : '' ?>" id="nomor" name="nomor" inputmode="numeric" maxlength="2" value="<?= e($data['nomor']) ?>" placeholder="otomatis">
                <?php if (isset($galat['nomor'])): ?><div class="invalid-feedback d-block"><?= e($galat['nomor']) ?></div><?php endif; ?>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" for="pertanyaan">Pertanyaan</label>
              <input class="form-control <?= isset($galat['pertanyaan']) ? 'is-invalid' : '' ?>" id="pertanyaan" name="pertanyaan" maxlength="255" required
                     value="<?= e($data['pertanyaan']) ?>" data-pesan-wajib="Pertanyaan wajib diisi.">
              <?php if (isset($galat['pertanyaan'])): ?><div class="invalid-feedback d-block"><?= e($galat['pertanyaan']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" for="jawaban">Jawaban</label>
              <textarea class="form-control <?= isset($galat['jawaban']) ? 'is-invalid' : '' ?>" id="jawaban" name="jawaban" rows="6" required
                        data-pesan-wajib="Jawaban wajib diisi."><?= e($data['jawaban']) ?></textarea>
              <?php if (isset($galat['jawaban'])): ?><div class="invalid-feedback d-block"><?= e($galat['jawaban']) ?></div><?php endif; ?>
            </div>
            <button class="btn btn-navy w-100" type="submit"><i class="bi bi-check2-circle"></i> <?= $edit ? 'Simpan perubahan' : 'Tambah pertanyaan' ?></button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <?php if (!$grup): ?>
        <div class="ad-card"><div class="ad-kosong"><i class="bi bi-question-circle"></i>Belum ada pertanyaan. Tambahkan lewat formulir di samping.</div></div>
      <?php endif; ?>
      <?php foreach ($grup as $k => $daftar): ?>
        <div class="ad-card mb-3">
          <div class="ad-card-h"><h3><i class="bi <?= e($kategori[$k][1]) ?> text-warning"></i> <?= e($kategori[$k][0]) ?></h3><span class="text-muted small"><?= count($daftar) ?> pertanyaan</span></div>
          <div class="table-responsive">
            <table class="table ad-table">
              <tbody>
              <?php foreach ($daftar as $f): ?>
                <tr>
                  <td style="width:2.5rem" class="text-muted"><?= (int) $f['urutan'] % 100 ?></td>
                  <td><strong><?= e($f['pertanyaan']) ?></strong><span class="ad-sub"><?= e(mb_strimwidth($f['jawaban'], 0, 110, '...')) ?></span></td>
                  <td class="text-end" style="width:9rem">
                    <span class="ad-aksi">
                      <a class="btn btn-outline-secondary btn-ad" href="<?= e(url('admin/faq.php?edit=' . (int) $f['id'])) ?>#form-faq">Ubah</a>
                      <form method="post" data-confirm="Pertanyaan ini akan dihapus dari halaman FAQ." data-confirm-judul="Hapus pertanyaan?" data-confirm-ok="Ya, hapus" data-confirm-tipe="danger">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                        <button class="btn btn-outline-danger btn-ad" type="submit" title="Hapus"><i class="bi bi-trash"></i></button>
                      </form>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
