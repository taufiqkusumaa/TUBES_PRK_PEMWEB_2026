<?php
/**
 * File: admin/index.php
 * Halaman Dashboard Utama - KlikObat
 */

require_once 'auth.php';
require_once '../config/koneksi.php';
require_once '../config/helper.php';

// 1. Ambil data statistik untuk Summary Cards
$q_kat = $conn->query("SELECT COUNT(*) as total FROM kategori");
$total_kategori = $q_kat ? $q_kat->fetch_assoc()['total'] : 0;

$q_obt = $conn->query("SELECT COUNT(*) as total FROM obat");
$total_obat = $q_obt ? $q_obt->fetch_assoc()['total'] : 0;

// Hitung stok menipis / habis
$q_semua_obat = $conn->query("SELECT stok FROM obat");
$stok_kritis = 0;
if ($q_semua_obat) {
    while ($row = $q_semua_obat->fetch_assoc()) {
        $status = getStatusStok((int)$row['stok']);
        if ($status === 'Menipis' || $status === 'Habis') {
            $stok_kritis++;
        }
    }
}

// 2. Ambil data obat dengan stok Menipis atau Habis (Low Stock Alert Table)
$sql_kritis = "SELECT o.*, k.nama_kategori FROM obat o 
               JOIN kategori k ON o.id_kategori = k.id_kategori 
               ORDER BY o.stok ASC";
$res_kritis = $conn->query($sql_kritis);
$daftar_kritis = [];
if ($res_kritis) {
    while ($r = $res_kritis->fetch_assoc()) {
        $st = getStatusStok((int)$r['stok']);
        if ($st === 'Menipis' || $st === 'Habis') {
            $daftar_kritis[] = $r;
        }
    }
}

// 3. Ambil 5 data obat terbaru (Recent Additions)
$sql_terbaru = "SELECT o.*, k.nama_kategori FROM obat o 
                JOIN kategori k ON o.id_kategori = k.id_kategori 
                ORDER BY o.id_obat DESC LIMIT 5";
$res_terbaru = $conn->query($sql_terbaru);

// Judul Halaman untuk Header Modular
$judulHalaman = 'Dashboard Utama';
require_once '../backend/admin/layout/header.php';
?>

<!-- CSS Pendukung Dashboard -->
<link rel="stylesheet" href="../frontend/assets/css/style.css">
<link rel="stylesheet" href="../frontend/assets/css/admin.css">

<div class="admin-content">
    <!-- 1. Kartu Statistik Ringkas (Summary Cards) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid var(--admin-primary);">
            <p style="margin: 0; color: var(--admin-secondary); font-size: 14px; font-weight: 600;">Total Kategori</p>
            <h3 style="margin: 10px 0 0 0; font-size: 28px; color: var(--admin-text);"><?= $total_kategori; ?></h3>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #10b981;">
            <p style="margin: 0; color: var(--admin-secondary); font-size: 14px; font-weight: 600;">Total Jenis Obat</p>
            <h3 style="margin: 10px 0 0 0; font-size: 28px; color: var(--admin-text);"><?= $total_obat; ?></h3>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #ef4444;">
            <p style="margin: 0; color: var(--admin-secondary); font-size: 14px; font-weight: 600;">Stok Menipis / Habis</p>
            <h3 style="margin: 10px 0 0 0; font-size: 28px; color: var(--admin-text);"><?= $stok_kritis; ?></h3>
        </div>
    </div>

    <!-- 2. Tabel Peringatan Stok (Low Stock Alert Table) -->
    <div style="margin-bottom: 35px;">
        <h2 style="font-size: 18px; margin-bottom: 15px; color: var(--admin-text);">Peringatan Stok Obat (Menipis / Habis)</h2>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Obat</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_kritis)): ?>
                        <?php $no = 1; foreach ($daftar_kritis as $row): ?>
                            <?php $status = getStatusStok((int)$row['stok']); ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nama_obat']); ?></td>
                                <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
                                <td><?= (int)$row['stok']; ?></td>
                                <td>
                                    <span style="color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: <?= $status === 'Habis' ? '#dc3545' : '#e69500'; ?>">
                                        <?= $status; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--admin-secondary); padding: 20px;">Semua stok obat dalam kondisi aman.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Daftar Obat Terbaru (Recent Additions) -->
    <div>
        <h2 style="font-size: 18px; margin-bottom: 15px; color: var(--admin-text);">5 Obat Terbaru Ditambahkan</h2>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Obat</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_terbaru && mysqli_num_rows($res_terbaru) > 0): ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($res_terbaru)): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nama_obat']); ?></td>
                                <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
                                <td><?= (int)$row['stok']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--admin-secondary); padding: 20px;">Belum ada data obat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// Pemanggilan Footer Modular
require_once '../backend/admin/layout/footer.php'; 
?>