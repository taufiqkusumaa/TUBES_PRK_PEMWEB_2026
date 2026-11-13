<?php
require_once '../config/database.php';
require_once '../middleware/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$role    = $_SESSION['role'] ?? '';

if (!$user_id || $role !== 'petani') {
    header("Location: ../auth/login.php");
    exit;
}

$produk_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Pastikan produk benar milik akun petani yang sedang login
$stmt_cek = mysqli_prepare($conn, "SELECT foto FROM produk WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_cek, "ii", $produk_id, $user_id);
mysqli_stmt_execute($stmt_cek);
$produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));

if ($produk) {
    $stmt_del = mysqli_prepare($conn, "DELETE FROM produk WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt_del, "ii", $produk_id, $user_id);

    if (mysqli_stmt_execute($stmt_del)) {
        $file_bawaan = ['cabai.jpg', 'pisang.jpg', 'background.jpg'];
        if (!empty($produk['foto']) && !in_array($produk['foto'], $file_bawaan) && file_exists('../assets/' . $produk['foto'])) {
            unlink('../assets/' . $produk['foto']);
        }
    }
}

header("Location: index.php?status=sukses_hapus");
exit;