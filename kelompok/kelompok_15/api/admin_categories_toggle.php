<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

// FITUR TOGGLE KATEGORI - ORANG 2
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$active = isset($_POST['active']) && (string) $_POST['active'] === '1' ? 1 : 0;

$stmt = db()->prepare('UPDATE categories SET is_active = ? WHERE id = ?');
$stmt->execute([$active, $id]);
if ($stmt->rowCount() < 1) {
    $check = db()->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
    $check->execute([$id]);
    if (!$check->fetch()) {
        flash('error', 'Kategori tidak ditemukan.');
        redirect('admin/kategori.html');
    }
}

flash('success', 'Status kategori diperbarui.');
redirect('admin/kategori.html');
