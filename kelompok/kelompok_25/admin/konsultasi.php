<?php
// MODUL ADMIN: moderasi konsultasi. Daftar semua konsultasi, baca percakapan, dan tutup bila perlu.
// Konsultasi berstatus "ditutup" tidak bisa dibalas lagi oleh klien maupun pengacara.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_role('admin');

$pdo = db();
$kunciFilter = ['q', 'status', 'bidang', 'hal'];

// ---- Aksi: tutup konsultasi ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dariDetail = (int) ($_POST['dari_detail'] ?? 0);
    $balik = $dariDetail > 0 ? 'admin/konsultasi.php?id=' . $dariDetail : admin_kembali('admin/konsultasi.php', $_POST, $kunciFilter);
    if (!csrf_valid()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        redirect($balik);
    }
    $id = (int) ($_POST['konsultasi_id'] ?? 0);
    if ($id > 0 && ($_POST['aksi'] ?? '') === 'tutup') {
        $st = $pdo->prepare("UPDATE konsultasi SET status = 'ditutup' WHERE id = ? AND status IN ('menunggu', 'dijawab')");
        $st->execute([$id]);
        flash($st->rowCount() > 0 ? 'success' : 'warning', $st->rowCount() > 0 ? 'Konsultasi berhasil ditutup.' : 'Konsultasi ini sudah selesai atau sudah ditutup.');
    }
    redirect($balik);
}

// ---- Tampilan detail satu konsultasi ----
$idDetail = (int) ($_GET['id'] ?? 0);
if ($idDetail > 0) {
    $st = $pdo->prepare(
        'SELECT k.id, k.judul, k.isi, k.status, k.created_at,
                uk.nama AS klien, uk.email AS klien_email, up.nama AS pengacara, b.nama AS bidang
         FROM konsultasi k
         JOIN users uk ON uk.id = k.klien_id
         LEFT JOIN users up ON up.id = k.pengacara_id
         JOIN bidang_hukum b ON b.id = k.bidang_id
         WHERE k.id = ?'
    );
    $st->execute([$idDetail]);
    $k = $st->fetch();
    if (!$k) {
        flash('warning', 'Konsultasi tidak ditemukan.');
        redirect('admin/konsultasi.php');
    }
    $st = $pdo->prepare(
        'SELECT bl.isi, bl.created_at, u.nama, u.role
         FROM balasan bl JOIN users u ON u.id = bl.pengirim_id
         WHERE bl.konsultasi_id = ? ORDER BY bl.created_at, bl.id'
    );
    $st->execute([$idDetail]);
    $balasan = $st->fetchAll();

    $judul = 'Detail Konsultasi';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="container ad-wrap">
      <?php admin_kepala('konsultasi', 'Detail Konsultasi', 'Mode baca untuk moderasi. Admin tidak ikut membalas percakapan.'); ?>
      <a class="btn btn-outline-secondary btn-sm mb-3" href="<?= e(url('admin/konsultasi.php')) ?>"><i class="bi bi-arrow-left"></i> Semua konsultasi</a>

      <div class="ad-card mb-4">
        <div class="ad-card-h">
          <h2><?= e($k['judul']) ?></h2>
          <?= admin_pil_konsultasi($k['status']) ?>
        </div>
        <div class="ad-card-b">
          <div class="ad-fakta mt-0">
            <span><i class="bi bi-person"></i>Klien: <strong><?= e($k['klien']) ?></strong> (<?= e($k['klien_email']) ?>)</span>
            <span><i class="bi bi-briefcase"></i>Pengacara: <strong><?= e($k['pengacara'] ?? 'Belum ada') ?></strong></span>
            <span><i class="bi bi-bank2"></i><?= e($k['bidang']) ?></span>
            <span><i class="bi bi-calendar3"></i><?= e(tgl($k['created_at'])) ?></span>
          </div>
          <div class="ad-bio mb-4"><?= nl2br(e($k['isi'])) ?></div>

          <h3 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.06em">Percakapan (<?= count($balasan) ?> balasan)</h3>
          <?php if (!$balasan): ?>
            <p class="text-muted mb-0">Belum ada balasan dari pengacara.</p>
          <?php else: ?>
            <div class="ad-chat">
              <?php foreach ($balasan as $b): ?>
                <div class="ad-pesan <?= e($b['role']) ?>">
                  <span class="siapa"><?= e($b['nama']) ?> &middot; <?= e(ucfirst($b['role'])) ?> &middot; <?= e(tgl($b['created_at'])) ?></span>
                  <?= nl2br(e($b['isi'])) ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <?php if (in_array($k['status'], ['menunggu', 'dijawab'], true)): ?>
          <div class="ad-card-h" style="border-top:1px solid var(--line);border-bottom:0">
            <span class="text-muted small">Tutup konsultasi jika melanggar ketentuan atau sudah tidak relevan.</span>
            <form method="post" data-confirm="Konsultasi ini tidak akan bisa dibalas lagi oleh klien maupun pengacara." data-confirm-judul="Tutup konsultasi?" data-confirm-ok="Ya, tutup" data-confirm-tipe="danger">
              <?= csrf_field() ?>
              <input type="hidden" name="konsultasi_id" value="<?= (int) $k['id'] ?>">
              <input type="hidden" name="dari_detail" value="<?= (int) $k['id'] ?>">
              <input type="hidden" name="aksi" value="tutup">
              <button class="btn btn-outline-danger btn-ad" type="submit"><i class="bi bi-slash-circle"></i> Tutup konsultasi</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ---- Daftar + filter ----
$q      = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
$status = in_array($_GET['status'] ?? '', ['menunggu', 'dijawab', 'selesai', 'ditutup'], true) ? $_GET['status'] : '';
$bidang = (int) ($_GET['bidang'] ?? 0);
$hal    = max(1, (int) ($_GET['hal'] ?? 1));
$perHal = 15;

$kondisi = ['1 = 1'];
$param   = [];
if ($q !== '') {
    $kondisi[] = '(k.judul LIKE ? OR uk.nama LIKE ?)';
    array_push($param, "%$q%", "%$q%");
}
if ($status !== '') {
    $kondisi[] = 'k.status = ?';
    $param[] = $status;
}
if ($bidang > 0) {
    $kondisi[] = 'k.bidang_id = ?';
    $param[] = $bidang;
}
$where = implode(' AND ', $kondisi);
$dari  = 'FROM konsultasi k JOIN users uk ON uk.id = k.klien_id LEFT JOIN users up ON up.id = k.pengacara_id JOIN bidang_hukum b ON b.id = k.bidang_id';

$st = $pdo->prepare("SELECT COUNT(*) $dari WHERE $where");
$st->execute($param);
$total    = (int) $st->fetchColumn();
$totalHal = max(1, (int) ceil($total / $perHal));
$hal      = min($hal, $totalHal);

$st = $pdo->prepare("SELECT k.id, k.judul, k.status, k.created_at, uk.nama AS klien, up.nama AS pengacara, b.nama AS bidang $dari WHERE $where ORDER BY k.created_at DESC, k.id DESC LIMIT ? OFFSET ?");
foreach ($param as $i => $v) {
    $st->bindValue($i + 1, $v);
}
$st->bindValue(count($param) + 1, $perHal, PDO::PARAM_INT);
$st->bindValue(count($param) + 2, ($hal - 1) * $perHal, PDO::PARAM_INT);
$st->execute();
$daftar = $st->fetchAll();

$bidangList  = $pdo->query('SELECT id, nama FROM bidang_hukum ORDER BY nama')->fetchAll();
$filterAktif = ['q' => $q, 'status' => $status, 'bidang' => $bidang ?: '', 'hal' => $hal > 1 ? $hal : ''];

$judul = 'Moderasi Konsultasi';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container ad-wrap">
  <?php admin_kepala('konsultasi', 'Moderasi Konsultasi', 'Semua konsultasi di Ruang Hukum. Buka satu konsultasi untuk membaca percakapannya.'); ?>

  <form class="ad-filter" method="get">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label" for="k-q">Cari</label>
        <div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span>
          <input class="form-control" id="k-q" name="q" type="search" value="<?= e($q) ?>" placeholder="Judul atau nama klien"></div>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="k-status">Status</label>
        <select class="form-select" id="k-status" name="status">
          <option value="">Semua</option>
          <?php foreach (['menunggu', 'dijawab', 'selesai', 'ditutup'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="k-bidang">Bidang</label>
        <select class="form-select" id="k-bidang" name="bidang">
          <option value="">Semua</option>
          <?php foreach ($bidangList as $b): ?>
            <option value="<?= (int) $b['id'] ?>" <?= (int) $b['id'] === $bidang ? 'selected' : '' ?>><?= e($b['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-8 col-md-1 d-grid"><button class="btn btn-navy" type="submit">Cari</button></div>
      <div class="col-4 col-md-1 d-grid"><a class="btn btn-outline-secondary" href="<?= e(url('admin/konsultasi.php')) ?>" title="Hapus filter"><i class="bi bi-x-lg"></i></a></div>
    </div>
  </form>

  <div class="ad-card">
    <?php if (!$daftar): ?>
      <div class="ad-kosong"><i class="bi bi-chat-square"></i>Tidak ada konsultasi yang cocok dengan filter.</div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table ad-table">
        <thead><tr><th>Konsultasi</th><th>Klien</th><th>Pengacara</th><th>Status</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($daftar as $k): ?>
          <tr>
            <td><div class="ad-potong"><a class="text-decoration-none fw-semibold" href="<?= e(url('admin/konsultasi.php?id=' . (int) $k['id'])) ?>"><?= e($k['judul']) ?></a></div><span class="ad-sub"><?= e($k['bidang']) ?></span></td>
            <td><?= e($k['klien']) ?></td>
            <td><?= e($k['pengacara'] ?? '-') ?></td>
            <td><?= admin_pil_konsultasi($k['status']) ?></td>
            <td><?= e(tgl($k['created_at'])) ?></td>
            <td class="text-end">
              <span class="ad-aksi">
                <a class="btn btn-outline-secondary btn-ad" href="<?= e(url('admin/konsultasi.php?id=' . (int) $k['id'])) ?>">Buka</a>
                <?php if (in_array($k['status'], ['menunggu', 'dijawab'], true)): ?>
                  <form method="post" data-confirm="Konsultasi &quot;<?= e($k['judul']) ?>&quot; tidak akan bisa dibalas lagi." data-confirm-judul="Tutup konsultasi?" data-confirm-ok="Ya, tutup" data-confirm-tipe="danger">
                    <?= csrf_field() ?>
                    <input type="hidden" name="konsultasi_id" value="<?= (int) $k['id'] ?>">
                    <input type="hidden" name="aksi" value="tutup">
                    <?php foreach ($filterAktif as $kk => $v): ?><input type="hidden" name="<?= $kk ?>" value="<?= e($v) ?>"><?php endforeach; ?>
                    <button class="btn btn-outline-danger btn-ad" type="submit">Tutup</button>
                  </form>
                <?php endif; ?>
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
    <span class="text-muted small">Menampilkan <?= count($daftar) ?> dari <?= $total ?> konsultasi</span>
    <div><?= art_pagination($hal, $totalHal, 'admin/konsultasi.php', ['q' => $q, 'status' => $status, 'bidang' => $bidang ?: '']) ?></div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
