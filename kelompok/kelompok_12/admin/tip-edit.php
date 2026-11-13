
<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$error = '';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM tips WHERE id = :id");
$stmt->execute([':id' => $id]);
$tip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tip) {
    die('Tips kesehatan tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $image = $tip['image'];

    if ($title === '' || $description === '') {
        $error = 'Judul dan deskripsi wajib diisi.';
    } else {
        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($_FILES['image']['tmp_name']);

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Upload gambar gagal.';
            } elseif (!in_array($fileType, $allowedTypes, true)) {
                $error = 'Format gambar harus JPG, PNG, atau WEBP.';
            } else {
                $uploadDir = '../assets/uploads/tips/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $extension = strtolower(
                    pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)
                );

                $fileName = uniqid('tip_', true) . '.' . $extension;

                if (move_uploaded_file(
                    $_FILES['image']['tmp_name'],
                    $uploadDir . $fileName
                )) {
                    $image = $fileName;
                } else {
                    $error = 'Gagal menyimpan gambar.';
                }
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare(
                "UPDATE tips
                 SET title = :title,
                     description = :description,
                     image = :image
                 WHERE id = :id"
            );

            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':image' => $image,
                ':id' => $id
            ]);
            
            $_SESSION['success'] = 'Tips kesehatan berhasil diperbarui!';
            
            header('Location: tips.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tips Kesehatan</title>
    <link rel="stylesheet" href="../assets/css/tips.css">
</head>
<body>

    <h1>Edit Tips Kesehatan</h1>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Judul Tips</label><br>
        <input
            type="text"
            name="title"
            value="<?= htmlspecialchars($tip['title']) ?>"
            required
        ><br><br>

        <label>Deskripsi Tips</label><br>
        <textarea name="description" rows="6" required><?= htmlspecialchars($tip['description']) ?></textarea><br><br>

        <label>Gambar Saat Ini</label><br>

        <?php if (!empty($tip['image'])): ?>
            <p><?= htmlspecialchars($tip['image']) ?></p>
        <?php else: ?>
            <p>Belum ada gambar.</p>
        <?php endif; ?>

        <label>Ganti Gambar (Opsional)</label><br>
        <input
            type="file"
            name="image"
            accept=".jpg,.jpeg,.png,.webp"
        ><br><br>

        <button type="submit">Simpan Perubahan</button>
        <a href="tips.php">Kembali</a>

    </form>

</body>
</html>