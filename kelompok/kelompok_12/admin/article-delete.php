<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: articles.php");
    exit;
}

$id = (int) $_GET['id'];

// Ambil data artikel
$stmt = $pdo->prepare("SELECT id, image FROM articles WHERE id = :id");
$stmt->execute([':id' => $id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header("Location: articles.php");
    exit;
}

// Hapus data artikel
$stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id");
$stmt->execute([':id' => $id]);

// Hapus file gambar jika ada
if (!empty($article['image'])) {
    $imagePath = '../uploads/articles/' . $article['image'];

    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

header("Location: articles.php");
exit;
?>