<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

// FITUR HAPUS BERITA - ORANG 5
require_editor_or_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM articles WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article || !can_manage_article($article)) {
    http_response_code(404);
    exit('Artikel tidak ditemukan.');
}

db()->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
delete_uploaded_image($article['featured_image'] ?? null);

flash('success', 'Artikel berhasil dihapus.');
redirect('admin/berita.html');
