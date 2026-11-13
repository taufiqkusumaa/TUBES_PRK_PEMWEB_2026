<?php
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../config/koneksi.php';

$pdo = db();
$awal = $_GET['awal'] ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-d');
$tanggalValid = static function ($value): bool {
    return is_string($value)
        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
        && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4));
};
$pesanError = '';
if (!$tanggalValid($awal) || !$tanggalValid($akhir) || $awal > $akhir) {
    $awal = date('Y-m-01');
    $akhir = date('Y-m-d');
    $pesanError = 'Rentang tanggal tidak valid. Periode dikembalikan ke bulan ini.';
}

$stmt = $pdo->prepare("SELECT p.kode, u.nama AS peminjam, p.tanggal_pinjam,
    p.tanggal_kembali_rencana, p.tanggal_kembali_aktual, p.status,
    (SELECT GROUP_CONCAT(CONCAT(a.nama, ' (', dp.jumlah, ')') ORDER BY a.nama SEPARATOR ', ')
     FROM detail_peminjaman dp JOIN alat a ON a.id=dp.alat_id
     WHERE dp.peminjaman_id=p.id) AS daftar_barang
    FROM peminjaman p
    JOIN users u ON u.id=p.user_id
    WHERE p.tanggal_pinjam >= ? AND p.tanggal_pinjam < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY p.tanggal_pinjam DESC");
$stmt->execute([$awal, $akhir]);
$laporan = $stmt->fetchAll();

function tanggalLaporan(string $value): string
{
    return date('d/m/Y', strtotime($value));
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Laporan Peminjaman | LabLoan</title>
<style>
body{font:15px Arial,sans-serif;margin:0;background:#f4f6f8;color:#222}header{background:#174ea6;color:white;padding:16px 5%;display:flex;justify-content:space-between;align-items:center}header a{color:white;margin-left:12px}main{max-width:1200px;margin:24px auto;padding:0 16px}.nav{background:#174ea6;padding:12px}.nav a{color:white;margin-right:16px}section{background:white;padding:18px;margin:16px 0;border:1px solid #ddd;border-radius:6px}input,button{padding:8px;margin:4px 0;font:inherit}input{border:1px solid #bbb;border-radius:4px}button{background:#174ea6;color:white;border:0;border-radius:4px;padding:8px 12px;cursor:pointer}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:9px;border-bottom:1px solid #ddd}.notice{padding:10px;background:#fff3cd}@media(max-width:750px){table{font-size:13px;display:block;overflow-x:auto}}@media print{body{background:white;color:black;font:11px Arial,sans-serif}header,.nav,.filter,.no-print{display:none!important}main{max-width:none;margin:0;padding:0}section{border:0;margin:0;padding:0}table{font-size:10px}th,td{padding:6px;border:1px solid #999}}
</style>
</head>
<body>
<header><strong>LabLoan — Laporan Admin</strong><div><?= e(current_user()['nama']) ?> · <a href="<?= e(url('auth/logout.php')) ?>">Keluar</a></div></header>
<main>
<nav class="nav"><a href="<?= e(url('index.php')) ?>">Beranda</a><a href="<?= e(url('admin/dashboard.php')) ?>">Dashboard</a><a href="<?= e(url('admin/peminjaman/index.php')) ?>">Peminjaman</a><a href="<?= e(url('admin/laporan.php')) ?>">Laporan</a><a href="<?= e(url('admin/dashboard.php#alat')) ?>">Data Alat</a><a href="<?= e(url('admin/dashboard.php#kategori')) ?>">Kategori</a></nav>
<section id="laporan">
<h1>Laporan Barang Dipinjam</h1>
<div class="filter"><form method="get"><label>Dari <input type="date" name="awal" value="<?= e($awal) ?>" required></label> <label>Sampai <input type="date" name="akhir" value="<?= e($akhir) ?>" required></label> <button type="submit">Filter</button> <button class="no-print" type="button" onclick="window.print()">Cetak laporan</button></form></div>
<?php if ($pesanError !== ''): ?><p class="notice"><?= e($pesanError) ?></p><?php endif; ?>
<p id="periode-cetak">Periode <?= tanggalLaporan($awal) ?> sampai <?= tanggalLaporan($akhir) ?> · <?= count($laporan) ?> transaksi</p>
<table><thead><tr><th>Kode</th><th>Peminjam</th><th>Barang (jumlah)</th><th>Tanggal pinjam</th><th>Batas kembali</th><th>Kembali aktual</th><th>Status</th></tr></thead><tbody>
<?php foreach ($laporan as $item): ?>
<tr><td><?= e($item['kode']) ?></td><td><?= e($item['peminjam']) ?></td><td><?= e($item['daftar_barang'] ?: 'Rincian barang tidak tersedia') ?></td><td><?= tanggalLaporan($item['tanggal_pinjam']) ?></td><td><?= tanggalLaporan($item['tanggal_kembali_rencana']) ?></td><td><?= $item['tanggal_kembali_aktual'] ? tanggalLaporan($item['tanggal_kembali_aktual']) : '—' ?></td><td><?= e(ucfirst($item['status'])) ?></td></tr>
<?php endforeach; ?>
<?php if (!$laporan): ?><tr><td colspan="7">Tidak ada data peminjaman pada periode ini.</td></tr><?php endif; ?>
</tbody></table>
</section>
</main>
</body>
</html>
