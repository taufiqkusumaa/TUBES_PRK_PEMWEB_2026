
<?php
// Memeriksa login admin
require_once '../includes/auth.php';

// Menghubungkan database
require_once '../config/database.php';

$error = '';

// Mengambil daftar kategori
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses ketika tombol Simpan ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    $author = $_SESSION['admin_username'] ?? 'Admin';
    $image = null;

    // Memeriksa input wajib
    if ($title === '' || $category_id <= 0 || $content === '') {
        $error = 'Judul, kategori, dan isi artikel wajib diisi!';
    } elseif (!in_array($status, ['draft', 'published', 'archived'])) {
        $error = 'Status artikel tidak valid!';
    } else {

        // Memproses upload gambar jika ada
        if (isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Gagal mengunggah gambar!';
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 2 MB!';
            } else {

                // Memeriksa jenis file gambar
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
                finfo_close($finfo);

                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowed[$mime])) {
                    $error = 'Format gambar harus JPG, PNG, atau WEBP!';
                } else {

                    // Menentukan lokasi penyimpanan
                    $uploadDir = __DIR__ . '/../uploads/articles/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Membuat nama file unik
                    $fileName = bin2hex(random_bytes(8))
                        . '.' . $allowed[$mime];

                    if (move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        $uploadDir . $fileName
                    )) {
                        $image = 'uploads/articles/' . $fileName;
                    } else {
                        $error = 'Gambar gagal disimpan!';
                    }
                }
            }
        }

        // Menyimpan artikel ke database
        if ($error === '') {
            try {
                $query = "INSERT INTO articles
                    (category_id, title, content, image, author, status)
                    VALUES
                    (:category_id, :title, :content, :image, :author, :status)";

                $stmt = $pdo->prepare($query);

                $stmt->execute([
                    ':category_id' => $category_id,
                    ':title' => $title,
                    ':content' => $content,
                    ':image' => $image,
                    ':author' => $author,
                    ':status' => $status
                ]);

                header('Location: articles.php');
                exit;

            } catch (PDOException $e) {
                $error = 'Gagal menyimpan artikel: ' . $e->getMessage();
            }
        }
    }
}

// Fungsi mengamankan teks
function escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Artikel - Sehatin</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        textarea {
            min-height: 180px;
            resize: vertical;
        }

        button {
            background: #28a745;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Tambah Artikel</h1>

    <a href="articles.php">Kembali ke Daftar Artikel</a>

    <?php if ($error !== ''): ?>
        <p class="error"><?= escape($error) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-group">
            <label>Judul Artikel</label>
            <input type="text" name="title"
                   value="<?= escape($_POST['title'] ?? '') ?>"
                   required>
        </div>

        <div class="form-group">
            <label>Kategori</label>
            <select name="category_id" required>
                <option value="">-- Pilih Kategori --</option>

                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"
                        <?= (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : '' ?>>
                        <?= escape($category['name']) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </div>

        <div class="form-group">
            <label>Isi Artikel</label>
            <textarea name="content" required><?= escape($_POST['content'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Gambar Artikel (Opsional)</label>
            <input type="file" name="image"
                   accept=".jpg,.jpeg,.png,.webp">
            <small>Maksimal 2 MB. Format JPG, PNG, atau WEBP.</small>
        </div>

        <div class="form-group">
            <label>Status Artikel</label>
            <select name="status">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
            </select>
        </div>

        <button type="submit">Simpan Artikel</button>

    </form>

</div>

</body>
</html>