<?php
require_once '../config/database.php';
require_once '../middleware/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$role    = $_SESSION['role'] ?? '';

if (!$user_id || $role !== 'petani') {
    echo "<script>
            alert('Akses ditolak! Halaman ini khusus untuk akun Petani.');
            window.location.href = '../auth/login.php';
          </script>";
    exit;
}

// Ambil info profil petani jika ada (opsional untuk tampilan header)
$stmt_petani = mysqli_prepare($conn, "SELECT * FROM petani WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_petani, "i", $user_id);
mysqli_stmt_execute($stmt_petani);
$data_petani = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_petani));

// Ambil daftar produk berdasarkan user_id petani yang sedang login
$query = "SELECT p.*, k.nama_kategori 
          FROM produk p 
          LEFT JOIN kategori k ON p.kategori_id = k.id 
          WHERE p.user_id = ? 
          ORDER BY p.id DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Produk Petani - BuyPanen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">BuyPanen | Panel Petani</a>
        <div class="d-flex gap-2">
            <a href="../petani/dashboard.php" class="btn btn-outline-light btn-sm">Katalog Utama</a>
            <a href="../petani/profil.php" class="btn btn-outline-light btn-sm">Profil Kebun</a>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h3 class="fw-bold mb-0">Kelola Produk Hasil Panen</h3>
            <small class="text-muted">Lokasi Kebun: <strong><?= htmlspecialchars($data_petani['lokasi_kebun'] ?? 'Petani BuyPanen'); ?></strong></small>
        </div>
        <a href="tambah.php" class="btn btn-success fw-semibold shadow-sm">+ Tambah Produk Baru</a>
    </div>

    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'sukses_tambah'): ?>
            <div class="alert alert-success alert-dismissible fade show">Produk hasil panen berhasil ditambahkan! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php elseif ($_GET['status'] === 'sukses_edit'): ?>
            <div class="alert alert-info alert-dismissible fade show">Perubahan data produk berhasil disimpan! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php elseif ($_GET['status'] === 'sukses_hapus'): ?>
            <div class="alert alert-warning alert-dismissible fade show">Produk telah dihapus dari daftar penjualan Anda. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Deskripsi</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <img src="../assets/<?= htmlspecialchars($row['foto']); ?>" 
                                         alt="<?= htmlspecialchars($row['nama_produk']); ?>" 
                                         width="65" height="65" class="rounded object-fit-cover border">
                                </td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_produk']); ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['nama_kategori'] ?? 'Umum'); ?></span></td>
                                <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?> / <?= htmlspecialchars($row['satuan']); ?></td>
                                <td>
                                    <?php if ((int)$row['stok'] > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <?= (int)$row['stok']; ?> <?= htmlspecialchars($row['satuan']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Habis</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small" style="max-width: 240px;">
                                    <?= htmlspecialchars(mb_strimwidth($row['deskripsi'], 0, 75, '...')); ?>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="edit.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-sm fw-semibold">Edit</a>
                                    <a href="hapus.php?id=<?= $row['id']; ?>" 
                                       class="btn btn-danger btn-sm fw-semibold"
                                       onclick="return confirm('Yakin ingin menghapus produk <?= htmlspecialchars(addslashes($row['nama_produk'])); ?>?')">
                                       Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                Belum ada produk hasil panen yang Anda tambahkan.<br>
                                Klik tombol <strong>+ Tambah Produk Baru</strong> di kanan atas untuk mulai mengisi katalog.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>