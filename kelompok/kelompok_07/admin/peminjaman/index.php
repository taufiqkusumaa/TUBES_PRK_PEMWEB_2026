<?php
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../config/koneksi.php';

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
        if (!in_array($aksi, ['setujui', 'tolak', 'kembali'], true)) {
            throw new Exception('Aksi tidak dikenal.');
        }

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
            foreach ($stmt->fetchAll() as $item) {
                $perubahan = $aksi === 'setujui' ? -1 : 1;
                $stmtStok = $pdo->prepare('UPDATE alat SET stok=stok + (? * ?) WHERE id=? AND (?=1 OR stok>=?)');
                $stmtStok->execute([$perubahan, $item['jumlah'], $item['alat_id'], $aksi === 'kembali' ? 1 : 0, $item['jumlah']]);
                if (!$stmtStok->rowCount()) {
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
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pesan = $error instanceof PDOException ? 'Gagal memproses peminjaman.' : $error->getMessage();
        $jenisPesan = 'error';
    }
}

$daftarPeminjaman = $pdo->query("SELECT p.*, u.nama AS peminjam,
    (SELECT GROUP_CONCAT(CONCAT(a.nama, ' (', dp.jumlah, ')') ORDER BY a.nama SEPARATOR ', ')
     FROM detail_peminjaman dp JOIN alat a ON a.id=dp.alat_id
     WHERE dp.peminjaman_id=p.id) AS daftar_barang
    FROM peminjaman p
    JOIN users u ON u.id=p.user_id
    WHERE p.status IN ('menunggu', 'disetujui')
    ORDER BY FIELD(p.status, 'menunggu', 'disetujui'), p.tanggal_kembali_rencana")->fetchAll();

function tanggalPeminjaman(string $value): string
{
    return date('d/m/Y', strtotime($value));
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Peminjaman | LabLoan</title>
<style>
body{font:15px Arial,sans-serif;margin:0;background:#f4f6f8;color:#222}header{background:#174ea6;color:white;padding:16px 5%;display:flex;justify-content:space-between;align-items:center}header a{color:white;margin-left:12px}main{max-width:1200px;margin:24px auto;padding:0 16px}.nav{background:#174ea6;padding:12px}.nav a{color:white;margin-right:16px}section{background:white;padding:18px;margin:16px 0;border:1px solid #ddd;border-radius:6px}.notice{padding:10px;background:#e8f5e9}.error{background:#ffebee}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:9px;border-bottom:1px solid #ddd}button,.btn{background:#174ea6;color:white;border:0;border-radius:4px;padding:8px 12px;cursor:pointer}.actions{display:flex;gap:6px}form{margin:0}@media(max-width:750px){table{font-size:13px;display:block;overflow-x:auto}.actions{flex-direction:column}}
</style>
</head>
<body>
<header><strong>LabLoan — Peminjaman Admin</strong><div><?= e(current_user()['nama']) ?> · <a href="<?= e(url('auth/logout.php')) ?>">Keluar</a></div></header>
<main>
<nav class="nav"><a href="<?= e(url('index.php')) ?>">Beranda</a><a href="<?= e(url('admin/dashboard.php')) ?>">Dashboard</a><a href="<?= e(url('admin/peminjaman/index.php')) ?>">Peminjaman</a><a href="<?= e(url('admin/laporan.php')) ?>">Laporan</a><a href="<?= e(url('admin/dashboard.php#alat')) ?>">Data Alat</a><a href="<?= e(url('admin/dashboard.php#kategori')) ?>">Kategori</a></nav>
<?php if ($pesan !== ''): ?><p class="notice <?= $jenisPesan === 'error' ? 'error' : '' ?>"><?= e($pesan) ?></p><?php endif; ?>
<section>
<h1>Daftar Peminjaman</h1>
<p>Pengajuan menunggu persetujuan dan barang yang sedang dipinjam ditampilkan di sini.</p>
<table><thead><tr><th>Kode</th><th>Peminjam</th><th>Barang (jumlah)</th><th>Tanggal pinjam</th><th>Batas kembali</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($daftarPeminjaman as $item): ?>
<tr><td><?= e($item['kode']) ?></td><td><?= e($item['peminjam']) ?></td><td><?= e($item['daftar_barang'] ?: 'Rincian barang tidak tersedia') ?></td><td><?= tanggalPeminjaman($item['tanggal_pinjam']) ?></td><td><?= tanggalPeminjaman($item['tanggal_kembali_rencana']) ?></td><td><?= e(ucfirst($item['status'])) ?></td><td class="actions">
<?php if ($item['status'] === 'menunggu'): ?>
<?php foreach (['setujui' => 'Setujui', 'tolak' => 'Tolak'] as $aksi => $label): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="peminjaman_id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="aksi" value="<?= e($aksi) ?>"><button type="submit"><?= e($label) ?></button></form><?php endforeach; ?>
<?php else: ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="peminjaman_id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="aksi" value="kembali"><button type="submit">Proses kembali</button></form><?php endif; ?>
</td></tr>
<?php endforeach; ?>
<?php if (!$daftarPeminjaman): ?><tr><td colspan="7">Belum ada pengajuan atau peminjaman aktif.</td></tr><?php endif; ?>
</tbody></table>
</section>
</main>
</body>
</html>
