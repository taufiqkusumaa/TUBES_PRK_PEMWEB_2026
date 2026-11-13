
<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tips.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    header('Location: tips.php');
    exit;
}

// Ambil data gambar sebelum menghapus tips
$stmt = $pdo->prepare("SELECT image FROM tips WHERE id = :id");
$stmt->execute([':id' => $id]);
$tip = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tip) {
    // Hapus data tips dari database
    $stmt = $pdo->prepare("DELETE FROM tips WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Hapus file gambar jika tersedia
    if (!empty($tip['image'])) {
        $imagePath = '../assets/uploads/tips/' . basename($tip['image']);

        if (is_file($imagePath)) {
            unlink($imagePath);
        }
    }
}
$_SESSION['success'] = 'Tips kesehatan berhasil dihapus!';
header('Location: tips.php');
exit;
