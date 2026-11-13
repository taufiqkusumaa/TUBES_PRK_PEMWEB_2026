<?php
// MODUL ADMIN (Linda): form tulis / ubah artikel.
// Ditulis ulang oleh Rafi (versi sebelumnya memakai kolom "konten" yang tidak ada; kolom yang benar: isi, ringkasan, bidang_id).
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_role('admin');

$pdo = db();
$id  = (int) ($_GET['id'] ?? 0);

$data = ['judul' => '', 'bidang_id' => '', 'ringkasan' => '', 'isi' => '', 'status' => 'draft', 'slug' => ''];
if ($id > 0) {
    $st = $pdo->prepare('SELECT judul, bidang_id, ringkasan, isi, status, slug FROM artikel WHERE id = ?');
    $st->execute([$id]);
    $ada = $st->fetch();
    if (!$ada) {
        flash('warning', 'Artikel tidak ditemukan.');
        redirect('admin/artikel.php');
    }
    $data = $ada;
}

$galat = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        redirect('admin/artikel_form.php' . ($id ? '?id=' . $id : ''));
    }
    $data['judul']     = trim((string) ($_POST['judul'] ?? ''));
    $data['bidang_id'] = (int) ($_POST['bidang_id'] ?? 0);
    $data['ringkasan'] = trim((string) ($_POST['ringkasan'] ?? ''));
    $data['isi']       = trim((string) ($_POST['isi'] ?? ''));
    $data['status']    = in_array($_POST['status'] ?? '', ['draft', 'terbit'], true) ? $_POST['status'] : 'draft';

    if (mb_strlen($data['judul']) < 5) {
        $galat['judul'] = 'Judul minimal 5 karakter.';
    } elseif (mb_strlen($data['judul']) > 180) {
        $galat['judul'] = 'Judul maksimal 180 karakter.';
    }
    if ($data['bidang_id'] > 0) {
        $cek = $pdo->prepare('SELECT COUNT(*) FROM bidang_hukum WHERE id = ?');
        $cek->execute([$data['bidang_id']]);
        if ((int) $cek->fetchColumn() === 0) {
            $galat['bidang_id'] = 'Bidang hukum tidak valid.';
        }
    }
    if (mb_strlen($data['ringkasan']) > 300) {
        $galat['ringkasan'] = 'Ringkasan maksimal 300 karakter.';
    }
    if (mb_strlen($data['isi']) < 50) {
        $galat['isi'] = 'Isi artikel minimal 50 karakter.';
    }

    if (!$galat) {
        $bidang = $data['bidang_id'] > 0 ? $data['bidang_id'] : null;
        $ringkasan = $data['ringkasan'] !== '' ? $data['ringkasan'] : art_cuplikan($data['isi'], 200);
        if ($id > 0) {
            // Slug tidak diubah saat ubah artikel, supaya tautan lama tetap berfungsi.
            $pdo->prepare('UPDATE artikel SET judul = ?, bidang_id = ?, ringkasan = ?, isi = ?, status = ? WHERE id = ?')
                ->execute([$data['judul'], $bidang, $ringkasan, $data['isi'], $data['status'], $id]);
            flash('success', 'Artikel berhasil diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO artikel (penulis_id, bidang_id, judul, slug, ringkasan, isi, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([(int) current_user()['id'], $bidang, $data['judul'], art_slug($pdo, $data['judul']), $ringkasan, $data['isi'], $data['status']]);
            flash('success', $data['status'] === 'terbit' ? 'Artikel baru diterbitkan.' : 'Artikel disimpan sebagai draft.');
        }
        redirect('admin/artikel.php');
    }
    flash('danger', 'Artikel belum bisa disimpan. Periksa kolom yang ditandai.');
}

$bidangList = $pdo->query('SELECT id, nama FROM bidang_hukum ORDER BY nama')->fetchAll();
$judulHal   = $id ? 'Ubah Artikel' : 'Tulis Artikel Baru';

$judul = $judulHal;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container ad-wrap">
  <?php admin_kepala('artikel', $judulHal, 'Tulis dengan bahasa yang mudah dipahami orang awam. Artikel baru sebaiknya disimpan sebagai draft dulu, lalu diterbitkan setelah dibaca ulang.'); ?>

  <form method="post" class="row g-4" novalidate>
    <?= csrf_field() ?>
    <div class="col-lg-8">
      <div class="ad-card"><div class="ad-card-b">
        <div class="mb-3">
          <label class="form-label fw-semibold" for="judul">Judul artikel</label>
          <input class="form-control <?= isset($galat['judul']) ? 'is-invalid' : '' ?>" id="judul" name="judul" maxlength="180" required
                 value="<?= e($data['judul']) ?>" placeholder="Contoh: Hak Pekerja Saat Terkena PHK" data-pesan-wajib="Judul artikel wajib diisi.">
          <?php if (isset($galat['judul'])): ?><div class="invalid-feedback d-block"><?= e($galat['judul']) ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" for="ringkasan">Ringkasan <span class="text-muted fw-normal">(tampil di kartu artikel)</span></label>
          <textarea class="form-control <?= isset($galat['ringkasan']) ? 'is-invalid' : '' ?>" id="ringkasan" name="ringkasan" rows="2" maxlength="300"
                    placeholder="Satu atau dua kalimat. Kosongkan jika ingin dibuat otomatis dari isi artikel."><?= e($data['ringkasan']) ?></textarea>
          <div class="ad-hitung"><span id="hitung">0</span>/300</div>
          <?php if (isset($galat['ringkasan'])): ?><div class="invalid-feedback d-block"><?= e($galat['ringkasan']) ?></div><?php endif; ?>
        </div>
        <div>
          <label class="form-label fw-semibold" for="isi">Isi artikel</label>
          <textarea class="form-control <?= isset($galat['isi']) ? 'is-invalid' : '' ?>" id="isi" name="isi" rows="16" required
                    data-pesan-wajib="Isi artikel wajib diisi."><?= e($data['isi']) ?></textarea>
          <?php if (isset($galat['isi'])): ?><div class="invalid-feedback d-block"><?= e($galat['isi']) ?></div><?php endif; ?>
        </div>
      </div></div>
    </div>

    <div class="col-lg-4">
      <div class="ad-card mb-3"><div class="ad-card-b">
        <div class="mb-3">
          <label class="form-label fw-semibold" for="bidang_id">Bidang hukum</label>
          <select class="form-select <?= isset($galat['bidang_id']) ? 'is-invalid' : '' ?>" id="bidang_id" name="bidang_id">
            <option value="0">Umum (tanpa bidang)</option>
            <?php foreach ($bidangList as $b): ?>
              <option value="<?= (int) $b['id'] ?>" <?= (int) $data['bidang_id'] === (int) $b['id'] ? 'selected' : '' ?>><?= e($b['nama']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($galat['bidang_id'])): ?><div class="invalid-feedback d-block"><?= e($galat['bidang_id']) ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" for="status">Status</label>
          <select class="form-select" id="status" name="status">
            <option value="draft" <?= $data['status'] === 'draft' ? 'selected' : '' ?>>Draft (belum terlihat pengunjung)</option>
            <option value="terbit" <?= $data['status'] === 'terbit' ? 'selected' : '' ?>>Terbit (tampil di halaman Artikel)</option>
          </select>
        </div>
        <div class="d-grid gap-2">
          <button class="btn btn-navy" type="submit"><i class="bi bi-check2-circle"></i> Simpan artikel</button>
          <a class="btn btn-outline-secondary" href="<?= e(url('admin/artikel.php')) ?>">Batal</a>
        </div>
      </div></div>

      <div class="ad-hint">
        <strong class="d-block mb-1"><i class="bi bi-magic"></i> Cara menata tulisan</strong>
        Pisahkan paragraf dengan satu baris kosong.<br>
        <code>## Subjudul</code> untuk judul bagian.<br>
        <code>- butir</code> untuk daftar poin.<br>
        <code>1. langkah</code> untuk daftar bernomor.<br>
        <code>**tebal**</code> untuk teks tebal.
      </div>
    </div>
  </form>
</div>

<script>
(function () {
  var r = document.getElementById('ringkasan'), h = document.getElementById('hitung');
  function hitung() { h.textContent = r.value.length; }
  r.addEventListener('input', hitung); hitung();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
