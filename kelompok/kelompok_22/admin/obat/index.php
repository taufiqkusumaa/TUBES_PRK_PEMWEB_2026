<?php
/**
 * File: admin/obat/index.php
 * Daftar obat (Read) - Kelompok 22 KlikObat
 */

require_once '../auth.php'; // hanya admin yang sudah login
require_once '../../config/koneksi.php';
require_once '../../config/helper.php';

$daftar_pesan = [
    'tambah' => 'Obat berhasil ditambahkan.',
    'ubah'   => 'Data obat berhasil diperbarui.',
    'hapus'  => 'Obat berhasil dihapus.',
    'gagal'  => 'Terjadi kesalahan, silakan coba lagi.',
];
$status_get = $_GET['status'] ?? '';
$notif = $daftar_pesan[$status_get] ?? '';

$sql = "SELECT o.id_obat, o.nama_obat, o.harga, o.stok, o.gambar, k.nama_kategori
        FROM obat o
        JOIN kategori k ON o.id_kategori = k.id_kategori
        ORDER BY o.id_obat DESC";
$hasil = mysqli_query($conn, $sql);
$error_db = $hasil ? '' : mysqli_error($conn);

$warna = [
    'Tersedia' => '#198754',
    'Menipis'  => '#e69500',
    'Habis'    => '#dc3545',
];

// Layout bersama: header.php memuat <head>, sidebar, dan header halaman
$judulHalaman = 'Data Obat';
require_once '../../backend/admin/layout/header.php';
?>

<!-- CSS dipasang ulang dengan path relatif supaya tetap termuat -->
<link rel="stylesheet" href="../../frontend/assets/css/style.css">
<link rel="stylesheet" href="../../frontend/assets/css/admin.css">
<style>
    .obat-notif { padding: 12px 15px; margin-bottom: 20px; border-radius: 10px; font-weight: 600; font-size: 14px; }
    .obat-notif.ok { background: #d1e7dd; color: #0f5132; }
    .obat-notif.err { background: #fee2e2; color: #b91c1c; }
    .obat-link { color: var(--admin-primary); font-weight: 600; text-decoration: none; }
    .obat-link-hapus { background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; font: inherit; font-weight: 600; }
    .obat-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
    .obat-badge { color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
</style>

<div class="admin-content">
    <div style="margin-bottom: 20px;">
        <a class="admin-btn" href="tambah.php">+ Tambah Obat</a>
    </div>

    <?php if ($notif): ?>
        <div class="obat-notif <?= $status_get === 'gagal' ? 'err' : 'ok' ?>">
            <?= htmlspecialchars($notif) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_db): ?>
        <div class="obat-notif err">
            <strong>Data obat tidak dapat dimuat.</strong><br>
            Detail: <?= htmlspecialchars($error_db) ?>
        </div>
    <?php endif; ?>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Gambar</th>
                    <th>Nama Obat</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($hasil && mysqli_num_rows($hasil) > 0): ?>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($hasil)): ?>
                        <?php $status = getStatusStok((int)$row['stok']); ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><img class="obat-thumb" src="../../assets/images/obat/<?= htmlspecialchars($row['gambar'] ?: 'default.jpg') ?>" alt=""></td>
                            <td><?= htmlspecialchars($row['nama_obat']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                            <td>Rp <?= number_format((float)$row['harga'], 0, ',', '.') ?></td>
                            <td><?= (int)$row['stok'] ?></td>
                            <td><span class="obat-badge" style="background: <?= $warna[$status] ?>"><?= $status ?></span></td>
                            <td>
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <a class="obat-link" href="ubah.php?id=<?= (int)$row['id_obat'] ?>">Ubah</a>
                                    <form action="hapus.php" method="post" style="display: inline;" onsubmit="return confirm('Yakin hapus obat ini?')">
                                        <input type="hidden" name="id" value="<?= (int)$row['id_obat'] ?>">
                                        <button type="submit" class="obat-link-hapus">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--admin-secondary); padding: 20px;"><?= $error_db ? 'Data tidak dapat dimuat.' : 'Belum ada data obat.' ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../backend/admin/layout/footer.php'; ?>