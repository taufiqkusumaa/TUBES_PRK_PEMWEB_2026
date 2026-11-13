<?php
require_once '../auth.php';
require_once '../../config/koneksi.php';

// Ambil data kategori
$result = $conn->query("SELECT * FROM kategori ORDER BY id_kategori DESC");

// Definisikan judul halaman untuk header
$page_title = "Manajemen Kategori Obat";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - KlikObat</title>
    <!-- Hubungkan CSS dari folder frontend/assets/css -->
    <link rel="stylesheet" href="../../frontend/assets/css/style.css">
    <link rel="stylesheet" href="../../frontend/assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Admin -->
        <?php include '../../backend/admin/layout/sidebar.php'; ?>

        <!-- Konten Utama Admin -->
        <main class="admin-main">
            <!-- Header Admin (Dipanggil otomatis dari file header.php) -->
            <?php include '../../backend/admin/layout/header.php'; ?>

            <div class="admin-content">
                <div style="margin-bottom: 20px;">
                    <a href="tambah.php" class="admin-btn">+ Tambah Kategori Baru</a>
                </div>

                <!-- Tabel Data Kategori -->
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
                                <td>
                                    <a href="ubah.php?id=<?= $row['id_kategori']; ?>" style="color: var(--admin-primary); font-weight: 600; margin-right: 10px;">Ubah</a>
                                    <a href="hapus.php?id=<?= $row['id_kategori']; ?>" onclick="return confirm('Yakin ingin menghapus kategori ini?');" style="color: #ef4444; font-weight: 600;">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Modular Saja (Tanpa tag <footer class="admin-footer"> ganda) -->
            <?php include '../../backend/admin/layout/footer.php'; ?>
        </main>
    </div>
</body>
</html>