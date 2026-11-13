<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Ambil ID dari URL
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    try {
        // Hapus kategori berdasarkan ID
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        // Jika gagal (misal terhalang foreign key constraint pada artikel), abaikan atau tangani
    }
}

// Kembalikan ke halaman daftar kategori
header('Location: categories.php');
exit;