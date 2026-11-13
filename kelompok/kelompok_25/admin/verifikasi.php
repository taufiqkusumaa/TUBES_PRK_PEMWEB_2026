<?php
// MODUL ADMIN: verifikasi pendaftaran pengacara (setujui / tolak).
// Ditulis ulang oleh Rafi (versi sebelumnya memakai $pdo, verify_csrf(), set_flash() dan SQL yang salah ketik).
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_role('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        redirect('admin/verifikasi.php');
    }
    $id   = (int) ($_POST['user_id'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';
    if ($id > 0 && in_array($aksi, ['setujui', 'tolak'], true)) {
        $baru = $aksi === 'setujui' ? 'aktif' : 'ditolak';
        // Hanya pengacara yang masih menunggu (atau yang sebelumnya ditolak) yang boleh diproses
        $st = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'pengacara' AND status IN ('menunggu', 'ditolak') AND status <> ?");
        $st->execute([$baru, $id, $baru]);
        if ($st->rowCount() > 0) {
            flash('success', $aksi === 'setujui' ? 'Pengacara disetujui. Akunnya kini aktif dan tampil di daftar pengacara.' : 'Pendaftaran pengacara ditolak.');
        } else {
            flash('warning', 'Pendaftaran ini sudah diproses sebelumnya.');
        }
    }
    redirect('admin/verifikasi.php');
}

$ambil = function (string $status) use ($pdo): array {
    $st = $pdo->prepare(
        "SELECT u.id, u.nama, u.email, u.telepon, u.created_at,
                p.no_izin, p.pengalaman_tahun, p.kota, p.bio, b.nama AS bidang
         FROM users u
         LEFT JOIN profil_pengacara p ON p.user_id = u.id
         LEFT JOIN bidang_hukum b ON b.id = p.bidang_id
         WHERE u.role = 'pengacara' AND u.status = ?
         ORDER BY u.created_at DESC, u.id DESC"
    );
    $st->execute([$status]);
    return $st->fetchAll();
};
$menunggu = $ambil('menunggu');
$ditolak  = $ambil('ditolak');
$aktif    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'pengacara' AND status = 'aktif'")->fetchColumn();

/** Satu tombol aksi (form kecil dengan jendela konfirmasi). */
function tombol_verifikasi(array $c, string $aksi): string
{
    $setujui = $aksi === 'setujui';
    ob_start(); ?>
    <form method="post" class="d-inline"
          data-confirm="<?= $setujui ? e($c['nama']) . ' akan tampil di daftar pengacara dan bisa menjawab konsultasi.' : 'Pendaftaran ' . e($c['nama']) . ' akan ditolak.' ?>"
          data-confirm-judul="<?= $setujui ? 'Setujui pengacara?' : 'Tolak pendaftaran?' ?>"
          data-confirm-ok="<?= $setujui ? 'Ya, setujui' : 'Ya, tolak' ?>"
          <?= $setujui ? '' : 'data-confirm-tipe="danger"' ?>>
      <?= csrf_field() ?>
      <input type="hidden" name="user_id" value="<?= (int) $c['id'] ?>">
      <input type="hidden" name="aksi" value="<?= $aksi ?>">
      <button class="btn <?= $setujui ? 'btn-navy' : 'btn-outline-danger' ?> btn-ad" type="submit">
        <i class="bi <?= $setujui ? 'bi-check-lg' : 'bi-x-lg' ?>"></i> <?= $setujui ? 'Setujui' : 'Tolak' ?>
      </button>
    </form>
    <?php
    return (string) ob_get_clean();
}

$judul = 'Verifikasi Pengacara';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container ad-wrap">
  <?php admin_kepala('verifikasi', 'Verifikasi Pengacara', 'Periksa data dan nomor izin advokat sebelum menyetujui. Pengacara yang disetujui langsung tampil di daftar pengacara.'); ?>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4"><div class="ad-stat"><span class="ikon ik-1"><i class="bi bi-hourglass-split"></i></span><div><div class="angka"><?= count($menunggu) ?></div><div class="label">Menunggu verifikasi</div></div></div></div>
    <div class="col-6 col-md-4"><div class="ad-stat"><span class="ikon ik-2"><i class="bi bi-patch-check"></i></span><div><div class="angka"><?= $aktif ?></div><div class="label">Pengacara aktif</div></div></div></div>
    <div class="col-12 col-md-4"><div class="ad-stat"><span class="ikon ik-3"><i class="bi bi-x-octagon"></i></span><div><div class="angka"><?= count($ditolak) ?></div><div class="label">Pernah ditolak</div></div></div></div>
  </div>

  <?php if (!$menunggu): ?>
    <div class="ad-card"><div class="ad-kosong"><i class="bi bi-check2-circle"></i>Tidak ada pendaftaran yang menunggu. Semua sudah diproses.</div></div>
  <?php endif; ?>

  <?php foreach ($menunggu as $c): ?>
    <div class="ad-card mb-3">
      <div class="ad-card-b">
        <div class="ad-calon">
          <span class="ad-ava ad-ava-<?= ui_warna($c['nama']) ?>" style="width:3.4rem;height:3.4rem;font-size:1.2rem"><?= e(ui_inisial($c['nama'])) ?></span>
          <div class="isi">
            <h3><?= e($c['nama']) ?> <?= admin_pil('menunggu') ?></h3>
            <div class="text-muted small"><?= e($c['email']) ?><?= $c['telepon'] ? ' &middot; ' . e($c['telepon']) : '' ?> &middot; mendaftar <?= e(tgl($c['created_at'])) ?></div>
            <div class="ad-fakta">
              <span><i class="bi bi-bank2"></i>Bidang: <strong><?= e($c['bidang'] ?? '-') ?></strong></span>
              <span><i class="bi bi-card-text"></i>No. izin: <strong><?= e($c['no_izin'] ?? '-') ?></strong></span>
              <?php if ($c['kota']): ?><span><i class="bi bi-geo-alt"></i><?= e($c['kota']) ?></span><?php endif; ?>
              <?php if ($c['pengalaman_tahun']): ?><span><i class="bi bi-briefcase"></i><?= (int) $c['pengalaman_tahun'] ?> tahun pengalaman</span><?php endif; ?>
            </div>
            <?php if (trim((string) $c['bio']) !== ''): ?><div class="ad-bio"><?= e($c['bio']) ?></div><?php endif; ?>
          </div>
          <div class="ad-aksi"><?= tombol_verifikasi($c, 'setujui') ?> <?= tombol_verifikasi($c, 'tolak') ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($ditolak): ?>
    <div class="ad-card mt-4">
      <div class="ad-card-h"><h3>Pendaftaran yang ditolak</h3><span class="text-muted small">Bisa disetujui kembali jika data sudah dilengkapi</span></div>
      <div class="table-responsive">
        <table class="table ad-table">
          <thead><tr><th>Nama</th><th>Bidang</th><th>No. izin</th><th class="text-end">Aksi</th></tr></thead>
          <tbody>
          <?php foreach ($ditolak as $c): ?>
            <tr>
              <td><strong><?= e($c['nama']) ?></strong><span class="ad-sub"><?= e($c['email']) ?></span></td>
              <td><?= e($c['bidang'] ?? '-') ?></td>
              <td><?= e($c['no_izin'] ?? '-') ?></td>
              <td class="text-end"><?= tombol_verifikasi($c, 'setujui') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
