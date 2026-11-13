<?php
// Memanggil autentikasi dan koneksi database
$allowed_roles = ['admin'];
require_once __DIR__ . '/../../includes/auth.check.php';
require_once __DIR__ . '/../../config/koneksi.php';

// Memastikan hanya admin yang dapat menghapus pengguna
$cek_role = strtolower(trim($_SESSION['user']['role'] ?? ($_SESSION['role'] ?? '')));
if ($cek_role !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Admin.'); window.location='../../index.php';</script>";
    exit;
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $id_sedang_login = (int) ($_SESSION['user']['id'] ?? ($_SESSION['id_user'] ?? 0));

    // Cegah admin menghapus akunnya sendiri yang sedang aktif login
    if ($id === $id_sedang_login) {
        echo "<script>alert('Tidak dapat menghapus akun yang sedang digunakan untuk login!'); window.location='index.php';</script>";
        exit;
    }

    // Hapus data user dari database
    $hapus = mysqli_query($koneksi, "DELETE FROM users WHERE id_user = '$id'");

    if ($hapus) {
        echo "<script>alert('Data user berhasil dihapus!'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data user!'); window.location='index.php';</script>";
    }
} else {
    header("Location: index.php");
    exit;
}
?>