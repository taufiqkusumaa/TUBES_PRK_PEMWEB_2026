<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $image = null;

    if ($title === '' || $description === '') {
        $error = 'Judul dan deskripsi wajib diisi.';
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
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

                $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $fileName = uniqid('tip_', true) . '.' . $extension;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                    $image = $fileName;
                } else {
                    $error = 'Gagal menyimpan gambar.';
                }
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare(
                "INSERT INTO tips (title, description, image)
                 VALUES (:title, :description, :image)"
            );

            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':image' => $image
            ]);
            
            $_SESSION['success'] = 'Tips kesehatan berhasil ditambahkan!';
            
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
    <title>Tambah Tips Kesehatan</title>
    <link rel="stylesheet" href="../assets/css/tips.css">
</head>
<body>

    <h1>Tambah Tips Kesehatan</h1>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Judul Tips</label><br>
        <input type="text" name="title" required><br><br>

        <label>Deskripsi Tips</label><br>
        <textarea name="description" rows="6" required></textarea><br><br>

        <label>Upload Gambar</label><br>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><br><br>

        <button type="submit">Simpan Tips</button>
        <a href="tips.php">Kembali</a>
    </form>

</body>
</html>