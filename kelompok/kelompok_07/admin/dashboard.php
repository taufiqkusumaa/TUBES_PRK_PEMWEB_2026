<?php
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../config/koneksi.php';

$pdo = db();
$adminId = (int) current_user()['id'];
$pesan = '';
$jenisPesan = 'success';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Permintaan tidak valid.');
        }

        $aksi = $_POST['aksi'] ?? '';

        if ($aksi === 'tambah_alat' || $aksi === 'edit_alat') {
            $id = (int) ($_POST['id'] ?? 0);
            $kategori = (int) ($_POST['kategori_id'] ?? 0);
            $kode = trim($_POST['kode'] ?? '');
            $nama = trim($_POST['nama'] ?? '');
            $jenis = $_POST['jenis'] ?? 'alat';
            $stok = filter_var($_POST['stok'] ?? null, FILTER_VALIDATE_INT);
            $lokasi = trim($_POST['lokasi'] ?? '');
            $kondisi = $_POST['kondisi'] ?? 'baik';
            $deskripsi = trim($_POST['deskripsi'] ?? '');

            if ($kategori < 1 || $kode === '' || $nama === '' || $stok === false || $stok < 0) {
                throw new Exception('Lengkapi data alat dengan benar.');
            }
            if (!in_array($jenis, ['alat', 'ruang'], true) ||
                !in_array($kondisi, ['baik', 'rusak_ringan', 'rusak_berat'], true)) {
                throw new Exception('Jenis atau kondisi tidak valid.');
            }

            if ($aksi === 'tambah_alat') {
                $stmt = $pdo->prepare('INSERT INTO alat (kategori_id, kode, nama, jenis, stok, lokasi, kondisi, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$kategori, $kode, $nama, $jenis, $stok, $lokasi, $kondisi, $deskripsi]);
                $pesan = 'Alat berhasil ditambahkan.';
            } else {
                $stmt = $pdo->prepare('UPDATE alat SET kategori_id=?, kode=?, nama=?, jenis=?, stok=?, lokasi=?, kondisi=?, deskripsi=? WHERE id=?');
                $stmt->execute([$kategori, $kode, $nama, $jenis, $stok, $lokasi, $kondisi, $deskripsi, $id]);
                $pesan = 'Data alat berhasil diperbarui.';
            }
        } elseif (in_array($aksi, ['setujui', 'tolak', 'kembali'], true)) {
            $id = (int) ($_POST['peminjaman_id'] ?? 0);
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT * FROM peminjaman WHERE id=? FOR UPDATE');
            $stmt->execute([$id]);
            $pinjam = $stmt->fetch();
            if (!$pinjam) {
                throw new Exception('Data peminjaman tidak ditemukan.');
            }

            if ($aksi === 'setujui' || $aksi === 'kembali') {
                $statusTujuan = $aksi === 'setujui' ? 'menunggu' : 'disetujui';
                if ($pinjam['status'] !== $statusTujuan) {
                    throw new Exception('Status peminjaman sudah berubah.');
                }

                $stmt = $pdo->prepare('SELECT alat_id, jumlah FROM detail_peminjaman WHERE peminjaman_id=?');
                $stmt->execute([$id]);
                $detail = $stmt->fetchAll();

                foreach ($detail as $item) {
                    $perubahan = $aksi === 'setujui' ? -1 : 1;
                    $stmt = $pdo->prepare('UPDATE alat SET stok=stok + (? * ?) WHERE id=? AND (?=1 OR stok>=?)');
                    $stmt->execute([$perubahan, $item['jumlah'], $item['alat_id'], $aksi === 'kembali' ? 1 : 0, $item['jumlah']]);
                    if (!$stmt->rowCount()) {
                        throw new Exception('Stok tidak mencukupi atau alat tidak ditemukan.');
                    }
                }

                if ($aksi === 'setujui') {
                    $stmt = $pdo->prepare("UPDATE peminjaman SET status='disetujui', diproses_oleh=?, diproses_pada=NOW() WHERE id=?");
                    $stmt->execute([$adminId, $id]);
                    $pesan = 'Peminjaman disetujui.';
                } else {
                    $stmt = $pdo->prepare("UPDATE peminjaman SET status='dikembalikan', tanggal_kembali_aktual=NOW() WHERE id=?");
                    $stmt->execute([$id]);
                    $pesan = 'Pengembalian berhasil diproses.';
                }
            } else {
                if ($pinjam['status'] !== 'menunggu') {
                    throw new Exception('Pengajuan sudah diproses.');
                }
                $stmt = $pdo->prepare("UPDATE peminjaman SET status='ditolak', diproses_oleh=?, diproses_pada=NOW() WHERE id=?");
                $stmt->execute([$adminId, $id]);
                $pesan = 'Pengajuan ditolak.';
            }
            $pdo->commit();
        } else {
            throw new Exception('Aksi tidak dikenal.');
        }
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pesan = $error instanceof PDOException
            ? 'Gagal menyimpan. Pastikan kode alat belum digunakan.'
            : $error->getMessage();
        $jenisPesan = 'error';
    }
}

$kategori = $pdo->query('SELECT id, nama FROM kategori ORDER BY nama')->fetchAll();
$alat = $pdo->query('SELECT a.*, k.nama AS kategori FROM alat a JOIN kategori k ON k.id=a.kategori_id ORDER BY a.nama')->fetchAll();
$editId = (int) ($_GET['edit'] ?? 0);
$editAlat = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM alat WHERE id=?');
    $stmt->execute([$editId]);
    $editAlat = $stmt->fetch();
}

$totalStok = $pdo->query("SELECT COALESCE(SUM(stok),0) FROM alat WHERE jenis='alat'")->fetchColumn();
$menunggu = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='menunggu'")->fetchColumn();
$aktif = $pdo->query("SELECT p.*, u.nama AS peminjam,
    GROUP_CONCAT(CONCAT(a.nama, ' (', dp.jumlah, ')') ORDER BY a.nama SEPARATOR ', ') AS daftar_barang
    FROM peminjaman p
    JOIN users u ON u.id=p.user_id
    LEFT JOIN detail_peminjaman dp ON dp.peminjaman_id=p.id
    LEFT JOIN alat a ON a.id=dp.alat_id
    WHERE p.status='disetujui'
    GROUP BY p.id
    ORDER BY p.tanggal_kembali_rencana")->fetchAll();
$pengajuan = $pdo->query("SELECT p.*, u.nama AS peminjam FROM peminjaman p JOIN users u ON u.id=p.user_id WHERE p.status='menunggu' ORDER BY p.created_at DESC")->fetchAll();

$awal = $_GET['awal'] ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $awal) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $akhir) ||
    !checkdate((int) substr($awal, 5, 2), (int) substr($awal, 8, 2), (int) substr($awal, 0, 4)) ||
    !checkdate((int) substr($akhir, 5, 2), (int) substr($akhir, 8, 2), (int) substr($akhir, 0, 4)) || $awal > $akhir) {
    $awal = date('Y-m-01');
    $akhir = date('Y-m-d');
}
$stmt = $pdo->prepare("SELECT p.kode, u.nama AS peminjam, p.tanggal_pinjam, p.status
    FROM peminjaman p JOIN users u ON u.id=p.user_id
    WHERE p.tanggal_pinjam >= ? AND p.tanggal_pinjam < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY p.tanggal_pinjam DESC");
$stmt->execute([$awal, $akhir]);
$laporan = $stmt->fetchAll();
function tanggal($value) { return date('d/m/Y', strtotime($value)); }
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Admin | LabLoan</title>
<style>
body{font:15px Arial,sans-serif;margin:0;background:#f4f6f8;color:#222}
header{background:#174ea6;color:white;padding:16px 5%;display:flex;justify-content:space-between;align-items:center}
nav{background:#174ea6;padding:12px}nav a,header a{color:white;margin-right:16px}
main{max-width:1100px;margin:24px auto;padding:0 16px}
section{background:white;padding:18px;margin:16px 0;border:1px solid #ddd;border-radius:6px}
.cards{display:flex;gap:12px}.cards div{background:#eef3fa;padding:14px;flex:1}
input,select,textarea,button{padding:8px;margin:4px 0;font:inherit}
input,select,textarea{border:1px solid #bbb;border-radius:4px}
table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:9px;border-bottom:1px solid #ddd}
button,.btn{background:#174ea6;color:white;border:0;border-radius:4px;padding:8px 12px;cursor:pointer}
.notice{padding:10px;background:#e8f5e9}.error{background:#ffebee}
.actions{display:flex;gap:6px}
@media(max-width:650px){.cards{display:block}header{align-items:flex-start;gap:12px;flex-direction:column}table{font-size:13px}}
@media print{body{background:#fff;color:#000;font:12px Arial,sans-serif}header,nav,#alat,#pengajuan,#aktif,#kategori,#filter-laporan,.no-print{display:none!important}main{max-width:none;margin:0;padding:0}section{border:0;margin:0;padding:0}#laporan{display:block!important}#laporan h2{margin-top:0}table{font-size:11px}th,td{padding:6px;border:1px solid #999}}
</style>
</head>
<body>
<header>
    <strong>LabLoan — Dashboard Admin</strong>
    <div><?= e(current_user()['nama']) ?> · <a href="<?= e(url('auth/logout.php')) ?>">Keluar</a></div>
</header>
<main>
<nav>
    <a href="<?= e(url('index.php')) ?>">Kembali ke Beranda</a>
    <a href="#laporan">Laporan</a>
    <a href="#pengajuan">Peminjaman</a>
    <a href="#alat">Data Alat</a>
    <a href="#kategori">Kategori</a>
</nav>

<?php if ($pesan !== ''): ?>
<p class="notice <?= $jenisPesan === 'error' ? 'error' : '' ?>"><?= e($pesan) ?></p>
<?php endif; ?>

<div class="cards">
    <div><strong><?= count($alat) ?></strong><br>Data alat dan ruang</div>
    <div><strong><?= (int) $totalStok ?></strong><br>Total stok alat</div>
    <div><strong><?= count($pengajuan) ?></strong><br>Pengajuan menunggu</div>
</div>

<section id="alat">
<h2>Data Alat</h2>
<h3><?= $editAlat ? 'Edit alat' : 'Tambah alat' ?></h3>
<?php if (!$kategori): ?>
<p>Tambahkan kategori terlebih dahulu sebelum memasukkan alat.</p>
<?php else: ?>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="aksi" value="<?= $editAlat ? 'edit_alat' : 'tambah_alat' ?>">
    <?php if ($editAlat): ?><input type="hidden" name="id" value="<?= (int) $editAlat['id'] ?>"><?php endif; ?>
    <label>Kode <input name="kode" required value="<?= e($editAlat['kode'] ?? '') ?>"></label>
    <label>Nama <input name="nama" required value="<?= e($editAlat['nama'] ?? '') ?>"></label>
    <label>Kategori <select name="kategori_id" required>
    <?php foreach ($kategori as $item): ?>
        <option value="<?= (int) $item['id'] ?>" <?= (int) ($editAlat['kategori_id'] ?? 0) === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['nama']) ?></option>
    <?php endforeach; ?>
    </select></label>
    <label>Jenis <select name="jenis">
        <option value="alat" <?= ($editAlat['jenis'] ?? '') === 'alat' ? 'selected' : '' ?>>Alat</option>
        <option value="ruang" <?= ($editAlat['jenis'] ?? '') === 'ruang' ? 'selected' : '' ?>>Ruang</option>
    </select></label>
    <label>Stok <input type="number" name="stok" min="0" required value="<?= e((string) ($editAlat['stok'] ?? 0)) ?>"></label>
    <label>Lokasi <input name="lokasi" value="<?= e($editAlat['lokasi'] ?? '') ?>"></label>
    <label>Kondisi <select name="kondisi">
        <?php foreach (['baik' => 'Baik', 'rusak_ringan' => 'Rusak ringan', 'rusak_berat' => 'Rusak berat'] as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= ($editAlat['kondisi'] ?? 'baik') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select></label>
    <label>Deskripsi <textarea name="deskripsi"><?= e($editAlat['deskripsi'] ?? '') ?></textarea></label>
    <button type="submit"><?= $editAlat ? 'Simpan perubahan' : 'Tambah alat' ?></button>
    <?php if ($editAlat): ?><a href="<?= e(url('admin/dashboard.php#alat')) ?>">Batal</a><?php endif; ?>
</form>
<?php endif; ?>
<table>
<thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Jenis</th><th>Stok</th><th>Kondisi</th><th>Aksi</th></tr></thead>
<tbody>
<?php foreach ($alat as $item): ?>
<tr>
<td><?= e($item['kode']) ?></td><td><?= e($item['nama']) ?></td><td><?= e($item['kategori']) ?></td>
<td><?= e($item['jenis']) ?></td><td><?= (int) $item['stok'] ?></td><td><?= e($item['kondisi']) ?></td>
<td><a class="btn" href="<?= e(url('admin/dashboard.php?edit=' . $item['id'] . '#alat')) ?>">Edit</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>

<section id="pengajuan">
<h2>Pengajuan Menunggu (<?= count($pengajuan) ?>)</h2>
<table><thead><tr><th>Kode</th><th>Peminjam</th><th>Tanggal pinjam</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($pengajuan as $item): ?>
<tr><td><?= e($item['kode']) ?></td><td><?= e($item['peminjam']) ?></td><td><?= tanggal($item['tanggal_pinjam']) ?></td>
<td class="actions">
<?php foreach (['setujui' => 'Setujui', 'tolak' => 'Tolak'] as $action => $label): ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="peminjaman_id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="aksi" value="<?= e($action) ?>"><button><?= e($label) ?></button></form>
<?php endforeach; ?>
</td></tr>
<?php endforeach; ?>
<?php if (!$pengajuan): ?><tr><td colspan="4">Belum ada pengajuan.</td></tr><?php endif; ?>
</tbody></table>
</section>

<section id="aktif">
<h2>Peminjaman Aktif (<?= count($aktif) ?>)</h2>
<table><thead><tr><th>Kode</th><th>Peminjam</th><th>Barang yang dipinjam</th><th>Batas kembali</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($aktif as $item): ?>
<tr><td><?= e($item['kode']) ?></td><td><?= e($item['peminjam']) ?></td><td><?= e($item['daftar_barang'] ?: 'Rincian barang tidak tersedia') ?></td><td><?= tanggal($item['tanggal_kembali_rencana']) ?></td>
<td><form method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="peminjaman_id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="aksi" value="kembali"><button>Proses kembali</button></form></td></tr>
<?php endforeach; ?>
<?php if (!$aktif): ?><tr><td colspan="5">Tidak ada peminjaman aktif.</td></tr><?php endif; ?>
</tbody></table>
</section>
<section id="kategori">
<h2>Kategori</h2>
<table><thead><tr><th>Nama kategori</th></tr></thead><tbody>
<?php foreach ($kategori as $item): ?>
<tr><td><?= e($item['nama']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$kategori): ?><tr><td>Belum ada kategori.</td></tr><?php endif; ?>
</tbody></table>
</section>

<section id="laporan">
<h2>Laporan Peminjaman</h2>
<div id="filter-laporan">
<form method="get">
<?php if (isset($_GET['edit'])): ?><input type="hidden" name="edit" value="<?= (int) $_GET['edit'] ?>"><?php endif; ?>
<label>Dari <input type="date" name="awal" value="<?= e($awal) ?>"></label>
<label>Sampai <input type="date" name="akhir" value="<?= e($akhir) ?>"></label>
<button>Filter</button>
</form>
<button type="button" class="no-print" onclick="window.print()">Cetak laporan</button>
</div>
<p id="periode-cetak">Periode laporan: <?= tanggal($awal) ?> sampai <?= tanggal($akhir) ?> · <?= count($laporan) ?> transaksi</p>
<table><thead><tr><th>Kode</th><th>Peminjam</th><th>Tanggal</th><th>Status</th></tr></thead><tbody>
<?php foreach ($laporan as $item): ?>
<tr><td><?= e($item['kode']) ?></td><td><?= e($item['peminjam']) ?></td><td><?= tanggal($item['tanggal_pinjam']) ?></td><td><?= e($item['status']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$laporan): ?><tr><td colspan="4">Tidak ada data pada periode ini.</td></tr><?php endif; ?>
</tbody></table>
</section>
</main>
</body>
</html>
