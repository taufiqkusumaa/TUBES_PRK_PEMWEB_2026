<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/categories.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
try {
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $stmt->execute(['id' => $id]);
    set_flash($stmt->rowCount() ? 'success' : 'warning', $stmt->rowCount() ? 'Kategori berhasil dihapus.' : 'Kategori tidak ditemukan.');
} catch (PDOException $exception) {
    set_flash('danger', 'Kategori tidak dapat dihapus karena masih digunakan oleh data alat. Pindahkan alat ke kategori lain terlebih dahulu.');
}
redirect('admin/categories.php');
