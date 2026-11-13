<?php
/**
 * File: admin/obat/hapus.php
 * Hapus obat (Delete) - Kelompok 22 KlikObat
 * Hanya menerima request POST supaya tidak terhapus lewat klik link/URL biasa.
 * File gambar obat ikut dihapus dari folder (kecuali default.jpg).
 */

require_once '../auth.php'; // halaman ini hanya boleh dibuka admin yang sudah login

require_once '../../config/koneksi.php';
require_once __DIR__ . '/gambar_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?status=gagal');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT gambar FROM obat WHERE id_obat = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "DELETE FROM obat WHERE id_obat = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    if ($row) {
        hapusFileGambar($row['gambar']);
    }
    header('Location: index.php?status=hapus');
} else {
    header('Location: index.php?status=gagal');
}
exit;